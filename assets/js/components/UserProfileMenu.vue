<template>
  <div class="user-profile-menu dropdown" v-if="user">
    <button
      class="btn btn-light d-flex align-items-center dropdown-toggle"
      type="button"
      id="userMenuButton"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <div class="avatar me-2" @click.stop="showImageUpload = !showImageUpload">
        <img v-if="user.profileImage" 
             :src="user.profileImage" 
             :alt="user.name + ' Profile'" 
             class="profile-image" 
        />
        <span v-else class="initials">{{ initials }}</span>
        <i class="fas fa-camera upload-icon" v-if="!user.profileImage"></i>
      </div>
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
    
    <!-- Profile Image Upload Modal -->
    <div class="modal fade" id="profileImageModal" tabindex="-1" v-if="showImageUpload">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Update Profile Picture</h5>
            <button type="button" class="btn-close" @click="closeImageUpload"></button>
          </div>
          <div class="modal-body text-center">
            <div class="current-avatar mb-3">
              <img v-if="user.profileImage" 
                   :src="user.profileImage" 
                   class="current-profile-image" 
                   :alt="user.name + ' Profile'"
              />
              <div v-else class="current-initials">{{ initials }}</div>
            </div>
            
            <input 
              type="file" 
              ref="fileInput" 
              @change="handleFileSelect" 
              accept="image/*" 
              class="d-none"
            />
            
            <button class="btn btn-primary mb-2" @click="$refs.fileInput.click()">
              <i class="fas fa-upload me-2"></i>Choose Image
            </button>
            
            <div v-if="selectedFile" class="selected-file mb-2">
              <small class="text-muted">{{ selectedFile.name }}</small>
            </div>
            
            <button 
              v-if="user.profileImage" 
              class="btn btn-outline-danger btn-sm" 
              @click="removeProfileImage"
            >
              <i class="fas fa-trash me-1"></i>Remove Image
            </button>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeImageUpload">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="uploadImage" 
              :disabled="!selectedFile || uploading"
            >
              <i v-if="uploading" class="fas fa-spinner fa-spin me-2"></i>
              {{ uploading ? 'Uploading...' : 'Upload' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showImageUpload"></div>
  </div>
</template>

<script>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AuthService from '../services/AuthService';

export default {
  name: 'UserProfileMenu',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  setup(props, { emit }) {
    const router = useRouter();
    const showImageUpload = ref(false);
    const selectedFile = ref(null);
    const uploading = ref(false);

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

    const handleFileSelect = (event) => {
      const file = event.target.files[0];
      if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
          alert('Please select an image file');
          return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('File size must be less than 5MB');
          return;
        }
        
        selectedFile.value = file;
      }
    };

    const uploadImage = async () => {
      if (!selectedFile.value) return;
      
      uploading.value = true;
      
      try {
        const formData = new FormData();
        formData.append('profileImage', selectedFile.value);
        
        const response = await axios.post('/api/users/profile-image', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        // Update user data with new profile image
        if (response.data.profileImageUrl) {
          // Emit event to update parent component
          emit('profile-updated', {
            ...props.user,
            profileImage: response.data.profileImageUrl
          });
        }
        
        closeImageUpload();
        
      } catch (error) {
        console.error('Error uploading image:', error);
        alert('Error uploading image: ' + (error.response?.data?.message || error.message));
      } finally {
        uploading.value = false;
      }
    };

    const removeProfileImage = async () => {
      if (!confirm('Are you sure you want to remove your profile picture?')) return;
      
      try {
        await axios.delete('/api/users/profile-image');
        
        // Update user data
        emit('profile-updated', {
          ...props.user,
          profileImage: null
        });
        
        closeImageUpload();
        
      } catch (error) {
        console.error('Error removing image:', error);
        alert('Error removing image: ' + (error.response?.data?.message || error.message));
      }
    };

    const closeImageUpload = () => {
      showImageUpload.value = false;
      selectedFile.value = null;
    };

    return {
      showImageUpload,
      selectedFile,
      uploading,
      initials,
      userRoles,
      formatRole,
      getRoleBadgeClass,
      logout,
      handleFileSelect,
      uploadImage,
      removeProfileImage,
      closeImageUpload
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
  position: relative;
  cursor: pointer;
  overflow: hidden;
}

.profile-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.initials {
  font-size: 0.85rem;
}

.upload-icon {
  position: absolute;
  bottom: -2px;
  right: -2px;
  background: #28a745;
  color: white;
  font-size: 0.6rem;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
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

/* Profile Image Modal Styles */
.modal.fade {
  display: block;
}

.current-profile-image {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
}

.current-initials {
  width: 80px;
  height: 80px;
  background: #007bff;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  margin: 0 auto;
}

.selected-file {
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 0.25rem;
  border: 1px solid #dee2e6;
}
</style> 