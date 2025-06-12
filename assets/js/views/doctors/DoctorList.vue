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
                <th>Phone</th>
                <th>Specialization</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doctor in doctors" :key="doctor.id">
                <td>{{ doctor.name }}</td>
                <td>{{ doctor.phone }}</td>
                <td>{{ doctor.specialization }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="editDoctor(doctor)">Edit</button>
                  <button class="btn btn-sm btn-danger" @click="deleteDoctor(doctor)">Delete</button>
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
                <label class="form-label">Phone</label>
                <input type="tel" class="form-control" v-model="form.phone" required>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Specialization</label>
                <select class="form-select" v-model="form.specialization" required>
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
        phone: doctor.phone || '',
        specialization: doctor.specialization || 'General Practice (GP)',
        licenseNumber: doctor.licenseNumber || ''
      };
      this.showAddModal = true;
    },
    async deleteDoctor(doctor) {
      if (confirm('Are you sure you want to delete this doctor?')) {
        try {
          await axios.delete(`/api/doctors/${doctor.id}`);
          this.doctors = this.doctors.filter(d => d.id !== doctor.id);
        } catch (error) {
          console.error('Failed to delete doctor:', error);
          // TODO: Add error handling
        }
      }
    },
    async handleSubmit() {
      try {
        const endpoint = this.editingDoctor ? `/api/doctors/${this.editingDoctor.id}` : '/api/doctors';
        const method = this.editingDoctor ? 'put' : 'post';
        
        const response = await axios[method](endpoint, {
          name: this.form.name,
          phone: this.form.phone,
          specialization: this.form.specialization,
          licenseNumber: this.form.licenseNumber || null
        });
        console.log('Server response:', response.data);
        
        this.closeModal();
        await this.loadDoctors();
        
        // Show success message
        alert(this.editingDoctor ? 'Doctor updated successfully!' : 'Doctor added successfully!');
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
</style>
