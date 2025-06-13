<template>
  <div class="payment-receipt" id="receiptContent">
    <div class="receipt-container">
      <!-- Clinic Header -->
      <div class="clinic-header text-center mb-4">
        <h2 class="clinic-name">KLINIK HIDUPsihat</h2>
        <div class="clinic-address">
          <p class="mb-1">No. 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor</p>
          <p class="mb-3">Tel : 03-8740 0678</p>
        </div>
      </div>

      <!-- Receipt Title and Number -->
      <div class="receipt-header d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="receipt-title mb-0">RESIT RASMI</h4>
        </div>
        <div class="text-end">
          <div class="receipt-number text-danger fw-bold">
            No. {{ receiptNumber }}
          </div>
          <div class="receipt-date">
            Tarikh : {{ formatReceiptDate(consultation.paidAt || new Date()) }}
          </div>
        </div>
      </div>

      <!-- Patient Information -->
      <div class="patient-info mb-4">
        <div class="info-row mb-2">
          <span class="label">Terima daripada :</span>
          <span class="value fw-bold">{{ consultation.patientName }}</span>
        </div>
        <div class="info-row mb-2">
          <span class="label">Untuk Bayaran :</span>
          <span class="value">Pemeriksaan kesihatan / rawatan</span>
        </div>
      </div>

      <!-- Amount Box -->
      <div class="amount-section mb-4">
        <div class="amount-box d-flex justify-content-between align-items-center">
          <div class="amount-label">RM</div>
          <div class="amount-value">{{ formatAmount(consultation.totalAmount) }}</div>
        </div>
      </div>

      <!-- Doctor Information -->
      <div class="doctor-info text-end">
        <div class="doctor-signature-area">
          <div class="signature-space mb-2" style="height: 60px; border-bottom: 1px solid #ccc;"></div>
          <div class="signature-label">
            <p class="mb-0"><small>Tandatangan/Cop</small></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaymentReceipt',
  props: {
    consultation: {
      type: Object,
      required: true
    },
    receiptNumber: {
      type: String,
      required: true
    }
  },
  methods: {
    formatReceiptDate(date) {
      if (!date) return '';
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('en-MY', {
        timeZone: 'Asia/Kuala_Lumpur',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    formatAmount(amount) {
      if (!amount) return '0.00';
      return parseFloat(amount).toFixed(2);
    },
    printReceipt() {
      // Create a clean print window without browser UI elements
      const printContent = document.getElementById('receiptContent').innerHTML;
      
      const printWindow = window.open('', '_blank', 'width=800,height=600');
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Receipt</title>
          <style>
            @page {
              margin: 0;
              size: A4;
            }
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }
            body {
              font-family: Arial, sans-serif;
              font-size: 14px;
              line-height: 1.4;
              color: #000;
              background: white;
              padding: 20px;
            }
            .payment-receipt {
              max-width: 600px;
              margin: 0 auto;
            }
            .receipt-container {
              border: 2px solid #000;
              padding: 30px;
              background: white;
            }
            .clinic-header {
              text-align: center;
              margin-bottom: 30px;
            }
            .clinic-name {
              font-size: 24px;
              font-weight: bold;
              margin-bottom: 10px;
            }
            .clinic-address p {
              margin: 2px 0;
              font-size: 14px;
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
            }
            .receipt-number {
              color: #dc3545;
              font-weight: bold;
              font-size: 16px;
            }
            .receipt-date {
              font-size: 14px;
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
            }
            .value {
              flex: 1;
              padding-left: 10px;
              border-bottom: 1px dotted #000;
              min-height: 25px;
              font-weight: bold;
            }
            .amount-section {
              margin-bottom: 30px;
            }
            .amount-box {
              border: 2px solid #000;
              padding: 15px 20px;
              text-align: left;
              margin-bottom: 10px;
              display: flex;
              justify-content: space-between;
              align-items: center;
              min-width: 200px;
            }
            .amount-label {
              font-weight: bold;
              margin-bottom: 0;
              color: #000;
              font-size: 16px;
            }
            .amount-value {
              font-size: 18px;
              font-weight: bold;
              color: #000;
            }
            .doctor-info {
              text-align: right;
            }
            .signature-space {
              height: 60px;
              border-bottom: 1px solid #000;
              margin-bottom: 10px;
              width: 200px;
              margin-left: auto;
            }
            .signature-label {
              margin-left: 10px;
              font-size: 12px;
            }
            .d-flex {
              display: flex;
            }
            .justify-content-between {
              justify-content: space-between;
            }
            .align-items-center {
              align-items: center;
            }
            .text-center {
              text-align: center;
            }
            .text-end {
              text-align: right;
            }
            .mb-1 { margin-bottom: 5px; }
            .mb-2 { margin-bottom: 10px; }
            .mb-3 { margin-bottom: 15px; }
            .mb-4 { margin-bottom: 20px; }
            .fw-bold { font-weight: bold; }
            .text-danger { color: #dc3545; }
          </style>
        </head>
        <body onload="window.print(); window.close();">
          ${printContent}
        </body>
        </html>
      `);
      
      printWindow.document.close();
    }
  }
};
</script>

<style scoped>
.payment-receipt {
  max-width: 700px;
  margin: 0 auto;
}

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

.clinic-address p {
  margin: 2px 0;
  font-size: 14px;
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

.receipt-date {
  font-size: 14px;
  color: #000;
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
  margin-bottom: 30px;
}

.amount-box {
  border: 2px solid #000;
  padding: 15px 20px;
  text-align: left;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: 200px;
}

.amount-label {
  font-weight: bold;
  margin-bottom: 0;
  color: #000;
  font-size: 16px;
}

.amount-value {
  font-size: 18px;
  font-weight: bold;
  color: #000;
}

.doctor-info {
  text-align: right;
}

.signature-space {
  height: 60px;
  border-bottom: 1px solid #000;
  margin-bottom: 10px;
  width: 200px;
  margin-left: auto;
}

.signature-label {
  margin-left: 10px;
}

/* Print styles */
@media print {
  .receipt-container {
    border: 2px solid #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  .clinic-name,
  .receipt-title,
  .receipt-number,
  .label,
  .value,
  .amount-label,
  .amount-value,
  .signature-label {
    color: #000 !important;
  }
}
</style> 