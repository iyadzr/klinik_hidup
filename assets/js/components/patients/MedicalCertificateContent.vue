<template>
  <div id="mcPrintContent" v-if="visit">
    <div class="mc-container">
      <!-- Clinic Header -->
      <div class="clinic-header">
        <h3 class="clinic-name">KLINIK HIDUPsihat</h3>
        <div class="clinic-address">
          <p class="mb-1">No. 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor</p>
          <p class="mb-3">Tel : 03-8740 0678</p>
        </div>
      </div>

      <!-- MC Number -->
      <div class="mc-number" v-if="visit.mcRunningNumber">
        No. {{ visit.mcRunningNumber }}
      </div>

      <!-- MC Title -->
      <div class="mc-title">
        <h4 class="fw-bold">SIJIL CUTI SAKIT</h4>
        <h5>(MEDICAL CERTIFICATE)</h5>
      </div>

      <!-- Patient Information -->
      <div class="patient-info mb-4">
        <div class="info-row">
          <span class="label">Saya dengan ini mengesahkan bahawa :</span>
        </div>
        <div class="info-row">
          <span class="label">I hereby certify that :</span>
        </div>
        <br>
        <div class="info-row">
          <span class="label">Nama / Name :</span>
          <span class="value">{{ patient?.name || 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="label">No. K/P / I.C. No. :</span>
          <span class="value">{{ formatNRIC(patient?.nric) || 'N/A' }}</span>
        </div>
      </div>

      <!-- Medical Information -->
      <div class="medical-info mb-4">
        <div class="info-row">
          <span class="label">Mengalami sakit dan tidak dapat menjalankan tugas dari :</span>
        </div>
        <div class="info-row">
          <span class="label">Is/was suffering from illness and unfit for duty from :</span>
        </div>
        <br>
        <div class="info-row">
          <span class="label">Tarikh / Date :</span>
          <span class="value">{{ formatDate(visit.mcStartDate) }}</span>
          <span class="label ms-3">Hingga / To :</span>
          <span class="value">{{ formatDate(visit.mcEndDate) }}</span>
        </div>
        <br>
        <div class="info-row">
          <span class="label">Tempoh / Period :</span>
          <span class="value">{{ calculateMCDays(visit.mcStartDate, visit.mcEndDate) }} hari / day(s)</span>
        </div>
      </div>

      <!-- MC Notes -->
      <div class="mc-notes mb-4" v-if="visit.mcNotes">
        <div class="info-row">
          <span class="label">Catatan / Remarks :</span>
        </div>
        <div class="notes-content">
          {{ visit.mcNotes }}
        </div>
      </div>

      <!-- Date and Signature -->
      <div class="signature-section">
        <div class="row">
          <div class="col-6">
            <div class="info-row">
              <span class="label">Tarikh / Date :</span>
              <span class="value">{{ formatDate(visit.consultationDate || new Date()) }}</span>
            </div>
          </div>
          <div class="col-6 text-end">
            <div class="signature-line"></div>
            <div class="signature-label">
              <p class="mb-0"><small>Tandatangan Doktor</small></p>
              <p class="mb-0"><small>Doctor's Signature</small></p>
              <p class="mb-0"><small>{{ visit.doctor?.name || 'Doctor' }}</small></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import timezoneUtils from '../../utils/timezoneUtils.js';
import { formatNRIC } from '../../utils/nricFormatter.js';

export default {
  name: 'MedicalCertificateContent',
  props: {
    visit: {
      type: Object,
      required: true
    },
    patient: {
      type: Object,
      default: null
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return '';
      return timezoneUtils.formatDateMalaysia(new Date(date));
    },
    formatNRIC(nric) {
      return formatNRIC(nric);
    },
    calculateMCDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      
      const start = new Date(startDate);
      const end = new Date(endDate);
      const timeDiff = end.getTime() - start.getTime();
      const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Include both start and end days
      
      return Math.max(1, dayDiff);
    }
  }
};
</script>

<style scoped>
.mc-container {
  background-color: #e8e5b0;
  padding: 20px;
  border: 1px solid #000;
  font-family: Arial, sans-serif;
}

.clinic-header {
  text-align: center;
  margin-bottom: 20px;
}

.clinic-name {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #000;
}

.mc-number {
  text-align: right;
  margin-bottom: 15px;
  font-size: 0.9rem;
  color: #a52a2a;
}

.mc-title {
  text-align: center;
  margin-bottom: 20px;
}

.mc-title h4,
.mc-title h5 {
  color: #000;
  margin-bottom: 5px;
}

.patient-info,
.medical-info,
.mc-notes {
  margin-bottom: 20px;
}

.info-row {
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.label {
  color: #000;
  font-size: 0.9rem;
}

.value {
  padding-left: 10px;
  border-bottom: 1px dotted #000;
  min-height: 20px;
  color: #000;
  font-weight: bold;
  flex: 1;
  margin-left: 5px;
  min-width: 100px;
}

.notes-content {
  border: 1px dotted #000;
  padding: 10px;
  margin-top: 8px;
  min-height: 40px;
  background-color: rgba(255, 255, 255, 0.3);
}

.signature-section {
  margin-top: 30px;
}

.signature-line {
  border-bottom: 1px dotted #000;
  width: 150px;
  margin-bottom: 10px;
  margin-left: auto;
}

.signature-label {
  text-align: center;
  font-size: 0.8rem;
}

@media print {
  .mc-container {
    background-color: #e8e5b0 !important;
    border: 1px solid #000 !important;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
  }
}
</style> 