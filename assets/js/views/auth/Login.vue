<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-brand text-center mb-4">
        <div class="login-logo mb-2">⚕️</div>
        <h2 class="mb-0">Klinik HiDUP sihat</h2>
        <p class="text-muted">Welcome back! Please sign in to continue.</p>
      </div>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input
            type="text"
            class="form-control"
            id="username"
            v-model="username"
            required
            :class="{ 'is-invalid': errors.username }"
            placeholder="Enter your username"
          >
          <div class="invalid-feedback" v-if="errors.username">
            {{ errors.username }}
          </div>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input
            type="password"
            class="form-control"
            id="password"
            v-model="password"
            required
            :class="{ 'is-invalid': errors.password }"
          >
          <div class="invalid-feedback" v-if="errors.password">
            {{ errors.password }}
          </div>
        </div>
        <div class="alert alert-danger text-center" v-if="loginError">
          {{ loginError }}
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-2" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Login
        </button>
        <router-link to="/register" class="btn btn-outline-secondary w-100">Sign Up</router-link>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../../services/AuthService';

export default {
  name: 'Login',
  emits: ['login-success'],
  setup(props, { emit }) {
    const router = useRouter();
    const username = ref('');
    const password = ref('');
    const loading = ref(false);
    const loginError = ref('');
    const errors = ref({});

    const validateForm = () => {
      errors.value = {};
      if (!username.value) {
        errors.value.username = 'Username is required';
      }
      if (!password.value) {
        errors.value.password = 'Password is required';
      }
      return Object.keys(errors.value).length === 0;
    };

    const handleLogin = async () => {
      if (!validateForm()) return;

      loading.value = true;
      loginError.value = '';

      try {
        const response = await AuthService.login(username.value, password.value);
        if (response && (response.token || response.user)) {
          console.log('✅ Login successful, user data:', response);
          
          // Emit login success event to parent App component first
          emit('login-success');
          
          // Ensure authentication state is properly established
          await ensureAuthenticationEstablished();
          
          // Get user roles for appropriate redirection
          const user = AuthService.getCurrentUser();
          const roles = user?.roles || [];
          
          // Redirect based on user role
          let redirectPath = '/dashboard';
          if (roles.includes('ROLE_ASSISTANT')) {
            redirectPath = '/registration';
          } else if (roles.includes('ROLE_DOCTOR')) {
            redirectPath = '/consultations/ongoing';
          }
          
          console.log('🚀 Redirecting to:', redirectPath);
          
          // Use router.replace instead of push to avoid back button issues
          await router.replace(redirectPath);
          
          console.log('✅ Navigation completed');
        }
      } catch (error) {
        console.error('❌ Login error:', error);
        loginError.value = error.response?.data?.message || 'Login failed. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    // New method to ensure authentication is properly established
    const ensureAuthenticationEstablished = async () => {
      try {
        const authReady = await AuthService.waitForAuthState();
        if (authReady) {
          console.log('✅ Authentication state confirmed');
        } else {
          console.warn('⚠️ Authentication state not confirmed after maximum attempts');
        }
      } catch (error) {
        console.error('❌ Error ensuring authentication state:', error);
      }
    };

    return {
      username,
      password,
      loading,
      loginError,
      errors,
      handleLogin
    };
  }
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
}
.login-box {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem 2rem 2rem 2rem;
  background: white;
  border-radius: 14px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
.login-brand {
  margin-bottom: 1.5rem;
}
.login-logo {
  font-size: 3rem;
  display: block;
  margin: 0 auto 0.5rem auto;
  width: 60px;
  text-align: center;
}
.login-form {
  margin-top: 1rem;
}
.btn-primary {
  background: #1e293b;
  border: none;
  font-weight: 600;
}
.btn-outline-secondary {
  border: 1px solid #cbd5e1;
  color: #1e293b;
  font-weight: 500;
}
.alert-danger {
  font-size: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
}
</style>
