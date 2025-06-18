<template>
  <div class="consultations-list-container">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mt-2">Loading consultations...</div>
    </div>
    
    <div v-else-if="error" class="alert alert-danger text-center">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
    </div>
    
    <div v-else>
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <h3 class="card-title mb-0">Consultations</h3>
                  
                  <!-- Date and Doctor Filters -->
                  <div class="d-flex align-items-center justify-content-end gap-4">
                    <!-- Date controls -->
                    <div class="d-flex align-items-center gap-2">
                      <button @click="setToday" class="btn btn-primary btn-sm px-3" :disabled="loading">
                        <i class="fas fa-calendar-day me-1"></i> Today
                      </button>
                      <button @click="manualRefresh" class="btn btn-outline-primary btn-sm px-3" :disabled="loading">
                        <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i> Refresh
                      </button>
                      <button @click="showDebugInfo" class="btn btn-outline-info btn-sm px-3">
                        <i class="fas fa-bug me-1"></i> Debug
                      </button>
                      <button @click="showMedicationDebug" class="btn btn-outline-warning btn-sm px-3">
                        <i class="fas fa-pills me-1"></i> Med Debug
                      </button>
                      <div class="filter-group">
                        <label for="dateFilter" class="form-label mb-0 me-2">Date:</label>
                        <input 
                          type="date" 
                          id="dateFilter"
                          v-model="selectedDate" 
                          class="form-control form-control-sm"
                          style="width: auto;"
                        >
                      </div>
                    </div>
                    
                    <!-- Doctor filter -->
                    <div class="filter-group">
                      <label for="doctorFilter" class="form-label mb-0 me-2">Filter by Doctor:</label>
                      <select 
                        id="doctorFilter" 
                        v-model="selectedDoctorFilter" 
                        class="form-select form-select-sm"
                        style="min-width: 200px;"
                      >
                        <option value="">All Doctors</option>
                        <option v-for="doctor in availableDoctors" :key="doctor.id" :value="doctor.id">
                          {{ doctor.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>
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
                          <button 
                            v-if="consultation.status !== 'Completed' && !consultation.isPaid" 
                            class="btn btn-sm btn-danger" 
                            @click="accessOngoingConsultation(consultation)"
                          >
                            <i class="fas fa-user-md me-1"></i> Access Ongoing
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
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
  </div>
</template>

<script>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import ConsultationService from '../../services/ConsultationService';
import AuthService from '../../services/AuthService';
import { formatDate, getTodayInMYT } from '../../utils/dateUtils';
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

    const allConsultations = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const fullText = ref('');
    const tooltipTimeout = ref(null);
    const selectedConsultation = ref(null);
    const paymentMethod = ref('');
    const processing = ref(false);
    const paidConsultation = ref(null);
    const lastPaymentMethod = ref('');
    const selectedDoctorFilter = ref('');
    const availableDoctors = ref([]);
    const selectedDate = ref(getTodayInMYT());
    
    // Add debouncing for date filter
    let dateFilterTimeout = null;
    
    const isSuperAdmin = computed(() => {
      return AuthService.hasRole('ROLE_SUPER_ADMIN');
    });
    
    const isDoctor = computed(() => {
      return AuthService.hasRole('ROLE_DOCTOR');
    });
    
    const isAssistant = computed(() => {
      return AuthService.hasRole('ROLE_ASSISTANT');
    });
    
    const currentUser = computed(() => {
      return AuthService.getCurrentUser();
    });
    
    // Computed property for filtered consultations
    const consultations = computed(() => {
      if (!selectedDoctorFilter.value) {
        return allConsultations.value;
      }
      return allConsultations.value.filter(consultation => 
        consultation.doctorId == selectedDoctorFilter.value
      );
    });
    
    // Watch for date changes with debouncing
    watch(selectedDate, (newDate) => {
      if (newDate) {
        // Clear existing timeout
        if (dateFilterTimeout) {
          clearTimeout(dateFilterTimeout);
        }
        
        // Set new timeout for 2 seconds
        dateFilterTimeout = setTimeout(() => {
          console.log('Date filter triggered after 2 second delay:', newDate);
          fetchConsultations();
        }, 2000);
      }
    });
    
    const router = useRouter();
    
    // Add AbortController for request cancellation
    let currentRequest = null;

    const fetchConsultations = async () => {
      // Cancel previous request if still pending
      if (currentRequest) {
        currentRequest.abort();
      }
      
      // Prevent multiple simultaneous requests
      if (loading.value) return;
      
      loading.value = true;
      error.value = null;
      
      // Create new AbortController for this request
      currentRequest = new AbortController();
      
      try {
        console.log('Fetching consultations for date:', selectedDate.value);
        const response = await axios.get(`/api/consultations?date=${selectedDate.value}`, {
          signal: currentRequest.signal
        });
        console.log('Consultations response:', response);
        allConsultations.value = response.data || [];
        
        // Show success notification
        if (response.data && response.data.length > 0) {
          console.log(`✅ Loaded ${response.data.length} consultations for ${selectedDate.value}`);
        } else {
          console.log(`ℹ️ No consultations found for ${selectedDate.value}`);
        }
        
        // Extract unique doctors
        const doctorMap = new Map();
        response.data.forEach(consultation => {
          if (consultation.doctorId && consultation.doctorName) {
            doctorMap.set(consultation.doctorId, consultation.doctorName);
          }
        });
        availableDoctors.value = Array.from(doctorMap.entries()).map(([id, name]) => ({ id, name }));
        
        // Set default filter based on user role
        const user = currentUser.value;
        if (isDoctor.value && user) {
          // For doctors, find their doctor ID and filter by default
          const doctorConsultation = response.data.find(c => 
            c.doctorName && (
              c.doctorName.toLowerCase().includes(user.name.toLowerCase()) ||
              user.name.toLowerCase().includes(c.doctorName.toLowerCase())
            )
          );
          if (doctorConsultation) {
            selectedDoctorFilter.value = doctorConsultation.doctorId;
          }
        }
        // For super admin and assistants, show all by default (selectedDoctorFilter.value remains '')
        
      } catch (err) {
        if (err.name === 'AbortError') {
          console.log('⏹️ Request was cancelled');
          return;
        }
        console.error('Error fetching consultations:', err);
        error.value = err.response?.data?.message || err.message || 'Failed to fetch consultations';
      } finally {
        loading.value = false;
        currentRequest = null;
      }
    };

    const accessOngoingConsultation = (consultation) => {
      router.push(`/consultations/${consultation.id}`);
    };

    const formatDateMYT = (date) => {
      if (!date) return 'N/A';
      try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: 'short',
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

    const formatTime = (date) => {
      if (!date) return 'N/A';
      try {
        const dateObj = new Date(date);
        return dateObj.toLocaleTimeString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        console.error('Error formatting time:', error);
        return 'Invalid Time';
      }
    };

    const truncateText = (text, maxLength) => {
      if (!text) return 'N/A';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    };

    const getMedicationText = (consultation) => {
      console.log('🔍 Processing medications for consultation:', consultation.id, {
        prescribedMedications: consultation.prescribedMedications,
        medications: consultation.medications,
        medicationsType: typeof consultation.medications,
        prescribedMedicationsType: typeof consultation.prescribedMedications,
        consultationKeys: Object.keys(consultation)
      });
      
      // Priority 1: Try prescribedMedications array (structured data)
      if (consultation.prescribedMedications && Array.isArray(consultation.prescribedMedications) && consultation.prescribedMedications.length > 0) {
        const medicationText = consultation.prescribedMedications
          .map(med => {
            const name = med.name || med.medication || 'Unknown';
            const quantity = med.quantity || 1;
            const unit = med.unitType || med.unit || 'pcs';
            return `${name} (${quantity} ${unit})`;
          })
          .join(', ');
        console.log('✅ Using prescribedMedications:', medicationText);
        return medicationText;
      }
      
      // Priority 2: Try medications field (could be string or array)
      if (consultation.medications) {
        // Handle string medications
        if (typeof consultation.medications === 'string' && consultation.medications.trim() !== '') {
          const medicationsStr = consultation.medications.trim();
          
          // Try to parse as JSON first
          try {
            const parsed = JSON.parse(medicationsStr);
            if (Array.isArray(parsed) && parsed.length > 0) {
              const medicationText = parsed
                .map(med => {
                  const name = med.name || med.medication || med.drug || 'Unknown';
                  const quantity = med.quantity || med.qty || 1;
                  const unit = med.unitType || med.unit || med.type || 'pcs';
                  return `${name} (${quantity} ${unit})`;
                })
                .join(', ');
              console.log('✅ Using parsed JSON medications:', medicationText);
              return medicationText;
            } else if (parsed && typeof parsed === 'object') {
              // Handle single medication object
              const name = parsed.name || parsed.medication || parsed.drug || 'Unknown';
              const quantity = parsed.quantity || parsed.qty || 1;
              const unit = parsed.unitType || parsed.unit || parsed.type || 'pcs';
              const medicationText = `${name} (${quantity} ${unit})`;
              console.log('✅ Using single medication object:', medicationText);
              return medicationText;
            }
          } catch (e) {
            console.log('📝 JSON parse failed, treating as plain text:', medicationsStr);
          }
          
          // If not JSON or JSON parsing failed, return as plain text
          console.log('✅ Using plain text medications:', medicationsStr);
          return medicationsStr;
        }
        
        // Handle array medications (direct array)
        else if (Array.isArray(consultation.medications) && consultation.medications.length > 0) {
          const medicationText = consultation.medications
            .map(med => {
              if (typeof med === 'string') {
                return med;
              }
              const name = med.name || med.medication || med.drug || 'Unknown';
              const quantity = med.quantity || med.qty || 1;
              const unit = med.unitType || med.unit || med.type || 'pcs';
              return `${name} (${quantity} ${unit})`;
            })
            .join(', ');
          console.log('✅ Using array medications:', medicationText);
          return medicationText;
        }
      }
      
      // Priority 3: Check for alternative medication fields
      const altFields = ['medicine', 'drugs', 'prescription', 'medicationList'];
      for (const field of altFields) {
        if (consultation[field]) {
          console.log(`✅ Using alternative field ${field}:`, consultation[field]);
          return consultation[field];
        }
      }
      
      console.log('❌ No medications found in any format');
      return 'N/A';
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
        const index = allConsultations.value.findIndex(c => c.id === selectedConsultation.value.id);
        if (index !== -1) {
          allConsultations.value[index].isPaid = true;
          allConsultations.value[index].paidAt = new Date().toISOString();
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
      // Get the PaymentReceipt component instance and call its print method
      const receiptComponent = document.querySelector('#paymentReceipt');
      if (receiptComponent && receiptComponent.__vue__) {
        receiptComponent.__vue__.printReceipt();
      } else {
        // Fallback: direct print of the receipt content
        const receiptContent = document.getElementById('receiptContent');
        if (receiptContent) {
          const printWindow = window.open('', '_blank', 'width=800,height=600');
          const receiptHTML = receiptContent.innerHTML;
          
          printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
              <title>Receipt</title>
              <style>
                @page { margin: 0; size: A4; }
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.4; color: #000; background: white; padding: 20px; }
                .payment-receipt { max-width: 600px; margin: 0 auto; }
                .receipt-container { border: 2px solid #000; padding: 30px; background: white; }
                .clinic-header { text-align: center; margin-bottom: 30px; }
                .clinic-name { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                .clinic-address p { margin: 2px 0; font-size: 14px; }
                .receipt-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
                .receipt-title { font-size: 20px; font-weight: bold; }
                .receipt-number { color: #dc3545; font-weight: bold; font-size: 16px; }
                .receipt-date { font-size: 14px; }
                .patient-info { margin-bottom: 30px; }
                .info-row { margin-bottom: 10px; display: flex; align-items: center; }
                .label { min-width: 140px; display: inline-block; }
                .value { flex: 1; padding-left: 10px; border-bottom: 1px dotted #000; min-height: 25px; font-weight: bold; }
                .bottom-section { display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px; }
                .amount-section { flex: 0 0 auto; }
                .amount-box { border: 2px solid #000; padding: 15px 20px; text-align: left; display: flex; justify-content: space-between; align-items: center; min-width: 200px; width: 200px; }
                .amount-label { font-weight: bold; margin-bottom: 0; color: #000; font-size: 16px; }
                                 .amount-value { font-size: 18px; font-weight: bold; color: #000; }
                .doctor-info { flex: 0 0 auto; text-align: right; }
                .signature-space { height: 60px; border-bottom: 1px solid #000; margin-bottom: 10px; width: 200px; margin-left: auto; }
                .signature-label { margin-left: 10px; font-size: 12px; }
                .d-flex { display: flex; }
                .justify-content-between { justify-content: space-between; }
                .align-items-center { align-items: center; }
                .align-items-start { align-items: start; }
                .text-center { text-align: center; }
                .text-end { text-align: right; }
                .mb-1 { margin-bottom: 5px; }
                .mb-2 { margin-bottom: 10px; }
                .mb-3 { margin-bottom: 15px; }
                .mb-4 { margin-bottom: 20px; }
                .fw-bold { font-weight: bold; }
                .text-danger { color: #dc3545; }
              </style>
            </head>
            <body onload="window.print(); window.close();">
              ${receiptHTML}
            </body>
            </html>
          `);
          
          printWindow.document.close();
        } else {
          alert('Receipt content not found. Please try again.');
        }
      }
    };

    const showReceipt = (consultation) => {
      selectedConsultation.value = consultation;
      const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
      receiptModal.show();
    };

    const viewDetails = (consultation) => {
      console.log('Viewing consultation details:', consultation);
      // TODO: Implement detailed view
    };

    const setToday = () => {
      selectedDate.value = getTodayInMYT();
      // fetchConsultations will be called automatically by the watch method
    };

    const manualRefresh = () => {
      // Clear any pending debounced calls
      if (dateFilterTimeout) {
        clearTimeout(dateFilterTimeout);
        dateFilterTimeout = null;
      }
      // Immediately fetch consultations
      console.log('Manual refresh triggered');
      fetchConsultations();
    };

    const showDebugInfo = () => {
      console.log('Debug information:', {
        allConsultations: allConsultations.value,
        loading: loading.value,
        error: error.value,
        fullText: fullText.value,
        tooltipTimeout: tooltipTimeout.value,
        selectedConsultation: selectedConsultation.value,
        paymentMethod: paymentMethod.value,
        processing: processing.value,
        paidConsultation: paidConsultation.value,
        lastPaymentMethod: lastPaymentMethod.value,
        selectedDoctorFilter: selectedDoctorFilter.value,
        availableDoctors: availableDoctors.value,
        selectedDate: selectedDate.value,
        consultations: consultations.value,
        isSuperAdmin: isSuperAdmin.value,
        isDoctor: isDoctor.value,
        isAssistant: isAssistant.value,
        currentUser: currentUser.value
      });
    };

    const showMedicationDebug = () => {
      console.log('🔍 MEDICATION DEBUG - All consultations:');
      allConsultations.value.forEach((consultation, index) => {
        console.log(`Consultation ${index + 1} (ID: ${consultation.id}):`, {
          id: consultation.id,
          patientName: consultation.patientName,
          medications: consultation.medications,
          prescribedMedications: consultation.prescribedMedications,
          medicationsType: typeof consultation.medications,
          prescribedMedicationsType: typeof consultation.prescribedMedications,
          medicationsLength: consultation.medications ? consultation.medications.length : 'N/A',
          prescribedMedicationsLength: consultation.prescribedMedications ? consultation.prescribedMedications.length : 'N/A',
          allKeys: Object.keys(consultation),
          medicationText: getMedicationText(consultation)
        });
      });
      
      // Also test the API endpoint directly
      console.log('🔍 Testing API endpoint directly...');
      fetch(`/api/consultations?date=${selectedDate.value}`)
        .then(response => response.json())
        .then(data => {
          console.log('🔍 Raw API response:', data);
          if (data && data.length > 0) {
            console.log('🔍 First consultation raw data:', data[0]);
          }
        })
        .catch(error => {
          console.error('🔍 API test failed:', error);
        });
    };

    onMounted(() => {
      console.log('ConsultationList component mounted');
      fetchConsultations();
    });

    onUnmounted(() => {
      // Cleanup debounce timeout
      if (dateFilterTimeout) {
        clearTimeout(dateFilterTimeout);
        dateFilterTimeout = null;
      }
    });

    return {
      consultations,
      loading,
      error,
      isSuperAdmin,
      isDoctor,
      isAssistant,
      currentUser,
      accessOngoingConsultation,
      formatDate: formatDateMYT,
      formatTime,
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
      viewDetails,
      selectedDoctorFilter,
      availableDoctors,
      selectedDate,
      setToday,
      manualRefresh,
      showDebugInfo,
      showMedicationDebug
    };
  }
};
</script>

<style scoped>
.consultations-list-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

.empty-state {
  color: #64748b;
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
  border-radius: 8px;
  font-weight: 600;
}

.card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  background: white;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px 16px 0 0 !important;
  padding: 1.5rem;
  border: none;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group .form-label {
  white-space: nowrap;
  margin-bottom: 0;
  font-weight: 500;
}

.filter-group .form-control,
.filter-group .form-select {
  min-width: 150px;
}

.btn-primary.btn-sm {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
  transition: all 0.2s ease;
}

.btn-primary.btn-sm:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.card-header h4 {
  margin: 0;
  font-weight: 600;
  font-size: 1.25rem;
}

.table {
  margin: 0;
  border-radius: 0 0 16px 16px;
  overflow: hidden;
}

.table th {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-weight: 600;
  color: #374151;
  border: none;
  padding: 1rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table td {
  padding: 1rem;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table tbody tr {
  transition: background-color 0.2s ease;
}

.table tbody tr:hover {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.table tbody tr:last-child td {
  border-bottom: none;
}

.btn {
  border-radius: 8px;
  font-weight: 500;
  padding: 0.5rem 1rem;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-outline-secondary {
  border: 2px solid #e5e7eb;
  color: #6b7280;
  background: white;
}

.btn-outline-secondary:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  transform: translateY(-1px);
}

.badge-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.badge-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.badge-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.badge-secondary {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
}

.spinner-border {
  color: #667eea;
}

.text-center {
  text-align: center;
}

.py-5 {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.visually-hidden {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}

/* Custom tooltip styles */
.custom-tooltip {
  position: absolute;
  background: #1f2937;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  max-width: 300px;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Modal enhancements */
.modal-content {
  border: none;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px 16px 0 0;
  border: none;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  border: none;
  padding: 1rem 2rem 2rem;
}

/* Loading state improvements */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  z-index: 10;
}

/* Responsive improvements */
@media (max-width: 768px) {
  .consultations-list-container {
    padding: 1rem 0.5rem;
  }
  
  .card-header {
    padding: 1rem;
  }
  
  .table th,
  .table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.875rem;
  }
  
  .btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
  }
}
</style>
