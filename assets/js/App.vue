<template>
  <div class="app-wrapper">
    <!-- Header Bar -->
    <header v-if="isAuthenticated && !isAuthPage" class="app-header">
      <div class="header-left">
        <button v-if="isMobile" class="sidebar-toggle-btn" @click="toggleSidebar">
          <i class="fas fa-bars"></i>
        </button>
        <span class="header-title">Klinik HiDUP sihat</span>
      </div>
      <div class="header-right">
        <UserProfileMenu :user="currentUser" @profile-updated="updateCurrentUser" />
      </div>
    </header>

    <div class="main-area">
      <!-- Sidebar Backdrop -->
      <div v-if="isMobile && isSidebarOpen" class="sidebar-backdrop" @click="closeSidebar"></div>
      
      <!-- Sidebar -->
      <nav v-if="isAuthenticated && !isAuthPage" :class="['sidebar', { open: isSidebarOpen }]">
        <ul class="nav flex-column sidebar-nav">
          <li v-for="item in filteredMenu" :key="item.path" class="nav-item">
            <router-link 
              :to="item.path" 
              class="nav-link" 
              @click="handleLinkClick"
              :class="{ 'router-link-active': isMenuItemActive(item) }"
            >
              <i :class="item.icon"></i> {{ item.label }}
            </router-link>
          </li>
          
          <li class="nav-item" v-if="hasRole('ROLE_SUPER_ADMIN')">
            <router-link to="/backup-management" class="nav-link">
              <i class="fas fa-database me-2"></i>Backup Management
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- Main Content -->
      <main :class="['main-content', { 'auth-page': isAuthPage }]">
        <div class="content-wrapper">
          <Suspense>
            <template #default>
              <router-view 
                :key="$route.fullPath"
                @patient-added="handleDataChange" 
                @patient-updated="handleDataChange" 
                @patient-deleted="handleDataChange"
                @login-success="handleLoginSuccess"
                ref="currentView"
              ></router-view>
            </template>
            <template #fallback>
              <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </template>
          </Suspense>
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
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
.main-content.auth-page {
  margin-left: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
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
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AuthService from './services/AuthService';
import UserProfileMenu from './components/UserProfileMenu.vue';

export default {
  name: 'App',
  components: { UserProfileMenu },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const isSidebarOpen = ref(false);
    const currentUser = ref(null);
    const isMobile = ref(window.innerWidth < 992);

    // Make computed properties reactive to currentUser changes
    const isAuthenticated = computed(() => !!currentUser.value && !!currentUser.value.token);
    const isSuperAdmin = computed(() => currentUser.value?.roles?.includes('ROLE_SUPER_ADMIN') || false);
    const userRoles = computed(() => currentUser.value?.roles || []);
    const isAuthPage = computed(() => ['/login', '/register'].includes(route.path));

    const toggleSidebar = () => {
      isSidebarOpen.value = !isSidebarOpen.value;
    };

    const closeSidebar = () => {
      isSidebarOpen.value = false;
    };

    const handleLinkClick = () => {
      if (isMobile.value) {
        closeSidebar();
      }
    };
    
    const handleResize = () => {
      isMobile.value = window.innerWidth < 992;
      if (!isMobile.value) {
        isSidebarOpen.value = false;
      }
    };

    const updateCurrentUser = () => {
      currentUser.value = AuthService.getCurrentUser();
    };

    let tokenCheckInterval = null;
    let expirationWarningShown = false;

    const startTokenExpirationMonitoring = () => {
      // Check token expiration every 5 minutes
      tokenCheckInterval = setInterval(() => {
        if (AuthService.isAuthenticated()) {
          const timeRemaining = AuthService.getTokenTimeRemaining();
          
          if (timeRemaining && timeRemaining > 0) {
            const minutesRemaining = Math.floor(timeRemaining / (1000 * 60));
            
            // Warn user when 30 minutes remaining (only once)
            if (minutesRemaining <= 30 && minutesRemaining > 5 && !expirationWarningShown) {
              expirationWarningShown = true;
              console.log(`⚠️ Session expires in ${minutesRemaining} minutes`);
              
              // Show warning toast if available
              if (window.Vue && window.Vue.config && window.Vue.config.globalProperties.$toast) {
                window.Vue.config.globalProperties.$toast.warning(
                  `Your session will expire in ${minutesRemaining} minutes. Please save your work.`,
                  { timeout: 8000 }
                );
              }
            }
            
            // Final warning when 5 minutes remaining
            if (minutesRemaining <= 5 && minutesRemaining > 0) {
              console.log(`🚨 Session expires in ${minutesRemaining} minutes - final warning`);
              
              if (window.Vue && window.Vue.config && window.Vue.config.globalProperties.$toast) {
                window.Vue.config.globalProperties.$toast.error(
                  `Session expires in ${minutesRemaining} minutes! Please save your work and refresh to continue.`,
                  { timeout: 10000 }
                );
              }
            }
          }
        } else {
          // Stop monitoring if user is not authenticated
          stopTokenExpirationMonitoring();
        }
      }, 5 * 60 * 1000); // Check every 5 minutes
    };

    const stopTokenExpirationMonitoring = () => {
      if (tokenCheckInterval) {
        clearInterval(tokenCheckInterval);
        tokenCheckInterval = null;
        expirationWarningShown = false;
      }
    };

    // Define the menu items with role-based access
    const menu = [
      // Core menu items - available to ALL authenticated users
      {
        path: '/dashboard',
        label: 'Dashboard',
        icon: 'fas fa-tachometer-alt',
        roles: ['ROLE_USER', 'ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN']
      },
      
      // Role-specific primary menu items (ordered by priority)
      {
        path: '/registration',
        label: 'Patient Registration',
        icon: 'fas fa-user-plus',
        roles: ['ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN'],
        priority: 1 // High priority for assistants
      },
      {
        path: '/consultations/ongoing',
        label: 'Patient Consultation',
        icon: 'fas fa-stethoscope',
        roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN'],
        priority: 1 // High priority for doctors
      },
      {
        path: '/patients',
        label: 'Patients',
        icon: 'fas fa-user-injured',
        roles: ['ROLE_USER', 'ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN']
      },
      {
        path: '/queue',
        label: 'Queue Management',
        icon: 'fas fa-list-ol',
        roles: ['ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN']
      },
      {
        path: '/doctors',
        label: 'Doctors',
        icon: 'fas fa-user-md',
        roles: ['ROLE_USER', 'ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN']
      },
      {
        path: '/queue-display',
        label: 'Queue Display',
        icon: 'fas fa-tv',
        roles: ['ROLE_USER', 'ROLE_DOCTOR', 'ROLE_ASSISTANT', 'ROLE_SUPER_ADMIN']
      },
      
      // Additional menu items
      {
        path: '/medications',
        label: 'Medications',
        icon: 'fas fa-pills',
        roles: ['ROLE_DOCTOR', 'ROLE_SUPER_ADMIN']
      },
      // {
      //   path: '/financial',
      //   label: 'Financial Dashboard',
      //   icon: 'fas fa-chart-line',
      //   roles: ['ROLE_SUPER_ADMIN']
      // },
      {
        path: '/payments/dashboard',
        label: 'Payments Dashboard',
        icon: 'fas fa-credit-card',
        roles: ['ROLE_SUPER_ADMIN']
      },
      
      // Admin-only menu items
      {
        path: '/admin/users',
        label: 'User Management',
        icon: 'fas fa-users',
        roles: ['ROLE_SUPER_ADMIN']
      },
      {
        path: '/admin/settings',
        label: 'System Settings',
        icon: 'fas fa-cog',
        roles: ['ROLE_SUPER_ADMIN']
      }
    ];

    const handleLogout = () => {
      AuthService.logout();
      currentUser.value = null;
      router.push('/login');
    };
    

    const handleLoginSuccess = () => {
      // Add a small delay to ensure the login process is complete
      setTimeout(() => {
        loadUserData();
        // Force reactivity update
        currentUser.value = { ...AuthService.getCurrentUser() };
      }, 100);
    };

    const handleDataChange = () => {
      // Handle data changes if needed
      // This is referenced in router-view events
    };

    const loadUserData = () => {
      const user = AuthService.getCurrentUser();
      if (user) {
        currentUser.value = user;
        AuthService.setAuthHeader(user.token);
      } else {
        currentUser.value = null;
      }
    };

    const hasRole = (role) => {
      if (!currentUser.value || !currentUser.value.roles) return false;
      return currentUser.value.roles.includes(role);
    };

    const filteredMenu = computed(() => {
      if (!currentUser.value) return [];
      
      // Ensure all users have at least ROLE_USER
      const currentRoles = currentUser.value.roles && currentUser.value.roles.length > 0 
        ? currentUser.value.roles 
        : ['ROLE_USER'];

      return menu.filter(item => 
        item.roles.some(role => currentRoles.includes(role))
      );
    });

    const isMenuItemActive = (item) => {
      const currentPath = route.path;
      
      // Special handling for consultation-related routes
      if (item.path === '/consultations/ongoing') {
        return currentPath.startsWith('/consultations');
      }
      
      // Special handling for registration routes
      if (item.path === '/registration') {
        return currentPath === '/registration';
      }
      
      // Default exact match or starts with match for most menu items
      return currentPath === item.path || currentPath.startsWith(item.path + '/');
    };

    // Watch for route changes to refresh authentication state
    watch(() => route.path, (newPath) => {
      loadUserData();
      // If navigating to dashboard and user is authenticated, ensure state is updated
      if (newPath === '/dashboard' && AuthService.isAuthenticated()) {
        setTimeout(() => {
          currentUser.value = { ...AuthService.getCurrentUser() };
        }, 50);
      }
    });

    onMounted(() => {
      currentUser.value = AuthService.getCurrentUser();
      window.addEventListener('resize', handleResize);
      
      // Watch for route changes to close sidebar
      watch(() => route.path, () => {
          if (isMobile.value) {
              closeSidebar();
          }
      });

      // Set up token expiration monitoring
      startTokenExpirationMonitoring();
    });

    onUnmounted(() => {
      window.removeEventListener('resize', handleResize);
      stopTokenExpirationMonitoring(); // Clean up token monitoring
    });

    return {
      isSidebarOpen,
      isMobile,
      currentUser,
      isAuthenticated,
      isSuperAdmin,
      userRoles,
      isAuthPage,
      hasRole,
      toggleSidebar,
      closeSidebar,
      handleLinkClick,
      updateCurrentUser,
      handleLoginSuccess,
      handleDataChange,
      filteredMenu,
      isMenuItemActive
    };
  }
};
</script>
