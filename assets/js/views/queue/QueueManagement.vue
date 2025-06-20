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
          <button @click="setToday" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-calendar-day"></i> Today
          </button>
          <button @click="manualRefresh" class="btn btn-outline-success btn-sm">
            <i class="fas fa-sync-alt"></i> Refresh
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
                      Group ({{ queue.totalPatients || queue.groupMembers?.length || 0 }} patients)
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
                  <div v-if="queue.status === 'completed_consultation'">
                    <span v-if="queue.isPaid" class="badge bg-success">
                      <i class="fas fa-check me-1"></i>Paid
                    </span>
                    <button v-else class="btn btn-sm btn-warning" @click="processPayment(queue)">
                      <i class="fas fa-dollar-sign me-1"></i>Process Payment
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

  <!-- Payment Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Process Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="selectedQueue">
            <h6>Patient: {{ selectedQueue.patient?.name }}</h6>
            <h6>Queue Number: {{ formatQueueNumber(selectedQueue.queueNumber) }}</h6>
            <h6>Total Amount: RM {{ calculateAmount(selectedQueue) }}</h6>
            
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
            @click="acceptPayment"
            :disabled="!paymentMethod || processing"
          >
            <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
            {{ processing ? 'Processing...' : 'Accept Payment' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';
import { getTodayInMYT } from '../../utils/dateUtils';

export default {
  name: 'QueueManagement',
  data() {

    return {
      patients: [],
      doctors: [],
      queueList: [],
      selectedDate: getTodayInMYT(), // Today's date in MYT
      newQueue: {
        patientId: '',
        doctorId: ''
      },
      eventSource: null,
      refreshInterval: null,
      dateFilterTimeout: null, // Add debounce timeout
      currentQueueRequest: null, // Add request controller
      lastRefreshTime: 0, // Track last refresh to avoid rapid calls
      // Payment modal data
      selectedQueue: null,
      paymentMethod: '',
      processing: false,
      paymentModal: null
    };
  },
  computed: {
    currentQueue() {
      return this.queueList.find(q => q.status === 'in_consultation');
    }
  },
  created() {
    this.loadData();
    // Refresh queue list every 30 seconds as backup
    this.refreshInterval = setInterval(this.loadQueueList, 30000);
    // Initialize real-time updates
    this.initializeSSE();
  },
  async mounted() {
    await this.loadData();
    this.initializeSSE();
    
    // Request notification permission for queue updates
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  },
  async activated() {
    // This will be called when returning to this component (if using keep-alive)
    console.log('Queue management component activated - refreshing data');
    await this.loadQueueList();
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
    if (this.eventSource) {
      this.eventSource.close();
    }
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
      await Promise.all([
        this.loadPatients(),
        this.loadDoctors(),
        this.loadQueueList()
      ]);
    },
    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        console.log('Raw patient API response:', response);
        
        // Check the data structure
        if (response.data && Array.isArray(response.data)) {
          this.patients = response.data;
          console.log('Sample patient item:', this.patients.length > 0 ? JSON.stringify(this.patients[0]) : 'No patients');
        } else {
          console.error('Unexpected patient data format:', response.data);
          this.patients = [];
        }
        
        if (this.patients.length === 0) {
          console.warn('No patients were loaded from the API');
        }
      } catch (error) {
        console.error('Error loading patients:', error);
        this.patients = [];
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        console.log('Raw doctor API response:', response);
        
        // Check the data structure
        if (response.data && Array.isArray(response.data)) {
          this.doctors = response.data;
          
          // Detailed logging for each doctor to see firstName and lastName fields
          this.doctors.forEach((doctor, index) => {
            console.log(`Doctor ${index + 1}:`, {
              id: doctor.id,
              firstName: doctor.firstName,
              lastName: doctor.lastName,
              specialization: doctor.specialization,
              raw: doctor
            });
          });
          
        } else {
          console.error('Unexpected doctor data format:', response.data);
          this.doctors = [];
        }
        
        if (this.doctors.length === 0) {
          console.warn('No doctors were loaded from the API');
        }
      } catch (error) {
        console.error('Error loading doctors:', error);
        this.doctors = [];
      }
    },
    async loadQueueList() {
      // Throttle rapid successive calls (minimum 500ms between calls)
      const now = Date.now();
      if (now - this.lastRefreshTime < 500) {
        console.log('⏩ Queue refresh throttled - too soon since last call');
        return;
      }
      this.lastRefreshTime = now;
      
      // Cancel previous request if still pending
      if (this.currentQueueRequest) {
        this.currentQueueRequest.abort();
      }
      
      // Create new AbortController for this request
      this.currentQueueRequest = new AbortController();
      
      try {
        console.log('Loading queue list for date:', this.selectedDate);
        const response = await axios.get(`/api/queue?date=${this.selectedDate}`, {
          signal: this.currentQueueRequest.signal
        });
        console.log('Queue API response:', response);
        
        if (response.data && Array.isArray(response.data)) {
          this.queueList = response.data;
          
          // Show success notification
          if (response.data.length > 0) {
            console.log(`✅ Loaded ${response.data.length} queue entries for ${this.selectedDate}`);
          } else {
            console.log(`ℹ️ No queue entries found for ${this.selectedDate}`);
          }
          
          // Log each queue item for debugging
          this.queueList.forEach((queue, index) => {
            console.log(`Queue ${index + 1}:`, {
              id: queue.id,
              queueNumber: queue.queueNumber,
              registrationNumber: queue.registrationNumber,
              patientName: queue.patient?.name,
              doctorName: queue.doctor?.name,
              status: queue.status,
              time: queue.time || queue.queueDateTime
            });
          });
        } else {
          console.error('Unexpected queue data format:', response.data);
          this.queueList = [];
        }
      } catch (error) {
        if (error.name === 'AbortError' || error.name === 'CanceledError') {
          console.log('⏹️ Queue request was cancelled');
          return;
        }
        if (error.code === 'ERR_CANCELED') {
          console.log('⏹️ Queue request was cancelled (axios)');
          return;
        }
        console.error('❌ Error loading queue:', error);
        // Only clear the queue list if it's a real error, not a cancellation
        if (error.response) {
          // Server responded with error status
          console.error('Server error:', error.response.status, error.response.data);
        } else if (error.request) {
          // Request was made but no response received
          console.error('Network error - no response received');
        } else {
          // Something else happened
          console.error('Request error:', error.message);
        }
        this.queueList = [];
      } finally {
        this.currentQueueRequest = null;
      }
    },
    
    getTodayInMYT() {
      // Get actual current date in Malaysia timezone
      const now = new Date();
      // Convert to MYT by adding 8 hours (MYT is UTC+8)
      const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
      const mytTime = new Date(utc + (8 * 3600000)); // Add 8 hours for MYT
      
      const year = mytTime.getFullYear();
      const month = String(mytTime.getMonth() + 1).padStart(2, '0');
      const day = String(mytTime.getDate()).padStart(2, '0');
      
      const dateString = `${year}-${month}-${day}`;
      console.log('🕐 Current MYT date:', dateString, 'Local time:', now.toLocaleString(), 'MYT time:', mytTime.toLocaleString());
      return dateString;
    },
    setToday() {
      this.selectedDate = getTodayInMYT();
      this.loadQueueList();
    },
    manualRefresh() {
      // Clear any pending debounced calls
      if (this.dateFilterTimeout) {
        clearTimeout(this.dateFilterTimeout);
        this.dateFilterTimeout = null;
      }
      // Immediately load queue list
      console.log('Manual queue refresh triggered');
      this.loadQueueList();
    },
    async addToQueue() {
      try {
        await axios.post('/api/queue', this.newQueue);
        this.newQueue = {
          patientId: '',
          doctorId: ''
        };
        this.loadQueueList();
      } catch (error) {
        console.error('Error adding to queue:', error);
        alert('Error adding to queue. Please try again.');
      }
    },
    processPayment(queue) {
      console.log('processPayment called with queue:', queue);
      this.selectedQueue = queue;
      this.paymentMethod = '';
      this.processing = false;
      
      // Initialize Bootstrap modal if not already done
      if (!this.paymentModal) {
        this.paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
      }
      this.paymentModal.show();
    },
    
    calculateAmount(queue) {
      // Default consultation fee if not specified
      return queue.consultationFee || queue.amount || 50;
    },
    

    
    async acceptPayment() {
      if (!this.paymentMethod || !this.selectedQueue) {
        alert('Please select a payment method');
        return;
      }

      this.processing = true;
      try {
        // Create payment record through the queue endpoint
        const response = await axios.post(`/api/queue/${this.selectedQueue.id}/payment`, {
          paymentMethod: this.paymentMethod,
          amount: this.calculateAmount(this.selectedQueue)
        });

        // Update the queue item in the list
        const queueIndex = this.queueList.findIndex(q => q.id === this.selectedQueue.id);
        if (queueIndex !== -1) {
          this.queueList[queueIndex].isPaid = true;
          this.queueList[queueIndex].paidAt = new Date().toISOString();
        }

        // Close payment modal
        this.paymentModal.hide();

        // Show success message
        alert(`Payment of RM ${this.calculateAmount(this.selectedQueue)} received via ${this.paymentMethod} for ${this.selectedQueue.patient?.name}`);

        // Refresh the queue list
        this.loadQueueList();

      } catch (error) {
        console.error('Error processing payment:', error);
        alert('Error processing payment. Please try again.');
      } finally {
        this.processing = false;
      }
    },
    async updateStatus(queueId, newStatus) {
      try {
        await axios.put(`/api/queue/${queueId}/status`, { status: newStatus });
        this.loadQueueList();
      } catch (error) {
        console.error('Error updating queue status:', error);
        alert('Error updating queue status. Please try again.');
      }
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
    initializeSSE() {
      // Skip SSE if not supported or in development
      if (!window.EventSource) {
        console.warn('EventSource not supported, skipping SSE initialization');
        return;
      }
      
      // Initialize Server-Sent Events for real-time queue updates
      try {
        // Close existing connection if any
        if (this.eventSource) {
          this.eventSource.close();
        }
        
        this.eventSource = new EventSource('/api/sse/queue-updates');
        
        this.eventSource.onmessage = (event) => {
          try {
            const update = JSON.parse(event.data);
            
            if (update.type === 'queue_status_update') {
              this.handleQueueUpdate(update.data);
            }
          } catch (error) {
            console.error('Error parsing SSE message:', error);
          }
        };
        
        this.eventSource.addEventListener('heartbeat', (event) => {
          // Just keep the connection alive, no action needed
          console.log('SSE heartbeat received');
        });
        
        this.eventSource.onerror = (error) => {
          console.warn('SSE connection error (this is expected in development):', error);
          
          // Don't attempt automatic reconnection to reduce console noise
          // The regular refresh interval will handle updates
          if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
          }
        };
        
        this.eventSource.onopen = () => {
          console.log('SSE connection established for queue updates');
        };
      } catch (error) {
        console.warn('SSE initialization failed (using fallback polling):', error);
      }
    },
    handleQueueUpdate(queueData) {
      // Find and update the specific queue item in the list
      const queueIndex = this.queueList.findIndex(q => q.id === queueData.id);
      
      if (queueIndex !== -1) {
        // Update existing queue item
        this.queueList[queueIndex] = {
          ...this.queueList[queueIndex],
          status: queueData.status,
          patient: queueData.patient,
          doctor: queueData.doctor,
          queueDateTime: queueData.queueDateTime
        };
        
        // Show notification
        this.showUpdateNotification(queueData);
      } else {
        // If queue item not found, only refresh if there's no pending request
        if (!this.currentQueueRequest) {
          console.log('Queue item not found, refreshing list...');
          this.loadQueueList();
        } else {
          console.log('Queue refresh skipped - request already in progress');
        }
      }
    },
    showUpdateNotification(queueData) {
      // Create a simple notification (you can enhance this with a proper notification library)
      const message = `Queue #${this.formatQueueNumber(queueData.queueNumber)} status updated to: ${this.formatStatus(queueData.status)}`;
      
      // Simple browser notification (optional)
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('Queue Update', {
          body: message,
          icon: '/favicon.ico',
          timeout: 3000
        });
      }
      
      // Console log for development
      console.log('Queue update:', message);
      
      // You could also add a toast notification here using a library like vue-toastification
    },
    formatDateTime(datetime) {
      if (!datetime) return '';
      const date = new Date(datetime);
      return date.toLocaleString('en-MY', {
        timeZone: 'Asia/Kuala_Lumpur',
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
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
    }
  },
  watch: {
    selectedDate() {
      // Clear existing timeout
      if (this.dateFilterTimeout) {
        clearTimeout(this.dateFilterTimeout);
      }
      
      // Set new timeout for 2 seconds
      this.dateFilterTimeout = setTimeout(() => {
        console.log('Queue date filter triggered after 2 second delay:', this.selectedDate);
        this.loadQueueList();
      }, 2000);
    },
    '$route.query.refresh'() {
      // Refresh queue list when refresh parameter changes
      console.log('Refresh parameter detected - reloading queue list');
      this.loadQueueList();
    }
  },
  async created() {
    await this.loadData();
    this.initializeSSE();
    
    // Request notification permission for queue updates
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  },
  async mounted() {
    await this.loadData();
    this.initializeSSE();
    
    // Request notification permission for queue updates
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  },
  async activated() {
    // This will be called when returning to this component (if using keep-alive)
    console.log('Queue management component activated - refreshing data');
    await this.loadQueueList();
  },
  beforeUnmount() {
    // Cleanup intervals
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
    
    // Cleanup SSE connection
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
    
    // Cleanup pending requests
    if (this.currentQueueRequest) {
      this.currentQueueRequest.abort();
      this.currentQueueRequest = null;
    }
    
    // Cleanup debounce timeout
    if (this.dateFilterTimeout) {
      clearTimeout(this.dateFilterTimeout);
      this.dateFilterTimeout = null;
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

/* Modal fallback styles */
.modal {
  z-index: 1050;
}

.modal.show {
  display: block !important;
}

.modal-backdrop {
  z-index: 1040;
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
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
</style>
