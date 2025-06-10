<template>
  <div class="queue-display">
    <div class="container-fluid h-100">
      <div class="row h-100">
        <!-- Left Column - Current Queue Number -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
          <div class="text-center">
            <h2 class="display-6 mb-4">Now Serving</h2>
            <div v-if="currentQueue" class="current-queue-number">
              <div class="queue-number display-1 fw-bold mb-3">
                {{ formatQueueNumber(currentQueue.queueNumber) }}
              </div>
              <div class="patient-name h4 mb-3">
                {{ getFormattedPatientName(currentQueue.patient) }}
              </div>
              <div class="doctor-info">
                <div class="doctor-name h5 mb-1">
                  Dr. {{ getFormattedDoctorName(currentQueue.doctor) }}
                </div>
                <div class="room-number h6 text-light">
                  {{ getDoctorRoom(currentQueue.doctor) }}
                </div>
              </div>
            </div>
            <div v-else class="no-queue">
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
    </div>

    <!-- Bottom Status Bar -->
    <div class="status-bar position-fixed bottom-0 w-100 bg-dark text-white py-2">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-3">
            <small>Last Updated: {{ lastUpdated }}</small>
          </div>
          <div class="col-md-6 text-center">
            <small>
              Total Today: {{ totalToday }} | 
              Completed: {{ completedToday }} | 
              Waiting: {{ waitingCount }}
            </small>
          </div>
          <div class="col-md-3 text-end">
            <button class="btn btn-sm btn-outline-light" @click="refreshData">
              <i class="fas fa-sync-alt"></i> Refresh
            </button>
          </div>
        </div>
      </div>
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
      refreshInterval: null
    };
  },
  computed: {
    currentQueue() {
      return this.queueList.find(q => q.status === 'in_consultation');
    },
    waitingQueue() {
      return this.queueList.filter(q => q.status === 'waiting');
    },
    waitingCount() {
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
    // Auto-refresh every 10 seconds
    this.refreshInterval = setInterval(this.loadData, 10000);
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
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
        const response = await axios.get('/api/queue');
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
    }
  }
};
</script>

<style scoped>
.queue-display {
  height: 100vh;
  overflow: hidden;
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
  background: white;
}
</style> 