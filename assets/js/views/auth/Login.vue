<template>
  <div class="login-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <i class="fas fa-clinic-medical fa-3x text-primary"></i>
                <h4 class="mt-3">Clinic Management System</h4>
                <p class="text-muted">{{ isLogin ? 'Sign in to your account' : 'Create a new account' }}</p>
              </div>

              <!-- Login Form -->
              <form v-if="isLogin" @submit.prevent="login">
                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-user"></i>
                    </span>
                    <input type="text" 
                           v-model="form.username" 
                           class="form-control" 
                           required
                           autocomplete="username">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'"
                           v-model="form.password" 
                           class="form-control" 
                           required
                           autocomplete="current-password">
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            @click="showPassword = !showPassword">
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <button type="submit" 
                        class="btn btn-primary w-100" 
                        :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Signing in...' : 'Sign In' }}
                </button>

                <div class="text-center mt-4">
                  <p class="mb-0">
                    Don't have an account? 
                    <a href="#" @click.prevent="isLogin = false">Register here</a>
                  </p>
                </div>
              </form>

              <!-- Registration Form -->
              <form v-else @submit.prevent="register">
                <div class="mb-3">
                  <label class="form-label">Full Name</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-user"></i>
                    </span>
                    <input type="text" 
                           v-model="form.name" 
                           class="form-control" 
                           required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" 
                           v-model="form.email" 
                           class="form-control" 
                           required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-user-circle"></i>
                    </span>
                    <input type="text" 
                           v-model="form.username" 
                           class="form-control" 
                           required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'"
                           v-model="form.password" 
                           class="form-control" 
                           required
                           minlength="8">
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            @click="showPassword = !showPassword">
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <small class="text-muted">Password must be at least 8 characters</small>
                </div>

                <div class="mb-4">
                  <label class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'"
                           v-model="form.confirmPassword" 
                           class="form-control" 
                           required
                           minlength="8"
                           :class="{'is-invalid': !isPasswordMatch && form.confirmPassword}">
                  </div>
                  <div class="invalid-feedback" v-if="!isPasswordMatch && form.confirmPassword">
                    Passwords do not match
                  </div>
                </div>

                <button type="submit" 
                        class="btn btn-primary w-100" 
                        :disabled="loading || !isPasswordMatch">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Creating account...' : 'Create Account' }}
                </button>

                <div class="text-center mt-4">
                  <p class="mb-0">
                    Already have an account? 
                    <a href="#" @click.prevent="isLogin = true">Login here</a>
                  </p>
                </div>
              </form>

              <div class="alert alert-danger mt-3" v-if="error">
                {{ error }}
              </div>
            </div>
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
  data() {
    return {
      isLogin: true,
      form: {
        name: '',
        email: '',
        username: '',
        password: '',
        confirmPassword: ''
      },
      showPassword: false,
      loading: false,
      error: null
    };
  },
  computed: {
    isPasswordMatch() {
      return !this.isLogin && 
             this.form.password && 
             this.form.password === this.form.confirmPassword;
    }
  },
  methods: {
    async login() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post('/api/login', {
          username: this.form.username,
          password: this.form.password
        });

        // Store user data in localStorage
        localStorage.setItem('user', JSON.stringify(response.data.user));
        
        // Emit login success event
        this.$emit('login-success');
        
        // Redirect to dashboard
        this.$router.push('/dashboard');
      } catch (error) {
        this.error = error.response?.data?.message || 'Invalid username or password';
      } finally {
        this.loading = false;
      }
    },
    async register() {
      // Clear any previous errors
      this.error = null;

      // Log form data
      console.log('Form data before submission:', this.form);

      // Frontend validation
      const frontendValidation = ['name', 'email', 'username', 'password', 'confirmPassword'];
      for (const field of frontendValidation) {
        if (!this.form[field] || this.form[field].trim() === '') {
          this.error = `Please fill in your ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}`;
          console.log('Validation failed for field:', field);
          return;
        }
      }

      if (!this.isPasswordMatch) {
        this.error = 'Passwords do not match';
        console.log('Password match validation failed');
        return;
      }

      this.loading = true;

      try {
        // Only send required fields to backend
        const formData = {
          name: this.form.name.trim(),
          email: this.form.email.trim(),
          username: this.form.username.trim(),
          password: this.form.password
        };

        console.log('Sending registration data:', formData);

        // First try without JSON.stringify
        const response = await axios({
          method: 'POST',
          url: '/api/register',
          data: formData,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });
        
        console.log('Registration successful:', response.data);
        
        // Clear form data
        this.form = {
          name: '',
          email: '',
          username: '',
          password: '',
          confirmPassword: ''
        };
        
        // Auto login after registration
        await this.login();
      } catch (error) {
        console.error('Registration error details:', {
          status: error.response?.status,
          statusText: error.response?.statusText,
          data: error.response?.data,
          headers: error.response?.headers,
          config: error.config
        });

        if (error.response?.data?.message) {
          this.error = error.response.data.message;
        } else if (error.response?.status === 400) {
          this.error = 'Please check all required fields are filled correctly';
        } else {
          this.error = 'Error creating account. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  background-color: #f8f9fa;
}

.card {
  border: none;
  border-radius: 10px;
}

.input-group-text {
  background-color: transparent;
}

.btn-outline-secondary {
  border-left: none;
}

.btn-outline-secondary:hover {
  background-color: transparent;
  color: #6c757d;
}

.fa-clinic-medical {
  color: #0d6efd;
}
</style>
