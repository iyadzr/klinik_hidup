<template>
  <div class="login-container">
    <div class="login-box">
      <h2 class="text-center mb-4">Login</h2>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="email"
            required
            :class="{ 'is-invalid': errors.email }"
          >
          <div class="invalid-feedback" v-if="errors.email">
            {{ errors.email }}
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

        <div class="alert alert-danger" v-if="loginError">
          {{ loginError }}
        </div>

        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Login
        </button>
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
  setup() {
    const router = useRouter();
    const email = ref('');
    const password = ref('');
    const loading = ref(false);
    const loginError = ref('');
    const errors = ref({});

    const validateForm = () => {
      errors.value = {};
      if (!email.value) {
        errors.value.email = 'Email is required';
      } else if (!/\S+@\S+\.\S+/.test(email.value)) {
        errors.value.email = 'Please enter a valid email';
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
        const response = await AuthService.login(email.value, password.value);
        if (response.token) {
          router.push('/dashboard');
        }
      } catch (error) {
        loginError.value = error.response?.data?.message || 'Login failed. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    return {
      email,
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
  background-color: #f8f9fa;
}

.login-box {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.login-form {
  margin-top: 1.5rem;
}
</style>
