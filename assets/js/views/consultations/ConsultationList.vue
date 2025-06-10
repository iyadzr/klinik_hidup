<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Consultations</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="consultation in consultations" :key="consultation.id">
                    <td>{{ formatDate(consultation.createdAt) }}</td>
                    <td>{{ consultation.patientName || 'N/A' }}</td>
                    <td>{{ consultation.doctorName || 'N/A' }}</td>
                    <td>{{ consultation.diagnosis || 'N/A' }}</td>
                    <td>{{ consultation.treatment || 'N/A' }}</td>
                    <td>
                      <span :class="getStatusClass(consultation.status)">
                        {{ consultation.status || (consultation.isPaid === true ? 'Paid' : (consultation.isPaid === false ? 'Unpaid' : 'N/A')) }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="consultations.length === 0">
                    <td colspan="6" class="text-center">No consultations found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ConsultationList',
  data() {
    return {
      consultations: [],
      loading: false,
      error: null
    };
  },
  methods: {
    async loadConsultations() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/consultations');
        console.log('Consultations data:', response.data); // Debug log
        this.consultations = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        console.error('Error loading consultations:', error);
        this.error = 'Failed to load consultations';
        alert(this.error);
      } finally {
        this.loading = false;
      }
    },
    formatDate(date) {
      if (!date) return 'N/A';
      try {
        return new Date(date).toLocaleString();
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    getStatusClass(status) {
      const classes = {
        'pending': 'badge badge-warning',
        'completed': 'badge badge-success',
        'cancelled': 'badge badge-danger'
      };
      return classes[status?.toLowerCase()] || 'badge badge-secondary';
    }
  },
  created() {
    this.loadConsultations();
  }
};
</script>

<style scoped>
.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
}
.table th {
  background-color: #f8f9fa;
}
.loading {
  text-align: center;
  padding: 20px;
}
.error {
  color: #dc3545;
  text-align: center;
  padding: 20px;
}
</style>
