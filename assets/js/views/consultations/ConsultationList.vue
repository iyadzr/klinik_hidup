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
                    <th v-if="isSuperAdmin">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="consultation in consultations" :key="consultation.id">
                    <td>{{ formatDate(consultation.createdAt) }}</td>
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
                        <i class="fas fa-money-bill-wave me-1"></i>Unpaid
                      </button>
                      <div v-else class="d-flex gap-1">
                        <span class="badge bg-success">
                          <i class="fas fa-check me-1"></i>Paid
                        </span>
                        <button 
                          @click="showReceipt(consultation)"
                          class="btn btn-sm btn-outline-primary"
                          title="View Receipt"
                        >
                          <i class="fas fa-receipt me-1"></i>Receipt
                        </button>
                      </div>
                    </td>
                    <td v-if="isSuperAdmin">
                      <button v-if="consultation.status !== 'Completed'" class="btn btn-sm btn-danger" @click="accessOngoingConsultation(consultation)">
                        <i class="fas fa-user-md me-1"></i> Access Ongoing
                      </button>
                    </td>
                  </tr>
                  <tr v-if="consultations.length === 0">
                    <td colspan="7" class="text-center">No consultations found</td>
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

    <!-- Professional Payment Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-receipt me-2"></i>Payment Receipt
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
            <payment-receipt 
              v-if="selectedConsultation"
              :consultation="selectedConsultation"
              :receipt-number="selectedConsultation.receiptNumber || 'N/A'"
              ref="paymentReceipt"
            />
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-2"></i>Close
            </button>
            <button type="button" class="btn btn-primary" @click="printProfessionalReceipt">
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
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import ConsultationService from '../../services/ConsultationService';
import { formatDate } from '../../utils/dateUtils';
import axios from 'axios';
import * as bootstrap from 'bootstrap';
import PaymentReceipt from '../../components/PaymentReceipt.vue';
import { useRouter } from 'vue-router';

export default {
  name: 'ConsultationList',
  components: {
    PaymentReceipt
  },
  setup() {
    const authStore = useAuthStore();
    const consultations = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const fullText = ref('');
    const tooltipTimeout = ref(null);
    const selectedConsultation = ref(null);
    const paymentMethod = ref('');
    const processing = ref(false);
    const paidConsultation = ref(null);
    const lastPaymentMethod = ref('');
    const isSuperAdmin = computed(() => {
      return authStore.userRoles?.includes('ROLE_SUPER_ADMIN');
    });
    const router = useRouter();

    const fetchConsultations = async () => {
      loading.value = true;
      try {
        const response = await ConsultationService.getAllConsultations();
        consultations.value = response.data;
      } catch (err) {
        error.value = err.message || 'Failed to fetch consultations';
      } finally {
        loading.value = false;
      }
    };

    const accessOngoingConsultation = (consultation) => {
      router.push(`/consultations/${consultation.id}`);
    };

    const formatDateMYT = (date) => {
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
    };

    const truncateText = (text, maxLength) => {
      if (!text) return 'N/A';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    };

    const getMedicationText = (consultation) => {
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
    };

    const showTooltip = (event) => {
      // Clear any existing timeout
      if (tooltipTimeout.value) {
        clearTimeout(tooltipTimeout.value);
      }
      
      // Show tooltip after a short delay
      tooltipTimeout.value = setTimeout(() => {
        const fullText = event.target.getAttribute('title');
        if (fullText && fullText.length > 50) {
          // Create and show tooltip
          createTooltip(event.target, fullText);
        }
      }, 500);
    };

    const hideTooltip = () => {
      if (tooltipTimeout.value) {
        clearTimeout(tooltipTimeout.value);
      }
      // Remove any existing tooltips
      const existingTooltips = document.querySelectorAll('.custom-tooltip');
      existingTooltips.forEach(tooltip => tooltip.remove());
    };

    const createTooltip = (element, text) => {
      // Remove any existing tooltips
      hideTooltip();
      
      const tooltip = document.createElement('div');
      tooltip.className = 'custom-tooltip';
      tooltip.innerHTML = text;
      
      document.body.appendChild(tooltip);
      
      const rect = element.getBoundingClientRect();
      tooltip.style.left = rect.left + 'px';
      tooltip.style.top = (rect.bottom + 5) + 'px';
    };

    const getStatusClass = (status) => {
      const classes = {
        'pending': 'badge badge-warning',
        'completed': 'badge badge-success',
        'cancelled': 'badge badge-danger'
      };
      return classes[status?.toLowerCase()] || 'badge badge-secondary';
    };

    const openPaymentModal = (consultation) => {
      selectedConsultation.value = consultation;
      paymentMethod.value = '';
      const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
      modal.show();
    };

    const processPayment = async () => {
      if (!paymentMethod.value || !selectedConsultation.value) {
        alert('Please select a payment method');
        return;
      }

      processing.value = true;
      try {
        const response = await axios.post(`/api/consultations/${selectedConsultation.value.id}/payment`, {
          paymentMethod: paymentMethod.value
        });

        // Update the consultation in the list
        const index = consultations.value.findIndex(c => c.id === selectedConsultation.value.id);
        if (index !== -1) {
          consultations.value[index].isPaid = true;
          consultations.value[index].paidAt = new Date().toISOString();
        }

        // Store for receipt
        paidConsultation.value = selectedConsultation.value;
        lastPaymentMethod.value = paymentMethod.value;

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
        processing.value = false;
      }
    };

    const printProfessionalReceipt = () => {
      const paymentReceipt = document.getElementById('paymentReceipt');
      if (paymentReceipt) {
        paymentReceipt.printReceipt();
      }
    };

    const showReceipt = (consultation) => {
      selectedConsultation.value = consultation;
      const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
      receiptModal.show();
    };

    onMounted(() => {
      fetchConsultations();
    });

    return {
      consultations,
      loading,
      error,
      isSuperAdmin,
      accessOngoingConsultation,
      formatDate: formatDateMYT,
      truncateText,
      getMedicationText,
      showTooltip,
      hideTooltip,
      createTooltip,
      getStatusClass,
      openPaymentModal,
      processPayment,
      printProfessionalReceipt,
      showReceipt,
      selectedConsultation,
      paymentMethod,
      processing,
      paidConsultation,
      lastPaymentMethod,
      formatDate
    };
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
