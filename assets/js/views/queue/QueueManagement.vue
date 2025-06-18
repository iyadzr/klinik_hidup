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
                  <div v-if="queue.status === 'completed'">
                    <span v-if="queue.isPaid" class="badge bg-success">
                      <i class="fas fa-check me-1"></i>Paid
                    </span>
                    <button v-else class="btn btn-sm btn-warning" @click="processPayment(queue)">
                      <i class="fas fa-dollar-sign me-1"></i>Process Payment
                    </button>
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
</template>

<script>
import axios from 'axios';

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
      currentQueueRequest: null // Add request controller
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
        if (error.name === 'AbortError') {
          console.log('⏹️ Queue request was cancelled');
          return;
        }
        console.error('❌ Error loading queue:', error);
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
    async processPayment(queue) {
      try {
        // Navigate to payment form with queue information
        this.$router.push({
          path: '/payments/form',
          query: {
            queueId: queue.id,
            patientId: queue.patient.id,
            amount: queue.consultationFee || 50 // Default consultation fee
          }
        });
      } catch (error) {
        console.error('Error processing payment:', error);
        alert('Error processing payment. Please try again.');
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
      return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ');
    },
    getStatusBadgeClass(status) {
      const classes = {
        waiting: 'badge bg-warning',
        in_consultation: 'badge bg-info',
        completed: 'badge bg-success',
        cancelled: 'badge bg-danger'
      };
      return classes[status] || 'badge bg-secondary';
    },
    initializeSSE() {
      // Initialize Server-Sent Events for real-time queue updates
      try {
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
          console.error('SSE connection error:', error);
          
          // Attempt to reconnect after 5 seconds
          setTimeout(() => {
            if (this.eventSource.readyState === EventSource.CLOSED) {
              console.log('Attempting to reconnect SSE...');
              this.initializeSSE();
            }
          }, 5000);
        };
        
        this.eventSource.onopen = () => {
          console.log('SSE connection established for queue updates');
        };
      } catch (error) {
        console.error('Failed to initialize SSE:', error);
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
        // If queue item not found, refresh the entire list
        this.loadQueueList();
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
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
    if (this.eventSource) {
      this.eventSource.close();
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
</style>
