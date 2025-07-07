<template>
  <div class="card">
    <div class="card-body">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading patients...</p>
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
            <tr v-if="!loading && displayPatients.length === 0">
              <td colspan="7" class="text-center py-4">
                <i class="fas fa-search fa-2x text-muted mb-2"></i>
                <div v-if="searchQuery">
                  No patients found matching "{{ searchQuery }}"
                </div>
                <div v-else>
                  No patients found
                </div>
              </td>
            </tr>
            <tr v-for="(patient, index) in displayPatients" :key="patient.id">
              <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td>{{ patient.name || 'N/A' }}</td>
              <td>{{ formatDisplayNRIC(patient.nric) || 'N/A' }}</td>
              <td>{{ patient.phone || 'N/A' }}</td>
              <td>{{ formatGender(patient.gender) }}</td>
              <td>{{ formatDateOfBirth(patient.dateOfBirth) }}</td>
              <td>
                <div class="btn-group" role="group">
                  <button 
                    type="button"
                    class="btn btn-sm btn-secondary" 
                    @click="$emit('show-visit-history', patient)" 
                    title="View Visit History"
                  >
                    <i class="fas fa-history"></i>
                  </button>
                  <button 
                    type="button"
                    class="btn btn-sm btn-info" 
                    @click="$emit('edit-patient', patient)" 
                    title="Edit Patient"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    v-if="canDelete"
                    type="button"
                    class="btn btn-sm btn-danger" 
                    @click="$emit('delete-patient', patient)" 
                    title="Delete Patient"
                  >
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
    canDelete() {
      const user = AuthService.getCurrentUser();
      return user && user.role === 'SUPER_ADMIN';
    },
    displayPatients() {
      if (!Array.isArray(this.patients)) {
        return [];
      }
      return this.patients.filter(patient => {
        return patient && typeof patient === 'object' && patient.id;
      });
    }
  },
  methods: {
    formatDisplayNRIC(nric) {
      return formatNRIC(nric);
    },
    formatGender(gender) {
      if (gender === 'M') return 'Male';
      if (gender === 'F') return 'Female';
      return 'N/A';
    },
    formatDateOfBirth(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      const dateObj = new Date(dateOfBirth);
      if (isNaN(dateObj.getTime())) return 'N/A';
      return dateObj.toLocaleDateString('en-GB', {
        timeZone: 'Asia/Kuala_Lumpur',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    }
  }
};
</script>

<style scoped>
.btn-group .btn {
  margin-right: 2px;
}
.btn-group .btn:last-child {
  margin-right: 0;
}
</style> 