<template>
  <div class="register-container">
    <div class="register-box">
      <div class="register-brand text-center mb-4">
        <div class="register-logo mb-2">⚕️</div>
        <h2 class="mb-0">Klinik HiDUP sihat</h2>
        <p class="text-muted">Create your account to get started.</p>
      </div>
      <form @submit.prevent="handleSubmit" class="register-form">
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input 
            type="text" 
            class="form-control" 
            id="name" 
            v-model="name" 
            required 
            :class="{ 'is-invalid': errors.name }"
          />
          <div class="invalid-feedback" v-if="errors.name">
            {{ errors.name }}
          </div>
        </div>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input 
            type="text" 
            class="form-control" 
            id="username" 
            v-model="username" 
            required 
            :class="{ 'is-invalid': errors.username }"
            placeholder="Choose a unique username"
          />
          <div class="invalid-feedback" v-if="errors.username">
            {{ errors.username }}
          </div>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            v-model="email" 
            required 
            :class="{ 'is-invalid': errors.email }"
          />
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
          />
          <div class="invalid-feedback" v-if="errors.password">
            {{ errors.password }}
          </div>
        </div>
        <div class="alert alert-danger text-center" v-if="registerError">
          {{ registerError }}
        </div>
        <div class="alert alert-success text-center" v-if="registerSuccess">
          {{ registerSuccess }}
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-2" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Register
        </button>
        <router-link to="/login" class="btn btn-outline-secondary w-100">Back to Login</router-link>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'Register',
  setup() {
    const router = useRouter();
    const name = ref('');
    const username = ref('');
    const email = ref('');
    const password = ref('');
    const loading = ref(false);
    const registerError = ref('');
    const registerSuccess = ref('');
    const errors = ref({});

    const validateForm = () => {
      errors.value = {};
      if (!name.value) {
        errors.value.name = 'Name is required';
      }
      if (!username.value) {
        errors.value.username = 'Username is required';
      } else if (username.value.length < 3) {
        errors.value.username = 'Username must be at least 3 characters';
      }
      if (!email.value) {
        errors.value.email = 'Email is required';
      } else if (!/\S+@\S+\.\S+/.test(email.value)) {
        errors.value.email = 'Please enter a valid email';
      }
      if (!password.value) {
        errors.value.password = 'Password is required';
      } else if (password.value.length < 6) {
        errors.value.password = 'Password must be at least 6 characters';
      }
      return Object.keys(errors.value).length === 0;
    };

    const handleSubmit = async () => {
      if (!validateForm()) return;

      loading.value = true;
      registerError.value = '';
      registerSuccess.value = '';

      try {
        const response = await axios.post('/api/register', {
          name: name.value,
          username: username.value,
          email: email.value,
          password: password.value
        });
        
        registerSuccess.value = 'Registration successful! Redirecting to login...';
        
        // Redirect to login after successful registration
        setTimeout(() => {
          router.push('/login');
        }, 2000);
        
      } catch (error) {
        console.error('Registration failed:', error);
        registerError.value = error.response?.data?.message || 'Registration failed. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    return {
      name,
      username,
      email,
      password,
      loading,
      registerError,
      registerSuccess,
      errors,
      handleSubmit
    };
  }
};
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
}
.register-box {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem 2rem 2rem 2rem;
  background: white;
  border-radius: 14px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
.register-brand {
  margin-bottom: 1.5rem;
}
.register-logo {
  font-size: 3rem;
  display: block;
  margin: 0 auto 0.5rem auto;
  width: 60px;
  text-align: center;
}
.register-form {
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
.alert-danger, .alert-success {
  font-size: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
}
</style> 