<template>
  <div class="app-wrapper">
    <button v-if="!isAuthPage" class="sidebar-toggle-btn" @click="sidebarOpen = true" aria-label="Open sidebar">
      <i class="fas fa-bars"></i>
    </button>
    <div v-if="!isAuthPage" :class="['sidebar-backdrop', { active: sidebarOpen } ]" @click="sidebarOpen = false"></div>
  
    <!-- Sidebar -->
    <nav :class="['sidebar', 'bg-dark', { open: sidebarOpen } ]" v-if="isAuthenticated && !isAuthPage">
      <div class="sidebar-header p-3">
        <router-link to="/" class="text-white text-decoration-none">
          <h5 class="mb-0 d-flex align-items-center">
            <i class="fas fa-clinic-medical me-2"></i>
            <span>Klinik HiDUP sihat</span>
          </h5>
        </router-link>
      </div>

      <div class="sidebar-menu">
        <div class="menu-section">
          <h6 class="menu-title text-muted px-4 py-2 mb-0">MAIN MENU</h6>
          <ul class="nav flex-column">
            <li class="nav-item">
              <router-link to="/dashboard" class="nav-link">
                <i class="fas fa-chart-line fa-fw"></i>
                <span>Dashboard</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/registration" class="nav-link">
                <i class="fas fa-user-plus fa-fw"></i>
                <span>Registration</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/queue" class="nav-link">
                <i class="fas fa-list-ol fa-fw"></i>
                <span>Queue</span>
              </router-link>
            </li>
          </ul>
        </div>

        <div class="menu-section">
          <h6 class="menu-title text-muted px-4 py-2 mb-0">MEDICAL</h6>
          <ul class="nav flex-column">
            <li class="nav-item">
              <router-link to="/consultations" class="nav-link">
                <i class="fas fa-stethoscope fa-fw"></i>
                <span>Consultations</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/patients" class="nav-link">
                <i class="fas fa-user-injured fa-fw"></i>
                <span>Patients</span>
              </router-link>
            </li>
          </ul>
        </div>

        <div class="menu-section">
          <h6 class="menu-title text-muted px-4 py-2 mb-0">ADMINISTRATION</h6>
          <ul class="nav flex-column">
            <li class="nav-item">
              <router-link to="/doctors" class="nav-link">
                <i class="fas fa-user-md fa-fw"></i>
                <span>Doctors</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/clinic-assistants" class="nav-link">
                <i class="fas fa-user-nurse fa-fw"></i>
                <span>Clinic Assistants</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/payments" class="nav-link" v-if="isAssistant">
                <i class="fas fa-money-bill fa-fw"></i>
                <span>Payments</span>
              </router-link>
            </li>
          </ul>
        </div>
      </div>

      <div class="sidebar-footer">
        <button class="btn btn-dark w-100 text-start" @click="logout">
          <i class="fas fa-sign-out-alt fa-fw me-2"></i>
          <span>Logout</span>
        </button>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-wrapper">
        <router-view 
          @patient-added="handleDataChange" 
          @patient-updated="handleDataChange" 
          @patient-deleted="handleDataChange"
          @appointment-added="handleDataChange"
          @appointment-updated="handleDataChange"
          @appointment-deleted="handleDataChange"
          @login-success="handleLoginSuccess"
          ref="currentView"
        ></router-view>
      </div>
    </main>
  </div>
</template>

<style src="../styles/responsive-sidebar.css"></style>


<script>
import axios from 'axios';
import mitt from 'mitt';

export const emitter = mitt();

export default {
  name: 'App',
  data() {
    return {
      sidebarOpen: false
    };
  },
  computed: {
    isAuthenticated() {
      return !!localStorage.getItem('user');
    },
    isAssistant() {
      const user = localStorage.getItem('user');
      if (user) {
        const userData = JSON.parse(user);
        return userData.role === 'assistant';
      }
      return false;
    },
    isAuthPage() {
      return this.$route.path === '/login' || this.$route.path === '/register';
    }
  },
  created() {
    this.checkAuth();
    this.setupAxiosInterceptors();

    // Listen for data change events
    emitter.on('data-change', () => {
      this.handleDataChange();
    });

    // Listen for auth change events
    emitter.on('auth-change', () => {
      this.checkAuth();
    });
  },
  beforeUnmount() {
    emitter.off('data-change');
    emitter.off('auth-change');
  },
  methods: {
    checkAuth() {
      const user = localStorage.getItem('user');
      this.isAuthenticated = !!user;
      
      if (!this.isAuthenticated && this.$route.path !== '/login') {
        this.$router.push('/login');
      }
    },
    setupAxiosInterceptors() {
      axios.interceptors.response.use(
        response => response,
        error => {
          if (error.response?.status === 401) {
            this.logout();
          }
          return Promise.reject(error);
        }
      );
    },
    handleLoginSuccess() {
      this.checkAuth();
      if (this.$route.path !== '/dashboard') {
        this.$router.push('/dashboard');
      }
    },
    handleDataChange() {
      // Find the dashboard component if it exists
      const dashboard = this.$refs.currentView?.$refs?.dashboard;
      if (dashboard && typeof dashboard.fetchData === 'function') {
        dashboard.fetchData();
      }
    },
    async logout() {
      localStorage.removeItem('user');
      delete axios.defaults.headers.common['Authorization'];
      this.isAuthenticated = false;
      this.$router.push('/login');
    }
  },
  watch: {
    '$route'() {
      this.checkAuth();
    }
  }
};
</script>

<style>
:root {
  --sidebar-width: 280px;
  --header-height: 60px;
  --primary-color: #4361ee;
  --secondary-color: #3f37c9;
  --success-color: #4caf50;
  --info-color: #2196f3;
  --warning-color: #ff9800;
  --danger-color: #f44336;
  --light-color: #f8f9fa;
  --dark-color: #212529;
}

/* Global Styles */
body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: #f5f6fa;
  color: #333;
  line-height: 1.6;
}

/* Layout */
.app-wrapper {
  display: flex;
  min-height: 100vh;
}

/* Sidebar */
.sidebar {
  width: var(--sidebar-width);
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  display: flex;
  flex-direction: column;
  color: #fff;
  transition: all 0.3s ease;
  z-index: 1000;
}

.sidebar-header {
  height: var(--header-height);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 0;
}

.menu-section {
  margin-bottom: 1.5rem;
}

.menu-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nav-link {
  display: flex;
  align-items: center;
  color: rgba(255, 255, 255, 0.7);
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
}

.nav-link:hover,
.nav-link.router-link-active {
  color: #fff;
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link i {
  width: 20px;
  margin-right: 10px;
  font-size: 1.1rem;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* Main Content */
.main-content {
  flex: 1;
  margin-left: var(--sidebar-width);
  min-height: 100vh;
  background-color: #f5f6fa;
}

.content-wrapper {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Cards */
.card {
  border: none;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Forms */
.form-control,
.form-select {
  border-radius: 8px;
  border: 1px solid #dee2e6;
  padding: 0.6rem 1rem;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

/* Buttons */
.btn {
  border-radius: 8px;
  padding: 0.6rem 1.2rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

.btn-primary:hover {
  background-color: var(--secondary-color);
  border-color: var(--secondary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
}

/* Tables */
.table {
  background-color: #fff;
  border-radius: 10px;
  overflow: hidden;
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}

/* Utilities */
.shadow-sm {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
}

.rounded {
  border-radius: 10px !important;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }

  .main-content {
    margin-left: 0;
  }

  .sidebar.show {
    transform: translateX(0);
  }
}
</style>
