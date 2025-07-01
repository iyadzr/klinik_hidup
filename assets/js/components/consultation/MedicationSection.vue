<template>
  <div class="medication-section">
    <h6 class="fw-bold mb-3">
      <i class="fas fa-pills me-2"></i>
      Prescribed Medications
    </h6>
    
    <!-- Add Medication Button -->
    <div class="mb-3">
      <button type="button" class="btn btn-outline-primary btn-sm" @click="addMedicationRow">
        <i class="fas fa-plus me-1"></i>Add Medication
      </button>
    </div>

    <!-- Medication List -->
    <div v-for="(medItem, index) in medications" :key="index" class="medication-row mb-3 p-3 border rounded">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input
              type="text"
              class="form-control"
              :id="`medication-${index}`"
              v-model="medItem.name"
              @input="searchMedications(medItem, $event)"
              @blur="handleMedicationBlur(medItem)"
              @keydown.enter.prevent="selectFirstSuggestion(medItem)"
              @keydown.escape="clearSuggestions(medItem)"
              placeholder="Type to search medications..."
              autocomplete="off"
            >
            <label :for="`medication-${index}`">Medication Name</label>
            
            <!-- Medication Suggestions Dropdown with Pagination -->
            <div v-if="medItem.allSuggestions && medItem.allSuggestions.length > 0" class="medication-suggestions">
              <div class="suggestions-header d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="fas fa-search me-1"></i>Select from existing medications:</small>
                <small class="text-muted">{{ medItem.allSuggestions.length }} results</small>
              </div>
              
              <!-- Paginated Suggestions -->
              <div class="suggestion-item" v-for="(suggestion, sIndex) in medItem.paginatedSuggestions" :key="sIndex"
                   @click="selectMedication(medItem, suggestion)"
                   :class="{ active: sIndex === medItem.selectedSuggestionIndex }">
                <div class="suggestion-main">
                  <strong>{{ suggestion.name }}</strong>
                  <span class="badge bg-secondary ms-2">{{ suggestion.category || 'General' }}</span>
                </div>
                <div class="suggestion-details">
                  <small class="text-muted">
                    {{ suggestion.unitDescription || suggestion.unitType || 'Unit not specified' }}
                    <span v-if="suggestion.sellingPrice" class="text-success ms-2">
                      <i class="fas fa-tag"></i> RM {{ parseFloat(suggestion.sellingPrice).toFixed(2) }}
                    </span>
                  </small>
                </div>
              </div>
              
              <!-- Pagination Controls -->
              <div v-if="medItem.totalPages > 1" class="suggestions-pagination">
                <div class="d-flex justify-content-between align-items-center py-2 px-3 border-top">
                  <small class="text-muted">
                    Page {{ medItem.currentPage }} of {{ medItem.totalPages }}
                  </small>
                  <div class="btn-group btn-group-sm">
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="medItem.currentPage === 1"
                      @click="changeMedicationPage(medItem, medItem.currentPage - 1)"
                    >
                      <i class="fas fa-chevron-left"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="medItem.currentPage === medItem.totalPages"
                      @click="changeMedicationPage(medItem, medItem.currentPage + 1)"
                    >
                      <i class="fas fa-chevron-right"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Create new medication option -->
              <div v-if="medItem.name && medItem.name.length >= 2 && !medItem.allSuggestions.some(s => s.name.toLowerCase() === medItem.name.toLowerCase())" 
                   class="suggestion-item suggestion-create-new"
                   @click="$emit('create-medication', medItem)">
                <div class="suggestion-main">
                  <i class="fas fa-plus text-success me-2"></i>
                  <strong>Create new: "{{ medItem.name }}"</strong>
                </div>
                <small class="text-muted">Add this medication to the database</small>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="form-floating">
            <input type="number" class="form-control" :id="`quantity-${index}`" v-model.number="medItem.quantity" min="1" required>
            <label :for="`quantity-${index}`">Qty</label>
          </div>
          <small class="text-muted">{{ medItem.unitDescription || medItem.unitType || 'pieces' }}</small>
        </div>
        
        <div class="col-md-2">
          <button type="button" class="btn btn-outline-danger btn-sm h-100" @click="removeMedicationRow(index)">
            <i class="fas fa-trash"></i>
          </button>
        </div>
        
        <div class="col-md-12" v-if="medItem.showInstructions">
          <div class="form-floating">
            <textarea class="form-control" :id="`instructions-${index}`" v-model="medItem.instructions" style="height: 60px" placeholder="Dosage instructions...">
            </textarea>
            <label :for="`instructions-${index}`">Instructions</label>
          </div>
        </div>
        
        <div class="col-md-12">
          <button type="button" class="btn btn-link btn-sm p-0" @click="medItem.showInstructions = !medItem.showInstructions">
            {{ medItem.showInstructions ? 'Hide' : 'Add' }} Instructions
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import searchDebouncer from '../../utils/searchDebouncer';

export default {
  name: 'MedicationSection',
  props: {
    modelValue: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:modelValue', 'create-medication'],
  data() {
    return {
      medicationSearcher: null
    };
  },
  computed: {
    medications: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value);
      }
    }
  },
  mounted() {
    this.medicationSearcher = searchDebouncer;
    // Add event listeners for dropdown positioning
    window.addEventListener('scroll', this.handleScroll);
    window.addEventListener('resize', this.handleResize);
  },
  beforeUnmount() {
    if (this.medicationSearcher) {
      this.medicationSearcher.cleanup();
    }
    // Remove event listeners
    window.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.handleResize);
  },
  methods: {
    addMedicationRow() {
      this.medications.push({
        id: null,
        name: '',
        medicationId: null,
        quantity: 1,
        actualPrice: 0.00,
        unitType: 'pieces',
        unitDescription: '',
        category: '',
        instructions: '',
        showInstructions: false,
        allSuggestions: [],
        paginatedSuggestions: [],
        selectedSuggestionIndex: -1,
        currentPage: 1,
        itemsPerPage: 5,
        totalPages: 1
      });
    },
    
    removeMedicationRow(index) {
      this.medications.splice(index, 1);
    },
    
    async searchMedications(medItem, event) {
      const searchTerm = event.target.value;
      
      try {
        if (!this.medicationSearcher) {
          console.warn('⚠️ MedicationSearcher not initialized, using direct search');
          const results = await this.performMedicationSearch(searchTerm);
          this.updateMedicationPagination(medItem, results || []);
          this.positionDropdown(event.target);
          return;
        }

        const results = await this.medicationSearcher.search('medication', searchTerm, this.performMedicationSearch);
        
        if (results) {
          this.updateMedicationPagination(medItem, results);
          this.positionDropdown(event.target);
        }
        
      } catch (error) {
        console.error('Medication search error:', error);
        this.updateMedicationPagination(medItem, []);
      }
    },
    
    positionDropdown(inputElement) {
      this.$nextTick(() => {
        const dropdown = inputElement.parentNode.querySelector('.medication-suggestions');
        if (dropdown) {
          // Force display and positioning
          dropdown.style.display = 'block';
          dropdown.style.position = 'absolute';
          dropdown.style.top = '100%';
          dropdown.style.left = '0';
          dropdown.style.right = '0';
          dropdown.style.zIndex = '10000';
          dropdown.style.marginTop = '2px';
          
          console.log('Dropdown positioned:', dropdown);
        }
      });
    },
    
    async performMedicationSearch(searchTerm) {
      try {
        const response = await axios.get(`/api/medications?search=${encodeURIComponent(searchTerm)}`);
        return response.data;
      } catch (error) {
        console.error('❌ Error in medication search API:', error);
        throw error;
      }
    },
    
    updateMedicationPagination(medItem, allResults) {
      medItem.allSuggestions = allResults;
      medItem.currentPage = 1;
      medItem.totalPages = Math.ceil(allResults.length / medItem.itemsPerPage);
      medItem.selectedSuggestionIndex = allResults.length > 0 ? 0 : -1;
      this.updatePaginatedSuggestions(medItem);
      
      // Force dropdown to show if there are results
      if (allResults.length > 0) {
        this.$nextTick(() => {
          const dropdowns = document.querySelectorAll('.medication-suggestions');
          dropdowns.forEach(dropdown => {
            dropdown.style.display = 'block';
          });
        });
      }
    },
    
    updatePaginatedSuggestions(medItem) {
      const startIndex = (medItem.currentPage - 1) * medItem.itemsPerPage;
      const endIndex = startIndex + medItem.itemsPerPage;
      medItem.paginatedSuggestions = medItem.allSuggestions.slice(startIndex, endIndex);
    },
    
    changeMedicationPage(medItem, newPage) {
      if (newPage >= 1 && newPage <= medItem.totalPages) {
        medItem.currentPage = newPage;
        this.updatePaginatedSuggestions(medItem);
        medItem.selectedSuggestionIndex = 0;
      }
    },
    
    selectMedication(medItem, medication) {
      medItem.id = medication.id;
      medItem.medicationId = medication.id;
      medItem.name = medication.name;
      medItem.unitType = medication.unitType;
      medItem.unitDescription = medication.unitDescription;
      medItem.category = medication.category;
      
      if (medication.sellingPrice && medication.sellingPrice > 0) {
        medItem.actualPrice = parseFloat(medication.sellingPrice);
      } else {
        medItem.actualPrice = 0.00;
        console.warn(`⚠️ Medication "${medication.name}" has no selling price set.`);
      }
      
      console.log(`✅ Selected medication: ${medication.name}, Price: RM${medItem.actualPrice}`);
      
      medItem.allSuggestions = [];
      medItem.paginatedSuggestions = [];
      medItem.selectedSuggestionIndex = -1;
      
      // Hide dropdown after selection
      this.$nextTick(() => {
        const dropdown = document.querySelector('.medication-suggestions');
        if (dropdown) {
          dropdown.style.display = 'none';
        }
      });
    },
    
    handleMedicationBlur(medItem) {
      setTimeout(() => {
        if (!medItem.medicationId && medItem.name && medItem.name.length >= 2) {
          const exactMatch = medItem.allSuggestions.find(med => 
            med.name.toLowerCase() === medItem.name.toLowerCase()
          );
          
          if (exactMatch) {
            this.selectMedication(medItem, exactMatch);
          } else {
            // Don't auto-popup create medication modal
            // User can manually click "Add Medication" button if they want to create new
            console.log(`No exact match found for "${medItem.name}". Use Add Medication button to create new.`);
          }
        }
        medItem.allSuggestions = [];
        medItem.paginatedSuggestions = [];
        medItem.selectedSuggestionIndex = -1;
      }, 200);
    },
    
    selectFirstSuggestion(medItem) {
      if (medItem.paginatedSuggestions && medItem.paginatedSuggestions.length > 0) {
        this.selectMedication(medItem, medItem.paginatedSuggestions[0]);
      }
    },
    
    clearSuggestions(medItem) {
      medItem.allSuggestions = [];
      medItem.paginatedSuggestions = [];
      medItem.selectedSuggestionIndex = -1;
      
      // Hide dropdown
      this.$nextTick(() => {
        const dropdowns = document.querySelectorAll('.medication-suggestions');
        dropdowns.forEach(dropdown => {
          dropdown.style.display = 'none';
        });
      });
    },
    
    handleScroll() {
      // No repositioning needed with absolute positioning
    },
    
    handleResize() {
      // No repositioning needed with absolute positioning
    }
  }
};
</script>

<style scoped>
.medication-suggestions {
  position: absolute !important;
  top: 100% !important;
  left: 0 !important;
  right: 0 !important;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  max-height: 400px !important; /* Increased height to exceed card */
  overflow-y: auto;
  z-index: 9999 !important; /* Very high z-index */
  box-shadow: 0 15px 35px rgba(0,0,0,0.3);
  display: none; /* Hidden by default */
  margin-top: 2px;
  /* Force it to break out of all containers */
  transform: translateZ(0);
  backface-visibility: hidden;
}

.medication-row {
  background: #f8f9fa;
  border: 1px solid #dee2e6 !important;
  border-radius: 12px;
  position: relative;
  overflow: visible !important;
}

.medication-section {
  overflow: visible !important;
}

.form-floating {
  position: relative !important;
  overflow: visible !important;
}

/* Ensure parent containers don't clip the dropdown */
.card-body {
  overflow: visible !important;
}

.section-card {
  overflow: visible !important;
}

.consultation-form {
  overflow: visible !important;
}

/* Force all parent containers to allow overflow */
.card {
  overflow: visible !important;
}

.row {
  overflow: visible !important;
}

.col-md-12 {
  overflow: visible !important;
}

.medication-section {
  overflow: visible !important;
  z-index: 1000;
}

.suggestions-header {
  padding: 0.5rem 0.75rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  font-size: 0.85rem;
}

.suggestion-item {
  padding: 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f3f4;
  transition: all 0.2s ease;
}

.suggestion-item:hover,
.suggestion-highlighted {
  background-color: #e3f2fd;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-main {
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
}

.suggestion-create-new {
  background-color: #f0f9f0;
  border-top: 2px solid #28a745;
}

.suggestion-create-new:hover {
  background-color: #e8f5e8;
}

.suggestion-create-new .suggestion-main {
  color: #28a745;
  font-weight: 500;
}

.suggestions-pagination {
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.suggestions-pagination .btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  min-width: 30px;
}

.suggestions-pagination .btn-outline-secondary {
  border-color: #6c757d;
  color: #6c757d;
}

.suggestions-pagination .btn-outline-secondary:hover:not(:disabled) {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
}

.suggestions-pagination .btn-outline-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.medication-row .btn-outline-danger {
  border: 1px solid #dc3545;
  color: #dc3545;
}

.medication-row .btn-outline-danger:hover {
  background: #dc3545;
  color: white;
}
</style> 