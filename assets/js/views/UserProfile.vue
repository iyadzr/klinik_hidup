<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card">
          <div class="card-header">
            <h4 class="mb-0 d-flex align-items-center">
              <i class="fas fa-user-circle text-primary me-2"></i>
              User Profile
            </h4>
          </div>
          <div class="card-body">
            <div class="text-center mb-4">
              <div class="profile-avatar">
                {{ userInitials }}
              </div>
              <h5 class="mt-3">{{ user.name }}</h5>
              <p class="text-muted">{{ user.email }}</p>
            </div>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="success" class="alert alert-success">{{ success }}</div>

            <form @submit.prevent="updateProfile">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.name" 
                  required
                  :disabled="loading">
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input 
                  type="email" 
                  class="form-control" 
                  v-model="form.email" 
                  required
                  :disabled="loading">
              </div>

              <div class="mb-3">
                <label class="form-label">Username</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.username" 
                  required
                  :disabled="loading">
              </div>

              <hr class="my-4">

              <h6 class="mb-3">Change Password</h6>
              
              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <div class="input-group">
                  <input 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    class="form-control" 
                    v-model="form.currentPassword"
                    :disabled="loading">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary" 
                    @click="showCurrentPassword = !showCurrentPassword">
                    <i :class="showCurrentPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <small class="text-muted">Leave blank to keep current password</small>
              </div>

              <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                  <input 
                    :type="showNewPassword ? 'text' : 'password'" 
                    class="form-control" 
                    v-model="form.newPassword"
                    minlength="8"
                    :disabled="loading">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary" 
                    @click="showNewPassword = !showNewPassword">
                    <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <small class="text-muted">Minimum 8 characters</small>
              </div>

              <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                  <input 
                    :type="showConfirmPassword ? 'text' : 'password'" 
                    class="form-control" 
                    v-model="form.confirmPassword"
                    minlength="8"
                    :disabled="loading"
                    :class="{'is-invalid': !isPasswordMatch && form.confirmPassword}">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary" 
                    @click="showConfirmPassword = !showConfirmPassword">
                    <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <div class="invalid-feedback" v-if="!isPasswordMatch && form.confirmPassword">
                  Passwords do not match
                </div>
              </div>

              <div class="d-flex gap-2">
                <button 
                  type="submit" 
                  class="btn btn-primary" 
                  :disabled="loading || (form.newPassword && !isPasswordMatch)">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Updating...' : 'Update Profile' }}
                </button>
                <router-link to="/dashboard" class="btn btn-secondary">
                  Cancel
                </router-link>
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
  name: 'UserProfile',
  data() {
    return {
      user: {},
      form: {
        name: '',
        email: '',
        username: '',
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
      },
      loading: false,
      error: null,
      success: null,
      showCurrentPassword: false,
      showNewPassword: false,
      showConfirmPassword: false
    };
  },
  computed: {
    userInitials() {
      if (!this.user.name) return '';
      return this.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
    },
    isPasswordMatch() {
      if (!this.form.newPassword) return true;
      return this.form.newPassword === this.form.confirmPassword;
    }
  },
  mounted() {
    this.loadUserData();
  },
  methods: {
    loadUserData() {
      const userStr = localStorage.getItem('user');
      if (userStr) {
        try {
          this.user = JSON.parse(userStr);
          this.form.name = this.user.name || '';
          this.form.email = this.user.email || '';
          this.form.username = this.user.username || '';
        } catch (e) {
          this.error = 'Error loading user data';
        }
      } else {
        this.$router.push('/login');
      }
    },
    async updateProfile() {
      this.loading = true;
      this.error = null;
      this.success = null;

      try {
        // Validate form
        if (!this.form.name || !this.form.email || !this.form.username) {
          this.error = 'Please fill in all required fields';
          return;
        }

        if (this.form.newPassword && !this.isPasswordMatch) {
          this.error = 'New passwords do not match';
          return;
        }

        if (this.form.newPassword && !this.form.currentPassword) {
          this.error = 'Current password is required to change password';
          return;
        }

        // Prepare update data
        const updateData = {
          name: this.form.name,
          email: this.form.email,
          username: this.form.username
        };

        // Add password fields if changing password
        if (this.form.newPassword) {
          updateData.currentPassword = this.form.currentPassword;
          updateData.newPassword = this.form.newPassword;
        }

        // Make API call to update profile
        const response = await axios.put('/api/profile', updateData);

        // Update localStorage with new user data
        const updatedUser = {
          ...this.user,
          name: this.form.name,
          email: this.form.email,
          username: this.form.username
        };
        localStorage.setItem('user', JSON.stringify(updatedUser));
        this.user = updatedUser;

        // Clear password fields
        this.form.currentPassword = '';
        this.form.newPassword = '';
        this.form.confirmPassword = '';

        this.success = 'Profile updated successfully';

        // Emit event to update header if needed
        this.$emit('profile-updated');

      } catch (error) {
        console.error('Profile update error:', error);
        this.error = error.response?.data?.message || 'Failed to update profile';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.profile-avatar {
  width: 80px;
  height: 80px;
  background: #007bff;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 2rem;
  margin: 0 auto;
}

.card {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.input-group .btn {
  border-left: none;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}
</style> 