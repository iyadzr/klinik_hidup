<template>
  <div class="queue-management">
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Add to Queue</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="addToQueue">
              <div class="mb-3">
                <label class="form-label">Patient</label>
                <select v-model="newQueue.patientId" class="form-select" required>
                  <option value="">Select Patient</option>
                  <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                    {{ patient.name }}
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Doctor</label>
                <select v-model="newQueue.doctorId" class="form-select" required>
                  <option value="">Select Doctor</option>
                  <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                    {{ doctor.name }}
                  </option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary">Add to Queue</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h4 class="mb-0">Current Queue Display</h4>
          </div>
          <div class="card-body">
            <div class="current-queue text-center">
              <h2>Now Serving</h2>
              <div class="display-1 mb-3" v-if="currentQueue">
                {{ currentQueue.queueNumber }}
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
                <td>{{ queue.queueNumber }}</td>
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
        this.patients = response.data;
      } catch (error) {
        console.error('Error loading patients:', error);
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data;
      } catch (error) {
        console.error('Error loading doctors:', error);
      }
    },
    async loadQueueList() {
      try {
        const response = await axios.get('/api/queue');
        this.queueList = response.data;
      } catch (error) {
        console.error('Error loading queue:', error);
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
      return new Date(datetime).toLocaleTimeString();
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
