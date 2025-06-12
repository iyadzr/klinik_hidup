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
                    <th>Remarks</th>
                    <th>Medication</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="consultation in consultations" :key="consultation.id">
                    <td>{{ formatDateMYT(consultation.createdAt) }}</td>
                    <td>{{ consultation.patientName || 'N/A' }}</td>
                    <td>{{ consultation.doctorName || 'N/A' }}</td>
                    <td>
                      <div 
                        class="truncated-text" 
                        :title="consultation.notes || consultation.remarks"
                        @mouseenter="showTooltip"
                        @mouseleave="hideTooltip"
                      >
                        {{ truncateText(consultation.notes || consultation.remarks || 'N/A', 50) }}
                      </div>
                    </td>
                    <td>
                      <div 
                        class="truncated-text"
                        :title="getMedicationText(consultation)"
                        @mouseenter="showTooltip"
                        @mouseleave="hideTooltip"
                      >
                        {{ truncateText(getMedicationText(consultation), 50) }}
                      </div>
                    </td>
                    <td>
                      <button 
                        v-if="!consultation.isPaid"
                        @click="openPaymentModal(consultation)"
                        class="btn btn-sm btn-outline-warning"
                        :disabled="consultation.isPaid"
                      >
                        Unpaid
                      </button>
                      <span v-else class="badge bg-success">
                        Paid
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

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Process Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedConsultation">
              <h6>Patient: {{ selectedConsultation.patientName }}</h6>
              <h6>Total Amount: RM {{ selectedConsultation.totalAmount }}</h6>
              
              <div class="mt-3">
                <label class="form-label">Payment Method:</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="paymentMethod" 
                      id="cash" 
                      value="Cash"
                      v-model="paymentMethod"
                    >
                    <label class="form-check-label" for="cash">
                      <i class="fas fa-money-bill-wave me-2"></i>Cash
                    </label>
                  </div>
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="paymentMethod" 
                      id="card" 
                      value="Card"
                      v-model="paymentMethod"
                    >
                    <label class="form-check-label" for="card">
                      <i class="fas fa-credit-card me-2"></i>Card
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-success" 
              @click="processPayment"
              :disabled="!paymentMethod || processing"
            >
              <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
              {{ processing ? 'Processing...' : 'Mark as Paid' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Payment Receipt</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="receiptContent">
            <div v-if="paidConsultation" class="receipt">
              <div class="text-center mb-4">
                <h3>CLINIC MANAGEMENT SYSTEM</h3>
                <h5>Payment Receipt</h5>
                <hr>
              </div>
              
              <div class="row mb-3">
                <div class="col-6">
                  <strong>Receipt No:</strong> {{ generateReceiptNumber(paidConsultation.id) }}
                </div>
                <div class="col-6 text-end">
                  <strong>Date:</strong> {{ formatDateMYT(new Date()) }}
                </div>
              </div>
              
              <div class="mb-3">
                <strong>Patient:</strong> {{ paidConsultation.patientName }}<br>
                <strong>Doctor:</strong> {{ paidConsultation.doctorName }}<br>
                <strong>Consultation Date:</strong> {{ formatDateMYT(paidConsultation.createdAt) }}
              </div>
              
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Consultation Fee</td>
                    <td class="text-end">RM {{ paidConsultation.consultationFee || '0.00' }}</td>
                  </tr>
                  <tr v-if="paidConsultation.medicinesFee && paidConsultation.medicinesFee > 0">
                    <td>Medicines Fee</td>
                    <td class="text-end">RM {{ paidConsultation.medicinesFee }}</td>
                  </tr>
                  <tr class="table-active">
                    <td><strong>Total Amount</strong></td>
                    <td class="text-end"><strong>RM {{ paidConsultation.totalAmount }}</strong></td>
                  </tr>
                </tbody>
              </table>
              
              <div class="mb-3">
                <strong>Payment Method:</strong> {{ lastPaymentMethod }}
              </div>
              
              <div class="text-center mt-4">
                <small class="text-muted">Thank you for your payment!</small>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="printReceipt">
              <i class="fas fa-print me-2"></i>Print Receipt
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tooltip Modal for Full Text -->
    <div class="modal fade" id="textModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Full Text</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>{{ fullText }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';

export default {
  name: 'ConsultationList',
  data() {
    return {
      consultations: [],
      loading: false,
      error: null,
      fullText: '',
      tooltipTimeout: null,
      selectedConsultation: null,
      paymentMethod: '',
      processing: false,
      paidConsultation: null,
      lastPaymentMethod: ''
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
    formatDateMYT(date) {
      if (!date) return 'N/A';
      try {
        // Convert to Malaysia Time (MYT - UTC+8)
        const dateObj = new Date(date);
        return dateObj.toLocaleString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    truncateText(text, maxLength) {
      if (!text) return 'N/A';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    },
    getMedicationText(consultation) {
      // Try to get medications from different possible formats
      if (consultation.prescribedMedications && Array.isArray(consultation.prescribedMedications)) {
        return consultation.prescribedMedications
          .map(med => `${med.name} (${med.quantity} ${med.unitType || 'pcs'})`)
          .join(', ') || 'N/A';
      } else if (consultation.medications) {
        return consultation.medications;
      } else {
        return 'N/A';
      }
    },
    showTooltip(event) {
      // Clear any existing timeout
      if (this.tooltipTimeout) {
        clearTimeout(this.tooltipTimeout);
      }
      
      // Show tooltip after a short delay
      this.tooltipTimeout = setTimeout(() => {
        const fullText = event.target.getAttribute('title');
        if (fullText && fullText.length > 50) {
          // Create and show tooltip
          this.createTooltip(event.target, fullText);
        }
      }, 500);
    },
    hideTooltip() {
      if (this.tooltipTimeout) {
        clearTimeout(this.tooltipTimeout);
      }
      // Remove any existing tooltips
      const existingTooltips = document.querySelectorAll('.custom-tooltip');
      existingTooltips.forEach(tooltip => tooltip.remove());
    },
    createTooltip(element, text) {
      // Remove any existing tooltips
      this.hideTooltip();
      
      const tooltip = document.createElement('div');
      tooltip.className = 'custom-tooltip';
      tooltip.innerHTML = text;
      
      document.body.appendChild(tooltip);
      
      const rect = element.getBoundingClientRect();
      tooltip.style.left = rect.left + 'px';
      tooltip.style.top = (rect.bottom + 5) + 'px';
    },
    getStatusClass(status) {
      const classes = {
        'pending': 'badge badge-warning',
        'completed': 'badge badge-success',
        'cancelled': 'badge badge-danger'
      };
      return classes[status?.toLowerCase()] || 'badge badge-secondary';
    },
    openPaymentModal(consultation) {
      this.selectedConsultation = consultation;
      this.paymentMethod = '';
      const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
      modal.show();
    },
    async processPayment() {
      if (!this.paymentMethod || !this.selectedConsultation) {
        alert('Please select a payment method');
        return;
      }

      this.processing = true;
      try {
        const response = await axios.post(`/api/consultations/${this.selectedConsultation.id}/payment`, {
          paymentMethod: this.paymentMethod
        });

        // Update the consultation in the list
        const index = this.consultations.findIndex(c => c.id === this.selectedConsultation.id);
        if (index !== -1) {
          this.consultations[index].isPaid = true;
          this.consultations[index].paidAt = new Date().toISOString();
        }

        // Store for receipt
        this.paidConsultation = this.selectedConsultation;
        this.lastPaymentMethod = this.paymentMethod;

        // Close payment modal
        const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
        paymentModal.hide();

        // Show receipt modal
        const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
        receiptModal.show();

      } catch (error) {
        console.error('Error processing payment:', error);
        alert('Error processing payment. Please try again.');
      } finally {
        this.processing = false;
      }
    },
    generateReceiptNumber(consultationId) {
      const date = new Date();
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `R${year}${month}${day}-${String(consultationId).padStart(4, '0')}`;
    },
    printReceipt() {
      const printContent = document.getElementById('receiptContent').innerHTML;
      const printWindow = window.open('', '', 'height=600,width=800');
      
      printWindow.document.write(`
        <html>
          <head>
            <title>Payment Receipt</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
              .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              .table th { background-color: #f2f2f2; }
              .text-center { text-align: center; }
              .text-end { text-align: right; }
              .table-active { background-color: #f8f9fa; }
              hr { margin: 20px 0; }
              .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
              .col-6 { width: 48%; }
              .mb-3 { margin-bottom: 15px; }
              .mt-4 { margin-top: 20px; }
            </style>
          </head>
          <body>
            ${printContent}
          </body>
        </html>
      `);
      
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
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

.truncated-text {
  cursor: help;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.truncated-text:hover {
  background-color: #f8f9fa;
  padding: 2px 4px;
  border-radius: 3px;
}

:global(.custom-tooltip) {
  position: absolute;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 0.875rem;
  max-width: 300px;
  z-index: 9999;
  word-wrap: break-word;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
</style>
