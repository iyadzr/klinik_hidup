<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Patients</h2>

    </div>

    <!-- Search Bar -->
    <div class="row mb-3">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Search by name, NRIC, or phone" v-model="searchQuery" @keyup.enter="searchPatients">
          <button class="btn btn-outline-primary" @click="searchPatients">Search</button>
          <button class="btn btn-outline-secondary" @click="clearSearch" v-if="searchQuery">Clear</button>
        </div>
      </div>
    </div>
    <!-- Patient List -->
    <div class="card">
      <div class="card-body">
        <div v-if="loading" class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>NRIC</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(patient, index) in patients" :key="patient.id">
                <td>{{ index + 1 }}</td>
                <td>{{ patient.name }}</td>
                <td>{{ patient.nric || 'N/A' }}</td>
                <td>{{ patient.phone }}</td>
                <td>{{ patient.gender === 'M' ? 'Male' : patient.gender === 'F' ? 'Female' : 'N/A' }}</td>
                <td>{{ patient.dateOfBirth ? new Date(patient.dateOfBirth).toLocaleDateString() : 'N/A' }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="editPatient(patient)">Edit</button>
                  <button class="btn btn-sm btn-danger" @click="deletePatient(patient)">Delete</button>
                </td>
              </tr>
              <tr v-if="patients.length === 0">
                <td colspan="7" class="text-center">No patients found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" tabindex="-1" v-if="showAddModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingPatient ? 'Edit Patient' : 'Add Patient' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
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
                           @blur="checkNricUniqueness"
                           required>
                    <small class="text-muted" v-if="editingPatient">NRIC cannot be changed</small>
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
                    <input type="tel" class="form-control" v-model="form.phone" required>
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
                <label class="form-label">Pre-informed Illness/Symptoms</label>
                <textarea class="form-control" v-model="form.preInformedIllness" rows="2" placeholder="Initial symptoms or complaints reported during registration"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Medical History</label>
                <textarea class="form-control" v-model="form.medicalHistory" rows="3" placeholder="Past medical conditions, surgeries, medications, etc."></textarea>
              </div>
              
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving || nricError">
                  {{ saving ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showAddModal"></div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PatientList',
  emits: ['patient-added', 'patient-updated', 'patient-deleted'],
  data() {
    return {
      patients: [],
      showAddModal: false,
      searchQuery: '',
      editingPatient: null,
      loading: false,
      saving: false,
      error: null,
      nricError: null,
      form: {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        preInformedIllness: '',
        medicalHistory: ''
      }
    };
  },
  created() {
    this.loadPatients();
  },
  methods: {
    async loadPatients() {
      this.loading = true;
      try {
        const response = await axios.get('/api/patients');
        this.patients = response.data;
      } catch (error) {
        console.error('Failed to load patients:', error);
      } finally {
        this.loading = false;
      }
    },
    async searchPatients() {
      if (!this.searchQuery) {
        this.loadPatients();
        return;
      }
      this.loading = true;
      try {
        const response = await axios.get('/api/patients/search', {
          params: { query: this.searchQuery }
        });
        this.patients = response.data;
      } catch (error) {
        console.error('Failed to search patients:', error);
      } finally {
        this.loading = false;
      }
    },
    clearSearch() {
      this.searchQuery = '';
      this.loadPatients();
    },
    editPatient(patient) {
      this.editingPatient = patient;
      this.form = { ...patient };
      this.showAddModal = true;
    },
    async deletePatient(patient) {
      if (!confirm('Are you sure you want to delete this patient?')) {
        return;
      }
      
      try {
        await axios.delete(`/api/patients/${patient.id}`);
        this.patients = this.patients.filter(p => p.id !== patient.id);
        this.$emit('patient-deleted');
      } catch (error) {
        console.error('Failed to delete patient:', error);
      }
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
    async savePatient() {
      this.saving = true;
      this.error = null;
      
      try {
        if (this.editingPatient) {
          const response = await axios.put(`/api/patients/${this.editingPatient.id}`, this.form);
          const index = this.patients.findIndex(p => p.id === this.editingPatient.id);
          this.patients[index] = response.data.patient;
          this.$emit('patient-updated');
        } else {
          const response = await axios.post('/api/patients', this.form);
          this.patients.push(response.data.patient);
          this.$emit('patient-added');
        }
        this.closeModal();
      } catch (error) {
        console.error('Failed to save patient:', error);
        this.error = error.response?.data?.message || 'Failed to save patient. Please try again.';
      } finally {
        this.saving = false;
      }
    },
    closeModal() {
      this.showAddModal = false;
      this.editingPatient = null;
      this.error = null;
      this.nricError = null;
      this.form = {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        preInformedIllness: '',
        medicalHistory: ''
      };
    }
  }
};
</script>

<style scoped>
.modal {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  z-index: 1040;
}

.modal {
  z-index: 1050;
}
</style>
