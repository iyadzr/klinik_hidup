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
              <div class="profile-avatar-container">
                <img v-if="user.profileImage" :src="user.profileImage" alt="Profile" class="profile-avatar-image">
                <div v-else class="profile-avatar-initials">{{ userInitials }}</div>
                <button class="upload-btn" @click="openImageUpload">
                  <i class="fas fa-camera"></i>
                </button>
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

  <!-- Profile Image Upload Modal (true overlay) -->
  <div v-if="showImageUploadModal">
    <div class="profile-modal-backdrop"></div>
    <div class="profile-modal-outer">
      <div class="modal-content" style="max-width: 400px; width: 100%;">
        <div class="modal-header">
          <h5 class="modal-title">Update Profile Picture</h5>
          <button type="button" class="btn-close" @click="closeImageUpload"></button>
        </div>
        <div class="modal-body text-center">
          <input type="file" ref="fileInput" @change="handleFileSelect" accept="image/*" class="d-none"/>
          <button class="btn btn-primary mb-3" @click="triggerFileInput">
            <i class="fas fa-upload me-2"></i>Choose Image
          </button>
          <div v-if="selectedFile" class="text-muted mb-3">
            {{ selectedFile.name }}
          </div>
          <button v-if="user.profileImage" class="btn btn-outline-danger btn-sm" @click="removeProfileImage">
            <i class="fas fa-trash me-1"></i>Remove Image
          </button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeImageUpload">Cancel</button>
          <button type="button" class="btn btn-primary" @click="uploadImage" :disabled="!selectedFile || uploading">
            <span v-if="uploading" class="spinner-border spinner-border-sm me-2"></span>
            {{ uploading ? 'Uploading...' : 'Upload' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { nextTick } from 'vue';

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
      showConfirmPassword: false,
      showImageUploadModal: false,
      selectedFile: null,
      uploading: false
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
  async mounted() {
    console.log('UserProfile component mounted');
    // Force remove any leftover modal/backdrop
    this.showImageUploadModal = false;
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    await this.loadUserData();
  },
  methods: {
    async loadUserData() {
      this.loading = true;
      this.error = null;
      try {
        // First, get the user ID and token from localStorage
        const userStr = localStorage.getItem('user');
        if (!userStr) {
          this.$router.push('/login');
          return;
        }
        const localUser = JSON.parse(userStr);
        const userId = localUser.id;
        const token = localUser.token;
        if (!userId || !token) {
          throw new Error('User ID or token not found in local storage.');
        }
        // Fetch the latest user data from the server with Authorization header
        const response = await axios.get(`/api/users/${userId}`, { headers: { Authorization: `Bearer ${token}` } });
        this.user = response.data;
        // Populate the form with the fresh data
        this.form.name = this.user.name || '';
        this.form.email = this.user.email || '';
        this.form.username = this.user.username || '';
        // Update localStorage with the fresh data
        const updatedUser = { ...localUser, ...this.user };
        localStorage.setItem('user', JSON.stringify(updatedUser));
      } catch (e) {
        console.error('Error loading user data:', e);
        this.error = 'Failed to load user data. Please try refreshing the page.';
        // If API fails, fallback to localStorage data
        const userStr = localStorage.getItem('user');
        if (userStr) {
          this.user = JSON.parse(userStr);
          this.form.name = this.user.name || '';
          this.form.email = this.user.email || '';
          this.form.username = this.user.username || '';
        } else {
          this.$router.push('/login');
        }
      } finally {
        this.loading = false;
      }
    },
    openImageUpload() {
      this.showImageUploadModal = true;
    },
    closeImageUpload() {
      console.log('Closing image upload modal');
      this.showImageUploadModal = false;
      this.selectedFile = null;
    },
    handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        if (!file.type.startsWith('image/')) {
          alert('Please select an image file');
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          alert('File size must be less than 5MB');
          return;
        }
        this.selectedFile = file;
      }
    },
    async uploadImage() {
      if (!this.selectedFile) return;
      this.uploading = true;
      try {
        const formData = new FormData();
        formData.append('profileImage', this.selectedFile);
        const userStr = localStorage.getItem('user');
        const token = userStr ? JSON.parse(userStr).token : null;
        const headers = { 'Content-Type': 'multipart/form-data' };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const response = await axios.post('/api/users/profile-image', formData, { headers });
        if (response.data.profileImageUrl) {
          this.user.profileImage = response.data.profileImageUrl;
          // Update localStorage correctly
          const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
          currentUser.profileImage = response.data.profileImageUrl;
          localStorage.setItem('user', JSON.stringify(currentUser));
          this.$emit('profile-updated');
        }
        this.closeImageUpload();
      } catch (error) {
        console.error('Error uploading image:', error);
        alert('Error uploading image: ' + (error.response?.data?.error || error.response?.data?.message || error.message));
      } finally {
        this.uploading = false;
      }
    },
    async removeProfileImage() {
      if (!confirm('Are you sure you want to remove your profile picture?')) return;
      try {
        const userStr = localStorage.getItem('user');
        const token = userStr ? JSON.parse(userStr).token : null;
        const headers = token ? { Authorization: `Bearer ${token}` } : {};
        await axios.delete('/api/users/profile-image', { headers });
        this.user.profileImage = null;
        // Update localStorage correctly
        const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
        currentUser.profileImage = null;
        localStorage.setItem('user', JSON.stringify(currentUser));
        this.$emit('profile-updated');
        this.closeImageUpload();
      } catch (error) {
        console.error('Error removing image:', error);
        alert('Error removing image: ' + (error.response?.data?.error || error.response?.data?.message || error.message));
      }
    },
    async updateProfile() {
      this.loading = true;
      this.error = null;
      this.success = null;
      try {
        const userId = this.user.id;
        const userStr = localStorage.getItem('user');
        const token = userStr ? JSON.parse(userStr).token : null;
        const headers = token ? { Authorization: `Bearer ${token}` } : {};
        if (!userId) {
          throw new Error('User ID not found.');
        }
        const response = await axios.put(`/api/users/${userId}`, this.form, { headers });
        this.user = response.data.user;
        localStorage.setItem('user', JSON.stringify(this.user));
        this.success = 'Profile updated successfully!';
        // Clear password fields after successful update
        this.form.currentPassword = '';
        this.form.newPassword = '';
        this.form.confirmPassword = '';
        // Optionally, reload user data to ensure UI is fully in sync
        await this.loadUserData();
      } catch (err) {
        this.error = err.response?.data?.message || 'An error occurred during profile update.';
      } finally {
        this.loading = false;
      }
    },
    async triggerFileInput() {
      await nextTick();
      if (this.$refs.fileInput) {
        this.$refs.fileInput.click();
      }
    }
  }
};
</script>

<style scoped>
.profile-avatar-container {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto;
}
.profile-avatar-image, .profile-avatar-initials {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.profile-avatar-initials {
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #007bff;
  color: #fff;
  font-size: 2.5rem;
  font-weight: bold;
}
.upload-btn {
  position: absolute;
  bottom: 5px;
  right: 5px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #ddd;
  color: #007bff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.upload-btn:hover {
  background: #007bff;
  color: #fff;
}
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

.profile-modal-backdrop {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1040;
}
.profile-modal-outer {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
</style> 