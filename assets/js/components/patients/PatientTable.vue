<template>
  <div class="card">
    <div class="card-body">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading patients...</p>
        <div class="progress mt-2 mx-auto" style="width: 200px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" 
               role="progressbar" 
               :style="{width: loadingProgress + '%'}"
               :aria-valuenow="loadingProgress" 
               aria-valuemin="0" 
               aria-valuemax="100">
          </div>
        </div>
        <small class="text-muted d-block mt-2">Optimizing database queries...</small>
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
              <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td>{{ patient.name }}</td>
              <td>{{ formatDisplayNRIC(patient.nric) || 'N/A' }}</td>
              <td>{{ patient.phone }}</td>
              <td>{{ patient.gender === 'M' ? 'Male' : patient.gender === 'F' ? 'Female' : 'N/A' }}</td>
              <td>{{ formatDateOfBirth(patient.dateOfBirth) }}</td>
              <td>
                <button class="btn btn-sm btn-secondary me-1" @click="$emit('show-visit-history', patient)" title="View Visit History">
                  <i class="fas fa-history"></i>
                </button>
                <button class="btn btn-sm btn-info me-1" @click="$emit('edit-patient', patient)" title="Edit Patient">
                  <i class="fas fa-edit"></i>
                </button>
                <button 
                  v-if="isSuperAdmin" 
                  class="btn btn-sm btn-danger" 
                  @click="$emit('delete-patient', patient)" 
                  title="Delete Patient"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="patients.length === 0">
              <td colspan="7" class="text-center">
                <div class="py-4">
                  <i class="fas fa-search fa-2x text-muted mb-2"></i>
                  <div v-if="searchQuery">
                    No patients found matching "{{ searchQuery }}"
                    <br>
                    <small class="text-muted">Try a different search term or clear the search to see all patients</small>
                  </div>
                  <div v-else>
                    No patients found
                    <br>
                    <small class="text-muted">Start by adding a new patient</small>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { formatNRIC } from '../../utils/nricFormatter.js';
import AuthService from '../../services/AuthService';

export default {
  name: 'PatientTable',
  props: {
    patients: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    loadingProgress: {
      type: Number,
      default: 0
    },
    currentPage: {
      type: Number,
      default: 1
    },
    perPage: {
      type: Number,
      default: 25
    },
    searchQuery: {
      type: String,
      default: ''
    }
  },
  emits: ['show-visit-history', 'edit-patient', 'delete-patient'],
  computed: {
    isSuperAdmin() {
      return AuthService.getUser()?.role === 'SUPER_ADMIN';
    }
  },
  methods: {
    formatDisplayNRIC(nric) {
      return formatNRIC(nric);
    },
    formatDateOfBirth(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const dateObj = new Date(dateOfBirth);
        return dateObj.toLocaleDateString('en-GB', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    }
  }
};
</script>

<style scoped>
.progress {
  height: 8px;
}
</style> 