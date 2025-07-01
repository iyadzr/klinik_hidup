<template>
  <div class="card section-card dashboard-card mb-4">
    <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">
      <h5 class="mb-0 d-flex align-items-center">
        <i class="fas fa-history text-secondary me-2"></i>
        Medical History
        <span v-if="visitHistories?.length" class="badge bg-secondary ms-2">{{ visitHistories.length }} visits</span>
      </h5>
    </div>
    <div class="card-body">
      <div v-if="visitHistories && visitHistories.length > 0" class="history-list">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Doctor</th>
              <th>Diagnosis</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="visit in visitHistories" 
              :key="visit.id" 
              class="history-item"
              @click="showVisitDetails(visit)"
              style="cursor: pointer;"
            >
              <td>{{ formatDate(visit.consultationDate) }}</td>
              <td>Dr. {{ visit.doctor?.name || 'Unknown' }}</td>
              <td>{{ visit.diagnosis || 'No diagnosis recorded' }}</td>
              <td>
                <span :class="getStatusClass(visit.status)">
                  {{ visit.status || 'Completed' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center text-muted py-4">
        <i class="fas fa-file-medical-alt fa-2x mb-2"></i>
        <div>No medical history found</div>
        <small class="text-muted">This is the patient's first visit</small>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MedicalHistorySection',
  props: {
    visitHistories: {
      type: Array,
      default: () => []
    }
  },
  emits: ['show-visit-details'],
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        const dateObj = new Date(dateString);
        return dateObj.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    getStatusClass(status) {
      const statusMap = {
        'Completed': 'badge bg-success',
        'In Progress': 'badge bg-warning',
        'Cancelled': 'badge bg-danger',
        'Pending': 'badge bg-info'
      };
      return statusMap[status] || 'badge bg-secondary';
    },
    showVisitDetails(visit) {
      this.$emit('show-visit-details', visit);
    }
  }
};
</script>

<style scoped>
.section-card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  background: #fff;
}

.history-item:hover {
  background-color: #f8f9fa;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.badge {
  font-size: 0.75rem;
}
</style> 