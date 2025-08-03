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
              <label class="form-label">Start Date (DD/MM/YYYY)</label>
              <input 
                type="text" 
                v-model="certificate.startDate" 
                class="form-control" 
                placeholder="DD/MM/YYYY"
                maxlength="10"
                required
                @input="formatStartDate"
              >
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date (DD/MM/YYYY)</label>
              <input 
                type="text" 
                v-model="certificate.endDate" 
                class="form-control" 
                placeholder="DD/MM/YYYY"
                maxlength="10"
                required
                @input="formatEndDate"
              >
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
import { formatDateForAPI, formatDateOnlyMalaysia } from '../../utils/timezoneUtils.js';

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
        // Convert dates to API format before sending
        const certificateData = {
          ...this.certificate,
          startDate: this.certificate.startDate ? formatDateForAPI(this.certificate.startDate) : '',
          endDate: this.certificate.endDate ? formatDateForAPI(this.certificate.endDate) : ''
        };
        const response = await axios.post('/api/medical-certificates', certificateData);
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
    formatStartDate(event) {
      const inputValue = event.target.value;
      const formattedDate = this.parseAndFormatDate(inputValue);
      if (formattedDate) {
        this.certificate.startDate = formatDateOnlyMalaysia(formattedDate);
      }
    },
    formatEndDate(event) {
      const inputValue = event.target.value;
      const formattedDate = this.parseAndFormatDate(inputValue);
      if (formattedDate) {
        this.certificate.endDate = formatDateOnlyMalaysia(formattedDate);
      }
    },
    parseAndFormatDate(inputValue) {
      // Remove any non-digit characters
      const cleanValue = inputValue.replace(/\D/g, '');
      
      // Check if we have exactly 8 digits (DDMMYYYY)
      if (cleanValue.length === 8) {
        const day = cleanValue.substring(0, 2);
        const month = cleanValue.substring(2, 4);
        const year = cleanValue.substring(4, 8);
        
        // Validate the date
        const date = new Date(year, month - 1, day);
        if (date.getFullYear() == year && 
            date.getMonth() == month - 1 && 
            date.getDate() == day) {
          return date;
        }
      }
      
      // Try to parse DD/MM/YYYY format
      const parts = inputValue.split('/');
      if (parts.length === 3) {
        const day = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const year = parseInt(parts[2]);
        
        if (!isNaN(day) && !isNaN(month) && !isNaN(year)) {
          const date = new Date(year, month - 1, day);
          if (date.getFullYear() == year && 
              date.getMonth() == month - 1 && 
              date.getDate() == day) {
            return date;
          }
        }
      }
      
      return null;
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-GB', {
        timeZone: 'Asia/Kuala_Lumpur',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
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
