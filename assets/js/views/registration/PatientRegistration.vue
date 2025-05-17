<template>
  <div class="registration-form">
    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Patient Registration</h4>
      </div>
      <div class="card-body">
        <form @submit.prevent="registerPatient">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" v-model="patient.name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" v-model="patient.email" class="form-control" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="tel" v-model="patient.phone" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date of Birth</label>
              <input type="date" v-model="patient.dateOfBirth" class="form-control" required>
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

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Company</label>
              <input type="text" v-model="patient.company" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Company Address</label>
              <input type="text" v-model="patient.companyAddress" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Pre-informed Illness/Symptoms</label>
            <textarea v-model="patient.preInformedIllness" class="form-control" rows="3" required></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Assign Doctor</label>
              <select v-model="queueInfo.doctorId" class="form-select" required>
                <option value="">Select Doctor</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.name }}
                </option>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>
            Register & Queue Patient
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PatientRegistration',
  data() {
    return {
      patient: {
        name: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        companyAddress: '',
        preInformedIllness: ''
      },
      queueInfo: {
        doctorId: ''
      },
      doctors: []
    };
  },
  async created() {
    await this.loadDoctors();
  },
  methods: {
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data;
      } catch (error) {
        console.error('Error loading doctors:', error);
      }
    },
    async registerPatient() {
      try {
        // Register patient
        const patientResponse = await axios.post('/api/patients', this.patient);
        const patientId = patientResponse.data.id;

        // Add to queue
        await axios.post('/api/queue', {
          patientId: patientId,
          doctorId: this.queueInfo.doctorId
        });

        this.$router.push('/queue');
      } catch (error) {
        console.error('Error registering patient:', error);
        alert('Error registering patient. Please try again.');
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
