<template>
  <div class="app-wrapper">
    <!-- Header Bar -->
    <header v-if="isAuthenticated && !isAuthPage" class="app-header">
      <div class="header-left">
        <span class="header-title">Klinik HiDUP sihat</span>
      </div>
      <div class="header-right">
        <UserProfileMenu />
      </div>
    </header>

    <div class="main-area">
      <!-- Sidebar (move below backdrop for proper stacking) -->
      <nav :class="['sidebar', { open: isSidebarOpen } ]" v-if="isAuthenticated && !isAuthPage">
        <ul class="nav flex-column sidebar-nav">
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
          <li class="nav-item">
            <router-link to="/queue-display" class="nav-link">
              <i class="fas fa-tv fa-fw"></i>
              <span>Queue Display</span>
            </router-link>
          </li>
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
          <li class="nav-item" v-if="isAssistant">
            <router-link to="/payments" class="nav-link">
              <i class="fas fa-money-bill fa-fw"></i>
              <span>Payments</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/financial" class="nav-link">
              <i class="fas fa-chart-bar fa-fw"></i>
              <span>Financial</span>
            </router-link>
          </li>
        </ul>
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
  </div>
</template>

<style src="../styles/responsive-sidebar.css"></style>
<style>
.app-header {
  width: 100vw;
  min-width: 100vw;
  height: 60px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.03);
  position: fixed;
  left: 0;
  top: 0;
  z-index: 2000;
}
.header-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #222;
}
.header-right {
  display: flex;
  align-items: center;
}
.app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
.main-area {
  display: flex;
  flex: 1 1 auto;
  margin-top: 60px;
  min-height: 0;
}
.sidebar {
  width: 260px;
  min-width: 220px;
  max-width: 300px;
  height: calc(100vh - 60px);
  position: fixed;
  left: 0;
  top: 60px;
  margin-top: 0;
  flex-shrink: 0;
  background: #f8f9fa !important;
  color: #222;
  border-right: 1px solid #e0e0e0;
  padding-top: 1.5rem;
  z-index: 1000;
  overflow-y: auto;
}
.sidebar-nav {
  width: 100%;
  padding: 0;
  margin: 0;
}
.sidebar .nav-link {
  color: #222;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  margin: 0.25rem 0;
  font-size: 1rem;
  font-weight: 500;
  transition: background 0.2s, color 0.2s;
}
.sidebar .nav-link.router-link-active,
.sidebar .nav-link:hover {
  background: #e9ecef;
  color: #007bff;
}
.sidebar .nav-link i {
  width: 20px;
  font-size: 1.1rem;
}
.main-content {
  flex: 1 1 0%;
  min-width: 0;
  background: #f5f6fa;
  min-height: calc(100vh - 60px);
  overflow-x: auto;
  margin-left: 260px;
}
.content-wrapper {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
@media (max-width: 768px) {
  .app-header {
    padding: 0 1rem;
  }
  .sidebar {
    width: 180px;
    min-width: 120px;
  }
  .main-content {
    margin-left: 180px;
  }
  .content-wrapper {
    padding: 1rem;
  }
}
@media (max-width: 576px) {
  .sidebar {
    width: 100vw;
    min-width: 0;
    max-width: 100vw;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 2000;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .main-content {
    margin-left: 0 !important;
    padding: 0.5rem !important;
  }
  .content-wrapper {
    padding: 0.5rem !important;
  }
}
</style>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from './services/AuthService';
import UserProfileMenu from './components/UserProfileMenu.vue';

export default {
  name: 'App',
  components: { UserProfileMenu },
  setup() {
    const router = useRouter();
    const isSidebarOpen = ref(false);
    const currentUser = ref(null);

    const isAuthenticated = computed(() => AuthService.isAuthenticated());
    const isSuperAdmin = computed(() => AuthService.isSuperAdmin());
    const userRoles = computed(() => currentUser.value?.roles || []);

    const toggleSidebar = () => {
      isSidebarOpen.value = !isSidebarOpen.value;
    };

    const handleLogout = () => {
      AuthService.logout();
      currentUser.value = null;
      router.push('/login');
    };

    onMounted(() => {
      const user = AuthService.getCurrentUser();
      if (user) {
        currentUser.value = user;
        AuthService.setAuthHeader(user.token);
      }
    });

    return {
      isSidebarOpen,
      currentUser,
      isAuthenticated,
      isSuperAdmin,
      userRoles,
      toggleSidebar,
      handleLogout
    };
  }
};
</script>
