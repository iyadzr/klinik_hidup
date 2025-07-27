<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Patients</h2>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Search Bar Component -->
    <PatientSearchBar
      v-model="searchQuery"
      :loading="loading"
      :result-count="patients.length"
      @search="onSearch"
      @clear="clearSearch"
    />

    <!-- Patient Table Component -->
    <PatientTable
      :patients="paginatedPatients"
      :loading="loading"
      :current-page="currentPage"
      :per-page="itemsPerPage"
      :search-query="searchQuery"
      @show-visit-history="onViewHistory"
      @edit-patient="onEditPatient"
      @delete-patient="deletePatient"
    />

    <!-- Pagination Controls Component -->
    <PaginationControls
      :current-page="currentPage"
      :per-page="itemsPerPage"
      :total-records="totalPatients"
      :total-pages="totalPages"
      item-name="patients"
      @page-change="onPageChange"
      @per-page-change="updatePerPage"
    />

    <!-- Patient Form Modal Component -->
    <PatientFormModal
      :visible="showPatientModal"
      :editing-patient="selectedPatient"
      @close="onPatientModalClose"
      @save="onPatientSaved"
    />

    <!-- Visit History Modal Component -->
    <VisitHistoryModal
      :visible="showVisitHistoryModal"
      :patient="selectedPatient"
      :visit-histories="visitHistories"
      :loading="visitHistoryLoading"
      @close="onVisitHistoryModalClose"
    />
  </div>
</template>

<script>
import axios from 'axios';
import PatientTable from '../../components/patients/PatientTable.vue';
import PatientSearchBar from '../../components/patients/PatientSearchBar.vue';
import PaginationControls from '../../components/patients/PaginationControls.vue';
import PatientFormModal from '../../components/patients/PatientFormModal.vue';
import VisitHistoryModal from '../../components/patients/VisitHistoryModal.vue';
import AuthService from '../../services/AuthService.js';

export default {
    name: 'PatientList',
    components: {
        PatientTable,
        PatientSearchBar,
        PaginationControls,
        PatientFormModal,
        VisitHistoryModal
    },
    data() {
        return {
            patients: [],
            loading: false,
            searchQuery: '',
            currentPage: 1,
            itemsPerPage: 10,
            totalPatients: 0,
            selectedPatient: null,
            showPatientModal: false,
            showVisitHistoryModal: false,
            error: null,
            clickProtection: false, // Simple click protection
            visitHistories: [],
            visitHistoryLoading: false
        };
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalPatients / this.itemsPerPage);
        },
        paginatedPatients() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.patients.slice(start, end);
        }
    },
    created() {
        this.fetchPatients();
    },
    methods: {
        async safeApiCall(apiCall, errorMessage = 'API call failed') {
            // Simple protection against rapid clicks
            if (this.clickProtection) {
                console.log('Click protection active, ignoring request');
                return null;
            }

            this.clickProtection = true;
            setTimeout(() => {
                this.clickProtection = false;
            }, 500); // 500ms protection

            try {
                return await apiCall();
            } catch (error) {
                console.error(errorMessage, error);
                if (error.response?.status === 401) {
                    AuthService.logout();
                    this.$router.push('/login');
                } else {
                    this.error = `${errorMessage}: ${error.message}`;
                }
                return null;
            }
        },

        async fetchPatients(searchQuery = '') {
            // Don't start new fetch if already loading
            if (this.loading) {
                console.log('Already loading patients, skipping...');
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const url = searchQuery 
                    ? `/api/patients/search?query=${encodeURIComponent(searchQuery)}`
                    : '/api/patients';

                console.log('Fetching patients from:', url);
                const response = await axios.get(url);
                const data = response.data;

                // Handle both array and object responses
                if (Array.isArray(data)) {
                    this.patients = data;
                    this.totalPatients = data.length;
                } else if (data.patients && Array.isArray(data.patients)) {
                    this.patients = data.patients;
                    this.totalPatients = data.total || data.patients.length;
                } else {
                    this.patients = data.data || [];
                    this.totalPatients = data.total || this.patients.length;
                }

                // Reset to first page on new search
                if (searchQuery) {
                    this.currentPage = 1;
                }

                console.log(`Loaded ${this.patients.length} patients`);

            } catch (error) {
                console.error('Error fetching patients:', error);
                if (error.response?.status === 401) {
                    AuthService.logout();
                    this.$router.push('/login');
                } else {
                    this.error = `Failed to load patients: ${error.message}`;
                    // Keep existing data on error
                    if (this.patients.length === 0) {
                        this.patients = [];
                        this.totalPatients = 0;
                    }
                }
            } finally {
                this.loading = false;
            }
        },

        async onSearch(query) {
            this.searchQuery = query;
            await this.safeApiCall(
                () => this.fetchPatients(query),
                'Failed to search patients'
            );
        },

        onPageChange(page) {
            this.currentPage = page;
        },

        onAddPatient() {
            this.selectedPatient = null;
            this.showPatientModal = true;
        },

        onEditPatient(patient) {
            this.selectedPatient = patient;
            this.showPatientModal = true;
        },

        async onViewHistory(patient) {
            // Show modal immediately with loading state for better UX
            this.selectedPatient = patient;
            this.visitHistoryLoading = true;
            this.visitHistories = [];
            this.showVisitHistoryModal = true;
            
            // Load data in background
            const result = await this.safeApiCall(async () => {
                try {
                    const response = await axios.get(`/api/patients/${patient.id}/visit-history`);
                    console.log('Visit history loaded:', response.data);
                    
                    // Extract visits array from response.data
                    const visits = response.data.visits || response.data || [];
                    
                    // Debug the first visit to see data structure
                    if (visits.length > 0) {
                        console.log('First visit data structure:', {
                            id: visits[0].id,
                            consultationDate: visits[0].consultationDate,
                            medications: visits[0].medications,
                            medicationsType: typeof visits[0].medications,
                            mcRequired: visits[0].mcRequired,
                            diagnosis: visits[0].diagnosis,
                            doctor: visits[0].doctor
                        });
                    }
                    
                    this.visitHistories = visits;
                } catch (error) {
                    console.warn('Could not load visit history:', error);
                    this.visitHistories = [];
                    this.error = 'Failed to load visit history';
                } finally {
                    this.visitHistoryLoading = false;
                }
                return true;
            }, 'Failed to load visit history');
        },

        async onPatientSaved() {
            this.showPatientModal = false;
            this.selectedPatient = null;
            
            // Simple refresh without complex management
            await this.safeApiCall(
                () => this.fetchPatients(this.searchQuery),
                'Failed to refresh patient list'
            );
        },

        onPatientModalClose() {
            this.showPatientModal = false;
            this.selectedPatient = null;
        },

        onVisitHistoryModalClose() {
            this.showVisitHistoryModal = false;
            this.selectedPatient = null;
        },

        async refreshPatients() {
            await this.safeApiCall(
                () => this.fetchPatients(this.searchQuery),
                'Failed to refresh patients'
            );
        },

        async deletePatient(patient) {
            if (!confirm(`Are you sure you want to delete patient ${patient.name}?`)) {
                return;
            }

            await this.safeApiCall(async () => {
                await axios.delete(`/api/patients/${patient.id}`);
                await this.fetchPatients(this.searchQuery);
            }, 'Failed to delete patient');
        },

        updatePerPage(newPerPage) {
            this.itemsPerPage = newPerPage;
            this.currentPage = 1;
            this.fetchPatients(this.searchQuery);
        },

        clearSearch() {
            this.searchQuery = '';
            this.currentPage = 1;
            this.fetchPatients();
        }
    }
};
</script>

<style scoped>
/* Minimal styling - most styling is now in components */
.container {
  max-width: 100%;
  padding: 1rem;
}

@media (max-width: 768px) {
  .container {
    padding: 0.5rem;
  }
}
</style> 