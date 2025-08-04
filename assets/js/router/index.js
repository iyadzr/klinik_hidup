import { createRouter, createWebHistory } from 'vue-router';
import AuthService from '../services/AuthService';
import globalRequestManager from '../utils/GlobalRequestManager.js';
import requestKiller from '../utils/AggressiveRequestKiller.js';
import Dashboard from '../views/Dashboard.vue';
import PatientList from '../views/patients/PatientList.vue';
import DoctorList from '../views/doctors/DoctorList.vue';
import QueueManagement from '../views/queue/QueueManagement.vue';
import QueueDisplay from '../views/queue/QueueDisplay.vue';
import ConsultationForm from '../views/consultations/ConsultationFormNew.vue';
import OngoingConsultations from '../views/consultations/OngoingConsultations.vue';
import PatientRegistration from '../views/registration/PatientRegistration.vue';
import ClinicAssistantList from '../views/clinic-assistants/ClinicAssistantList.vue';
import Login from '../views/auth/Login.vue';
import Register from '../views/auth/Register.vue';
import PaymentList from '../views/payments/PaymentList.vue';
import PaymentsDashboard from '../views/finance/PaymentsDashboard.vue';
import PrescriptionForm from '../views/prescriptions/PrescriptionForm.vue';
import UserProfile from '../views/UserProfile.vue';
import FinancialDashboard from '../views/finance/FinancialDashboard.vue';
import MedicationAdmin from '../views/admin/MedicationAdmin.vue';
import UserAdmin from '../views/admin/UserAdmin.vue';
import SystemSettings from '../views/admin/SystemSettings.vue';

const routes = [
  {
    path: '/',
    name: 'Home',
    beforeEnter: (to, from, next) => {
      // Redirect based on user role
      if (AuthService.isAuthenticated()) {
        const user = AuthService.getCurrentUser();
        const roles = user?.roles || [];
        
        if (roles.includes('ROLE_ASSISTANT')) {
          next('/registration');
        } else if (roles.includes('ROLE_DOCTOR')) {
          next('/consultations/ongoing');
        } else {
          next('/dashboard');
        }
      } else {
        next('/login');
      }
    }
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
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN'] }
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
    redirect: '/consultations/ongoing',
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/consultations/ongoing',
    name: 'OngoingConsultations',
    component: OngoingConsultations,
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/consultations/new',
    name: 'NewConsultation',
    component: ConsultationForm,
    meta: { requiresAuth: true }
  },
  {
    path: '/consultations/form',
    name: 'ConsultationForm',
    component: ConsultationForm,
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/consultations/:id',
    name: 'EditConsultation',
    component: ConsultationForm,
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN'] }
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
    meta: { requiresAuth: true, roles: ['ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/payments/dashboard',
    name: 'PaymentsDashboard',
    component: PaymentsDashboard,
    meta: { requiresAuth: true, roles: ['ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN'] }
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
    path: '/financial',
    name: 'Financial',
    component: FinancialDashboard,
    meta: { requiresAuth: true, roles: ['ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/medications',
    name: 'Medications',
    component: MedicationAdmin,
    meta: { requiresAuth: true, roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/admin/users',
    name: 'UserAdmin',
    component: UserAdmin,
    meta: { requiresAuth: true, roles: ['ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/admin/settings',
    name: 'SystemSettings',
    component: SystemSettings,
    meta: { requiresAuth: true, roles: ['ROLE_SUPER_ADMIN'] }
  },
  
  {
    path: '/backup-management',
    name: 'BackupManagement',
    component: () => import('../views/admin/BackupManagement.vue'),
    meta: { requiresAuth: true, roles: ['ROLE_SUPER_ADMIN'] }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation throttling to prevent rapid navigation deadlocks
let lastNavigationTime = 0;
const NAVIGATION_THROTTLE_MS = 300; // Reduced to 300ms for faster login response

router.beforeEach((to, from, next) => {
  const now = Date.now();
  
  // Skip throttling and request killing for login/auth related navigations
  const isAuthNavigation = ['/login', '/register'].includes(to.path) || ['/login', '/register'].includes(from.path);
  
  // Throttle rapid navigation (but not for auth)
  if (!isAuthNavigation && from.path && from.path !== to.path && (now - lastNavigationTime) < NAVIGATION_THROTTLE_MS) {
    console.log('🚨 NAVIGATION THROTTLED - Too fast');
    return; // Block the navigation
  }
  
  // NUCLEAR OPTION: Kill ALL pending requests on navigation (but not for auth)
  if (!isAuthNavigation && from.path && from.path !== to.path) {
    console.log(`🚨 NAVIGATION: ${from.path} → ${to.path} - KILLING ALL REQUESTS`);
    lastNavigationTime = now;
    
    // Immediate nuclear cleanup
    requestKiller.killAllRequests();
    
    // Also use existing cleanup methods as backup
    globalRequestManager.cancelRouteRequests(from.path);
    
    // Force close specific EventSource connections (backup)
    if (window._queueDisplayEventSource) {
      window._queueDisplayEventSource.close();
      window._queueDisplayEventSource = null;
    }
    
    if (window._queueManagementEventSource) {
      window._queueManagementEventSource.close();
      window._queueManagementEventSource = null;
    }
  }

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const requiredRoles = to.meta.roles;
  const isAuthenticated = AuthService.isAuthenticated();

  // If user is authenticated and trying to access login/register, redirect based on role
  if (isAuthenticated && ['/login', '/register'].includes(to.path)) {
    const user = AuthService.getCurrentUser();
    const roles = user?.roles || [];
    
    if (roles.includes('ROLE_ASSISTANT')) {
      next('/registration');
    } else if (roles.includes('ROLE_DOCTOR')) {
      next('/consultations/ongoing');
    } else {
      next('/dashboard');
    }
    return;
  }

  // For protected routes, check authentication more thoroughly
  if (requiresAuth && !isAuthenticated) {
    // Double-check authentication state with a small delay
    setTimeout(() => {
      if (AuthService.isAuthenticated()) {
        console.log('✅ Authentication confirmed after delay, proceeding');
        next();
      } else {
        console.log('❌ Authentication not confirmed, redirecting to login');
        next('/login');
      }
    }, 100);
    return;
  }
  
  if (requiresAuth && requiredRoles && requiredRoles.length > 0) {
    const hasRequiredRole = requiredRoles.some(role => AuthService.hasRole(role));
    if (!hasRequiredRole) {
      next('/dashboard'); // Redirect to dashboard if user doesn't have required role
      return;
    }
  }
  
  next();
});

// Add error handling for navigation failures
router.onError((error) => {
  console.error('Router navigation error:', error);
});

// Add navigation completion handler
router.afterEach((to, from) => {
  // Ensure the page title is updated
  document.title = to.meta.title || 'Klinik HiDUP sihat';
  
  // Scroll to top on route change
  window.scrollTo(0, 0);
});

export default router;
