<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card mt-5">
          <div class="card-body">
            <h2 class="text-center mb-4">Login</h2>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <form @submit.prevent="handleSubmit">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                  type="email"
                  class="form-control"
                  id="email"
                  v-model="email"
                  required
                  :disabled="loading"
                  v-enter-submit
                />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                  type="password"
                  class="form-control"
                  id="password"
                  v-model="password"
                  required
                  :disabled="loading"
                  v-enter-submit
                />
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary" :disabled="loading">
                  {{ loading ? 'Logging in...' : 'Login' }}
                </button>
              </div>
              <div class="text-center mt-3">
                <router-link to="/register">Don't have an account? Register</router-link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Login',
  emits: ['login-success'],
  data() {
    return {
      email: '',
      password: '',
      loading: false,
      error: null
    };
  },
  methods: {
    async handleSubmit() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/login', {
          email: this.email,
          password: this.password
        });
        
        if (response.data && response.data.user) {
          localStorage.setItem('user', JSON.stringify(response.data.user));
          this.$emit('login-success');
          this.$router.push('/dashboard');
        } else {
          this.error = 'Invalid response from server';
        }
      } catch (error) {
        console.error('Login failed:', error);
        this.error = error.response?.data?.message || 'Login failed. Please try again.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
