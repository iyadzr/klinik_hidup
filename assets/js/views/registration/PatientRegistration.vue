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
                    <th>Reg #</th>
                    <th>Name</th>
                    <th>NRIC</th>
                    <th>Phone</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="result in searchResults" :key="result.id">
                    <td>{{ result.registrationNumber }}</td>
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
          
          <!-- New Patient Registration Fields -->
          <div v-if="patientType === 'new' || selectedPatient">
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Name</label>
                <input type="text" v-model="patient.name" class="form-control" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">NRIC</label>
                <input type="text" v-model="patient.nric" class="form-control" required placeholder="Enter NRIC/Identification Number" @input="calculateDOBFromNRIC" :readonly="patientType === 'existing'"> <!-- NRIC is readonly for existing only -->
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
                <input type="text" v-model="patient.address" class="form-control">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Pre-informed Illness/Symptoms</label>
              <textarea v-model="patient.preInformedIllness" class="form-control" rows="3" required></textarea>
            </div>
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
          
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus me-2" v-if="patientType === 'new'"></i>
            <i class="fas fa-clipboard-list me-2" v-else></i>
            {{ patientType === 'new' ? 'Register & Queue Patient' : 'Add Existing Patient to Queue' }}
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
        // Reset all patient fields
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
        // Optionally reset queue info (doctor assignment)
        this.queueInfo = { doctorId: '' };
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
      isLoading: false
    };
  },
  
  // No need for watch since we're using the @input event

  async created() {
    await this.loadDoctors();
  },
  methods: {
    calculateDOBFromNRIC() {
      const nric = this.patient.nric;
      
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
            console.warn('Invalid month or day in NRIC');
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
          this.patient.dateOfBirth = formattedDate;
          
          // Determine gender based on the last digit of NRIC
          // If last digit is even (0,2,4,6,8) -> Female, if odd (1,3,5,7,9) -> Male
          const lastDigit = parseInt(cleanNric.charAt(11), 10);
          if (lastDigit % 2 === 0) { // Even number (including 0)
            this.patient.gender = 'F'; // Female
          } else { // Odd number
            this.patient.gender = 'M'; // Male
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
      // Populate the patient form with the selected patient's data
      this.patient = {
        name: patient.name,
        nric: patient.nric,
        phone: patient.phone,
        address: patient.address,
        gender: patient.gender,
        dateOfBirth: patient.dateOfBirth,
        company: patient.company
      };
    },
    
    async registerPatient() {
      try {
        this.isLoading = true;
        let patientId, regNumber;

        if (this.patientType === 'new') {
          // Register new patient
          const response = await axios.post('/api/patients', this.patient);
          patientId = response.data.id;
          regNumber = response.data.registrationNumber;
        } else if (this.selectedPatient) {
          // Use existing patient
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
          this.$router.push('/queue');
        }
      } catch (error) {
        console.error('Error registering patient:', error);
        alert('Failed to register patient. Please try again.');
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
</style>
