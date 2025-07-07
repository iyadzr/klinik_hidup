<template>
  <div class="queue-management">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Queue List</h4>
        <div class="d-flex gap-3 align-items-center">
          <div class="d-flex align-items-center gap-2">
            <label for="queueDate" class="form-label mb-0">Date:</label>
            <input 
              type="date" 
              id="queueDate"
              v-model="selectedDate" 
              class="form-control form-control-sm"
              style="width: auto;"
            >
          </div>
          <div class="d-flex align-items-center gap-2">
            <label for="queueStatus" class="form-label mb-0">Status:</label>
            <select 
              id="queueStatus"
              v-model="selectedStatus" 
              class="form-select form-select-sm"
              style="width: auto;"
            >
              <option value="all">All Statuses</option>
              <option value="waiting">Waiting</option>
              <option value="in_consultation">In Consultation</option>
              <option value="completed_consultation">Pending Payment</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <button @click="setToday" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-calendar-day"></i> Today
          </button>
          <button @click="manualRefresh" class="btn btn-outline-success btn-sm" :disabled="isLoading || isRefreshing">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i> 
            {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Queue #</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Time</th>
                <th>Medicines</th>
                <th>Payment Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="queue in queueList" :key="queue.id" :class="{'table-active': queue.status === 'in_consultation'}">
                <td>{{ formatQueueNumber(queue.queueNumber) }}</td>
                <td>
                  <div v-if="queue.isGroupConsultation">
                    <strong>{{ queue.mainPatient ? queue.mainPatient.name : 'Group Consultation' }}</strong>
                    <small class="text-muted d-block">
                      <i class="fas fa-users me-1"></i>
                      Group ({{ queue.patientCount ?? (queue.patients?.length || 0) }} {{ (queue.patientCount ?? (queue.patients?.length || 0)) === 1 ? 'patient' : 'patients' }})
                    </small>
                  </div>
                  <div v-else>
                    {{ queue.patient ? queue.patient.name : 'N/A' }}
                  </div>
                </td>
                <td>{{ queue.doctor ? queue.doctor.name : 'N/A' }}</td>
                <td>
                  <span :class="getStatusBadgeClass(queue.status)">
                    {{ formatStatus(queue.status) }}
                  </span>
                </td>
                <td>{{ formatDateTime(queue.queueDateTime) }}</td>
                <td>
                  <div v-if="queue.hasMedicines">
                    <!-- Always show clickable medicines button when medicines exist -->
                    <button class="btn btn-sm fw-bold premium-medicines-btn" @click="viewMedicines(queue)">
                      <i class="fas fa-pills me-1"></i>
                      <span class="d-none d-md-inline">Medicines</span>
                      <span class="d-md-none">Meds</span>
                    </button>
                    <!-- Show additional status badge for completed consultations -->
                    <div v-if="queue.status === 'completed'" class="mt-1">
                      <span class="badge bg-success badge-sm">
                        <i class="fas fa-check me-1"></i>Ready
                      </span>
                    </div>
                  </div>
                  <div v-else>
                    <span class="text-muted">-</span>
                  </div>
                </td>
                <td>
                  <div v-if="queue.status === 'completed_consultation'">
                    <span v-if="queue.isPaid" class="badge bg-success">
                      <i class="fas fa-check me-1"></i>Paid
                    </span>
                    <button v-else class="btn btn-sm fw-bold premium-payment-btn" @click="processPayment(queue)">
                      <i class="fas fa-credit-card me-1"></i>
                      <span class="d-none d-md-inline">Payment</span>
                      <span class="d-md-none">Pay</span>
                    </button>
                  </div>
                  <div v-else-if="queue.status === 'completed'">
                    <span class="badge bg-success">
                      <i class="fas fa-check me-1"></i>Completed
                    </span>
                  </div>
                  <div v-else>
                    <span class="text-muted">-</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment Modal (Vue-based, No Bootstrap JS) -->
  <div v-if="showPaymentModal" class="vue-modal-overlay" @click="closePaymentModal">
    <div class="vue-modal-dialog" @click.stop>
      <div class="vue-modal-content">
        <div class="vue-modal-header">
          <h5 class="vue-modal-title">
            <i class="fas fa-credit-card me-2"></i>Process Payment
          </h5>
          <button type="button" class="vue-btn-close" @click="closePaymentModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="vue-modal-body">
          <div v-if="selectedQueue">
            <div class="row mb-3">
              <div class="col-md-6">
                <h6><i class="fas fa-user me-2"></i>Patient: {{ selectedQueue.patient?.name }}</h6>
              </div>
              <div class="col-md-6">
                <h6><i class="fas fa-list-ol me-2"></i>Queue: {{ formatQueueNumber(selectedQueue.queueNumber) }}</h6>
              </div>
            </div>
            
            <div class="mb-3">
              <h6 class="text-primary"><i class="fas fa-money-bill-wave me-2"></i>Total Amount: RM {{ calculateAmount(selectedQueue) }}</h6>
            </div>
            
            <div class="mt-3">
              <label class="form-label fw-bold">Payment Method:</label>
              <div class="d-flex gap-3 mt-2">
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
                    <i class="fas fa-money-bill-wave me-2 text-success"></i>Cash
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
                    <i class="fas fa-credit-card me-2 text-primary"></i>Card
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="vue-modal-footer">
          <button type="button" class="btn btn-secondary" @click="closePaymentModal">Cancel</button>
          <button 
            type="button" 
            class="btn btn-success fw-bold premium-accept-btn"
            @click="acceptPayment"
            :disabled="!paymentMethod || processing"
          >
            <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
            <i v-if="!processing" class="fas fa-check me-2"></i>
            {{ processing ? 'Processing...' : 'Accept Payment' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Simple Vue Modal (No Bootstrap JS) -->
  <div v-if="showMedicinesModal" class="vue-modal-overlay" @click="closeMedicinesModal">
    <div class="vue-modal-dialog" @click.stop>
      <div class="vue-modal-content">
        <div class="vue-modal-header">
          <h5 class="vue-modal-title">
            <i class="fas fa-pills me-2"></i>Prescribed Medicines
          </h5>
          <button type="button" class="vue-btn-close" @click="closeMedicinesModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="vue-modal-body">
          <div v-if="selectedQueue">
            <div class="row mb-3">
              <div class="col-md-6">
                <h6><i class="fas fa-user me-2"></i>Patient: {{ getSelectedQueuePatientName() }}</h6>
              </div>
              <div class="col-md-6">
                <h6><i class="fas fa-list-ol me-2"></i>Queue: {{ formatQueueNumber(selectedQueue.queueNumber) }}</h6>
              </div>
            </div>
            
            <div v-if="medicinesList && medicinesList.length > 0">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Medicine</th>
                      <th>Dosage</th>
                      <th>Frequency</th>
                      <th>Duration</th>
                      <th>Instructions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(medicine, index) in medicinesList" :key="index">
                      <td>
                        <strong>{{ medicine.name || medicine.medicationName || 'Unknown Medicine' }}</strong>
                        <br>
                        <small class="text-muted">{{ medicine.unitType || '' }}</small>
                      </td>
                      <td>{{ medicine.dosage || '-' }}</td>
                      <td>{{ medicine.frequency || '-' }}</td>
                      <td>{{ medicine.duration || '-' }}</td>
                      <td>
                        <small>{{ medicine.instructions || '-' }}</small>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>For Clinic Assistant:</strong> Please prepare the above medicines for this patient.
              </div>
            </div>
            
            <div v-else>
              <div class="text-center text-muted py-4">
                <i class="fas fa-pills fa-3x mb-3"></i>
                <p>No medicines prescribed for this patient.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="vue-modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeMedicinesModal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';
import { makeProtectedRequest, makeNavigationRequest, cancelAllRequests } from '../../utils/requestManager.js';
import timezoneUtils from '../../utils/timezoneUtils.js';

export default {
  name: 'QueueManagement',
  emits: ['patientAdded', 'patientUpdated', 'patientDeleted', 'loginSuccess'],
  data() {
    return {
      queueList: [],
      patients: [],
      doctors: [],
      selectedDate: '',
      selectedStatus: 'all', // Default to show all statuses
      selectedQueue: null,
      paymentAmount: 0,
      paymentMethod: '',
      processing: false,
      isLoading: false,
      isDoctorLoading: false,
      isPaymentProcessing: false,
      isRefreshing: false,
      
      // Pagination
      currentPage: 1,
      pageSize: 50,
      totalPages: 1,
      
      // SSE connection management
      eventSource: null,
      reconnectAttempts: 0,
      maxReconnectAttempts: 5,
      reconnectDelay: 1000,
      
      // Modal instances
      showPaymentModal: false,
      showMedicinesModal: false,
      
      // Medicines data
      medicinesList: [],
      
      // Timezone utilities
      timezoneUtils: timezoneUtils
    };
  },
  computed: {
    currentQueue() {
      return this.queueList.find(queue => queue.status === 'in_consultation');
    }
  },
  created() {
    this.selectedDate = this.getTodayInMYT();
    this.loadData();
    
    // Check for refresh parameter in URL to force immediate data refresh
    if (this.$route.query.refresh) {
      console.log('🔄 Refresh parameter detected, forcing queue data refresh');
      setTimeout(() => {
        this.manualRefresh();
      }, 500); // Small delay to allow initial load to complete
    }
  },
  mounted() {
    // Initialize SSE connection with better error handling
    this.initializeSSE();
    
    // Set up beforeunload handler
    window.addEventListener('beforeunload', this.handleBeforeUnload);
    
    // Add keyboard listener for ESC key
    window.addEventListener('keydown', this.handleKeydown);
  },
  activated() {
    // Refresh data when component is activated (for keep-alive)
    this.loadData();
  },
  beforeUnmount() {
    this.cleanup();
  },
  methods: {
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      // Ensure string
      queueNumber = queueNumber.toString();
      // Pad to 4 digits (e.g., 8001 -> 8001, 801 -> 0801)
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    async loadData() {
      if (this.isLoading) {
        console.log('⏩ Data loading already in progress, skipping duplicate request');
        return;
      }
      
      console.log('🔄 Loading queue management data...');
      
      // Prioritize queue list loading for immediate UI feedback
      // Load other data in background to avoid blocking the main view
      await this.loadQueueList();
      
      // Load other data in parallel without blocking queue display
      Promise.all([
        this.loadPatients(),
        this.loadDoctors()
      ]).catch(error => {
        console.warn('⚠️ Background data loading failed:', error);
      });
    },

    async loadPatients() {
      try {
        const response = await makeNavigationRequest(
          'load-patients-queue',
          async (signal) => {
            return await axios.get('/api/patients', { 
              signal,
              params: {
                limit: 50, // Reduced limit for faster initial load
                fields: 'id,name,nric' // Only essential fields for dropdown
              }
            });
          },
          {
            timeout: 8000 // Reduced timeout for background loading
          }
        );
        
        this.patients = response.data.data || response.data;
        console.log('✅ Patients loaded for queue management');
        
      } catch (error) {
        if (error.message.includes('cancelled') || error.message.includes('throttled')) {
          console.log('⏩ Patient loading skipped:', error.message);
          return;
        }
        
        console.error('❌ Error loading patients:', error);
        // Don't show error toast for background loading failures
        console.warn('Patient data loading failed - queue management will work without patient list');
      }
    },

    async loadDoctors() {
      // Skip loading if doctors are already loaded and not empty
      if (this.doctors && this.doctors.length > 0) {
        console.log('⏩ Doctors already loaded, skipping API call');
        return;
      }
      
      try {
        this.isDoctorLoading = true;
        
        const response = await makeNavigationRequest(
          'load-doctors-queue',
          async (signal) => {
            return await axios.get('/api/doctors', { signal });
          },
          {
            timeout: 10000 // Increased timeout
          }
        );
        
        this.doctors = response.data;
        console.log('✅ Doctors loaded for queue management');
        
      } catch (error) {
        // Handle cancellation gracefully without logging as error
        if (error.name === 'CanceledError' || 
            error.message?.includes('cancelled') || 
            error.message?.includes('canceled') ||
            error.message?.includes('throttled')) {
          console.log('⏩ Doctor loading skipped (cancelled/throttled)');
          return;
        }
        
        // Only log actual errors
        console.error('❌ Error loading doctors:', error);
        this.$toast?.error?.('Failed to load doctors data');
      } finally {
        this.isDoctorLoading = false;
      }
    },

    async loadQueueList() {
      if (this.isLoading) {
        console.log('⏩ Queue list loading already in progress');
        return;
      }

      try {
        this.isLoading = true;
        this.error = null;

        console.log('📋 Loading queue list for date:', this.selectedDate, 'status:', this.selectedStatus);

        // Use high priority for user-initiated requests with longer timeout
        const response = await makeNavigationRequest(
          `queue-list-${this.selectedDate}-${this.selectedStatus}`,
          async (signal) => {
            return await axios.get('/api/queue', {
              params: {
                date: this.selectedDate,
                status: this.selectedStatus,
                page: this.currentPage,
                limit: this.pageSize,
                _t: Date.now() // Cache-busting timestamp
              },
              signal,
              timeout: 15000 // Increased timeout for complex queries
            });
          },
          {
            priority: 'high', // High priority for user interactions
            timeout: 15000, // Increased timeout
            maxRetries: 2, // Allow more retries
            skipThrottle: false // Allow some throttling even for high priority
          }
        );

        // Filter and organize queue data to show all relevant statuses
        let queueData = Array.isArray(response.data) ? response.data : (response.data.data || []);
        
        console.log('🔍 Debug - Raw queue data:', queueData);
        console.log('🔍 Debug - Selected date:', this.selectedDate);
        console.log('🔍 Debug - Selected status:', this.selectedStatus);
        
        // Show all relevant statuses for today's activities
        queueData = queueData.filter(queue => {
          // Check if queue is for the selected date
          const isToday = queue.queueDateTime && queue.queueDateTime.startsWith(this.selectedDate);
          
          console.log('🔍 Debug - Queue item:', {
            id: queue.id,
            queueDateTime: queue.queueDateTime,
            status: queue.status,
            isToday: isToday,
            selectedDate: this.selectedDate
          });
          
          // If a specific status is selected, filter by that status
          if (this.selectedStatus !== 'all') {
            return isToday && queue.status === this.selectedStatus;
          }
          
          // Otherwise, show all statuses that are relevant for daily operations:
          // - waiting: patients waiting to be seen
          // - in_consultation: patients currently being seen
          // - completed_consultation: patients who finished consultation but need payment
          // - completed: patients who are fully done (for reference)
          const relevantStatuses = [
            'waiting', 
            'in_consultation', 
            'completed_consultation', 
            'completed'
          ];
          
          return isToday && relevantStatuses.includes(queue.status);
        });

        this.queueList = queueData;
        this.totalPages = Math.max(1, Math.ceil((response.data.total || queueData.length) / this.pageSize));

        console.log('✅ Queue list loaded successfully:', {
          count: this.queueList.length,
          date: this.selectedDate,
          status: this.selectedStatus,
          filteredForToday: this.queueList.filter(q => q.queueDateTime?.startsWith(this.selectedDate)).length
        });

      } catch (error) {
        // Enhanced error handling with specific error types
        if (error.circuitBreakerOpen) {
          console.log('🔌 Circuit breaker open for queue list - using cached data if available');
          this.error = 'Service temporarily unavailable. Using cached data.';
          // Try to use any cached data
          return;
        }
        
        if (error.message.includes('throttled')) {
          console.log('⏸️ Queue list request throttled');
          this.error = 'Too many requests. Please wait a moment.';
          return;
        }

        if (error.name === 'TimeoutError' || error.message.includes('timeout')) {
          console.error('⏰ Queue list request timed out');
          this.error = 'Request timed out. The server may be overloaded.';
        } else if (error.response?.status === 429) {
          console.error('🚫 Rate limited');
          this.error = 'Too many requests. Please wait before trying again.';
        } else if (error.response?.status >= 500) {
          console.error('🔥 Server error');
          this.error = 'Server error occurred. Please try again in a moment.';
        } else {
          console.error('❌ Error loading queue list:', error);
          this.error = 'Failed to load queue list. Please try again.';
        }
        
        this.queueList = [];
      } finally {
        this.isLoading = false;
      }
    },

    async manualRefresh() {
      if (this.isLoading || this.isRefreshing) {
        console.log('⏩ Manual refresh skipped - already loading');
        this.$toast?.warning?.('Already refreshing, please wait...');
        return;
      }
      
      this.isRefreshing = true;
      console.log('🔄 Manual refresh triggered');
      
      // Show user feedback
      this.$toast?.info?.('Refreshing queue data...');
      
      try {
        // Cancel any existing queue requests to prevent conflicts
        cancelAllRequests();
        
        // Wait a moment for cancellations to process
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Use a unique key with timestamp to avoid conflicts
        const refreshKey = `manual-refresh-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        // Force refresh by clearing cache and using high priority
        await makeNavigationRequest(
          refreshKey,
          async (signal) => {
            return await axios.get('/api/queue', {
              params: {
                date: this.selectedDate,
                status: this.selectedStatus,
                _refresh: Date.now() // Cache busting
              },
              signal,
              timeout: 15000 // Longer timeout for manual refresh
            });
          },
          {
            priority: 'high',
            skipThrottle: true, // Skip throttling for manual refresh
            skipDeduplication: true, // Skip deduplication for manual refresh
            timeout: 15000,
            maxRetries: 1 // Reduced retries for manual actions
          }
        );
        
        // Reload the list after successful refresh
        await this.loadQueueList();
        this.$toast?.success?.('Queue data refreshed successfully');
        
      } catch (error) {
        console.error('❌ Manual refresh failed:', error);
        
        if (error.message?.includes('cancelled') || error.message?.includes('canceled')) {
          console.log('Manual refresh was cancelled, probably due to navigation or duplicate request');
          this.$toast?.warning?.('Refresh cancelled. Please try again.');
        } else if (error.circuitBreakerOpen) {
          this.$toast?.warning?.('Service temporarily unavailable. Please try again in a moment.');
        } else if (error.response?.status === 429) {
          this.$toast?.warning?.('Too many requests. Please wait before refreshing again.');
        } else if (error.name === 'TimeoutError') {
          this.$toast?.error?.('Refresh timed out. The server may be overloaded. Please try again.');
        } else {
          this.$toast?.error?.('Failed to refresh queue data. Please check your connection.');
        }
      } finally {
        this.isRefreshing = false;
      }
    },

    async acceptPayment() {
      if (!this.paymentMethod || !this.selectedQueue) {
        this.$toast?.error?.('Please select a payment method');
        return;
      }

      if (this.processing) {
        console.log('⏩ Payment processing already in progress');
        return;
      }

      try {
        this.processing = true;
        
        await makeProtectedRequest(
          `process-payment-${this.selectedQueue.id}`,
          async (signal) => {
            return await axios.post(`/api/queue/${this.selectedQueue.id}/payment`, {
              amount: this.calculateAmount(this.selectedQueue),
              paymentMethod: this.paymentMethod
            }, { signal });
          },
          {
            throttleMs: 1000,    // Prevent rapid payment processing
            timeout: 20000,      // Longer timeout for payment processing
            maxRetries: 1,       // Only retry once for payments
            skipThrottle: false  // Always respect throttling for payments
          }
        );

        console.log('✅ Payment processed successfully');
        this.$toast?.success?.(`Payment of RM ${this.calculateAmount(this.selectedQueue)} received via ${this.paymentMethod}`);
        
        // Update the queue item in the list
        const queueIndex = this.queueList.findIndex(q => q.id === this.selectedQueue.id);
        if (queueIndex !== -1) {
          this.queueList[queueIndex].isPaid = true;
          this.queueList[queueIndex].paidAt = new Date().toISOString();
        }
        
        // Notify other components about payment
        window.dispatchEvent(new CustomEvent('paymentProcessed', {
          detail: {
            queueId: this.selectedQueue.id,
            amount: this.calculateAmount(this.selectedQueue),
            paymentMethod: this.paymentMethod
          }
        }));
        
        // Cross-tab communication via localStorage
        localStorage.setItem('paymentsUpdated', Date.now().toString());
        
        // Close Vue modal and reset data
        this.closePaymentModal();
        
        // Refresh queue list
        await this.loadQueueList();
        
      } catch (error) {
        if (error.message.includes('throttled')) {
          this.$toast?.warning?.('Please wait before processing another payment');
          return;
        }
        
        console.error('❌ Payment processing failed:', error);
        
        if (error.response?.status === 409) {
          this.$toast?.error?.('Payment has already been processed for this queue');
        } else if (error.response?.status === 400) {
          this.$toast?.error?.('Invalid payment amount or method');
        } else {
          this.$toast?.error?.('Payment processing failed. Please try again.');
        }
      } finally {
        this.processing = false;
      }
    },

    async updateStatus(queueId, newStatus) {
      try {
        await makeProtectedRequest(
          `update-queue-status-${queueId}`,
          async (signal) => {
            return await axios.put(`/api/queue/${queueId}/status`, {
              status: newStatus
            }, { signal });
          },
          {
            throttleMs: 500,
            timeout: 10000,
            maxRetries: 1
          }
        );
        
        console.log(`✅ Queue ${queueId} status updated to ${newStatus}`);
        
        // Refresh queue list to reflect changes
        await this.loadQueueList();
        
      } catch (error) {
        console.error('❌ Error updating queue status:', error);
        this.$toast?.error?.('Failed to update queue status');
      }
    },

    initializeSSE() {
      // Close existing connection
      if (this.eventSource) {
        this.eventSource.close();
      }

      try {
        console.log('🔌 Initializing SSE connection for queue updates...');
        this.eventSource = new EventSource('/api/sse/queue-updates');
        
        this.eventSource.onopen = () => {
          console.log('✅ SSE connection established');
          this.reconnectAttempts = 0;
          this.$toast?.success?.('Real-time updates connected', { timeout: 2000 });
        };
        
        this.eventSource.onmessage = (event) => {
          try {
            const queueData = JSON.parse(event.data);
            console.log('📡 SSE queue update received:', queueData);
            this.handleQueueUpdate(queueData);
          } catch (error) {
            console.error('❌ Error parsing SSE data:', error);
          }
        };
        
        this.eventSource.onerror = (error) => {
          console.error('❌ SSE connection error:', error);
          
          if (this.eventSource.readyState === EventSource.CLOSED) {
            this.handleSSEReconnect();
          }
        };
        
      } catch (error) {
        console.error('❌ Failed to initialize SSE:', error);
        this.$toast?.warning?.('Real-time updates unavailable');
      }
    },

    handleSSEReconnect() {
      if (this.reconnectAttempts >= this.maxReconnectAttempts) {
        console.warn('⚠️ Max SSE reconnection attempts reached');
        this.$toast?.warning?.('Real-time updates disconnected. Please refresh manually.');
        return;
      }

      this.reconnectAttempts++;
      const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1); // Exponential backoff
      
      console.log(`🔄 Attempting SSE reconnection ${this.reconnectAttempts}/${this.maxReconnectAttempts} in ${delay}ms`);
      
      setTimeout(() => {
        this.initializeSSE();
      }, delay);
    },

    // Component cleanup
    cleanup() {
      console.log('🧹 Cleaning up QueueManagement component...');
      
      // Close SSE connection
      if (this.eventSource) {
        this.eventSource.close();
        this.eventSource = null;
      }
      
      // Cancel all pending requests
      cancelAllRequests();
      
      // Remove event listeners
      window.removeEventListener('beforeunload', this.handleBeforeUnload);
      window.removeEventListener('keydown', this.handleKeydown);
      
      console.log('✅ QueueManagement cleanup completed');
    },

    handleBeforeUnload() {
      this.cleanup();
    },

    getTodayInMYT() {
      // Get actual current date in Malaysia timezone
      const mytTime = this.timezoneUtils.nowInMalaysia();
    
      const year = mytTime.getFullYear();
      const month = String(mytTime.getMonth() + 1).padStart(2, '0');
      const day = String(mytTime.getDate()).padStart(2, '0');
      
      const dateString = `${year}-${month}-${day}`;
      console.log('🕐 Current MYT date:', dateString, 'Local time:', new Date().toLocaleString(), 'MYT time:', mytTime.toLocaleString());
      return dateString;
    },
    setToday() {
      this.selectedDate = this.getTodayInMYT();
      this.loadQueueList();
    },
    processPayment(queue) {
      console.log('processPayment called with queue:', queue);
      this.selectedQueue = queue;
      this.paymentMethod = '';
      this.processing = false;
      
      // Show Vue modal (no Bootstrap JS)
      this.showPaymentModal = true;
    },
    
    calculateAmount(queue) {
      // Always use the total payment set by the doctor (totalAmount or amount)
      if (!queue) return 0;
      if (typeof queue.totalAmount !== 'undefined' && queue.totalAmount !== null) {
        return parseFloat(queue.totalAmount);
      }
      if (typeof queue.amount !== 'undefined' && queue.amount !== null) {
        return parseFloat(queue.amount);
      }
      // If missing, show 0 and warn
      console.warn('No total payment amount found for queue', queue);
      return 0;
    },
    
    formatStatus(status) {
      const statusMap = {
        'waiting': 'Waiting',
        'in_consultation': 'In Consultation',
        'completed_consultation': 'Completed Consultation',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
      };
      return statusMap[status] || status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ');
    },
    getStatusBadgeClass(status) {
      const statusClasses = {
        'waiting': 'badge bg-info',
        'in_consultation': 'badge bg-warning text-dark',
        'completed_consultation': 'badge bg-primary',
        'completed': 'badge bg-success',
        'cancelled': 'badge bg-danger'
      };
      return statusClasses[status] || 'badge bg-secondary';
    },
    formatDateTime(datetime) {
      if (!datetime) return '';
      const date = new Date(datetime);
      return date.toLocaleString('en-GB', {
        timeZone: this.timezoneUtils.MALAYSIA_TIMEZONE,
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
      });
    },
    getPatientName(patientId) {
      const patient = this.patients.find(p => p.id === patientId);
      if (!patient) return 'Unknown Patient';
      return this.getFormattedPatientName(patient);
    },
    getFormattedPatientName(patient) {
      if (!patient) return 'Unknown Patient';
      if (patient.displayName && patient.displayName.trim() !== '') {
        return patient.displayName;
      }
      if (patient.name && patient.name.trim() !== '') {
        return patient.name;
      }
      return 'Unknown Patient';
    },
    getFormattedDoctorName(doctor) {
      if (!doctor) return 'Unknown Doctor';
      if (doctor.displayName && doctor.displayName.trim() !== '') {
        return doctor.displayName;
      }
      if (doctor.name && doctor.name.trim() !== '') {
        return doctor.name;
      }
      if (doctor.firstName || doctor.lastName) {
        return `Dr. ${doctor.firstName || ''} ${doctor.lastName || ''}`.trim();
      }
      return 'Unknown Doctor';
    },
    
    // Enhanced SSE queue update handler for instant updates
    handleQueueUpdate(queueData) {
      console.log('📡 SSE queue update received:', queueData);
      
      // Always refresh the list for status changes to ensure immediate updates
      if (queueData.status && ['completed_consultation', 'completed', 'in_consultation'].includes(queueData.status)) {
        console.log('🔄 Status change detected, refreshing queue list immediately');
        this.loadQueueList();
        return;
      }
      
      // If this is a group consultation or patientCount is present, always refresh the list
      if (queueData.isGroupConsultation || typeof queueData.patientCount !== 'undefined') {
        this.loadQueueList();
        return;
      }
      
      const queueIndex = this.queueList.findIndex(q => q.id === queueData.id);
      if (queueIndex !== -1) {
        // Update the specific queue item
        this.queueList[queueIndex] = {
          ...this.queueList[queueIndex],
          status: queueData.status,
          patient: queueData.patient,
          doctor: queueData.doctor,
          queueDateTime: queueData.queueDateTime,
          hasMedicines: queueData.hasMedicines,
          isPaid: queueData.isPaid
        };
      } else {
        // If queue not found, refresh the entire list
        this.loadQueueList();
      }
    },
    
    // View medicines for a queue
    async viewMedicines(queue) {
      console.log('🔍 Viewing medicines for queue:', queue);
      this.selectedQueue = queue;
      this.medicinesList = [];
      
      try {
        // Show loading state
        this.showMedicinesModal = true;
        
        // Fetch medicines based on consultation ID or queue ID
        let response;
        if (queue.consultationId) {
          response = await axios.get(`/api/consultations/${queue.consultationId}/medications`);
        } else {
          // Fallback to queue-based lookup
          response = await axios.get(`/api/queue/${queue.id}/medications`);
        }
        
        this.medicinesList = response.data || [];
        console.log('💊 Fetched medicines:', this.medicinesList);
        
        // Show success message if medicines found
        if (this.medicinesList.length > 0) {
          this.$toast?.success?.(`Found ${this.medicinesList.length} prescribed medicine(s)`);
        } else {
          this.$toast?.info?.('No medicines prescribed for this patient');
        }
        
      } catch (error) {
        console.error('❌ Error fetching medicines:', error);
        this.medicinesList = [];
        
        // Provide specific error messages
        if (error.response?.status === 404) {
          this.$toast?.warning?.('No consultation or medicines found for this queue');
        } else if (error.response?.status === 429) {
          this.$toast?.warning?.('Too many requests. Please wait before trying again.');
        } else if (error.response?.status >= 500) {
          this.$toast?.error?.('Server error occurred. Please try again later.');
        } else {
          this.$toast?.error?.('Failed to load medicines. Please try again.');
        }
        
        // Still show modal even if there's an error
        this.showMedicinesModal = true;
      }
    },
    
    // Close medicines modal
    closeMedicinesModal() {
      this.showMedicinesModal = false;
      // Clean up data
      this.selectedQueue = null;
      this.medicinesList = [];
    },
    
    // Close payment modal
    closePaymentModal() {
      this.showPaymentModal = false;
      // Clean up data
      this.selectedQueue = null;
      this.paymentAmount = 0;
      this.paymentMethod = '';
      this.processing = false;
    },
    
    // Handle keyboard events
    handleKeydown(event) {
      if (event.key === 'Escape') {
        if (this.showMedicinesModal) {
          this.closeMedicinesModal();
        } else if (this.showPaymentModal) {
          this.closePaymentModal();
        }
      }
    },
    
    // Handle body scroll locking for modals
    handleBodyScrollLock(isVisible) {
      if (isVisible || this.showMedicinesModal || this.showPaymentModal) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.removeProperty('overflow');
      }
    },
    
    // Get patient name for selected queue (handles both individual and group consultations)
    getSelectedQueuePatientName() {
      if (!this.selectedQueue) return 'Unknown Patient';
      
      if (this.selectedQueue.isGroupConsultation) {
        if (this.selectedQueue.mainPatient) {
          return `${this.selectedQueue.mainPatient.name} (Group of ${this.selectedQueue.patientCount || 0})`;
        }
        return `Group Consultation (${this.selectedQueue.patientCount || 0} patients)`;
      }
      
      return this.selectedQueue.patient?.name || 'Unknown Patient';
    },
    
    // Print medicines list
    printMedicinesList() {
      const patientName = this.getSelectedQueuePatientName();
      const queueNumber = this.formatQueueNumber(this.selectedQueue.queueNumber);
      const today = new Date().toLocaleDateString('en-GB', {
        timeZone: 'Asia/Kuala_Lumpur',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
      
      let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <title>Medicines List - ${patientName}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .clinic-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
            .patient-info { margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            @media print { body { margin: 0; } }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="clinic-name">KLINIK HIDUPsihat</div>
            <div>Medicines List</div>
          </div>
          
          <div class="patient-info">
            <p><strong>Patient:</strong> ${patientName}</p>
            <p><strong>Queue Number:</strong> ${queueNumber}</p>
            <p><strong>Date:</strong> ${today}</p>
          </div>
          
          <table>
            <thead>
              <tr>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Instructions</th>
              </tr>
            </thead>
            <tbody>
      `;
      
      this.medicinesList.forEach(medicine => {
        printContent += `
          <tr>
            <td>
              <strong>${medicine.name || medicine.medicationName || 'Unknown Medicine'}</strong>
              ${medicine.unitType ? `<br><small>${medicine.unitType}</small>` : ''}
            </td>
            <td>${medicine.dosage || '-'}</td>
            <td>${medicine.frequency || '-'}</td>
            <td>${medicine.duration || '-'}</td>
            <td>${medicine.instructions || '-'}</td>
          </tr>
        `;
      });
      
      printContent += `
            </tbody>
          </table>
          
          <div class="footer">
            <p>Prepared by: Clinic Assistant</p>
                            <p>Time: ${this.timezoneUtils.formatDateMalaysia(new Date())}</p>
          </div>
        </body>
        </html>
      `;
      
      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write(printContent);
      printWindow.document.close();
      printWindow.focus();
      
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 300);
    }
  },
  watch: {
    selectedDate() {
      console.log('📅 Date changed to:', this.selectedDate);
      this.loadQueueList();
    },
    selectedStatus() {
      console.log('🔍 Status filter changed to:', this.selectedStatus);
      this.loadQueueList();
    },
    '$route.query.refresh'() {
      // Refresh queue list when refresh parameter changes
      console.log('🔄 Refresh parameter detected - reloading queue list');
      this.loadQueueList();
    },
    showMedicinesModal(isVisible) {
      // Handle body scroll locking
      this.handleBodyScrollLock(isVisible);
    },
    showPaymentModal(isVisible) {
      // Handle body scroll locking
      this.handleBodyScrollLock(isVisible);
    }
  }
};
</script>

<style scoped>
.queue-management {
  padding: 20px;
}

.current-queue {
  padding: 20px;
  background-color: #f8f9fa;
  border-radius: 5px;
}

.badge {
  font-size: 0.9em;
  padding: 0.5em 0.7em;
}

.table-active {
  background-color: rgba(0, 123, 255, 0.1) !important;
}

/* Medicines button styling */
.medicines-btn {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
  border: none !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.medicines-btn:hover {
  background: linear-gradient(135deg, #0a58ca 0%, #084298 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.medicines-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.medicines-btn i {
  font-size: 1.1em;
  margin-right: 0.3rem;
}

/* Pulse animation for medicines button */
.medicines-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}

.medicines-btn:hover::before {
  width: 100px;
  height: 100px;
}

/* Premium Medicines button styling */
.premium-medicines-btn {
  background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%) !important;
  border: none !important;
  border-radius: 10px !important;
  padding: 0.5rem 1rem !important;
  box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4) !important;
  transition: all 0.3s ease !important;
  color: white !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
  position: relative !important;
  overflow: hidden !important;
}

.premium-medicines-btn:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.premium-medicines-btn:hover:before {
  left: 100%;
}

.premium-medicines-btn:hover {
  background: linear-gradient(135deg, #5a2d91 0%, #432874 100%) !important;
  transform: translateY(-2px) scale(1.05) !important;
  box-shadow: 0 6px 20px rgba(111, 66, 193, 0.6) !important;
  color: white !important;
}

.premium-medicines-btn:active {
  transform: translateY(0) scale(1) !important;
  box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4) !important;
}

/* Premium Payment button styling */
.premium-payment-btn {
  background: linear-gradient(135deg, #20c997 0%, #17a085 100%) !important;
  border: none !important;
  border-radius: 10px !important;
  padding: 0.5rem 1rem !important;
  box-shadow: 0 4px 12px rgba(32, 201, 151, 0.4) !important;
  transition: all 0.3s ease !important;
  color: white !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
  position: relative !important;
  overflow: hidden !important;
}

.premium-payment-btn:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.premium-payment-btn:hover:before {
  left: 100%;
}

.premium-payment-btn:hover {
  background: linear-gradient(135deg, #17a085 0%, #138f7a 100%) !important;
  transform: translateY(-2px) scale(1.05) !important;
  box-shadow: 0 6px 20px rgba(32, 201, 151, 0.6) !important;
  color: white !important;
}

.premium-payment-btn:active {
  transform: translateY(0) scale(1) !important;
  box-shadow: 0 4px 12px rgba(32, 201, 151, 0.4) !important;
}

/* Pulse animation for premium buttons */
@keyframes premium-pulse {
  0% {
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4);
  }
  50% {
    box-shadow: 0 4px 20px rgba(111, 66, 193, 0.8);
  }
  100% {
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4);
  }
}

.premium-medicines-btn:focus,
.premium-payment-btn:focus {
  animation: premium-pulse 1s infinite;
}

/* Premium Accept Payment button styling */
.premium-accept-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
  border: none !important;
  border-radius: 8px !important;
  padding: 0.6rem 1.2rem !important;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4) !important;
  transition: all 0.3s ease !important;
  color: white !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
  position: relative !important;
  overflow: hidden !important;
}

.premium-accept-btn:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.premium-accept-btn:hover:before {
  left: 100%;
}

.premium-accept-btn:hover {
  background: linear-gradient(135deg, #20c997 0%, #17a085 100%) !important;
  transform: translateY(-2px) scale(1.05) !important;
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6) !important;
  color: white !important;
}

.premium-accept-btn:active {
  transform: translateY(0) scale(1) !important;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4) !important;
}

.premium-accept-btn:disabled {
  background: #6c757d !important;
  color: #fff !important;
  transform: none !important;
  box-shadow: none !important;
  cursor: not-allowed !important;
}

/* Modal fallback styles */
.modal {
  z-index: 1050;
}

.modal.show {
  display: block !important;
}

/* Enhanced modal styling */
.modal-content {
  border: none;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 2px solid #dee2e6;
  border-radius: 10px 10px 0 0;
}

.modal-title i {
  color: #0d6efd;
}

/* Backdrop cleanup styles */
.modal-backdrop {
  z-index: 1040;
}

/* Table styling in modal */
.modal .table-striped > tbody > tr:nth-of-type(odd) > td {
  background-color: rgba(0, 0, 0, 0.05);
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 1.75rem auto;
  max-width: 500px;
}

.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 0.375rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.modal-header {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1rem;
  border-bottom: 1px solid #dee2e6;
  border-top-left-radius: calc(0.375rem - 1px);
  border-top-right-radius: calc(0.375rem - 1px);
}

.modal-title {
  margin-bottom: 0;
  line-height: 1.5;
  font-size: 1.25rem;
  font-weight: 500;
}

.btn-close {
  box-sizing: content-box;
  width: 1em;
  height: 1em;
  padding: 0.25em 0.25em;
  color: #000;
  background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='m.235.757 14.008 14.008c.395.395 1.036.395 1.431 0 .396-.395.396-1.036 0-1.431L1.666.326C1.271-.07.63-.07.235.326c-.395.395-.395 1.036 0 1.431z'/%3e%3cpath d='m14.243.757-14.008 14.008c-.395.395-.395 1.036 0 1.431.396.396 1.037.396 1.432 0L15.674 2.188c.395-.395.395-1.036 0-1.431-.396-.396-1.037-.396-1.431 0z'/%3e%3c/svg%3e") center/1em auto no-repeat;
  border: 0;
  border-radius: 0.375rem;
  opacity: 0.5;
  cursor: pointer;
}

.btn-close:hover {
  opacity: 0.75;
}

.modal-body {
  position: relative;
  flex: 1 1 auto;
  padding: 1rem;
}

.modal-footer {
  display: flex;
  flex-wrap: wrap;
  flex-shrink: 0;
  align-items: center;
  justify-content: flex-end;
  padding: 0.75rem;
  border-top: 1px solid #dee2e6;
  border-bottom-right-radius: calc(0.375rem - 1px);
  border-bottom-left-radius: calc(0.375rem - 1px);
}

.modal-footer > * {
  margin: 0.25rem;
}

/* Button styles */
.btn {
  display: inline-block;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  text-align: center;
  text-decoration: none;
  vertical-align: middle;
  cursor: pointer;
  user-select: none;
  background-color: transparent;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  border-radius: 0.375rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn:hover {
  color: #212529;
}

.btn:focus {
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn:disabled {
  pointer-events: none;
  opacity: 0.65;
}

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  color: #fff;
  background-color: #5c636a;
  border-color: #565e64;
}

.btn-secondary:focus {
  color: #fff;
  background-color: #5c636a;
  border-color: #565e64;
  box-shadow: 0 0 0 0.25rem rgba(130, 138, 145, 0.5);
}

.btn-success {
  color: #fff;
  background-color: #198754;
  border-color: #198754;
}

.btn-success:hover {
  color: #fff;
  background-color: #157347;
  border-color: #146c43;
}

.btn-success:focus {
  color: #fff;
  background-color: #157347;
  border-color: #146c43;
  box-shadow: 0 0 0 0.25rem rgba(60, 153, 110, 0.5);
}

/* Form styles */
.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-check {
  display: block;
  min-height: 1.5rem;
  padding-left: 1.5em;
  margin-bottom: 0.125rem;
}

.form-check-input {
  width: 1em;
  height: 1em;
  margin-top: 0.25em;
  margin-left: -1.5em;
  vertical-align: top;
  background-color: #fff;
  background-repeat: no-repeat;
  background-position: center;
  background-size: contain;
  border: 1px solid rgba(0, 0, 0, 0.25);
  appearance: none;
  color-adjust: exact;
}

.form-check-input[type="radio"] {
  border-radius: 50%;
}

.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.form-check-input:checked[type="radio"] {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
}

.form-check-label {
  color: #212529;
  cursor: pointer;
}

/* Spinner */
.spinner-border {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  vertical-align: -0.125em;
  border: 0.125em solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
  width: 0.875rem;
  height: 0.875rem;
  border-width: 0.125em;
}

@keyframes spinner-border {
  to {
    transform: rotate(360deg);
  }
}

/* Utility classes */
.d-flex {
  display: flex !important;
}

.gap-3 {
  gap: 1rem !important;
}

.mt-3 {
  margin-top: 1rem !important;
}

.me-2 {
  margin-right: 0.5rem !important;
}

/* Vue Modal Styles (No Bootstrap JS conflicts) */
.vue-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.2s ease-out;
}

.vue-modal-dialog {
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideIn 0.3s ease-out;
}

.vue-modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.vue-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 2px solid #dee2e6;
}

.vue-modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}

.vue-modal-title i {
  color: #0d6efd;
}

.vue-btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 50%;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  transition: all 0.2s ease;
}

.vue-btn-close:hover {
  background-color: #f8f9fa;
  color: #dc3545;
  transform: scale(1.1);
}

.vue-modal-body {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.vue-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .vue-modal-dialog {
    width: 95%;
    margin: 1rem;
  }
  
  .vue-modal-header,
  .vue-modal-body,
  .vue-modal-footer {
    padding: 1rem;
  }
  
  .vue-modal-title {
    font-size: 1.1rem;
  }
}
</style>
