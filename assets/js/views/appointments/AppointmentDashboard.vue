<template>
  <div class="registration-dashboard">
    <div class="row">
      <!-- Today's Stats -->
      <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
          <div class="card-body">
            <h5 class="card-title">Today's Registrations</h5>
            <div class="d-flex align-items-center mt-3">
              <div class="display-4 me-3">{{ todayAppointments.length }}</div>
              <div>
                <div>{{ pendingToday }} Pending</div>
                <div>{{ completedToday }} Completed</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Weekly Stats -->
      <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
          <div class="card-body">
            <h5 class="card-title">This Week's Registrations</h5>
            <div class="d-flex align-items-center mt-3">
              <div class="display-4 me-3">{{ weeklyRegistrations.length }}</div>
              <div>
                <div>{{ averagePerDay }} Avg/Day</div>
                <div>{{ completedThisWeek }} Completed</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Doctor Availability -->
      <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-info text-white">
          <div class="card-body">
            <h5 class="card-title">Doctor Availability</h5>
            <div class="d-flex align-items-center mt-3">
              <div class="display-4 me-3">{{ availableDoctors }}</div>
              <div>
                <div>Doctors Available</div>
                <div>{{ totalDoctors }} Total</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Registrations List -->
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Today's Registrations</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Time</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="registration in todayRegistrations" :key="registration.id"
                  :class="{ 'table-warning': isCurrentRegistration(registration) }">
                <td>{{ formatTime(registration.startTime) }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle me-2">
                      <i class="fas fa-user text-secondary"></i>
                    </div>
                    <div>
                      <div class="fw-medium">{{ appointment.patientName }}</div>
                      <small class="text-muted">{{ appointment.patientPhone }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle me-2">
                      <i class="fas fa-user-md text-secondary"></i>
                    </div>
                    <div>Dr. {{ appointment.doctorName }}</div>
                  </div>
                </td>
                <td>
                  <span :class="getStatusBadgeClass(appointment)">
                    {{ appointment.status }}
                  </span>
                </td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" @click="editAppointment(appointment)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" 
                            v-if="appointment.status === 'Pending'"
                            @click="markAsCompleted(appointment)">
                      <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="cancelAppointment(appointment)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="todayAppointments.length === 0">
                <td colspan="5" class="text-center py-4">
                  No appointments scheduled for today
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- New/Edit Appointment Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Edit' : 'New' }} Appointment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveAppointment">
              <div class="mb-3">
                <label class="form-label">Patient</label>
                <select class="form-select" v-model="form.patientId" required>
  <option value="">Select Patient</option>
  <option v-for="patient in patients" :key="patient.id" :value="patient.id">
    {{ patient.displayName || ((patient.firstName || '') + ' ' + (patient.lastName || '')) }}
  </option>
</select>
              </div>
              <div class="mb-3">
                <label class="form-label">Doctor</label>
                <select class="form-select" v-model="form.doctorId" required>
                  <option value="">Select Doctorssadsd</option>
                  <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                    {{ doctor.displayName || ('Dr. ' + (doctor.firstName || '') + ' ' + (doctor.lastName || '')) }}<span v-if="doctor.specialization"> ({{ doctor.specialization }})</span>
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
                <label class="form-label">Notes</label>
                <textarea class="form-control" v-model="form.notes" rows="3"></textarea>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditing ? 'Update' : 'Create' }} Appointment
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/vue3';

export default {
  name: 'AppointmentDashboard',
  components: {
    FullCalendar
  },
  data() {
    return {
      appointments: [],
      patients: [],
      doctors: [],
      form: {
        patientId: '',
        doctorId: '',
        date: '',
        time: '',
        notes: ''
      },
      isEditing: false,
      selectedAppointment: null,
      appointmentModal: null,
      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        select: this.handleDateSelect,
        eventClick: this.handleEventClick,
        eventsSet: this.handleEvents,
        events: [] // Will be populated from appointments
      }
    };
  },
  computed: {
    safeAppointments() {
      return Array.isArray(this.appointments) ? this.appointments : [];
    },
    todayAppointments() {
      const today = new Date().toISOString().split('T')[0];
      return this.safeAppointments.filter(apt => apt.date === today);
    },
    weeklyAppointments() {
      const today = new Date();
      const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
      const weekEnd = new Date(today.setDate(today.getDate() + 6));
      return this.safeAppointments.filter(apt => {
        const aptDate = new Date(apt.date);
        return aptDate >= weekStart && aptDate <= weekEnd;
      });
    },
    pendingToday() {
      return this.todayAppointments.filter(apt => apt.status === 'Pending').length;
    },
    completedToday() {
      return this.todayAppointments.filter(apt => apt.status === 'Completed').length;
    },
    completedThisWeek() {
      return this.weeklyAppointments.filter(apt => apt.status === 'Completed').length;
    },
    averagePerDay() {
      return Math.round(this.weeklyAppointments.length / 7);
    },
    availableDoctors() {
      return this.doctors.filter(doc => doc.isAvailable).length;
    },
    totalDoctors() {
      return this.doctors.length;
    }
  },
  methods: {
    async loadAppointments() {
      try {
        const response = await axios.get('/api/appointments');
        // Robust: ensure appointments is always an array
        let appointments = [];
        if (Array.isArray(response.data.appointments)) {
          appointments = response.data.appointments;
        } else if (Array.isArray(response.data)) {
          appointments = response.data;
        }
        this.appointments = appointments;
        console.log('Loaded appointments:', this.appointments);
        this.updateCalendarEvents();
      } catch (error) {
        console.error('Error loading appointments:', error);
      }
    },
    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        this.patients = response.data || [];
        console.log('Loaded patients:', this.patients);
        if (!Array.isArray(this.patients) || this.patients.length === 0) {
          console.error('No patients loaded or wrong structure:', this.patients);
        }
      } catch (error) {
        console.error('Error loading patients:', error);
        this.patients = [];
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = response.data || [];
        console.log('Loaded doctors:', this.doctors);
        if (!Array.isArray(this.doctors) || this.doctors.length === 0) {
          console.error('No doctors loaded or wrong structure:', this.doctors);
        }
      } catch (error) {
        console.error('Error loading doctors:', error);
        this.doctors = [];
      }
    },
    formatTime(time) {
      return new Date(`2000-01-01T${time}`).toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    },
    getStatusBadgeClass(appointment) {
      return {
        'badge rounded-pill': true,
        'bg-warning': appointment.status === 'Pending',
        'bg-success': appointment.status === 'Completed',
        'bg-danger': appointment.status === 'Cancelled'
      };
    },
    isCurrentAppointment(appointment) {
      const now = new Date();
      const aptTime = new Date(`${appointment.date}T${appointment.time}`);
      return aptTime <= now && appointment.status === 'Pending';
    },
    openNewAppointmentModal() {
      this.isEditing = false;
      this.form = {
        patientId: '',
        doctorId: '',
        date: '',
        time: '',
        notes: ''
      };
      this.appointmentModal.show();
    },
    editAppointment(appointment) {
      this.isEditing = true;
      this.selectedAppointment = appointment;
      this.form = {
        patientId: appointment.patientId,
        doctorId: appointment.doctorId,
        date: appointment.date,
        time: appointment.time,
        notes: appointment.notes
      };
      this.appointmentModal.show();
    },
    async saveAppointment() {
      try {
        if (this.isEditing) {
          await axios.put(`/api/appointments/${this.selectedAppointment.id}`, this.form);
        } else {
          await axios.post('/api/appointments', this.form);
        }
        await this.loadAppointments();
        this.appointmentModal.hide();
      } catch (error) {
        console.error('Error saving appointment:', error);
      }
    },
    async markAsCompleted(appointment) {
      try {
        await axios.patch(`/api/appointments/${appointment.id}/complete`);
        await this.loadAppointments();
      } catch (error) {
        console.error('Error completing appointment:', error);
      }
    },
    async cancelAppointment(appointment) {
      if (confirm('Are you sure you want to cancel this appointment?')) {
        try {
          await axios.patch(`/api/appointments/${appointment.id}/cancel`);
          await this.loadAppointments();
        } catch (error) {
          console.error('Error cancelling appointment:', error);
        }
      }
    },
    updateCalendarEvents() {
      this.calendarOptions.events = this.appointments.map(apt => ({
        id: apt.id,
        title: `${apt.patientName} - Dr. ${apt.doctorName}`,
        start: `${apt.date}T${apt.time}`,
        end: this.calculateEndTime(apt.date, apt.time),
        className: this.getEventClassName(apt.status)
      }));
    },
    calculateEndTime(date, time) {
      const datetime = new Date(`${date}T${time}`);
      datetime.setMinutes(datetime.getMinutes() + 30); // 30-minute appointments
      return datetime.toISOString();
    },
    getEventClassName(status) {
      switch (status) {
        case 'Completed': return 'bg-success';
        case 'Cancelled': return 'bg-danger';
        default: return 'bg-primary';
      }
    },
    handleDateSelect(selectInfo) {
      this.form.date = selectInfo.startStr.split('T')[0];
      this.form.time = selectInfo.startStr.split('T')[1].slice(0, 5);
      this.openNewAppointmentModal();
    },
    handleEventClick(clickInfo) {
      const appointment = this.appointments.find(apt => apt.id === parseInt(clickInfo.event.id));
      if (appointment) {
        this.editAppointment(appointment);
      }
    }
  },
  mounted() {
    this.loadPatients();
    this.loadDoctors();
  }
};
</script>

<style scoped>
.appointment-dashboard {
  padding: 20px;
}

.calendar-wrapper {
  height: 600px;
  margin-bottom: 2rem;
}

.avatar-sm {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge {
  font-size: 0.85rem;
  padding: 0.5em 1em;
}

/* Calendar customization */
:deep(.fc) {
  background-color: white;
  border-radius: 8px;
  padding: 1rem;
}

:deep(.fc-toolbar-title) {
  font-size: 1.25rem !important;
  font-weight: 600;
}

:deep(.fc-button) {
  background-color: var(--bs-primary) !important;
  border-color: var(--bs-primary) !important;
}

:deep(.fc-event) {
  cursor: pointer;
  padding: 4px 8px;
  border: none;
}

:deep(.fc-day-today) {
  background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}
</style>
