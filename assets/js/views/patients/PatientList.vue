<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Patients</h2>
      <button class="btn btn-primary" @click="showAddModal = true">Add Patient</button>
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
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Date of Birth</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="patient in patients" :key="patient.id">
                <td>{{ patient.firstName }} {{ patient.lastName }}</td>
                <td>{{ patient.email }}</td>
                <td>{{ patient.phone }}</td>
                <td>{{ patient.dateOfBirth }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="editPatient(patient)">Edit</button>
                  <button class="btn btn-sm btn-danger" @click="deletePatient(patient)">Delete</button>
                </td>
              </tr>
              <tr v-if="patients.length === 0">
                <td colspan="5" class="text-center">No patients found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" tabindex="-1" v-if="showAddModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingPatient ? 'Edit Patient' : 'Add Patient' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <form @submit.prevent="savePatient">
              <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" v-model="form.firstName" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" v-model="form.lastName" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" v-model="form.email" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-control" v-model="form.phone" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" v-model="form.dateOfBirth">
              </div>
              <div class="mb-3">
                <label class="form-label">Medical History</label>
                <textarea class="form-control" v-model="form.medicalHistory" rows="3"></textarea>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
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
      editingPatient: null,
      loading: false,
      saving: false,
      error: null,
      form: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        dateOfBirth: '',
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
      this.form = {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        dateOfBirth: '',
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
