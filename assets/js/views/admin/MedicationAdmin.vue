<template>
  <div class="medication-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">
          <i class="fas fa-pills text-primary me-2"></i>
          Medication Management
        </h2>
        <small class="text-muted">Manage all medications in the system</small>
      </div>
      <div class="d-flex gap-2">
        <button @click="loadMedications" class="btn btn-outline-info">
          <i class="fas fa-sync me-2"></i>Refresh
        </button>
        <button @click="openAddModal" class="btn btn-primary">
          <i class="fas fa-plus me-2"></i>Add New Medication
        </button>
      </div>
    </div>

    <!-- Debug Info -->
    <div class="alert alert-info mb-4" v-if="error || loading">
      <div v-if="loading">
        <i class="fas fa-spinner fa-spin me-2"></i>Loading medications...
      </div>
      <div v-if="error" class="text-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
      </div>
      <div class="mt-2">
        <small>
          Total medications: {{ medications.length }} | 
          Filtered: {{ filteredMedications.length }} | 
          API Status: {{ loading ? 'Loading...' : (error ? 'Error' : 'Ready') }}
        </small>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="form-floating">
              <input 
                type="text" 
                class="form-control" 
                id="searchInput"
                v-model="searchTerm" 
                @input="filterMedications"
                placeholder="Search medications..."
              >
              <label for="searchInput">Search Medications</label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-floating">
              <select class="form-select" id="categoryFilter" v-model="selectedCategory" @change="filterMedications">
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
              <label for="categoryFilter">Filter by Category</label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-floating">
              <select class="form-select" id="unitFilter" v-model="selectedUnitType" @change="filterMedications">
                <option value="">All Unit Types</option>
                <option v-for="unit in unitTypes" :key="unit" :value="unit">
                  {{ unit }}
                </option>
              </select>
              <label for="unitFilter">Filter by Unit Type</label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-floating">
              <select class="form-select" id="itemsPerPageSelect" v-model="itemsPerPage" @change="currentPage = 1">
                <option value="10">10 per page</option>
                <option value="15">15 per page</option>
                <option value="20">20 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
              <label for="itemsPerPageSelect">Rows per page</label>
            </div>
          </div>
          <div class="col-md-1">
            <button @click="clearFilters" class="btn btn-outline-secondary w-100 h-100">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Medications Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Medications List</h5>
        <span class="badge bg-primary">{{ filteredMedications.length }} medications</span>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading medications...</p>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else class="table-responsive">
          <!-- Bulk Actions -->
          <div class="mb-3 d-flex align-items-center gap-2">
            <input type="checkbox" id="selectAll" v-model="selectAll" @change="toggleSelectAll">
            <label for="selectAll" class="me-3 mb-0">Select All</label>
            <button class="btn btn-danger btn-sm" :disabled="selectedMedications.length === 0" @click="confirmBulkDelete">
              <i class="fas fa-trash me-1"></i>Delete Selected ({{ selectedMedications.length }})
            </button>
          </div>
          <table class="table table-hover">
            <thead>
              <tr>
                <th><input type="checkbox" v-model="selectAll" @change="toggleSelectAll"></th>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Unit Type</th>
                <th>Unit Description</th>
                <th>Description</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="medication in paginatedMedications" :key="medication.id">
                <td>
                  <input type="checkbox" :value="medication.id" v-model="selectedMedications">
                </td>
                <td>{{ medication.id }}</td>
                <td>
                  <strong>{{ medication.name }}</strong>
                </td>
                <td>
                  <span v-if="medication.category" class="badge bg-info">
                    {{ medication.category }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span class="badge bg-secondary">{{ medication.unitType }}</span>
                </td>
                <td>{{ medication.unitDescription || '-' }}</td>
                <td>
                  <span 
                    v-if="medication.description" 
                    class="truncated-text" 
                    :title="medication.description"
                  >
                    {{ truncateText(medication.description, 50) }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ formatDate(medication.createdAt) }}</td>
                <td>
                  <div class="btn-group">
                    <button 
                      @click="editMedication(medication)" 
                      class="btn btn-sm btn-outline-primary"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      @click="deleteMedication(medication)" 
                      class="btn btn-sm btn-outline-danger"
                      title="Delete"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredMedications.length === 0">
                <td colspan="9" class="text-center text-muted py-4">
                  <i class="fas fa-search fa-2x mb-2"></i>
                  <div>No medications found</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav v-if="totalPages > 1" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button @click="currentPage = 1" class="page-link" :disabled="currentPage === 1">
                First
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button @click="currentPage--" class="page-link" :disabled="currentPage === 1">
                Previous
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === currentPage }"
            >
              <button @click="currentPage = page" class="page-link">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button @click="currentPage++" class="page-link" :disabled="currentPage === totalPages">
                Next
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button @click="currentPage = totalPages" class="page-link" :disabled="currentPage === totalPages">
                Last
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Add/Edit Medication Modal -->
    <div class="modal fade" id="medicationModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-pills me-2"></i>
              {{ editingMedication ? 'Edit Medication' : 'Add New Medication' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveMedication">
              <div class="row g-3">
                <div class="col-md-12">
                  <div class="form-floating">
                    <input 
                      type="text" 
                      class="form-control" 
                      id="medicationName"
                      v-model="medicationForm.name" 
                      required
                      placeholder="Enter medication name"
                    >
                    <label for="medicationName">Medication Name *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <select class="form-select" id="unitType" v-model="medicationForm.unitType" required>
                      <option value="">Select unit type</option>
                      <option value="tablets">Tablets</option>
                      <option value="capsules">Capsules</option>
                      <option value="pieces">Pieces</option>
                      <option value="bottles">Bottles</option>
                      <option value="tubes">Tubes</option>
                      <option value="sachets">Sachets</option>
                      <option value="boxes">Boxes</option>
                      <option value="inhaler">Inhalers</option>
                      <option value="ml">Milliliters (ml)</option>
                      <option value="mg">Milligrams (mg)</option>
                      <option value="g">Grams (g)</option>
                      <option value="vials">Vials</option>
                      <option value="ampoules">Ampoules</option>
                      <option value="bags">IV Bags</option>
                      <option value="jars">Jars</option>
                      <option value="drops">Drops</option>
                      <option value="syringes">Syringes</option>
                      <option value="patches">Patches</option>
                      <option value="other">Other</option>
                    </select>
                    <label for="unitType">Unit Type *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="text" 
                      class="form-control" 
                      id="unitDescription"
                      v-model="medicationForm.unitDescription" 
                      placeholder="e.g., 500mg tablets, 75ml bottle"
                    >
                    <label for="unitDescription">Unit Description</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="number" 
                      class="form-control" 
                      id="costPrice"
                      v-model.number="medicationForm.costPrice" 
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    >
                    <label for="costPrice">Cost Price (RM)</label>
                  </div>
                  <small class="text-muted">Purchase/inventory cost</small>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="number" 
                      class="form-control" 
                      id="sellingPrice"
                      v-model.number="medicationForm.sellingPrice" 
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    >
                    <label for="sellingPrice">Selling Price (RM)</label>
                  </div>
                  <small class="text-muted">Default selling price</small>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <select class="form-select" id="category" v-model="medicationForm.category">
                      <option value="">Select category</option>
                      <option value="Pain Relief">Pain Relief</option>
                      <option value="Antibiotics">Antibiotics</option>
                      <option value="Respiratory">Respiratory</option>
                      <option value="Cough & Cold">Cough & Cold</option>
                      <option value="Antihistamine">Antihistamine</option>
                      <option value="Gastrointestinal">Gastrointestinal</option>
                      <option value="Cardiovascular">Cardiovascular</option>
                      <option value="Diabetes">Diabetes</option>
                      <option value="Topical">Topical</option>
                      <option value="Eye & Ear">Eye & Ear</option>
                      <option value="Oral Care">Oral Care</option>
                      <option value="Vitamins">Vitamins & Supplements</option>
                      <option value="Anti-inflammatory">Anti-inflammatory</option>
                      <option value="Anxiolytic">Anxiolytic</option>
                      <option value="Anticoagulant">Anticoagulant</option>
                      <option value="Emergency">Emergency</option>
                      <option value="Other">Other</option>
                    </select>
                    <label for="category">Category</label>
                  </div>
                </div>

                <div class="col-md-6" v-if="medicationForm.category === 'Other'">
                  <div class="form-floating">
                    <input 
                      type="text" 
                      class="form-control" 
                      id="customCategory"
                      v-model="medicationForm.customCategory" 
                      placeholder="Enter custom category"
                    >
                    <label for="customCategory">Custom Category</label>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-floating">
                    <textarea 
                      class="form-control" 
                      id="description"
                      v-model="medicationForm.description" 
                      style="height: 100px"
                      placeholder="Additional notes or description"
                    ></textarea>
                    <label for="description">Description</label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="saveMedication"
              :disabled="saving"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              {{ saving ? 'Saving...' : (editingMedication ? 'Update' : 'Save') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Confirm Deletion
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this medication?</p>
            <div v-if="medicationToDelete" class="alert alert-warning">
              <strong>{{ medicationToDelete.name }}</strong><br>
              <small class="text-muted">{{ medicationToDelete.category }} - {{ medicationToDelete.unitType }}</small>
            </div>
            <p class="text-danger"><small>This action cannot be undone.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="confirmDelete"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MedicationAdmin',
  data() {
    return {
      medications: [],
      filteredMedications: [],
      loading: false,
      error: null,
      searchTerm: '',
      selectedCategory: '',
      selectedUnitType: '',
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 15,
      
      // Modals
      medicationModal: null,
      deleteModal: null,
      
      // Form
      editingMedication: null,
      medicationForm: {
        name: '',
        unitType: '',
        unitDescription: '',
        category: '',
        customCategory: '',
        description: ''
      },
      saving: false,
      
      // Delete
      medicationToDelete: null,
      deleting: false,
      selectedMedications: [],
      selectAll: false
    };
  },
  computed: {
    categories() {
      const categories = [...new Set(this.medications.map(m => m.category).filter(Boolean))];
      return categories.sort();
    },
    unitTypes() {
      const units = [...new Set(this.medications.map(m => m.unitType).filter(Boolean))];
      return units.sort();
    },
    totalPages() {
      return Math.ceil(this.filteredMedications.length / parseInt(this.itemsPerPage));
    },
    paginatedMedications() {
      const start = (this.currentPage - 1) * parseInt(this.itemsPerPage);
      const end = start + parseInt(this.itemsPerPage);
      return this.filteredMedications.slice(start, end);
    },
    visiblePages() {
      const current = this.currentPage;
      const total = this.totalPages;
      const pages = [];
      
      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  async created() {
    await this.loadMedications();
  },
  mounted() {
    this.medicationModal = new bootstrap.Modal(document.getElementById('medicationModal'));
    this.deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  },
  watch: {
    paginatedMedications: {
      handler(newVal) {
        // Deselect all if page changes
        this.selectAll = false;
        this.selectedMedications = [];
      },
      immediate: false
    }
  },
  methods: {
    async loadMedications() {
      this.loading = true;
      this.error = null;
      try {
        console.log('🔍 Loading medications from API...');
        const response = await axios.get('/api/medications');
        console.log('✅ API Response:', response.data);
        this.medications = response.data;
        this.filterMedications();
        console.log('📊 Filtered medications:', this.filteredMedications.length);
      } catch (error) {
        console.error('❌ Error loading medications:', error);
        console.error('Response data:', error.response?.data);
        console.error('Status:', error.response?.status);
        this.error = `Failed to load medications: ${error.response?.data?.message || error.message}`;
      } finally {
        this.loading = false;
      }
    },
    
    filterMedications() {
      let filtered = [...this.medications];
      
      // Search filter
      if (this.searchTerm) {
        const search = this.searchTerm.toLowerCase();
        filtered = filtered.filter(m => 
          m.name.toLowerCase().includes(search) ||
          (m.description && m.description.toLowerCase().includes(search)) ||
          (m.category && m.category.toLowerCase().includes(search))
        );
      }
      
      // Category filter
      if (this.selectedCategory) {
        filtered = filtered.filter(m => m.category === this.selectedCategory);
      }
      
      // Unit type filter
      if (this.selectedUnitType) {
        filtered = filtered.filter(m => m.unitType === this.selectedUnitType);
      }
      
      this.filteredMedications = filtered;
      this.currentPage = 1; // Reset to first page
    },
    
    clearFilters() {
      this.searchTerm = '';
      this.selectedCategory = '';
      this.selectedUnitType = '';
      this.filterMedications();
    },
    
    openAddModal() {
      this.editingMedication = null;
      this.medicationForm = {
        name: '',
        unitType: '',
        unitDescription: '',
        costPrice: 0.00,
        sellingPrice: 0.00,
        category: '',
        customCategory: '',
        description: ''
      };
      this.medicationModal.show();
    },
    
    editMedication(medication) {
      this.editingMedication = medication;
      this.medicationForm = {
        name: medication.name,
        unitType: medication.unitType,
        unitDescription: medication.unitDescription || '',
        costPrice: medication.costPrice || 0.00,
        sellingPrice: medication.sellingPrice || 0.00,
        category: medication.category || '',
        customCategory: '',
        description: medication.description || ''
      };
      this.medicationModal.show();
    },
    
    async saveMedication() {
      if (!this.medicationForm.name || !this.medicationForm.unitType) {
        alert('Please fill in required fields');
        return;
      }
      
      this.saving = true;
      try {
        const finalCategory = this.medicationForm.category === 'Other' 
          ? this.medicationForm.customCategory 
          : this.medicationForm.category;
        
        const medicationData = {
          name: this.medicationForm.name,
          unitType: this.medicationForm.unitType,
          unitDescription: this.medicationForm.unitDescription || null,
          costPrice: this.medicationForm.costPrice || null,
          sellingPrice: this.medicationForm.sellingPrice || null,
          category: finalCategory || null,
          description: this.medicationForm.description || null
        };
        
        if (this.editingMedication) {
          await axios.put(`/api/medications/${this.editingMedication.id}`, medicationData);
        } else {
          await axios.post('/api/medications', medicationData);
        }
        
        this.medicationModal.hide();
        await this.loadMedications();
        
      } catch (error) {
        console.error('Error saving medication:', error);
        alert('Error saving medication: ' + (error.response?.data?.message || error.message));
      } finally {
        this.saving = false;
      }
    },
    
    deleteMedication(medication) {
      this.medicationToDelete = medication;
      this.deleteModal.show();
    },
    
    async confirmDelete() {
      if (!this.medicationToDelete) return;
      
      this.deleting = true;
      try {
        await axios.delete(`/api/medications/${this.medicationToDelete.id}`);
        this.deleteModal.hide();
        await this.loadMedications();
      } catch (error) {
        console.error('Error deleting medication:', error);
        alert('Error deleting medication: ' + (error.response?.data?.message || error.message));
      } finally {
        this.deleting = false;
      }
    },
    
    truncateText(text, maxLength) {
      if (!text) return '';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: 'short',
          day: '2-digit'
        });
      } catch {
        return '-';
      }
    },
    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedMedications = this.paginatedMedications.map(m => m.id);
      } else {
        this.selectedMedications = [];
      }
    },
    async confirmBulkDelete() {
      if (this.selectedMedications.length === 0) return;
      if (!confirm(`Are you sure you want to delete ${this.selectedMedications.length} medications? This action cannot be undone.`)) return;
      try {
        for (const id of this.selectedMedications) {
          await axios.delete(`/api/medications/${id}`);
        }
        this.selectedMedications = [];
        this.selectAll = false;
        await this.loadMedications();
      } catch (error) {
        alert('Error deleting medications: ' + (error.response?.data?.message || error.message));
      }
    }
  }
};
</script>

<style scoped>
.truncated-text {
  cursor: help;
}

.badge {
  font-size: 0.75rem;
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
}

.table td {
  vertical-align: middle;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
}

.pagination .page-link {
  border-radius: 0.375rem;
  margin: 0 0.125rem;
  border: 1px solid #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.bulk-actions {
  margin-bottom: 1rem;
}
</style> 