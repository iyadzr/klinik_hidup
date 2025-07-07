<template>
  <div id="receiptPrintContent" v-if="visit">
    <div class="receipt-container">
      <!-- Clinic Header -->
      <div class="clinic-header">
        <h2 class="clinic-name">KLINIK HIDUPsihat</h2>
        <div class="clinic-address">
          <p class="mb-1">No. 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor</p>
          <p class="mb-3">Tel : 03-8740 0678</p>
        </div>
      </div>

      <!-- Receipt Header -->
      <div class="receipt-header">
        <div>
          <h4 class="receipt-title mb-0">RESIT RASMI</h4>
        </div>
        <div class="text-end">
          <div class="receipt-number text-danger fw-bold">
            No. {{ visit.receiptNumber }}
          </div>
          <div class="receipt-date">
            Tarikh : {{ formatReceiptDate(visit.paidAt || visit.consultationDate) }}
          </div>
        </div>
      </div>

      <!-- Patient Information -->
      <div class="patient-info">
        <div class="info-row">
          <span class="label">Terima daripada :</span>
          <span class="value">{{ patient?.name || 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="label">Untuk Bayaran :</span>
          <span class="value">Pemeriksaan kesihatan / rawatan</span>
        </div>
      </div>

      <!-- Amount and Signature Section -->
      <div class="amount-section">
        <div class="amount-box">
          <div class="amount-label">RM</div>
          <div class="amount-value">{{ formatAmount(visit.totalAmount) }}</div>
        </div>
        <div class="signature-area">
          <div class="signature-space"></div>
          <div class="signature-label">
            <p class="mb-0"><small>Tandatangan/Cop</small></p>
            <p class="mb-0"><small>{{ visit.doctor?.name || 'Doctor' }}</small></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import timezoneUtils from '../../utils/timezoneUtils.js';

export default {
  name: 'ReceiptContent',
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
    formatReceiptDate(date) {
      if (!date) return '';
      try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-GB', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } catch (error) {
        console.error('Error formatting receipt date:', error);
        return 'Invalid Date';
      }
    },
    formatAmount(amount) {
      if (!amount) return '0.00';
      return parseFloat(amount).toFixed(2);
    }
  }
};
</script>

<style scoped>
.receipt-container {
  border: 2px solid #000;
  padding: 30px;
  background: white;
  font-family: Arial, sans-serif;
}

.clinic-header {
  text-align: center;
  margin-bottom: 30px;
}

.clinic-name {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #000;
}

.receipt-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.receipt-title {
  font-size: 20px;
  font-weight: bold;
  color: #000;
}

.receipt-number {
  color: #dc3545;
  font-weight: bold;
  font-size: 16px;
}

.patient-info {
  margin-bottom: 30px;
}

.info-row {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
}

.label {
  min-width: 140px;
  display: inline-block;
  color: #000;
}

.value {
  flex: 1;
  padding-left: 10px;
  border-bottom: 1px dotted #000;
  min-height: 25px;
  color: #000;
  font-weight: bold;
}

.amount-section {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 30px;
}

.amount-box {
  border: 2px solid #000;
  padding: 15px 20px;
  text-align: left;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: 200px;
  width: 200px;
}

.amount-label {
  font-weight: bold;
  margin-bottom: 0;
  color: #000;
  font-size: 18px;
}

.amount-value {
  font-weight: bold;
  color: #000;
  font-size: 18px;
}

.signature-area {
  flex: 0 0 auto;
  text-align: center;
}

.signature-space {
  height: 60px;
  border-bottom: 1px solid #ccc;
  margin-bottom: 10px;
}

@media print {
  .receipt-container {
    border: 2px solid #000 !important;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
  }
}
</style> 