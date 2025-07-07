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
      @search="searchPatients"
      @clear="clearSearch"
      @input="onSearchInput"
    />

    <!-- Patient Table Component -->
    <PatientTable
      :patients="patients"
      :loading="loading"
      :loading-progress="loadingProgress"
      :current-page="currentPage"
      :per-page="perPage"
      :search-query="searchQuery"
      @show-visit-history="showVisitHistory"
      @edit-patient="editPatient"
      @delete-patient="deletePatient"
    />

    <!-- Pagination Controls Component -->
    <PaginationControls
      :current-page="currentPage"
      :per-page="perPage"
      :total-records="totalPatients"
      :total-pages="totalPages"
      item-name="patients"
      @page-change="goToPage"
      @per-page-change="updatePerPage"
    />

    <!-- Patient Form Modal Component -->
    <PatientFormModal
      :visible="showAddModal"
      :editing-patient="editingPatient"
      :visit-histories="visitHistories"
      @close="closeModal"
      @save="handlePatientSave"
      @view-visit-details="showVisitDetailsModal"
      @show-visit-history="showVisitHistory"
    />

    <!-- Visit History Modal Component -->
    <VisitHistoryModal
      :visible="showVisitHistoryModal"
      :patient="selectedPatientForHistory"
      :visit-histories="visitHistories"
      :loading="loadingVisitHistory"
      @close="closeVisitHistoryModal"
      @view-visit-details="showVisitDetailsModal"
      @view-receipt="showReceiptModal"
      @view-medical-certificate="showMCModal"
    />

    <!-- Visit Details Modal -->
    <SimpleModalWrapper
      :visible="showVisitDetailsModal"
      title="Visit Details"
      size="lg"
      header-variant="info"
      icon="fas fa-file-medical"
      @close="closeVisitDetailsModal"
    >
      <VisitDetailsContent 
        v-if="selectedVisit"
        :visit="selectedVisit"
      />
    </SimpleModalWrapper>

    <!-- Receipt Modal -->
    <SimpleModalWrapper
      :visible="showReceiptModal"
      :title="`Receipt - ${selectedVisitForReceipt?.receiptNumber || ''}`"
      size="lg"
      header-variant="success"
      icon="fas fa-receipt"
      @close="closeReceiptModal"
    >
      <ReceiptContent 
        v-if="selectedVisitForReceipt"
        :visit="selectedVisitForReceipt"
        :patient="selectedPatientForHistory"
      />
      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeReceiptModal">Close</button>
        <button type="button" class="btn btn-primary" @click="printReceipt">Print</button>
      </template>
    </SimpleModalWrapper>

    <!-- Medical Certificate Modal -->
    <SimpleModalWrapper
      :visible="showMCModal"
      :title="`Medical Certificate - ${selectedVisitForMC?.mcRunningNumber || ''}`"
      size="lg"
      header-variant="warning"
      icon="fas fa-file-medical-alt"
      @close="closeMCModal"
    >
      <MedicalCertificateContent 
        v-if="selectedVisitForMC"
        :visit="selectedVisitForMC"
        :patient="selectedPatientForHistory"
      />
      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeMCModal">Close</button>
        <button type="button" class="btn btn-primary" @click="printMC">Print</button>
      </template>
    </SimpleModalWrapper>
  </div>
</template>

<script>
import axios from 'axios';
import AuthService from '../../services/AuthService';
import RequestManager from '../../utils/requestManager.js';
import timezoneUtils from '../../utils/timezoneUtils.js';

// Import components
import PatientSearchBar from '../../components/patients/PatientSearchBar.vue';
import PatientTable from '../../components/patients/PatientTable.vue';
import PaginationControls from '../../components/patients/PaginationControls.vue';
import PatientFormModal from '../../components/patients/PatientFormModal.vue';
import VisitHistoryModal from '../../components/patients/VisitHistoryModal.vue';
import SimpleModalWrapper from '../../components/patients/SimpleModalWrapper.vue';

// Import content components (to be created)
import VisitDetailsContent from '../../components/patients/VisitDetailsContent.vue';
import ReceiptContent from '../../components/patients/ReceiptContent.vue';
import MedicalCertificateContent from '../../components/patients/MedicalCertificateContent.vue';

export default {
  name: 'PatientList',
  components: {
    PatientSearchBar,
    PatientTable,
    PaginationControls,
    PatientFormModal,
    VisitHistoryModal,
    SimpleModalWrapper,
    VisitDetailsContent,
    ReceiptContent,
    MedicalCertificateContent
  },
  data() {
    return {
      patients: [],
      loading: false,
      loadingProgress: 0,
      error: null,
      searchQuery: '',
      currentPage: 1,
      perPage: 25,
      totalPatients: 0,
      totalPages: 1,
      searchTimeout: null,
      
      // Modal states
      showAddModal: false,
      showVisitHistoryModal: false,
      showVisitDetailsModal: false,
      showReceiptModal: false,
      showMCModal: false,
      
      // Selected data
      editingPatient: null,
      selectedPatientForHistory: null,
      selectedVisit: null,
      selectedVisitForReceipt: null,
      selectedVisitForMC: null,
      
      // Visit history
      visitHistories: [],
      loadingVisitHistory: false,
      
      // Request management
      requestManager: new RequestManager()
    };
  },
  computed: {
    pages() {
      const pages = [];
      const maxPagesToShow = 5;
      let startPage = Math.max(1, this.currentPage - Math.floor(maxPagesToShow / 2));
      let endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);
      
      if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
      }
      
      for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  async mounted() {
    await this.loadPatients();
  },
  beforeUnmount() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
  },
  methods: {
    // Core loading methods
    async loadPatients() {
      this.loading = true;
      this.loadingProgress = 10;
      this.error = null;

      try {
        const progressInterval = setInterval(() => {
          if (this.loadingProgress < 80) {
            this.loadingProgress += 10;
          }
        }, 200);

        const requestKey = `patient-list-${Date.now()}`;
        const params = {
          page: this.currentPage,
          limit: this.perPage
        };

        if (this.searchQuery && this.searchQuery.trim().length >= 2) {
          params.query = this.searchQuery.trim();
        }

        const response = await this.requestManager.get('/api/patients', {
          params,
          timeout: 12000 // Extended timeout for patient loading
        }, requestKey);

        clearInterval(progressInterval);
        this.loadingProgress = 100;

        this.patients = response.data.patients || [];
        this.totalPatients = response.data.total || 0;
        this.totalPages = Math.ceil(this.totalPatients / this.perPage);

        // If current page is beyond total pages, reset to page 1
        if (this.currentPage > this.totalPages && this.totalPages > 0) {
          this.currentPage = 1;
          await this.loadPatients();
          return;
        }

      } catch (error) {
        console.error('Failed to load patients:', error);
        this.error = 'Failed to load patients. Please try again.';
      } finally {
        this.loading = false;
        this.loadingProgress = 0;
      }
    },

    // Search methods
    onSearchInput() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      this.searchTimeout = setTimeout(() => {
        if (this.searchQuery.trim().length >= 2 || this.searchQuery.trim().length === 0) {
          this.currentPage = 1;
          this.loadPatients();
        }
      }, 300);
    },

    async searchPatients() {
      if (!this.searchQuery || this.searchQuery.trim().length < 2) {
        this.error = 'Please enter at least 2 characters to search';
        return;
      }
      
      this.currentPage = 1;
      await this.loadPatients();
    },

    async clearSearch() {
      this.searchQuery = '';
      this.currentPage = 1;
      await this.loadPatients();
    },

    // Pagination methods
    async goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.currentPage = page;
        await this.loadPatients();
      }
    },

    async updatePerPage(newPerPage) {
      this.perPage = newPerPage;
      this.currentPage = 1;
      await this.loadPatients();
    },

    // Patient CRUD operations
    addPatient() {
      this.editingPatient = null;
      this.showAddModal = true;
    },

    editPatient(patient) {
      this.editingPatient = patient;
      this.loadPatientVisitHistory(patient);
      this.showAddModal = true;
    },

    async handlePatientSave(eventData) {
      const { type, patient } = eventData;
      
      if (type === 'create') {
        // Add to current list if it would be visible
        this.patients.unshift(patient);
        this.totalPatients++;
      } else if (type === 'update') {
        // Update in current list
        const index = this.patients.findIndex(p => p.id === patient.id);
        if (index !== -1) {
          this.patients.splice(index, 1, patient);
        }
      }
      
      // Refresh the list to ensure consistency
      await this.loadPatients();
    },

    async deletePatient(patient) {
      if (!confirm(`Are you sure you want to delete patient "${patient.name}"? This action cannot be undone.`)) {
        return;
      }

      try {
        await axios.delete(`/api/patients/${patient.id}`);
        await this.loadPatients();
      } catch (error) {
        console.error('Failed to delete patient:', error);
        this.error = 'Failed to delete patient. Please try again.';
      }
    },

    // Modal management
    closeModal() {
      this.showAddModal = false;
      this.editingPatient = null;
      this.visitHistories = [];
    },

    // Visit history methods
    async showVisitHistory(patient) {
      this.selectedPatientForHistory = patient;
      this.showVisitHistoryModal = true;
      await this.loadPatientVisitHistory(patient);
    },

    async loadPatientVisitHistory(patient) {
      if (!patient?.id) return;

      this.loadingVisitHistory = true;
      try {
        const response = await axios.get(`/api/patients/${patient.id}/visit-history`);
        this.visitHistories = response.data.visits || [];
      } catch (error) {
        console.error('Failed to load visit history:', error);
        this.visitHistories = [];
      } finally {
        this.loadingVisitHistory = false;
      }
    },

    closeVisitHistoryModal() {
      this.showVisitHistoryModal = false;
      this.selectedPatientForHistory = null;
      this.visitHistories = [];
    },

    // Visit details modal
    showVisitDetailsModal(visit) {
      this.selectedVisit = visit;
      this.showVisitDetailsModal = true;
    },

    closeVisitDetailsModal() {
      this.showVisitDetailsModal = false;
      this.selectedVisit = null;
    },

    // Receipt modal
    showReceiptModal(visit) {
      this.selectedVisitForReceipt = visit;
      this.showReceiptModal = true;
    },

    closeReceiptModal() {
      this.showReceiptModal = false;
      this.selectedVisitForReceipt = null;
    },

    printReceipt() {
      window.print();
    },

    // Medical Certificate modal
    showMCModal(visit) {
      this.selectedVisitForMC = visit;
      this.showMCModal = true;
    },

    closeMCModal() {
      this.showMCModal = false;
      this.selectedVisitForMC = null;
    },

    printMC() {
      window.print();
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