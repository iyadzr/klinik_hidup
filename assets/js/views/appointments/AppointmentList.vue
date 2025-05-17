<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Appointments</h2>
      <button class="btn btn-primary" @click="showAddModal = true">Schedule Appointment</button>
    </div>

    <!-- Appointment List -->
    <div class="card">
      <div class="card-body">
        <div v-if="loading" class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="appointment in appointments" :key="appointment.id">
                <td>{{ appointment.patient.firstName }} {{ appointment.patient.lastName }}</td>
                <td>{{ appointment.doctor.firstName }} {{ appointment.doctor.lastName }}</td>
                <td>{{ formatDateTime(appointment.appointmentDateTime) }}</td>
                <td>
                  <span :class="getStatusBadgeClass(appointment.status)">
                    {{ appointment.status }}
                  </span>
                </td>
                <td>{{ appointment.reason }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="editAppointment(appointment)">Edit</button>
                  <button class="btn btn-sm btn-danger" @click="deleteAppointment(appointment)">Cancel</button>
                </td>
              </tr>
              <tr v-if="appointments.length === 0">
                <td colspan="6" class="text-center">No appointments found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Appointment Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" tabindex="-1" v-if="showAddModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingAppointment ? 'Edit Appointment' : 'Schedule Appointment' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <form @submit.prevent="saveAppointment">
              <div class="mb-3">
                <label class="form-label">Patient</label>
                <select class="form-select" v-model="form.patientId" required>
                  <option value="">Select Patient</option>
                  <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                    {{ patient.firstName }} {{ patient.lastName }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Doctor</label>
                <select class="form-select" v-model="form.doctorId" required>
                  <option value="">Select Doctor</option>
                  <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                    {{ doctor.firstName }} {{ doctor.lastName }} ({{ doctor.specialization }})
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" v-model="form.date" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Time</label>
                <input type="time" class="form-control" v-model="form.time" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea class="form-control" v-model="form.reason" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" v-model="form.status">
                  <option value="scheduled">Scheduled</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  {{ saving ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showAddModal"></div>
  </div>
</template>

<script>
import axios from 'axios';
import { emitter } from '../../App.vue';

export default {
  name: 'AppointmentList',
  emits: ['appointment-added', 'appointment-updated', 'appointment-deleted'],
  data() {
    return {
      appointments: [],
      patients: [],
      doctors: [],
      showAddModal: false,
      editingAppointment: null,
      loading: false,
      saving: false,
      error: null,
      form: {
        patientId: '',
        doctorId: '',
        date: '',
        time: '',
        reason: '',
        status: 'scheduled'
      }
    };
  },
  created() {
    this.loadData();
  },
  methods: {
    async loadData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadAppointments(),
          this.loadPatients(),
          this.loadDoctors()
        ]);
      } catch (error) {
        console.error('Failed to load data:', error);
      } finally {
        this.loading = false;
      }
    },
    async loadAppointments() {
      try {
        const response = await axios.get('/api/appointments');
        this.appointments = response.data.appointments;
      } catch (error) {
        console.error('Failed to load appointments:', error);
      }
    },
    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        this.patients = response.data.patients;
      } catch (error) {
        console.error('Failed to load patients:', error);
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data.doctors;
      } catch (error) {
        console.error('Failed to load doctors:', error);
      }
    },
    formatDateTime(datetime) {
      return new Date(datetime).toLocaleString();
    },
    getStatusBadgeClass(status) {
      const classes = {
        scheduled: 'badge bg-warning',
        confirmed: 'badge bg-info',
        completed: 'badge bg-success',
        cancelled: 'badge bg-danger'
      };
      return classes[status] || 'badge bg-secondary';
    },
    editAppointment(appointment) {
      this.editingAppointment = appointment;
      const appointmentDate = new Date(appointment.appointmentDateTime);
      this.form = {
        patientId: appointment.patient.id,
        doctorId: appointment.doctor.id,
        date: appointmentDate.toISOString().split('T')[0],
        time: appointmentDate.toTimeString().slice(0, 5),
        reason: appointment.reason || '',
        status: appointment.status
      };
      this.showAddModal = true;
    },
    async deleteAppointment(appointment) {
      if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
      }
      
      try {
        await axios.delete(`/api/appointments/${appointment.id}`);
        this.appointments = this.appointments.filter(a => a.id !== appointment.id);
        this.$emit('appointment-deleted');
        emitter.emit('data-change');
      } catch (error) {
        console.error('Failed to delete appointment:', error);
      }
    },
    async saveAppointment() {
      this.saving = true;
      this.error = null;
      
      try {
        const appointmentData = {
          patientId: this.form.patientId,
          doctorId: this.form.doctorId,
          appointmentDateTime: `${this.form.date}T${this.form.time}:00`,
          reason: this.form.reason,
          status: this.form.status
        };

        let response;
        if (this.editingAppointment) {
          response = await axios.put(`/api/appointments/${this.editingAppointment.id}`, appointmentData);
          const index = this.appointments.findIndex(a => a.id === this.editingAppointment.id);
          if (index !== -1) {
            this.appointments[index] = response.data.appointment;
          }
          this.$emit('appointment-updated');
        } else {
          response = await axios.post('/api/appointments', appointmentData);
          this.appointments.push(response.data.appointment);
          this.$emit('appointment-added');
        }
        
        emitter.emit('data-change');
        this.closeModal();
      } catch (error) {
        console.error('Failed to save appointment:', error);
        this.error = error.response?.data?.message || 'Failed to save appointment. Please try again.';
      } finally {
        this.saving = false;
      }
    },
    closeModal() {
      this.showAddModal = false;
      this.editingAppointment = null;
      this.error = null;
      this.form = {
        patientId: '',
        doctorId: '',
        date: '',
        time: '',
        reason: '',
        status: 'scheduled'
      };
    }
  }
};
</script>

<style scoped>
.modal {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  z-index: 1040;
}

.modal {
  z-index: 1050;
}

.badge {
  font-size: 0.875rem;
}
</style>
