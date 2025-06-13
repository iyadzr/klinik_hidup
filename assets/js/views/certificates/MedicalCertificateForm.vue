<template>
  <div class="medical-certificate-form">
    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Generate Medical Certificate</h4>
      </div>
      <div class="card-body">
        <form @submit.prevent="generateCertificate">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Patient</label>
              <select v-model="certificate.patientId" class="form-select" required>
                <option value="">Select Patient</option>
                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                  {{ patient.name }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Doctor</label>
              <select v-model="certificate.doctorId" class="form-select" required>
                <option value="">Select Doctor</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" v-model="certificate.startDate" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" v-model="certificate.endDate" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Diagnosis</label>
            <input type="text" v-model="certificate.diagnosis" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Remarks</label>
            <textarea v-model="certificate.remarks" class="form-control" rows="3"></textarea>
          </div>

          <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Generate Certificate</button>
            <button type="button" class="btn btn-secondary" @click="previewCertificate" v-if="certificateId">
              Preview Certificate
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Certificate Preview Modal -->
    <div class="modal fade" id="certificatePreview" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Medical Certificate</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="previewData" class="certificate-preview">
              <div class="text-center mb-4">
                <h2 style="margin-bottom: 0; font-weight: bold;">KLINIK HIDUPsihat</h2>
                <p style="margin-bottom: 5px;">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
                <p style="margin-bottom: 5px;">Tel: 03-8740 0678</p>
              </div>
              <h4 style="text-align: center; margin-bottom: 20px;">SURAT AKUAN SAKIT (MC)</h4>
              <div class="mb-4">
                <p>This is to certify that</p>
                <h4 class="mb-3">{{ previewData.patient.name }}</h4>
                <p>has been examined and is advised to rest from</p>
                <p class="mb-3">
                  <strong>{{ formatDate(previewData.startDate) }}</strong> to 
                  <strong>{{ formatDate(previewData.endDate) }}</strong>
                </p>
                <p class="mb-3">
                  <strong>Diagnosis:</strong> {{ previewData.diagnosis }}
                </p>
                <p v-if="previewData.remarks" class="mb-4">
                  <strong>Remarks:</strong> {{ previewData.remarks }}
                </p>
              </div>
              <div class="text-end mt-5">
                <p class="mb-0">{{ previewData.doctor.name }}</p>
                <p>Attending Physician</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="printCertificate">Print</button>
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
  name: 'MedicalCertificateForm',
  data() {
    return {
      patients: [],
      doctors: [],
      certificate: {
        patientId: '',
        doctorId: '',
        startDate: '',
        endDate: '',
        diagnosis: '',
        remarks: ''
      },
      certificateId: null,
      previewData: null,
      previewModal: null
    };
  },
  async created() {
    await this.loadData();
    this.previewModal = new Modal(document.getElementById('certificatePreview'));
  },
  methods: {
    async loadData() {
      try {
        const [patientsResponse, doctorsResponse] = await Promise.all([
          axios.get('/api/patients'),
          axios.get('/api/doctors')
        ]);
        this.patients = patientsResponse.data;
        this.doctors = doctorsResponse.data;
      } catch (error) {
        console.error('Error loading data:', error);
      }
    },
    async generateCertificate() {
      try {
        const response = await axios.post('/api/medical-certificates', this.certificate);
        this.certificateId = response.data.id;
        await this.previewCertificate();
      } catch (error) {
        console.error('Error generating certificate:', error);
        alert('Error generating medical certificate. Please try again.');
      }
    },
    async previewCertificate() {
      if (!this.certificateId) return;

      try {
        const response = await axios.get(`/api/medical-certificates/${this.certificateId}`);
        this.previewData = response.data;
        this.previewModal.show();
      } catch (error) {
        console.error('Error loading certificate preview:', error);
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    printCertificate() {
      window.print();
    }
  }
};
</script>

<style scoped>
.medical-certificate-form {
  padding: 20px;
}

.certificate-preview {
  padding: 20px;
  font-family: 'Times New Roman', Times, serif;
}

@media print {
  .modal-footer,
  .btn-close {
    display: none !important;
  }
  
  .certificate-preview {
    padding: 0;
  }

  .modal {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  .modal-dialog {
    max-width: 100% !important;
    margin: 0 !important;
  }

  .modal-content {
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
