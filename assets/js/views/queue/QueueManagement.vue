<template>
  <div class="queue-management">
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4 class="mb-0">Current Queue Display</h4>
          </div>
          <div class="card-body">
            <div class="current-queue text-center py-5 glass-card shadow mb-5" style="background:rgba(255,255,255,0.92);border:2px solid var(--accent);">
              <h2 class="mb-4">Now Serving</h2>
              <div class="display-1 mb-3" v-if="currentQueue">
                {{ formatQueueNumber(currentQueue.queueNumber) }} - {{ getFormattedPatientName(currentQueue.patient) }}
              </div>
              <div v-else class="text-muted">
                No active queue
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Queue List</h4>
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
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="queue in queueList" :key="queue.id" :class="{'table-active': queue.status === 'in_consultation'}">
                <td>{{ formatQueueNumber(queue.queueNumber) }}</td>
                <td>{{ queue.patient.name }}</td>
                <td>{{ queue.doctor.name }}</td>
                <td>
                  <span :class="getStatusBadgeClass(queue.status)">
                    {{ formatStatus(queue.status) }}
                  </span>
                </td>
                <td>{{ formatDateTime(queue.queueDateTime) }}</td>
                <td>
                  <div class="btn-group">
                    <button 
                      v-if="queue.status === 'waiting'"
                      class="btn btn-sm btn-success"
                      @click="updateStatus(queue.id, 'in_consultation')"
                    >
                      Start Consultation
                    </button>
                    <button 
                      v-if="queue.status === 'in_consultation'"
                      class="btn btn-sm btn-primary"
                      @click="updateStatus(queue.id, 'completed')"
                    >
                      Complete
                    </button>
                    <button 
                      v-if="queue.status === 'waiting'"
                      class="btn btn-sm btn-danger"
                      @click="updateStatus(queue.id, 'cancelled')"
                    >
                      Cancel
                    </button>
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

export default {
  name: 'QueueManagement',
  data() {
    return {
      patients: [],
      doctors: [],
      queueList: [],
      newQueue: {
        patientId: '',
        doctorId: ''
      }
    };
  },
  computed: {
    currentQueue() {
      return this.queueList.find(q => q.status === 'in_consultation');
    }
  },
  created() {
    this.loadData();
    // Refresh queue list every 30 seconds
    this.refreshInterval = setInterval(this.loadQueueList, 30000);
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
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
      try {
        const response = await axios.get('/api/queue');
        console.log('Queue API response:', response);
        
        if (response.data && Array.isArray(response.data)) {
          this.queueList = response.data;
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
    },
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
