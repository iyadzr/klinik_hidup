<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Doctors</h2>
      <button class="btn btn-primary" @click="showAddModal = true">Add Doctor</button>
    </div>

    <!-- Doctor List -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Specialization</th>
                <th>User Account</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doctor in doctors" :key="doctor.id">
                <td>{{ doctor.name }}</td>
                <td>{{ doctor.email }}</td>
                <td>{{ doctor.phone }}</td>
                <td>{{ doctor.specialization }}</td>
                <td>
                  <div v-if="doctor.hasUserAccount" class="d-flex align-items-center">
                    <span class="badge bg-success me-2">
                      <i class="fas fa-check me-1"></i>Active Account
                    </span>
                    <small class="text-muted">
                      <div v-if="doctor.userId">User ID: {{ doctor.userId }}</div>
                      <div>Status: 
                        <span v-if="doctor.isActive" class="badge badge-sm bg-success">Active</span>
                        <span v-else class="badge badge-sm bg-danger">Inactive</span>
                      </div>
                    </small>
                  </div>
                  <div v-else>
                    <span class="badge bg-secondary">
                      <i class="fas fa-times me-1"></i>No Account
                    </span>
                    <small class="text-muted d-block mt-1">Account will be created automatically</small>
                  </div>
                </td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-info" @click="editDoctor(doctor)" title="Edit Doctor">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="deleteDoctor(doctor)" title="Delete Doctor">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Doctor Modal -->
    <div class="modal" :class="{ 'd-block': showAddModal }" tabindex="-1" v-if="showAddModal" style="padding-top: 80px;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingDoctor ? 'Edit Doctor' : 'Add Doctor' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="handleSubmit">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" v-model="form.name" required>
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
                <label class="form-label">Specialization</label>
                <select class="form-select" v-model="form.specialization" ref="specializationSelect" required>
                  <option value="General Practice (GP)">General Practice (GP)</option>
                  <option value="Internal Medicine">Internal Medicine</option>
                  <option value="Pediatrics">Pediatrics</option>
                  <option value="Cardiology">Cardiology</option>
                  <option value="Dermatology">Dermatology</option>
                  <option value="Orthopedics">Orthopedics</option>
                  <option value="Gynecology">Gynecology</option>
                  <option value="Psychiatry">Psychiatry</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">License Number (Optional)</label>
                <input type="text" class="form-control" v-model="form.licenseNumber">
              </div>
              
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DoctorList',
  data() {
    return {
      doctors: [],
      showAddModal: false,
      editingDoctor: null,
      form: {
        name: '',
        email: '',
        phone: '',
        specialization: 'General Practice (GP)',
        licenseNumber: ''
      }
    };
  },
  created() {
    this.loadDoctors();
  },
  methods: {
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data;
      } catch (error) {
        console.error('Failed to load doctors:', error);
        // TODO: Add error handling
      }
    },
    editDoctor(doctor) {
      this.editingDoctor = doctor;
      this.form = {
        name: doctor.name || '',
        email: doctor.email || '',
        phone: doctor.phone || '',
        specialization: doctor.specialization || 'General Practice (GP)',
        licenseNumber: doctor.licenseNumber || ''
      };
      this.showAddModal = true;
      
      // Ensure Vue reactivity for the select dropdown
      this.$nextTick(() => {
        // Force reactivity update for the specialization select
        if (this.$refs.specializationSelect) {
          this.$refs.specializationSelect.value = this.form.specialization;
        }
      });
    },
    async deleteDoctor(doctor) {
      if (!confirm(`Are you sure you want to delete Dr. ${doctor.name}?`)) {
        return;
      }
      
      try {
        console.log('Deleting doctor with ID:', doctor.id);
        const response = await axios.delete(`/api/doctors/${doctor.id}`);
        console.log('Delete response:', response.data);
        
        // Remove from local array
        this.doctors = this.doctors.filter(d => d.id !== doctor.id);
        
        // Show success message
        alert('Doctor deleted successfully!');
      } catch (error) {
        console.error('Failed to delete doctor:', error);
        
        if (error.response) {
          console.error('Error response:', error.response.data);
          alert(`Failed to delete doctor: ${error.response.data.message || error.response.data.error || 'Unknown error'}`);
        } else if (error.request) {
          console.error('Network error:', error.request);
          alert('Network error: Unable to connect to server');
        } else {
          console.error('Error:', error.message);
          alert(`Error: ${error.message}`);
        }
      }
    },
    async handleSubmit() {
      try {
        const isEditing = this.editingDoctor !== null;
        const endpoint = isEditing ? `/api/doctors/${this.editingDoctor.id}` : '/api/doctors';
        const method = isEditing ? 'put' : 'post';
        
        const response = await axios[method](endpoint, {
          name: this.form.name,
          email: this.form.email,
          phone: this.form.phone,
          specialization: this.form.specialization,
          licenseNumber: this.form.licenseNumber || null
        });
        
        // Store editing state before closing modal (since closeModal resets editingDoctor)
        const wasEditing = isEditing;
        
        this.closeModal();
        await this.loadDoctors();
        
        // Show appropriate success message
        if (wasEditing) {
          alert('Doctor updated successfully!');
        } else {
          const doctor = response.data.doctor;
          if (doctor && doctor.temporaryPassword) {
            alert(`Doctor and user account created successfully!\n\nLogin Credentials:\nUsername: ${doctor.username}\nTemporary Password: ${doctor.temporaryPassword}\n\nPlease share these credentials with the doctor.`);
          } else {
            alert('Doctor added successfully!');
          }
        }
      } catch (error) {
        console.error('Failed to save doctor:', error.response?.data || error);
        alert(error.response?.data?.message || 'Failed to save doctor. Please try again.');
      }
    },
    closeModal() {
      this.showAddModal = false;
      this.editingDoctor = null;
      this.form = {
        name: '',
        email: '',
        phone: '',
        specialization: 'General Practice (GP)',
        licenseNumber: ''
      };
    }
  }
};
</script>

<style scoped>
.modal {
  display: block;
}

.btn-xs {
  padding: 0.125rem 0.25rem;
  font-size: 0.75rem;
}

.badge-sm {
  font-size: 0.65em;
}

.btn-group .btn {
  margin-right: 2px;
}

.doctor-info {
  min-width: 120px;
}
</style>
