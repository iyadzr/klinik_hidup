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
import requestManager from '../../utils/requestManager.js';
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
      
    };
  },
  created() {
    // Use the singleton requestManager instance directly
    this.requestManager = requestManager;
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
    console.log('PatientList mounted - checking authentication...');
    const user = AuthService.getCurrentUser();
    console.log('Current user:', user);
    console.log('Is authenticated:', AuthService.isAuthenticated());
    console.log('Token exists:', !!user?.token);
    
    if (user?.token) {
      console.log('Token length:', user.token.length);
      console.log('Token first 20 chars:', user.token.substring(0, 20) + '...');
    }
    
    // Add escape key listener to force close modals
    document.addEventListener('keydown', this.handleEscapeKey);
    // Add click listener to force close modals when clicking outside
    document.addEventListener('click', this.handleOutsideClick);
    
    await this.loadPatients();
  },
  beforeUnmount() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    // Remove escape key listener
    document.removeEventListener('keydown', this.handleEscapeKey);
    // Remove click listener
    document.removeEventListener('click', this.handleOutsideClick);
  },
  methods: {
    // Core loading methods
    async loadPatients() {
      console.log('loadPatients called - starting...');
      this.loading = true;
      this.error = null;

      const requestKey = `patient-list-${Date.now()}`;
      const params = {
        page: this.currentPage,
        limit: this.perPage
      };

      if (this.searchQuery && this.searchQuery.trim().length >= 2) {
        params.query = this.searchQuery.trim();
      }

      console.log('Making API call with params:', params);
      console.log('Request key:', requestKey);

      const response = await this.requestManager.makeRequest(requestKey, async (signal) => {
        return await axios.get('/api/patients', {
          params,
          timeout: 12000, // Extended timeout for patient loading
          signal // Pass the abort signal for cancellation
        });
      });

      console.log('API response received:', response);
      console.log('Response data structure:', Object.keys(response.data));
      console.log('Response data.data:', response.data.data);

      this.patients = response.data.data || [];
      this.totalPatients = response.data.total || 0;
      this.totalPages = Math.ceil(this.totalPatients / this.perPage);

      console.log('Patients loaded:', this.patients.length);
      console.log('Total patients:', this.totalPatients);

      // If current page is beyond total pages, reset to page 1
      if (this.currentPage > this.totalPages && this.totalPages > 0) {
        this.currentPage = 1;
        await this.loadPatients();
        return;
      }

      this.loading = false;
      console.log('loadPatients completed successfully');
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

      await axios.delete(`/api/patients/${patient.id}`);
      await this.loadPatients();
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
      const response = await axios.get(`/api/patients/${patient.id}/visit-history`);
      this.visitHistories = response.data.visits || [];
      this.loadingVisitHistory = false;
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
      // Force close any stuck modals
      this.forceCloseAllModals();
      this.forceDOMModalClose();
    },

    // Force close all modals - emergency method
    forceCloseAllModals() {
      this.showAddModal = false;
      this.showVisitHistoryModal = false;
      this.showVisitDetailsModal = false;
      this.showReceiptModal = false;
      this.showMCModal = false;
      this.selectedPatientForHistory = null;
      this.selectedVisit = null;
      this.selectedVisitForReceipt = null;
      this.selectedVisitForMC = null;
      this.editingPatient = null;
      this.visitHistories = [];
      
      // Also force DOM cleanup
      this.$nextTick(() => {
        this.forceDOMModalClose();
      });
    },

    // Handle escape key to close modals
    handleEscapeKey(event) {
      if (event.key === 'Escape') {
        console.log('Escape key pressed - force closing all modals');
        this.forceCloseAllModals();
        this.forceDOMModalClose();
      }
    },

    // Force close modals via DOM manipulation as last resort
    forceDOMModalClose() {
      console.log('Force closing modals via DOM manipulation');
      
      // Remove all modal backdrops
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach(backdrop => backdrop.remove());
      
      // Hide all modals
      const modals = document.querySelectorAll('.modal');
      modals.forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('show');
      });
      
      // Remove modal-open class from body
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
      
      console.log('DOM modal cleanup completed');
    },

    // Handle click outside modal to close
    handleOutsideClick(event) {
      // Check if any modal is open
      const isModalOpen = this.showMCModal || this.showVisitHistoryModal || 
                         this.showVisitDetailsModal || this.showReceiptModal || this.showAddModal;
      
      if (isModalOpen) {
        // Check if click is on modal backdrop
        if (event.target.classList.contains('modal-backdrop') || 
            event.target.classList.contains('modal')) {
          console.log('Click outside modal detected - force closing all modals');
          this.forceCloseAllModals();
        }
      }
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