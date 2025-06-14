<template>
  <div class="user-profile-menu dropdown" v-if="user">
    <button
      class="btn btn-light d-flex align-items-center dropdown-toggle"
      type="button"
      id="userMenuButton"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <span class="avatar me-2">{{ initials }}</span>
      <span class="user-name">{{ user.name }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
      <li class="dropdown-header">
        <div class="user-info">
          <div class="fw-bold">{{ user.name }}</div>
          <div class="text-muted small">{{ user.email }}</div>
        </div>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li class="dropdown-header">
        <div class="roles-section">
          <div class="small text-muted mb-1">Your Roles:</div>
          <div class="d-flex flex-wrap gap-1">
            <span 
              v-for="role in userRoles" 
              :key="role" 
              class="badge"
              :class="getRoleBadgeClass(role)"
            >
              {{ formatRole(role) }}
            </span>
            <span v-if="!userRoles || userRoles.length === 0" class="badge bg-secondary">
              User
            </span>
          </div>
        </div>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li>
        <router-link to="/profile" class="dropdown-item">
          <i class="fas fa-user me-2"></i>Profile
        </router-link>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li>
        <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
      </li>
    </ul>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../services/AuthService';

export default {
  name: 'UserProfileMenu',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  setup(props) {
    const router = useRouter();

    const initials = computed(() => {
      if (!props.user || !props.user.name) return '';
      return props.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
    });

    const userRoles = computed(() => {
      return props.user?.roles || [];
    });

    const formatRole = (role) => {
      const roleMap = {
        'ROLE_SUPER_ADMIN': 'Super Admin',
        'ROLE_DOCTOR': 'Doctor',
        'ROLE_ASSISTANT': 'Clinic Assistant',
        'ROLE_USER': 'User'
      };
      return roleMap[role] || role.replace('ROLE_', '').replace('_', ' ');
    };

    const getRoleBadgeClass = (role) => {
      const classMap = {
        'ROLE_SUPER_ADMIN': 'bg-danger',
        'ROLE_DOCTOR': 'bg-primary',
        'ROLE_ASSISTANT': 'bg-success',
        'ROLE_USER': 'bg-secondary'
      };
      return classMap[role] || 'bg-secondary';
    };

    const logout = () => {
      AuthService.logout();
      router.push('/login');
    };

    return {
      initials,
      userRoles,
      formatRole,
      getRoleBadgeClass,
      logout
    };
  }
};
</script>

<style scoped>
.user-profile-menu {
  position: relative;
  display: flex;
  align-items: center;
}
.avatar {
  width: 32px;
  height: 32px;
  background: #007bff;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.1rem;
}
.user-name {
  font-weight: 500;
  font-size: 1rem;
}
.dropdown-toggle::after {
  margin-left: 0.5em;
}

.dropdown-menu {
  min-width: 280px;
}

.dropdown-header {
  padding: 0.75rem 1rem;
  background: none;
  border: none;
}

.user-info {
  text-align: left;
}

.roles-section {
  text-align: left;
}

.roles-section .badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

.dropdown-item {
  padding: 0.5rem 1rem;
}

.dropdown-item i {
  width: 16px;
  text-align: center;
}
</style> 