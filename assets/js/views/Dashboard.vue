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
    </div>

    <!-- Admin Section - Only show to Super Admin users -->
    <div v-if="isSuperAdmin" class="row mt-4">
      <div class="col-12">
        <h3 class="mb-3">
          <i class="fas fa-tools text-warning me-2"></i>
          Administration
        </h3>
      </div>
      <div class="col-md-4">
        <div class="card border-warning">
          <div class="card-body">
            <h5 class="card-title text-warning d-flex align-items-center gap-2">
              <i class="fas fa-pills"></i> 
              Medication Management
            </h5>
            <p class="card-text">
              Manage medications, unit types, categories, and dosages in the system.
            </p>
            <router-link to="/admin/medications" class="btn btn-warning">
              <i class="fas fa-pills me-2"></i>Manage Medications
            </router-link>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-danger">
          <div class="card-body">
            <h5 class="card-title text-danger d-flex align-items-center gap-2">
              <i class="fas fa-users"></i> 
              User Management
            </h5>
            <p class="card-text">
              Manage system users, roles, permissions, and access control.
            </p>
            <router-link to="/admin/users" class="btn btn-danger">
              <i class="fas fa-users me-2"></i>Manage Users
            </router-link>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-info">
          <div class="card-body">
            <h5 class="card-title text-info d-flex align-items-center gap-2">
              <i class="fas fa-cog"></i> 
              System Settings
            </h5>
            <p class="card-text">
              Configure clinic settings, backup data, and system preferences.
            </p>
            <router-link to="/admin/settings" class="btn btn-info">
              <i class="fas fa-cog me-2"></i>Manage Settings
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import AuthService from '../services/AuthService';
import { emitter } from '../App.vue';

export default {
  name: 'Dashboard',
  data() {
    return {
      patientCount: 0,
      doctorCount: 0,
      loading: {
        patients: false,
        doctors: false
      }
    };
  },
  computed: {
    isSuperAdmin() {
      return AuthService.isSuperAdmin();
    }
  },
  async created() {
    await this.fetchData();
  },
  beforeUnmount() {
    this.stopAutoRefresh();
  },
  methods: {
    stopAutoRefresh() {
      // Dummy method to prevent errors. Implement auto-refresh cleanup if needed.
    },
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
    async fetchData() {
      await Promise.all([
        this.fetchPatientCount(),
        this.fetchDoctorCount()
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
