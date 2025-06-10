import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../views/Dashboard.vue';
import PatientList from '../views/patients/PatientList.vue';
import DoctorList from '../views/doctors/DoctorList.vue';
import QueueManagement from '../views/queue/QueueManagement.vue';
import QueueDisplay from '../views/queue/QueueDisplay.vue';
import ConsultationForm from '../views/consultations/ConsultationForm.vue';
import ConsultationList from '../views/consultations/ConsultationList.vue';
import PatientRegistration from '../views/registration/PatientRegistration.vue';
import ClinicAssistantList from '../views/clinic-assistants/ClinicAssistantList.vue';
import Login from '../views/auth/Login.vue';
import PaymentList from '../views/payments/PaymentList.vue';
import AppointmentDashboard from '../views/appointments/AppointmentDashboard.vue';
import PrescriptionForm from '../views/prescriptions/PrescriptionForm.vue';
import UserProfile from '../views/UserProfile.vue';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/registration',
    name: 'Registration',
    component: PatientRegistration,
    meta: { requiresAuth: true }
  },
  {
    path: '/queue',
    name: 'Queue',
    component: QueueManagement,
    meta: { requiresAuth: true }
  },
  {
    path: '/queue-display',
    name: 'QueueDisplay',
    component: QueueDisplay,
    meta: { requiresAuth: true }
  },
  {
    path: '/consultations',
    name: 'Consultations',
    component: ConsultationList,
    meta: { requiresAuth: true }
  },
  {
    path: '/consultations/new',
    name: 'NewConsultation',
    component: ConsultationForm,
    meta: { requiresAuth: true }
  },
  {
    path: '/patients',
    name: 'Patients',
    component: PatientList,
    meta: { requiresAuth: true }
  },
  {
    path: '/doctors',
    name: 'Doctors',
    component: DoctorList,
    meta: { requiresAuth: true }
  },
  {
    path: '/clinic-assistants',
    name: 'ClinicAssistants',
    component: ClinicAssistantList,
    meta: { requiresAuth: true }
  },
  {
    path: '/payments',
    name: 'payments',
    component: PaymentList,
    meta: { requiresAuth: true, role: 'assistant' }
  },
  {
    path: '/appointments',
    name: 'appointments',
    component: AppointmentDashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/prescriptions/new',
    name: 'prescription-form',
    component: PrescriptionForm,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: UserProfile,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!localStorage.getItem('user')) {
      next('/login');
      return;
    }
  }
  next();
});

export default router;
