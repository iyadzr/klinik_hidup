<template>
  <div class="registration-form glass-card shadow p-4 mt-4 mb-4">
    <!-- Patient Type Selection Toggle -->
    <div class="row mb-4">
      <div class="col-md-6 offset-md-3">
        <label for="patientTypeDropdown" class="form-label">Patient Type</label>
        <select id="patientTypeDropdown" v-model="patientType" class="form-select">
          <option value="new">New Patient</option>
          <option value="existing">Existing Patient</option>
        </select>
      </div>
    </div>
    
    <form @submit.prevent="registerPatient">
      <!-- Patient Registration Card -->
      <div class="card">
        <div class="card-header">
          <h4 class="mb-0">{{ patientType === 'new' ? 'Patient Registration' : 'Existing Patient Lookup' }}</h4>
        </div>
        <div class="card-body">
          <!-- Existing Patient Search Fields (shown only when existing patient selected) -->
          <div v-if="patientType === 'existing'" class="mb-4">
            <div class="row mb-3">
              <div class="col-md-12">
                <form @submit.prevent="searchPatients">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Enter Registration Number, NRIC, or Name" v-model="searchQuery">
                    <button class="btn btn-primary" type="submit">Search</button>
                  </div>
                </form>
              </div>
              <small class="text-muted">Search by registration number, NRIC, or patient name</small>
            </div>
            
            <!-- Search Results Table -->
            <div v-if="searchResults.length > 0" class="table-responsive mb-3">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>NRIC</th>
                    <th>Phone</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="result in searchResults" :key="result.id">
                    <td>{{ result.name }}</td>
                    <td>{{ result.nric }}</td>
                    <td>{{ result.phone }}</td>
                    <td>
                      <button type="button" class="btn btn-sm btn-primary" @click="selectExistingPatient(result)">
                        <i class="fas fa-check me-1"></i> Select
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div v-if="searchPerformed && searchResults.length === 0" class="alert alert-info">
              No patients found matching your search criteria.
            </div>
          </div>
          
          <!-- Multiple Patients Toggle -->
          <div v-if="patientType === 'new'" class="row mb-4">
            <div class="col-md-12">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="multiplePatients" 
                  v-model="multiplePatients"
                  @change="toggleMultiplePatients"
                >
                <label class="form-check-label" for="multiplePatients">
                  <i class="fas fa-users me-2"></i>
                  Register multiple patients for group consultation
                </label>
              </div>
              <small class="text-muted">Family members or companions can be registered together</small>
            </div>
          </div>

          <!-- Multiple Patients Interface -->
          <div v-if="multiplePatients && patientType === 'new'">
            <!-- Patients List -->
            <div class="row mb-3">
              <div class="col-12">
                <h5 class="mb-3">
                  <i class="fas fa-users text-primary me-2"></i>
                  Patients for Group Consultation ({{ patients.length }})
                </h5>
                
                <!-- Existing Patients Cards -->
                <div v-if="patients.length > 0" class="row g-3 mb-3">
                  <div v-for="(pat, index) in patients" :key="index" class="col-md-6">
                    <div class="card" :class="pat.relationship === 'self' ? 'border-success' : 'border-primary'">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <h6 class="card-title mb-1">
                              <i class="fas fa-user me-2"></i>
                              {{ pat.name }}
                              <span v-if="pat.relationship === 'self'" class="badge bg-success ms-2">Main Patient</span>
                              <span v-else-if="pat.relationship" class="badge bg-info ms-2">{{ formatRelationship(pat.relationship) }}</span>
                            </h6>
                            <p class="card-text small mb-1">
                              <strong>NRIC:</strong> {{ pat.nric }}<br>
                              <strong>Age:</strong> {{ calculateAge(pat.dateOfBirth) }} years<br>
                              <strong>Gender:</strong> {{ pat.gender === 'M' ? 'Male' : 'Female' }}<br>
                              <strong>Phone:</strong> {{ pat.phone }}
                            </p>
                            <p class="card-text small text-muted mb-1">
                              <strong>Symptoms:</strong> {{ pat.preInformedIllness }}
                            </p>
                          </div>
                          <div class="btn-group-vertical">
                            <button 
                              type="button" 
                              class="btn btn-sm btn-outline-primary mb-1" 
                              @click="editPatient(index)"
                            >
                              <i class="fas fa-edit"></i>
                            </button>
                            <button 
                              type="button" 
                              class="btn btn-sm btn-outline-danger" 
                              @click="removePatient(index)"
                            >
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Add Patient Button -->
                <button 
                  type="button" 
                  class="btn btn-outline-primary" 
                  @click="showPatientForm = true"
                  v-if="!showPatientForm"
                >
                  <i class="fas fa-plus me-2"></i>
                  Add {{ patients.length === 0 ? 'Main Patient' : 'Family Member/Companion' }}
                </button>
              </div>
            </div>

            <!-- Patient Form (when adding/editing) -->
            <div v-if="showPatientForm" class="card border-secondary mb-3">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="fas fa-user-plus me-2"></i>
                  {{ editingPatientIndex !== null ? 'Edit Patient' : 'Add New Patient' }}
                </h6>
              </div>
              <div class="card-body">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" v-model="currentPatient.name" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">NRIC *</label>
                    <input 
                      type="text" 
                      v-model="currentPatient.nric" 
                      class="form-control" 
                      required 
                      placeholder="Enter NRIC/Identification Number" 
                      @input="calculateDOBFromNRIC(currentPatient)"
                    >
                    <small class="text-muted">For 12-digit NRIC format, DOB and gender will be auto-calculated</small>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Phone *</label>
                    <input type="tel" v-model="currentPatient.phone" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" v-model="currentPatient.dateOfBirth" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Gender *</label>
                    <select v-model="currentPatient.gender" class="form-select" required>
                      <option value="">Select Gender</option>
                      <option value="M">Male</option>
                      <option value="F">Female</option>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <div :class="patients.length === 0 && editingPatientIndex === null ? 'col-md-12' : 'col-md-6'">
                    <label class="form-label">Address</label>
                    <input type="text" v-model="currentPatient.address" class="form-control" placeholder="Enter full address">
                  </div>
                  <div v-if="patients.length > 0 || editingPatientIndex !== null" class="col-md-6">
                    <label class="form-label">Relationship to Main Patient</label>
                    <select v-model="currentPatient.relationship" class="form-select" required>
                      <option value="">Select Relationship</option>
                      <option value="spouse">Spouse</option>
                      <option value="parent">Parent</option>
                      <option value="child">Child</option>
                      <option value="sibling">Sibling</option>
                      <option value="grandparent">Grandparent</option>
                      <option value="grandchild">Grandchild</option>
                      <option value="relative">Other Relative</option>
                      <option value="friend">Friend</option>
                      <option value="caregiver">Caregiver</option>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-12">
                    <label class="form-label">Symptoms & Chief Complaints *</label>
                    <textarea 
                      v-model="currentPatient.preInformedIllness" 
                      class="form-control" 
                      rows="3" 
                      required 
                      placeholder="Please describe the symptoms, complaints, or reason for this visit..."
                    ></textarea>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary" @click="saveCurrentPatient">
                    <i class="fas fa-save me-2"></i>
                    {{ editingPatientIndex !== null ? 'Update Patient' : 'Add Patient' }}
                  </button>
                  <button type="button" class="btn btn-secondary" @click="cancelPatientForm">
                    <i class="fas fa-times me-2"></i>
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Single Patient Registration Fields -->
          <div v-if="(patientType === 'new' && !multiplePatients) || selectedPatient">
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Name</label>
                <input type="text" v-model="patient.name" class="form-control" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">NRIC</label>
                <input type="text" v-model="patient.nric" class="form-control" required placeholder="Enter NRIC/Identification Number" @input="calculateDOBFromNRIC()" :readonly="patientType === 'existing'"> <!-- NRIC is readonly for existing only -->
                <small class="text-muted">For 12-digit NRIC format (YYMMDD-XX-XXXX), DOB and gender will be auto-calculated</small>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" v-model="patient.phone" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" v-model="patient.dateOfBirth" class="form-control" required :readonly="patientType === 'existing'"> <!-- DOB is readonly for existing only -->
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select v-model="patient.gender" class="form-select" required>
                  <option value="">Select Gender</option>
                  <option value="M">Male</option>
                  <option value="F">Female</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Address</label>
                <input type="text" v-model="patient.address" class="form-control" placeholder="Enter full address">
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Pre-informed Illness Section (only show for single patient registration) -->
      <div v-if="!multiplePatients" class="card mt-3">
        <div class="card-header">
          <h4 class="mb-0">Pre-informed Illness/Symptoms</h4>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Symptoms & Chief Complaints</label>
            <textarea v-model="patient.preInformedIllness" class="form-control" rows="4" required 
                      placeholder="Please describe the symptoms, complaints, or reason for this visit..."></textarea>
          </div>
        </div>
      </div>

      <!-- Doctor Consultation Section -->
      <div class="card mt-3">
        <div class="card-header">
          <h4 class="mb-0">Doctor Consultation</h4>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Assign Doctor</label>
              <select v-model="queueInfo.doctorId" class="form-select" required>
                <option value="">Select Doctor</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.displayName || (doctor.firstName && doctor.lastName ? doctor.firstName + ' ' + doctor.lastName : doctor.name || 'Unknown Doctor') }}
                </option>
              </select>
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary" :disabled="isSubmitDisabled">
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else-if="multiplePatients && patientType === 'new'" class="fas fa-users me-2"></i>
            <i v-else-if="patientType === 'new'" class="fas fa-user-plus me-2"></i>
            <i v-else class="fas fa-clipboard-list me-2"></i>
            
            <span v-if="isLoading">
              {{ multiplePatients ? 'Registering Patients...' : 'Registering Patient...' }}
            </span>
            <span v-else-if="multiplePatients && patientType === 'new'">
              Register {{ patients.length }} Patient{{ patients.length !== 1 ? 's' : '' }} for Group Consultation
            </span>
            <span v-else-if="patientType === 'new'">
              Register & Queue Patient
            </span>
            <span v-else>
              Add Existing Patient to Queue
            </span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  watch: {
    patientType(newType, oldType) {
      if (newType === 'new') {
        // Reset all patient fields when switching to new patient
        this.patient = {
          name: '',
          email: '',
          phone: '',
          dateOfBirth: '',
          gender: '',
          address: '',
          company: '',
          companyAddress: '',
          preInformedIllness: '',
          nric: ''
        };
        this.selectedPatient = null;
        this.searchQuery = '';
        this.searchResults = [];
        this.searchPerformed = false;
        // Keep the doctor selection (don't reset to maintain first doctor default)
      }
    }
  },
  name: 'PatientRegistration',
  data() {
    return {
      patientType: 'new', // Default to new patient
      searchQuery: '',
      searchResults: [],
      searchPerformed: false,
      selectedPatient: null, // Will store the selected existing patient
      patient: {
        name: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        companyAddress: '',
        preInformedIllness: '',
        nric: ''
      },
      queueInfo: {
        doctorId: ''
      },
      doctors: [],
      isLoading: false,
      
      // Multiple patients functionality
      multiplePatients: false,
      patients: [],
      showPatientForm: false,
      editingPatientIndex: null,
      currentPatient: {
        name: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        companyAddress: '',
        preInformedIllness: '',
        nric: '',
        relationship: ''
      }
    };
  },
  
  // No need for watch since we're using the @input event

  computed: {
    isSubmitDisabled() {
      if (this.isLoading) return true;
      if (this.multiplePatients && this.patientType === 'new') {
        return this.patients.length === 0 || !this.queueInfo.doctorId;
      }
      return false;
    }
  },
  async created() {
    await this.loadDoctors();
  },
  methods: {
    calculateDOBFromNRIC(patient = null) {
      const targetPatient = patient || this.patient;
      
      // Check if targetPatient exists and has nric property
      if (!targetPatient || !targetPatient.hasOwnProperty('nric')) {
        return;
      }
      
      const nric = targetPatient.nric;
      
      // Check if nric exists and is a string
      if (!nric || typeof nric !== 'string' || nric.trim() === '') {
        return;
      }
      
      // Check if the NRIC follows the YYMMDD-XX-XXXX pattern (with or without hyphens)
      // First, remove hyphens if they exist
      const cleanNric = nric.replace(/[\s-]/g, '');
      
      // Check if it has exactly 12 digits
      if (cleanNric.length === 12 && /^\d{12}$/.test(cleanNric)) {
        try {
          // Extract YYMMDD portion
          const yearStr = cleanNric.substring(0, 2);
          const monthStr = cleanNric.substring(2, 4);
          const dayStr = cleanNric.substring(4, 6);
          
          // Validate month and day
          const month = parseInt(monthStr, 10);
          const day = parseInt(dayStr, 10);
          
          if (month < 1 || month > 12 || day < 1 || day > 31) {
            console.warn('Invalid month or day in NRIC:', { month, day, nric: cleanNric });
            return;
          }
          
          // Determine century (19XX or 20XX)
          const currentYear = new Date().getFullYear();
          const currentLastTwoDigits = currentYear % 100;
          let fullYear;
          
          if (parseInt(yearStr, 10) <= currentLastTwoDigits) {
            // If the year portion is less than or equal to current year's last two digits,
            // assume it's from the 2000s
            fullYear = 2000 + parseInt(yearStr, 10);
          } else {
            // Otherwise, assume it's from the 1900s
            fullYear = 1900 + parseInt(yearStr, 10);
          }
          
          // Format as YYYY-MM-DD for the date input
          const formattedDate = `${fullYear}-${monthStr.padStart(2, '0')}-${dayStr.padStart(2, '0')}`;
          targetPatient.dateOfBirth = formattedDate;
          
          // Determine gender based on the last digit of NRIC
          // If last digit is even (0,2,4,6,8) -> Female, if odd (1,3,5,7,9) -> Male
          const lastDigit = parseInt(cleanNric.charAt(11), 10);
          if (lastDigit % 2 === 0) { // Even number (including 0)
            targetPatient.gender = 'F'; // Female
          } else { // Odd number
            targetPatient.gender = 'M'; // Male
          }
        } catch (e) {
          console.error('Error calculating DOB and gender from NRIC:', e);
        }
      }
    },
    
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data;
        
        // Auto-select the first doctor if available
        if (this.doctors && this.doctors.length > 0) {
          this.queueInfo.doctorId = this.doctors[0].id;
        }
      } catch (error) {
        console.error('Error loading doctors:', error);
      }
    },
    async searchPatients() {
      try {
        this.searchPerformed = true;
        const response = await axios.get(`/api/patients/search?query=${this.searchQuery}`);
        this.searchResults = response.data;
      } catch (error) {
        console.error('Error searching patients:', error);
        this.searchResults = [];
      }
    },

    selectExistingPatient(patient) {
      this.selectedPatient = patient;
      this.patientType = 'existing';
      // Populate the patient form with the selected patient's data (excluding preInformedIllness)
      this.patient = {
        name: patient.name || '',
        nric: patient.nric || '',
        email: patient.email || '',
        phone: patient.phone || '',
        address: patient.address || '',
        gender: patient.gender || '',
        dateOfBirth: patient.dateOfBirth || '',
        company: patient.company || '',
        companyAddress: patient.companyAddress || '',
        preInformedIllness: '' // Reset preInformedIllness for new visit
      };
    },

    resetForm() {
      // Clear all patient fields
      this.patient = {
        name: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        companyAddress: '',
        preInformedIllness: '',
        nric: ''
      };
      
      // Reset search and selection
      this.searchQuery = '';
      this.searchResults = [];
      this.searchPerformed = false;
      this.selectedPatient = null;
      
      // Reset to new patient type
      this.patientType = 'new';
      
      // Reset multiple patients data
      this.multiplePatients = false;
      this.patients = [];
      this.showPatientForm = false;
      this.editingPatientIndex = null;
      this.resetCurrentPatient();
      
      // Keep doctor selection (auto-select first doctor)
      if (this.doctors && this.doctors.length > 0) {
        this.queueInfo.doctorId = this.doctors[0].id;
      }
    },

    // Multiple patients functionality
    toggleMultiplePatients() {
      if (!this.multiplePatients) {
        // Reset multiple patients data when switching to single
        this.patients = [];
        this.showPatientForm = false;
        this.editingPatientIndex = null;
        this.resetCurrentPatient();
      }
    },

    resetCurrentPatient() {
      this.currentPatient = {
        name: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        companyAddress: '',
        preInformedIllness: '',
        nric: '',
        relationship: ''
      };
    },

    saveCurrentPatient() {
      // Validate required fields
      if (!this.currentPatient.name || !this.currentPatient.nric || !this.currentPatient.phone || 
          !this.currentPatient.dateOfBirth || !this.currentPatient.gender || !this.currentPatient.preInformedIllness) {
        alert('Please fill in all required fields marked with *');
        return;
      }

      // Check for duplicate NRIC
      const existingIndex = this.patients.findIndex((p, index) => 
        p.nric === this.currentPatient.nric && index !== this.editingPatientIndex
      );
      
      if (existingIndex !== -1) {
        alert('A patient with this NRIC has already been added.');
        return;
      }

      // Handle relationship logic
      if (this.editingPatientIndex !== null) {
        // Update existing patient
        this.patients[this.editingPatientIndex] = { ...this.currentPatient };
        this.editingPatientIndex = null;
      } else {
        // Add new patient
        const patientToAdd = { ...this.currentPatient };
        
        // If this is the first patient, automatically set as main patient
        if (this.patients.length === 0) {
          patientToAdd.relationship = 'self';
        } else {
          // For subsequent patients, validate that relationship is selected
          if (!patientToAdd.relationship) {
            alert('Please select the relationship to the main patient.');
            return;
          }
        }
        
        this.patients.push(patientToAdd);
      }

      this.resetCurrentPatient();
      this.showPatientForm = false;
    },

    editPatient(index) {
      this.currentPatient = { ...this.patients[index] };
      this.editingPatientIndex = index;
      this.showPatientForm = true;
    },

    removePatient(index) {
      if (confirm('Are you sure you want to remove this patient?')) {
        this.patients.splice(index, 1);
      }
    },

    cancelPatientForm() {
      this.resetCurrentPatient();
      this.showPatientForm = false;
      this.editingPatientIndex = null;
    },

    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 0;
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      
      return age;
    },

    formatRelationship(relationship) {
      const relationships = {
        'self': 'Main Patient',
        'spouse': 'Spouse',
        'parent': 'Parent',
        'child': 'Child',
        'sibling': 'Sibling',
        'grandparent': 'Grandparent',
        'grandchild': 'Grandchild',
        'relative': 'Other Relative',
        'friend': 'Friend',
        'caregiver': 'Caregiver'
      };
      return relationships[relationship] || relationship;
    },
    
    async registerPatient() {
      try {
        this.isLoading = true;
        
        if (this.multiplePatients && this.patientType === 'new') {
          // Multiple patients registration
          if (this.patients.length === 0) {
            alert('Please add at least one patient before submitting.');
            return;
          }

          const registeredPatients = [];
          
          // Register each patient
          for (const patient of this.patients) {
            try {
              const response = await axios.post('/api/patients', patient);
              registeredPatients.push({
                id: response.data.id,
                registrationNumber: response.data.registrationNumber,
                name: patient.name,
                relationship: patient.relationship
              });
            } catch (error) {
              console.error(`Error registering patient ${patient.name}:`, error);
              
              let errorMessage = `Failed to register patient: ${patient.name}. Please try again.`;
              
              if (error.response && error.response.data) {
                if (error.response.data.error) {
                  errorMessage = `${patient.name}: ${error.response.data.error}`;
                } else if (error.response.data.message) {
                  errorMessage = `${patient.name}: ${error.response.data.message}`;
                }
              }
              
              alert(errorMessage);
              return;
            }
          }

          // Create group queue entry
          const queueResponse = await axios.post('/api/queue/group', {
            patients: registeredPatients,
            doctorId: this.queueInfo.doctorId,
            isGroupConsultation: true
          });

          if (queueResponse.data) {
            alert(`Successfully registered ${registeredPatients.length} patients for group consultation!`);
            this.resetForm();
            // Force refresh of queue data by adding a timestamp parameter
            this.$router.push('/queue?refresh=' + Date.now());
          }

        } else {
          // Single patient registration (existing logic)
          let patientId, regNumber;

          if (this.patientType === 'new') {
            // Register new patient
            const response = await axios.post('/api/patients', this.patient);
            patientId = response.data.id;
            regNumber = response.data.registrationNumber;
          } else if (this.selectedPatient) {
            // Update existing patient with any modifications
            await axios.put(`/api/patients/${this.selectedPatient.id}`, this.patient);
            patientId = this.selectedPatient.id;
            regNumber = this.selectedPatient.registrationNumber;
          } else {
            alert('Please select an existing patient before proceeding.');
            return;
          }

          // Add to queue
          const queueResponse = await axios.post('/api/queue', {
            patientId: patientId,
            doctorId: this.queueInfo.doctorId
          });

          if (queueResponse.data) {
            alert('Patient registered and added to queue successfully!');
            // Reset the form for next registration
            this.resetForm();
            // Force refresh of queue data by adding a timestamp parameter
            this.$router.push('/queue?refresh=' + Date.now());
          }
        }
      } catch (error) {
        console.error('Error registering patient:', error);
        
        let errorMessage = 'Failed to register patient. Please try again.';
        
        if (error.response && error.response.data) {
          if (error.response.data.error) {
            errorMessage = error.response.data.error;
          } else if (error.response.data.message) {
            errorMessage = error.response.data.message;
          }
        }
        
        alert(errorMessage);
      } finally {
        this.isLoading = false;
      }
    }
  }
};
</script>

<style scoped>
.registration-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

/* Form focus improvements */
.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Card improvements */
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 0.5rem;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  border-radius: 0.5rem 0.5rem 0 0 !important;
}

/* Table hover effects */
.table-hover tbody tr:hover {
  background-color: #f5f5f5;
}

/* Button improvements */
.btn {
  border-radius: 0.375rem;
  font-weight: 500;
}

/* Search results styling */
.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

/* Multiple patients styling */
.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.card.border-primary {
  border-color: #0d6efd !important;
}

.card.border-secondary {
  border-color: #6c757d !important;
}

.btn-group-vertical .btn {
  border-radius: 0.25rem;
  margin-bottom: 0.25rem;
}

.btn-group-vertical .btn:last-child {
  margin-bottom: 0;
}

/* Patient card animations */
.card {
  transition: all 0.2s ease-in-out;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Form validation improvements */
.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
  border-color: #dc3545;
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
  border-color: #198754;
}
</style>
