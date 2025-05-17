<template>
  <div class="consultation-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Consultations</h2>
      <router-link to="/consultations/new" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>
        New Consultation
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>MC Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="consultation in consultations" :key="consultation.id">
                <td>{{ formatDate(consultation.consultationDate) }}</td>
                <td>{{ consultation.patient.name }}</td>
                <td>{{ consultation.doctor.name }}</td>
                <td>{{ truncateText(consultation.diagnosis, 50) }}</td>
                <td>{{ truncateText(consultation.treatment, 50) }}</td>
                <td>
                  <span v-if="consultation.hasMedicalCertificate" class="badge bg-success">
                    <i class="fas fa-file-medical me-1"></i> Issued
                  </span>
                  <span v-else class="badge bg-secondary">None</span>
                </td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="viewConsultation(consultation)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button v-if="!consultation.hasMedicalCertificate" 
                          class="btn btn-sm btn-success" 
                          @click="generateMC(consultation)">
                    <i class="fas fa-file-medical"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="consultations.length === 0">
                <td colspan="7" class="text-center py-4">
                  No consultations found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- View Consultation Modal -->
    <div class="modal fade" id="viewConsultationModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Consultation Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" v-if="selectedConsultation">
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Patient:</strong> {{ selectedConsultation.patient.name }}
                </div>
                <div class="col-md-6">
                  <strong>Doctor:</strong> {{ selectedConsultation.doctor.name }}
                </div>
              </div>
              <div class="mb-3">
                <strong>Date:</strong> {{ formatDate(selectedConsultation.consultationDate) }}
              </div>
              <div class="mb-3">
                <strong>Symptoms:</strong>
                <p>{{ selectedConsultation.symptoms }}</p>
              </div>
              <div class="mb-3">
                <strong>Diagnosis:</strong>
                <p>{{ selectedConsultation.diagnosis }}</p>
              </div>
              <div class="mb-3">
                <strong>Treatment:</strong>
                <p>{{ selectedConsultation.treatment }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';

export default {
  name: 'ConsultationList',
  data() {
    return {
      consultations: [],
      selectedConsultation: null,
      viewModal: null
    };
  },
  async created() {
    await this.loadConsultations();
  },
  mounted() {
    this.viewModal = new Modal(document.getElementById('viewConsultationModal'));
  },
  methods: {
    async loadConsultations() {
      try {
        const response = await axios.get('/api/consultations');
        this.consultations = response.data;
      } catch (error) {
        console.error('Error loading consultations:', error);
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleString();
    },
    truncateText(text, length) {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    },
    viewConsultation(consultation) {
      this.selectedConsultation = consultation;
      this.viewModal.show();
    },
    async generateMC(consultation) {
      try {
        await this.$router.push({
          name: 'NewConsultation',
          query: { 
            consultationId: consultation.id,
            generateMC: true 
          }
        });
      } catch (error) {
        console.error('Error navigating to MC generation:', error);
      }
    }
  }
};
</script>

<style scoped>
.consultation-list {
  padding: 20px;
}

.badge {
  font-size: 0.8rem;
}

.table th {
  background-color: #f8f9fa;
}
</style>
