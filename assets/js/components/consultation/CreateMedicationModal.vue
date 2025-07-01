<template>
  <div class="modal fade" id="createMedicationModal" tabindex="-1" aria-labelledby="createMedicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createMedicationModalLabel">
            <i class="fas fa-plus-circle text-success me-2"></i>
            Add New Medication to Database
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            This medication will be added to the database and available for future prescriptions.
          </div>
          
          <form @submit.prevent="createNewMedication">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">Medication Name</label>
                <input type="text" class="form-control" v-model="medicationForm.name" readonly>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-bold">Unit Type</label>
                <select class="form-select" v-model="medicationForm.unitType" required>
                  <option value="">Select unit type</option>
                  <option value="pieces">Pieces (tablets, capsules)</option>
                  <option value="bottles">Bottles</option>
                  <option value="tubes">Tubes</option>
                  <option value="sachets">Sachets</option>
                  <option value="boxes">Boxes</option>
                  <option value="ml">Milliliters (ml)</option>
                  <option value="mg">Milligrams (mg)</option>
                  <option value="g">Grams (g)</option>
                  <option value="vials">Vials</option>
                  <option value="other">Other</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-bold">Unit Description</label>
                <input type="text" class="form-control" v-model="medicationForm.unitDescription" 
                       placeholder="e.g., 500mg tablets, 75ml bottle">
                <small class="text-muted">Optional: Specific description of the unit</small>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-bold">Category</label>
                <select class="form-select" v-model="medicationForm.category">
                  <option value="">Select category (optional)</option>
                  <option value="pain reliever">Pain Reliever</option>
                  <option value="antibiotic">Antibiotic</option>
                  <option value="cough syrup">Cough Syrup</option>
                  <option value="fever reducer">Fever Reducer</option>
                  <option value="antacid">Antacid</option>
                  <option value="allergy medicine">Allergy Medicine</option>
                  <option value="vitamin">Vitamin/Supplement</option>
                  <option value="topical">Topical/External</option>
                  <option value="other">Other</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Custom Category</label>
                <input type="text" class="form-control" v-model="medicationForm.customCategory" 
                       placeholder="Enter custom category"
                       :disabled="medicationForm.category !== 'other'">
                <small class="text-muted">Only if "Other" is selected above</small>
              </div>
              
              <div class="col-md-12">
                <label class="form-label">Description (Optional)</label>
                <textarea class="form-control" v-model="medicationForm.description" rows="3"
                          placeholder="Additional notes about this medication..."></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" @click="createNewMedication" :disabled="!medicationForm.unitType">
            <i class="fas fa-plus me-2"></i>Add Medication
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CreateMedicationModal',
  props: {
    medicationItem: {
      type: Object,
      default: null
    }
  },
  emits: ['medication-created'],
  data() {
    return {
      medicationForm: {
        name: '',
        unitType: '',
        unitDescription: '',
        category: '',
        customCategory: '',
        description: ''
      }
    };
  },
  watch: {
    medicationItem: {
      handler(newItem) {
        if (newItem) {
          this.medicationForm.name = newItem.name || '';
          this.medicationForm.unitType = '';
          this.medicationForm.unitDescription = '';
          this.medicationForm.category = '';
          this.medicationForm.customCategory = '';
          this.medicationForm.description = '';
        }
      },
      immediate: true
    }
  },
  methods: {
    async createNewMedication() {
      try {
        if (!this.medicationForm.unitType) {
          alert('Please select a unit type.');
          return;
        }
        
        const finalCategory = this.medicationForm.category === 'other' 
          ? this.medicationForm.customCategory 
          : this.medicationForm.category;
        
        const newMedication = {
          name: this.medicationForm.name,
          unitType: this.medicationForm.unitType,
          unitDescription: this.medicationForm.unitDescription || null,
          category: finalCategory || null,
          description: this.medicationForm.description || null
        };
        
        const response = await axios.post('/api/medications', newMedication);
        
        // Emit the newly created medication
        this.$emit('medication-created', response.data);
        
        // Reset form
        this.resetForm();
        
        console.log('New medication added to database:', response.data);
        alert('Medication successfully added to the database!');
        
      } catch (error) {
        console.error('Error creating new medication:', error);
        const errorMessage = error.response?.data?.message || 'Error adding medication to database. Please try again.';
        alert(errorMessage);
      }
    },
    
    resetForm() {
      this.medicationForm = {
        name: '',
        unitType: '',
        unitDescription: '',
        category: '',
        customCategory: '',
        description: ''
      };
    }
  }
};
</script>

<style scoped>
.modal-content {
  border-radius: 16px;
  border: none;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #dee2e6;
  border-radius: 16px 16px 0 0;
  padding: 1.5rem;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #222;
}

.modal-body {
  padding: 2rem;
}

.form-label.fw-bold {
  color: #495057;
  font-size: 0.95rem;
}

.form-control, .form-select {
  border: 1px solid #ced4da;
  border-radius: 8px;
  padding: 0.75rem;
}

.form-control:focus, .form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
}

.btn-success {
  background-color: #198754;
  border-color: #198754;
}

.btn-success:hover {
  background-color: #157347;
  border-color: #146c43;
}

.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
  border-radius: 8px;
}
</style> 