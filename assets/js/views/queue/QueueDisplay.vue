<template>
  <div :class="['queue-display', { fullscreen: isFullscreen }]">
    <div class="container-fluid h-100">
      <!-- Digital Date & Clock -->
      <div class="clock-display text-center">
        <div class="clock-date">{{ currentDate }}</div>
        <span class="clock-time">{{ currentTime }}</span>
      </div>
      
      <!-- Dynamic Layout Based on Active Consultations -->
      <div v-if="activeConsultations.length === 0" class="row h-100">
        <!-- No Active Consultations - Show Single Row -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
          <div class="text-center">
            <h2 class="display-6 mb-4">Now Serving</h2>
            <div class="no-queue">
              <div class="queue-number display-1 fw-bold mb-3 text-muted">
                ---
              </div>
              <div class="patient-name h4 text-muted">
                No active consultation
              </div>
            </div>
          </div>
        </div>
        <!-- Right Column - Waiting Count -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-warning text-dark">
          <div class="text-center">
            <h2 class="display-6 mb-4">Waiting</h2>
            <div class="waiting-count display-1 fw-bold mb-3">
              {{ waitingCount }}
            </div>
            <div class="waiting-label h4">
              {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else-if="activeConsultations.length === 1" class="row h-100">
        <!-- Single Active Consultation - Original Layout -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
          <div class="text-center">
            <h2 class="display-6 mb-4">Now Serving</h2>
            <div class="current-queue-number">
              <div class="queue-number display-1 fw-bold mb-3">
                {{ formatQueueNumber(activeConsultations[0].queueNumber) }}
              </div>
              <div class="patient-name h4 mb-3">
                {{ getFormattedPatientName(activeConsultations[0].patient) }}
              </div>
              <div class="doctor-info">
                <div class="doctor-name h5 mb-1">
                  Dr. {{ getFormattedDoctorName(activeConsultations[0].doctor) }}
                </div>
                <div class="room-number h6 text-light">
                  {{ getDoctorRoom(activeConsultations[0].doctor) }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Right Column - Waiting Count -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-warning text-dark">
          <div class="text-center">
            <h2 class="display-6 mb-4">Waiting</h2>
            <div class="waiting-count display-1 fw-bold mb-3">
              {{ waitingCount }}
            </div>
            <div class="waiting-label h4">
              {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="multiple-consultations">
        <!-- Multiple Active Consultations - Stacked Layout -->
        <div v-for="(consultation, index) in activeConsultations" :key="consultation.id" 
             class="row consultation-row" 
             :class="{ 'mb-3': index < activeConsultations.length - 1 }">
          <div class="col-md-8 d-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center">
              <h3 class="h4 mb-3">Now Serving</h3>
              <div class="current-queue-number">
                <div class="queue-number display-4 fw-bold mb-2">
                  {{ formatQueueNumber(consultation.queueNumber) }}
                </div>
                <div class="patient-name h5 mb-2">
                  {{ getFormattedPatientName(consultation.patient) }}
                </div>
                <div class="doctor-info">
                  <div class="doctor-name h6 mb-1">
                    Dr. {{ getFormattedDoctorName(consultation.doctor) }}
                  </div>
                  <div class="room-number small text-light">
                    {{ getDoctorRoom(consultation.doctor) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="index === 0" class="col-md-4 d-flex align-items-center justify-content-center bg-warning text-dark">
            <div class="text-center">
              <h3 class="h4 mb-3">Waiting</h3>
              <div class="waiting-count display-4 fw-bold mb-2">
                {{ waitingCount }}
              </div>
              <div class="waiting-label h6">
                {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
              </div>
            </div>
          </div>
          <div v-else class="col-md-4"></div>
        </div>
      </div>
      
      <button v-if="!isFullscreen" @click="enterFullscreen" class="fullscreen-btn">
        <i class="fas fa-expand"></i> Fullscreen
      </button>
      <button v-if="isFullscreen" @click="exitFullscreen" class="fullscreen-btn exit">
        <i class="fas fa-compress"></i> Exit Fullscreen
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'QueueDisplay',
  data() {
    return {
      queueList: [],
      doctorList: [],
      lastUpdated: new Date().toLocaleTimeString(),
      refreshInterval: null,
      eventSource: null,
      isFullscreen: false,
      currentTime: '',
      currentDate: ''
    };
  },
  computed: {
    activeConsultations() {
      // Get all consultations that are currently in progress
      const inConsultation = this.queueList.filter(q => q.status === 'in_consultation');
      
      // Group by doctor to avoid duplicates and ensure one per doctor
      const byDoctor = {};
      inConsultation.forEach(consultation => {
        const doctorId = consultation.doctor?.id || 'unknown';
        if (!byDoctor[doctorId]) {
          byDoctor[doctorId] = consultation;
        }
      });
      
      // Return as array sorted by doctor name
      return Object.values(byDoctor).sort((a, b) => {
        const nameA = this.getFormattedDoctorName(a.doctor);
        const nameB = this.getFormattedDoctorName(b.doctor);
        return nameA.localeCompare(nameB);
      });
    },
    waitingQueue() {
      // Only count each queue entry once, regardless of group size
      return this.queueList.filter(q => q.status === 'waiting');
    },
    waitingCount() {
      // Count each group or single queue as 1
      return this.waitingQueue.length;
    },
    totalToday() {
      return this.queueList.length;
    },
    completedToday() {
      return this.queueList.filter(q => q.status === 'completed').length;
    }
  },
  created() {
    this.loadData();
    this.refreshInterval = setInterval(this.loadData, 2000);
    this.initializeSSE();
  },
  mounted() {
    // Don't automatically enter fullscreen - let user choose
    // this.enterFullscreen();
    document.body.classList.add('queue-fullscreen');
    window.addEventListener('keydown', this.handleKeydown);
    
    // Add fullscreen change event listeners to sync state
    document.addEventListener('fullscreenchange', this.handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', this.handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', this.handleFullscreenChange);
    
    this.updateClock();
    this.clockInterval = setInterval(this.updateClock, 1000);
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
    if (this.eventSource) {
      this.eventSource.close();
    }
    if (this.clockInterval) {
      clearInterval(this.clockInterval);
    }
    document.body.classList.remove('queue-fullscreen');
    window.removeEventListener('keydown', this.handleKeydown);
    
    // Remove fullscreen event listeners
    document.removeEventListener('fullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', this.handleFullscreenChange);
  },
  methods: {
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '---';
      // Ensure string
      queueNumber = queueNumber.toString();
      // Pad to 4 digits (e.g., 8001 -> 8001, 801 -> 0801)
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    async loadQueueList() {
      try {
        // Always request today's queue
        const now = new Date();
        const options = { timeZone: 'Asia/Kuala_Lumpur', year: 'numeric', month: '2-digit', day: '2-digit' };
        const todayMYT = now.toLocaleDateString('en-CA', options); // YYYY-MM-DD
        const response = await axios.get(`/api/queue?date=${todayMYT}`);
        console.log('Queue API response:', response);
        
        if (response.data && Array.isArray(response.data)) {
          this.queueList = response.data;
          this.lastUpdated = new Date().toLocaleTimeString();
          console.log('Queue list loaded:', this.queueList.length, 'items');
        } else {
          console.error('Unexpected queue data format:', response.data);
          this.queueList = [];
        }
      } catch (error) {
        console.error('Error loading queue:', error);
        this.queueList = [];
      }
    },
    async loadData() {
      await Promise.all([
        this.loadQueueList(),
        this.loadDoctors()
      ]);
    },
    refreshData() {
      this.loadData();
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        console.log('Doctor API response:', response);
        
        if (response.data && Array.isArray(response.data)) {
          this.doctorList = response.data;
          console.log('Doctor list loaded:', this.doctorList.length, 'items');
        } else {
          console.error('Unexpected doctor data format:', response.data);
          this.doctorList = [];
        }
      } catch (error) {
        console.error('Error loading doctors:', error);
        this.doctorList = [];
      }
    },
    getFormattedPatientName(patient) {
      if (!patient) return 'Unknown Patient';
      
      // Handle different patient name formats
      if (patient.displayName) {
        return patient.displayName;
      }
      
      if (patient.firstName && patient.lastName) {
        return `${patient.firstName} ${patient.lastName}`;
      }
      
      if (patient.name) {
        return patient.name;
      }
      
      return 'Unknown Patient';
    },
    getFormattedDoctorName(doctor) {
      if (!doctor) return 'Unknown Doctor';
      
      // Handle different doctor name formats
      if (doctor.displayName) {
        return doctor.displayName;
      }
      
      if (doctor.firstName && doctor.lastName) {
        return `${doctor.firstName} ${doctor.lastName}`;
      }
      
      if (doctor.name) {
        return doctor.name;
      }
      
      return 'Unknown Doctor';
    },
    getDoctorRoom(doctor) {
      if (!doctor || !doctor.id) return 'Room TBA';
      
      // Find the doctor's position in the sorted doctor list to determine room number
      const sortedDoctors = [...this.doctorList].sort((a, b) => a.id - b.id);
      const doctorIndex = sortedDoctors.findIndex(d => d.id === doctor.id);
      
      if (doctorIndex !== -1) {
        return `Room ${doctorIndex + 1}`;
      }
      
      // Fallback: use doctor ID to determine room
      return `Room ${((doctor.id - 1) % 10) + 1}`;
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
          // Just keep the connection alive
          console.log('Queue Display SSE heartbeat received');
        });
        
        this.eventSource.onerror = (error) => {
          console.warn('Queue Display SSE connection error, attempting reconnection in 3 seconds:', error);
          
          if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
          }
          
          // Attempt to reconnect after 3 seconds
          setTimeout(() => {
            if (!this.eventSource) { // Only reconnect if not already connected
              console.log('Attempting SSE reconnection...');
              this.initializeSSE();
            }
          }, 3000);
        };
        
        this.eventSource.onopen = () => {
          console.log('Queue Display SSE connection established');
        };
      } catch (error) {
        console.warn('SSE initialization failed (using fallback polling):', error);
      }
    },
    handleQueueUpdate(queueData) {
      // If this is a group consultation or patientCount is present, always refresh the list
      if (queueData.isGroupConsultation || typeof queueData.patientCount !== 'undefined') {
        this.loadData();
        return;
      }
      const queueIndex = this.queueList.findIndex(q => q.id === queueData.id);
      if (queueIndex !== -1) {
        this.queueList[queueIndex] = {
          ...this.queueList[queueIndex],
          status: queueData.status,
          patient: queueData.patient,
          doctor: queueData.doctor,
          queueDateTime: queueData.queueDateTime
        };
        this.lastUpdated = new Date().toLocaleTimeString();
      } else {
        this.loadData();
      }
    },
    enterFullscreen() {
      this.isFullscreen = true;
      document.body.classList.add('queue-fullscreen');
      
      // Try browser fullscreen API
      const el = document.documentElement;
      try {
        if (el.requestFullscreen) {
          el.requestFullscreen().catch(err => {
            console.warn('Failed to enter fullscreen:', err);
            this.isFullscreen = false;
            document.body.classList.remove('queue-fullscreen');
          });
        } else if (el.webkitRequestFullscreen) {
          el.webkitRequestFullscreen();
        } else if (el.mozRequestFullScreen) {
          el.mozRequestFullScreen();
        } else if (el.msRequestFullscreen) {
          el.msRequestFullscreen();
        }
      } catch (error) {
        console.warn('Error entering fullscreen:', error);
        this.isFullscreen = false;
        document.body.classList.remove('queue-fullscreen');
      }
    },
    exitFullscreen() {
      this.isFullscreen = false;
      document.body.classList.remove('queue-fullscreen');
      
      // Check if document is actually in fullscreen mode before trying to exit
      if (document.fullscreenElement || document.webkitFullscreenElement || 
          document.mozFullScreenElement || document.msFullscreenElement) {
        try {
          if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => {
              console.warn('Failed to exit fullscreen:', err);
            });
          } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
          }
        } catch (error) {
          console.warn('Error exiting fullscreen:', error);
        }
      }
    },
    handleKeydown(e) {
      if (e.key === 'Escape' && this.isFullscreen) {
        this.exitFullscreen();
      }
    },
    handleFullscreenChange() {
      // Sync our internal state with the actual fullscreen state
      const isCurrentlyFullscreen = !!(
        document.fullscreenElement || 
        document.webkitFullscreenElement || 
        document.mozFullScreenElement || 
        document.msFullscreenElement
      );
      
      this.isFullscreen = isCurrentlyFullscreen;
      
      if (isCurrentlyFullscreen) {
        document.body.classList.add('queue-fullscreen');
      } else {
        document.body.classList.remove('queue-fullscreen');
      }
    },
    updateClock() {
      const now = new Date();
      // Asia/Kuala_Lumpur time for clock (12-hour with AM/PM)
      const timeOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Kuala_Lumpur'
      };
      this.currentTime = now.toLocaleTimeString('en-MY', timeOptions);
      // Asia/Kuala_Lumpur time for date
      const dateOptions = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        timeZone: 'Asia/Kuala_Lumpur'
      };
      this.currentDate = now.toLocaleDateString('en-MY', dateOptions);
    },
    getStatusClass(status) {
      const statusClasses = {
        'waiting': 'status-waiting',
        'in_consultation': 'status-consultation',
        'completed_consultation': 'status-completed-consultation',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled'
      };
      return statusClasses[status] || 'status-unknown';
    }
  }
};
</script>

<style scoped>
.queue-display {
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(90deg, #007bff 50%, #ffc107 50%);
}

.container-fluid {
  height: 100%;
}

.row {
  margin: 0;
}

.col-md-6 {
  padding: 2rem;
  min-height: calc(100vh - 60px); /* Account for status bar */
}

.queue-number {
  font-size: 8rem !important;
  line-height: 1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.patient-name {
  font-weight: 500;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.doctor-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid rgba(255, 255, 255, 0.3);
}

.doctor-name {
  font-weight: 600;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.room-number {
  font-weight: 500;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
  opacity: 0.9;
}

.waiting-count {
  font-size: 8rem !important;
  line-height: 1;
  color: #856404 !important;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.waiting-label {
  font-weight: 500;
  color: #856404 !important;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.no-queue .queue-number {
  color: rgba(255, 255, 255, 0.6) !important;
}

.status-bar {
  z-index: 1000;
  height: 60px;
  display: flex;
  align-items: center;
}

.bg-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-warning {
  background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

@media (max-width: 768px) {
  .col-md-6 {
    min-height: 50vh;
    padding: 1rem;
  }
  
  .queue-number,
  .waiting-count {
    font-size: 4rem !important;
  }
  
  .display-6 {
    font-size: 2rem !important;
  }
  
  .h4 {
    font-size: 1.2rem !important;
  }
  
  .h5 {
    font-size: 1.1rem !important;
  }
  
  .h6 {
    font-size: 1rem !important;
  }
}

/* Fullscreen styles */
body.queue-fullscreen {
  overflow: hidden;
}

.queue-display.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  background: linear-gradient(90deg, #007bff 50%, #ffc107 50%);
}

.fullscreen-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 10001;
  background: rgba(0,0,0,0.2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1.2rem;
  cursor: pointer;
  transition: background 0.2s;
}

.fullscreen-btn.exit {
  right: 180px;
  background: rgba(0,0,0,0.4);
}

.clock-display {
  width: 100vw;
  padding-top: 2rem;
  padding-bottom: 1.5rem;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10000;
  pointer-events: none;
}
.clock-date {
  font-size: 2.2rem;
  font-weight: 600;
  color: #fff;
  text-shadow: 2px 2px 8px rgba(0,0,0,0.18);
  margin-bottom: 0.2em;
  letter-spacing: 0.04em;
}
.clock-time {
  font-size: 3.5rem;
  font-weight: 700;
  color: #fff;
  text-shadow: 2px 2px 8px rgba(0,0,0,0.25);
  letter-spacing: 0.1em;
}
.queue-display.fullscreen .clock-display {
  position: fixed;
}

.status-completed-consultation {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: bold;
  animation: pulse 2s infinite;
}

.status-completed {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  font-weight: bold;
}

/* Multiple Consultations Layout */
.multiple-consultations {
  padding-top: 8rem; /* Account for clock */
  height: calc(100vh - 8rem);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.consultation-row {
  margin: 0;
  flex: 1;
  min-height: 200px;
}

.consultation-row .col-md-8 {
  padding: 1.5rem;
  min-height: 200px;
}

.consultation-row .col-md-4 {
  padding: 1.5rem;
  min-height: 200px;
}

.multiple-consultations .queue-number {
  font-size: 5rem !important;
  line-height: 1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.multiple-consultations .waiting-count {
  font-size: 5rem !important;
  line-height: 1;
  color: #856404 !important;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
  .multiple-consultations {
    padding-top: 6rem;
    height: calc(100vh - 6rem);
  }
  
  .consultation-row .col-md-8,
  .consultation-row .col-md-4 {
    padding: 1rem;
    min-height: 150px;
  }
  
  .multiple-consultations .queue-number,
  .multiple-consultations .waiting-count {
    font-size: 3rem !important;
  }
  
  .multiple-consultations .h4 {
    font-size: 1.1rem !important;
  }
  
  .multiple-consultations .h5 {
    font-size: 1rem !important;
  }
  
  .multiple-consultations .h6 {
    font-size: 0.9rem !important;
  }
}
</style> 