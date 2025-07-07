<template>
  <div class="modal fade" :class="{ show: visible }" tabindex="-1" v-if="visible">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ editingPatient ? 'Edit Patient' : 'Add Patient' }}</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <form @submit.prevent="savePatient">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" class="form-control" v-model="form.name" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">NRIC</label>
                  <input type="text" 
                         class="form-control" 
                         v-model="form.nric" 
                         :readonly="editingPatient"
                         :class="{ 'bg-light': editingPatient }"
                         @input="handleNRICInput"
                         @blur="checkNricUniqueness"
                         placeholder="e.g., 123456-12-1234"
                         maxlength="14"
                         required>
                  <div class="form-check-container mt-2" v-if="!editingPatient">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="nricFormatEdit" id="newNricEdit" value="new" v-model="nricFormatType" @change="handleNRICFormatChange">
                      <label class="form-check-label" for="newNricEdit">
                        New NRIC (with dashes)
                      </label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="nricFormatEdit" id="armyPoliceEdit" value="army" v-model="nricFormatType" @change="handleNRICFormatChange">
                      <label class="form-check-label" for="armyPoliceEdit">
                        Army/Police (no dashes)
                      </label>
                    </div>
                  </div>
                  <small class="text-muted" v-if="editingPatient">NRIC cannot be changed</small>
                  <small class="text-muted" v-else-if="nricFormatType === 'new'">12-digit NRIC format: YYMMDD-XX-XXXX (DOB and gender auto-calculated)</small>
                  <small class="text-muted" v-else>Army/Police format: no dashes (e.g., 640611015064)</small>
                  <small class="text-danger" v-if="nricError">{{ nricError }}</small>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" v-model="form.email">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Phone</label>
                  <input type="tel" class="form-control" v-model="form.phone" required @input="handlePhoneInput">
                  <div class="form-check-container mt-2">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="phoneFormatEdit" id="mobileEdit" value="mobile" v-model="phoneFormatType" @change="handlePhoneFormatChange">
                      <label class="form-check-label" for="mobileEdit">
                        Mobile phone
                      </label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="phoneFormatEdit" id="fixedLineEdit" value="fixed" v-model="phoneFormatType" @change="handlePhoneFormatChange">
                      <label class="form-check-label" for="fixedLineEdit">
                        Fixed line
                      </label>
                    </div>
                  </div>
                  <small class="text-muted">{{ phoneFormatType === 'mobile' ? 'Mobile format: 011-39954423' : 'Fixed line format: 01-139954423' }}</small>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Date of Birth</label>
                  <input type="date" class="form-control" v-model="form.dateOfBirth" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Gender</label>
                  <select class="form-select" v-model="form.gender" required>
                    <option value="">Select Gender</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" v-model="form.address">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Company</label>
              <input type="text" class="form-control" v-model="form.company">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Visit History</label>
              <div v-if="editingPatient && editingPatient.id" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                <div v-if="visitHistories && visitHistories.length > 0">
                  <div v-for="visit in visitHistories.slice(0, 5)" :key="visit.id" class="mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <small class="text-muted">{{ formatDate(visit.consultationDate) }}</small>
                        <div class="fw-bold">Dr. {{ visit.doctor?.name || 'Unknown' }}</div>
                        <div class="text-muted small">{{ visit.diagnosis || 'No diagnosis recorded' }}</div>
                      </div>
                      <button 
                        type="button" 
                        class="btn btn-outline-primary btn-sm" 
                        @click="$emit('view-visit-details', visit)"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div v-if="visitHistories.length > 5" class="text-center">
                    <button type="button" class="btn btn-link btn-sm" @click="$emit('show-visit-history', editingPatient)">
                      View all {{ visitHistories.length }} visits
                    </button>
                  </div>
                </div>
                <div v-else class="text-center text-muted py-3">
                  <i class="fas fa-file-medical-alt fa-2x mb-2"></i>
                  <div>No visit history found</div>
                  <small>This is the patient's first visit</small>
                </div>
              </div>
              <div v-else class="text-muted p-3 border rounded">
                <i class="fas fa-info-circle me-2"></i>
                Visit history will be available after saving the patient
              </div>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-secondary me-2" @click="close">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="saving || nricError">
                {{ saving ? 'Saving...' : 'Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-if="visible"></div>
</template>

<script>
import axios from 'axios';
import { formatNRIC } from '../../utils/nricFormatter.js';
import timezoneUtils from '../../utils/timezoneUtils.js';

export default {
  name: 'PatientFormModal',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    editingPatient: {
      type: Object,
      default: null
    },
    visitHistories: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'save', 'view-visit-details', 'show-visit-history'],
  data() {
    return {
      form: {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: ''
      },
      saving: false,
      error: null,
      nricError: null,
      nricFormatType: 'new',
      phoneFormatType: 'mobile',
      timezoneUtils: timezoneUtils
    };
  },
  watch: {
    editingPatient: {
      handler(newPatient) {
        if (newPatient) {
          this.form = {
            name: newPatient.name || '',
            nric: newPatient.nric || '',
            email: newPatient.email || '',
            phone: newPatient.phone || '',
            dateOfBirth: newPatient.dateOfBirth || '',
            gender: newPatient.gender || '',
            address: newPatient.address || '',
            company: newPatient.company || ''
          };
        } else {
          this.resetForm();
        }
      },
      immediate: true
    },
    visible(newVal) {
      if (!newVal) {
        this.resetForm();
      }
    }
  },
  methods: {
    resetForm() {
      this.form = {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: ''
      };
      this.error = null;
      this.nricError = null;
      this.nricFormatType = 'new';
      this.phoneFormatType = 'mobile';
    },
    close() {
      this.$emit('close');
    },
    async savePatient() {
      this.saving = true;
      this.error = null;
      
      try {
        if (this.editingPatient) {
          const response = await axios.put(`/api/patients/${this.editingPatient.id}`, this.form);
          this.$emit('save', { type: 'update', patient: response.data.patient });
        } else {
          const response = await axios.post('/api/patients', this.form);
          this.$emit('save', { type: 'create', patient: response.data.patient });
        }
        this.close();
      } catch (error) {
        console.error('Failed to save patient:', error);
        this.error = error.response?.data?.message || 'Failed to save patient';
      } finally {
        this.saving = false;
      }
    },
    handleNRICInput(event) {
      if (!this.editingPatient) {
        const input = event.target.value;
        const cleanNric = input.replace(/\D/g, ''); // Remove all non-digits
        
        if (this.nricFormatType === 'new') {
          // New NRIC format with dashes: YYMMDD-XX-XXXX
          if (cleanNric.length >= 6) {
            let formatted = cleanNric.substring(0, 6);
            if (cleanNric.length > 6) {
              formatted += '-' + cleanNric.substring(6, 8);
            }
            if (cleanNric.length > 8) {
              formatted += '-' + cleanNric.substring(8, 12);
            }
            this.form.nric = formatted;
          } else {
            this.form.nric = cleanNric;
          }
        } else {
          // Army/Police format: no dashes, just numbers
          this.form.nric = cleanNric.substring(0, 12); // Limit to 12 digits
        }
      }
    },
    handleNRICFormatChange() {
      // Clear NRIC when changing format
      this.form.nric = '';
      this.nricError = null;
    },
    handlePhoneInput(event) {
      const input = event.target.value;
      const cleanPhone = input.replace(/\D/g, ''); // Remove all non-digits
      
      if (this.phoneFormatType === 'mobile') {
        // Mobile format: 011-39954423
        if (cleanPhone.length >= 3) {
          let formatted = cleanPhone.substring(0, 3);
          if (cleanPhone.length > 3) {
            formatted += '-' + cleanPhone.substring(3, 11);
          }
          this.form.phone = formatted;
        } else {
          this.form.phone = cleanPhone;
        }
      } else {
        // Fixed line format: 01-139954423
        if (cleanPhone.length >= 2) {
          let formatted = cleanPhone.substring(0, 2);
          if (cleanPhone.length > 2) {
            formatted += '-' + cleanPhone.substring(2, 11);
          }
          this.form.phone = formatted;
        } else {
          this.form.phone = cleanPhone;
        }
      }
    },
    handlePhoneFormatChange() {
      // Clear phone when changing format
      this.form.phone = '';
    },
    async checkNricUniqueness() {
      if (!this.form.nric || this.editingPatient) {
        this.nricError = null;
        return;
      }
      
      try {
        // Check if NRIC already exists
        const response = await axios.get('/api/patients/search', {
          params: { query: this.form.nric }
        });
        
        const existingPatient = response.data.find(patient => 
          patient.nric && patient.nric.toLowerCase() === this.form.nric.toLowerCase()
        );
        
        if (existingPatient) {
          this.nricError = 'This NRIC is already registered to another patient';
        } else {
          this.nricError = null;
        }
      } catch (error) {
        console.error('Error checking NRIC uniqueness:', error);
        // Don't show error to user for this check, just allow them to proceed
        this.nricError = null;
      }
    },
    formatDate(date) {
      if (!date) return '';
      return this.timezoneUtils.formatDateMalaysia(new Date(date));
    }
  }
};
</script>

<style scoped>
.form-check-container {
  display: flex;
  gap: 1rem;
}

.modal.show {
  display: block;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: #000;
  opacity: 0.5;
}
</style> 