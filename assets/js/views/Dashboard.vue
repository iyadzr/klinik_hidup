<template>
  <div class="container">
    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2"><i class="fas fa-user-injured"></i> Patients</h5>
            <p class="card-text">
              <span v-if="loading.patients">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                Loading...
              </span>
              <span v-else>
                Total Patients: {{ patientCount }}
              </span>
            </p>
            <router-link to="/patients" class="btn btn-primary">View Patients</router-link>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2"><i class="fas fa-user-md"></i> Doctors</h5>
            <p class="card-text">
              <span v-if="loading.doctors">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                Loading...
              </span>
              <span v-else>
                Total Doctors: {{ doctorCount }}
              </span>
            </p>
            <router-link to="/doctors" class="btn btn-primary">View Doctors</router-link>
          </div>
        </div>
      </div>
      <!--
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Appointments</h5>
            <p class="card-text">
              <span v-if="loading.appointments">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                Loading...
              </span>
              <span v-else>
                Today's Appointments: {{ todayAppointments }}
              </span>
            </p>
            <router-link to="/appointments" class="btn btn-primary">View Appointments</router-link>
          </div>
        </div>
      </div>
      -->
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { emitter } from '../App.vue';

export default {
  name: 'Dashboard',
  data() {
    return {
      patientCount: 0,
      doctorCount: 0,
      todayAppointments: 0,
      loading: {
        patients: false,
        doctors: false,
        appointments: false
      }
    };
  },
  async created() {
    await this.fetchData();
  },
  beforeUnmount() {
    this.stopAutoRefresh();
  },
  methods: {
    async fetchPatientCount() {
      this.loading.patients = true;
      try {
        const response = await axios.get('/api/patients/count');
        this.patientCount = response.data.count;
      } catch (error) {
        console.error('Failed to load patient count:', error);
      } finally {
        this.loading.patients = false;
      }
    },
    async fetchDoctorCount() {
      this.loading.doctors = true;
      try {
        const response = await axios.get('/api/doctors/count');
        this.doctorCount = response.data.count;
      } catch (error) {
        console.error('Failed to load doctor count:', error);
      } finally {
        this.loading.doctors = false;
      }
    },
    async fetchAppointmentCount() {
      this.loading.appointments = true;
      try {
        const response = await axios.get('/api/appointments/today');
        this.todayAppointments = response.data.count;
      } catch (error) {
        console.error('Failed to fetch today appointments count:', error);
      } finally {
        this.loading.appointments = false;
      }
    },
    async fetchData() {
      await Promise.all([
        this.fetchPatientCount(),
        this.fetchDoctorCount(),
        this.fetchAppointmentCount()
      ]);
    }
  }
};
</script>

<style scoped>
.card {
  margin-bottom: 1rem;
}
</style>
