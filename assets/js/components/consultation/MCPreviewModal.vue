<template>
  <div class="modal fade" id="mcPreviewModal" tabindex="-1" aria-labelledby="mcPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="mcPreviewModalLabel">
            <i class="fas fa-certificate me-2"></i>
            Medical Certificate Preview
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- MC Preview Content -->
          <div class="mc-preview-container" v-if="patient && mcData">
            <!-- Header -->
            <div class="text-center mb-4">
              <h3 class="fw-bold">MEDICAL CERTIFICATE</h3>
              <p class="text-muted">Klinik HiDUP sihat</p>
              <hr>
            </div>

            <!-- MC Number and Date -->
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>MC No:</strong> {{ mcData.mcRunningNumber || 'Auto-generated' }}
              </div>
              <div class="col-md-6 text-end">
                <strong>Date:</strong> {{ formatDate(new Date()) }}
              </div>
            </div>

            <!-- Patient Information -->
            <div class="mb-4">
              <h5 class="mb-3">Patient Information</h5>
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Name:</strong> {{ patient.name || patient.displayName }}</p>
                  <p><strong>IC/Passport:</strong> {{ patient.nric || patient.ic || 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                  <p><strong>Age:</strong> {{ calculateAge(patient.dateOfBirth) }} years</p>
                  <p><strong>Gender:</strong> {{ patient.gender }}</p>
                </div>
              </div>
            </div>

            <!-- Medical Certificate Content -->
            <div class="mb-4">
              <h5 class="mb-3">Medical Certificate</h5>
              <div class="mc-content p-3 border rounded bg-light">
                <p class="mb-3">
                  This is to certify that <strong>{{ patient.name || patient.displayName }}</strong> 
                  (IC/Passport: <strong>{{ patient.nric || patient.ic || 'N/A' }}</strong>) 
                  was examined by me on <strong>{{ formatDate(new Date()) }}</strong> 
                  and is suffering from illness/injury that requires medical leave.
                </p>
                
                <div class="row mb-3">
                  <div class="col-md-6">
                    <p><strong>Period of Medical Leave:</strong></p>
                    <p>From: <strong>{{ formatDate(mcData.mcStartDate) }}</strong></p>
                    <p>To: <strong>{{ formatDate(mcData.mcEndDate) }}</strong></p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Total Days:</strong> {{ calculateDaysDifference(mcData.mcStartDate, mcData.mcEndDate) }} day(s)</p>
                  </div>
                </div>



                <p class="mb-0">
                  The patient is advised to rest and refrain from work/study during the above period.
                </p>
              </div>
            </div>

            <!-- Doctor Information -->
            <div class="row">
              <div class="col-md-6">
                <!-- Empty space for patient copy -->
              </div>
              <div class="col-md-6 text-center">
                <div class="doctor-signature mt-4">
                  <div class="signature-line mb-2" style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto;"></div>
                  <p class="mb-1"><strong>Dr. {{ mcData.doctorName || 'Doctor Name' }}</strong></p>
                  <p class="mb-1">Medical Practitioner</p>
                  <p class="mb-0">MMC Reg. No: {{ mcData.doctorRegNo || 'MMC12345' }}</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-4 pt-3 border-top">
              <small class="text-muted">
                This medical certificate is issued based on medical examination and clinical assessment.
              </small>
            </div>
          </div>
          
          <div v-else class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <h5>Cannot Preview Medical Certificate</h5>
            <p class="text-muted">Patient information or MC data is missing.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Close
          </button>
          <button type="button" class="btn btn-primary" @click="printMC" v-if="patient && mcData">
            <i class="fas fa-print me-2"></i>Print Certificate
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { MALAYSIA_TIMEZONE } from '../../utils/timezoneUtils.js';

export default {
  name: 'MCPreviewModal',
  props: {
    patient: {
      type: Object,
      default: null
    },
    mcData: {
      type: Object,
      default: null
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        const dateObj = new Date(dateString);
        return dateObj.toLocaleDateString('en-MY', {
          timeZone: MALAYSIA_TIMEZONE,
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        if (isNaN(birthDate.getTime())) {
          return 'N/A';
        }
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        return age > 0 ? age : 'N/A';
      } catch (error) {
        console.error('Error calculating age:', error);
        return 'N/A';
      }
    },
    calculateDaysDifference(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end dates
        return diffDays;
      } catch (error) {
        console.error('Error calculating days difference:', error);
        return 0;
      }
    },
    printMC() {
      // Hide modal first
      const modal = window.bootstrap?.Modal?.getInstance(document.getElementById('mcPreviewModal'));
      if (modal) {
        modal.hide();
      }
      
      // Wait for modal to close, then print
      setTimeout(() => {
        const printContent = document.querySelector('.mc-preview-container').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
          <html>
            <head>
              <title>Medical Certificate</title>
              <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .mc-content { background: white !important; }
                h3, h5 { color: #000; }
                .signature-line { border-bottom: 1px solid #000 !important; }
                @media print {
                  body { margin: 0; }
                  .no-print { display: none; }
                }
              </style>
            </head>
            <body>
              ${printContent}
            </body>
          </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
      }, 300);
    }
  }
};
</script>

<style scoped>
/* Ensure MC Preview Modal is always on top */
#mcPreviewModal {
  z-index: 99999 !important;
}

#mcPreviewModal .modal-backdrop {
  z-index: 99998 !important;
}

#mcPreviewModal .modal-dialog {
  z-index: 100000 !important;
}

.mc-preview-container {
  font-family: 'Times New Roman', serif;
  line-height: 1.6;
  color: #000;
}

.mc-content {
  font-size: 1rem;
  line-height: 1.8;
}

.signature-line {
  height: 50px;
}

.doctor-signature {
  margin-top: 2rem;
}

.modal-lg {
  max-width: 900px;
}

@media (max-width: 768px) {
  .modal-lg {
    max-width: 95%;
    margin: 1rem auto;
  }
}

/* Print styles */
@media print {
  .mc-preview-container {
    margin: 0;
    padding: 20px;
  }
  
  .mc-content {
    background: white !important;
    border: none !important;
  }
}
</style> 