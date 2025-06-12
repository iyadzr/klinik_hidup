<template>
  <div class="user-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">
          <i class="fas fa-users text-primary me-2"></i>
          User Management
        </h2>
        <small class="text-muted">Manage system users, roles, and permissions</small>
      </div>
      <button @click="openAddModal" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Add New User
      </button>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="form-floating">
              <input 
                type="text" 
                class="form-control" 
                id="searchInput"
                v-model="searchTerm" 
                @input="filterUsers"
                placeholder="Search users..."
              >
              <label for="searchInput">Search Users</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-floating">
              <select class="form-select" id="roleFilter" v-model="selectedRole" @change="filterUsers">
                <option value="">All Roles</option>
                <option value="ROLE_SUPER_ADMIN">Super Admin</option>
                <option value="ROLE_DOCTOR">Doctor</option>
                <option value="ROLE_ASSISTANT">Assistant</option>
                <option value="ROLE_USER">User</option>
              </select>
              <label for="roleFilter">Filter by Role</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-floating">
              <select class="form-select" id="statusFilter" v-model="selectedStatus" @change="filterUsers">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
              <label for="statusFilter">Filter by Status</label>
            </div>
          </div>
          <div class="col-md-2">
            <button @click="clearFilters" class="btn btn-outline-secondary w-100 h-100">
              <i class="fas fa-times me-2"></i>Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Users List</h5>
        <span class="badge bg-primary">{{ filteredUsers.length }} users</span>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading users...</p>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Allowed Pages</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in paginatedUsers" :key="user.id">
                <td>{{ user.id }}</td>
                <td>
                  <strong>{{ user.name }}</strong>
                </td>
                <td>{{ user.username }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span 
                      v-for="role in user.roles" 
                      :key="role" 
                      class="badge"
                      :class="getRoleBadgeClass(role)"
                    >
                      {{ formatRole(role) }}
                    </span>
                  </div>
                </td>
                <td>
                  <span 
                    class="badge"
                    :class="user.isActive ? 'bg-success' : 'bg-danger'"
                  >
                    {{ user.isActive ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <div class="allowed-pages">
                    <span 
                      v-if="user.allowedPages && user.allowedPages.length > 0"
                      class="badge bg-info me-1"
                      v-for="page in user.allowedPages.slice(0, 3)" 
                      :key="page"
                    >
                      {{ formatPageName(page) }}
                    </span>
                    <span 
                      v-if="user.allowedPages && user.allowedPages.length > 3"
                      class="badge bg-secondary"
                    >
                      +{{ user.allowedPages.length - 3 }} more
                    </span>
                    <span v-else-if="!user.allowedPages || user.allowedPages.length === 0" class="text-muted">
                      No access
                    </span>
                  </div>
                </td>
                <td>{{ formatDate(user.createdAt) }}</td>
                <td>
                  <div class="btn-group">
                    <button 
                      @click="editUser(user)" 
                      class="btn btn-sm btn-outline-primary"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      @click="managePermissions(user)" 
                      class="btn btn-sm btn-outline-info"
                      title="Manage Permissions"
                    >
                      <i class="fas fa-key"></i>
                    </button>
                    <button 
                      @click="toggleUserStatus(user)" 
                      class="btn btn-sm"
                      :class="user.isActive ? 'btn-outline-warning' : 'btn-outline-success'"
                      :title="user.isActive ? 'Deactivate' : 'Activate'"
                    >
                      <i :class="user.isActive ? 'fas fa-user-slash' : 'fas fa-user-check'"></i>
                    </button>
                    <button 
                      @click="deleteUser(user)" 
                      class="btn btn-sm btn-outline-danger"
                      title="Delete"
                      :disabled="user.roles && user.roles.includes('ROLE_SUPER_ADMIN')"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="9" class="text-center text-muted py-4">
                  <i class="fas fa-search fa-2x mb-2"></i>
                  <div>No users found</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav v-if="totalPages > 1" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button @click="currentPage = 1" class="page-link" :disabled="currentPage === 1">
                First
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button @click="currentPage--" class="page-link" :disabled="currentPage === 1">
                Previous
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === currentPage }"
            >
              <button @click="currentPage = page" class="page-link">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button @click="currentPage++" class="page-link" :disabled="currentPage === totalPages">
                Next
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button @click="currentPage = totalPages" class="page-link" :disabled="currentPage === totalPages">
                Last
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user me-2"></i>
              {{ editingUser ? 'Edit User' : 'Add New User' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveUser">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="text" 
                      class="form-control" 
                      id="userName"
                      v-model="userForm.name" 
                      required
                      placeholder="Enter full name"
                    >
                    <label for="userName">Full Name *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="text" 
                      class="form-control" 
                      id="username"
                      v-model="userForm.username" 
                      required
                      placeholder="Enter username"
                    >
                    <label for="username">Username *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input 
                      type="email" 
                      class="form-control" 
                      id="userEmail"
                      v-model="userForm.email" 
                      required
                      placeholder="Enter email address"
                    >
                    <label for="userEmail">Email *</label>
                  </div>
                </div>

                <div class="col-md-6" v-if="!editingUser">
                  <div class="form-floating">
                    <input 
                      type="password" 
                      class="form-control" 
                      id="userPassword"
                      v-model="userForm.password" 
                      required
                      placeholder="Enter password"
                    >
                    <label for="userPassword">Password *</label>
                  </div>
                </div>

                <div class="col-md-12">
                  <label class="form-label fw-bold">Roles</label>
                  <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="roleSuperAdmin"
                        v-model="userForm.roles"
                        value="ROLE_SUPER_ADMIN"
                      >
                      <label class="form-check-label" for="roleSuperAdmin">
                        Super Admin
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="roleDoctor"
                        v-model="userForm.roles"
                        value="ROLE_DOCTOR"
                      >
                      <label class="form-check-label" for="roleDoctor">
                        Doctor
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="roleAssistant"
                        v-model="userForm.roles"
                        value="ROLE_ASSISTANT"
                      >
                      <label class="form-check-label" for="roleAssistant">
                        Assistant
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="roleUser"
                        v-model="userForm.roles"
                        value="ROLE_USER"
                        checked
                        disabled
                      >
                      <label class="form-check-label" for="roleUser">
                        User (Default)
                      </label>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="isActive"
                      v-model="userForm.isActive"
                    >
                    <label class="form-check-label" for="isActive">
                      Active User
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="saveUser"
              :disabled="saving"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              {{ saving ? 'Saving...' : (editingUser ? 'Update' : 'Save') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions Modal -->
    <div class="modal fade" id="permissionsModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-key me-2"></i>
              Manage Permissions
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="editingUserPermissions">
              <div class="alert alert-info">
                <strong>User:</strong> {{ editingUserPermissions.name }} ({{ editingUserPermissions.username }})
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Allowed Pages/Modules</label>
                <div class="row g-2">
                  <div class="col-md-6" v-for="page in availablePages" :key="page.value">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        :id="'page-' + page.value"
                        v-model="permissionForm.allowedPages"
                        :value="page.value"
                      >
                      <label class="form-check-label" :for="'page-' + page.value">
                        <i :class="page.icon + ' me-2'"></i>
                        {{ page.label }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="savePermissions"
              :disabled="savingPermissions"
            >
              <span v-if="savingPermissions" class="spinner-border spinner-border-sm me-2"></span>
              {{ savingPermissions ? 'Saving...' : 'Save Permissions' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" style="padding-top: 100px !important;">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Confirm Deletion
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this user?</p>
            <div v-if="userToDelete" class="alert alert-warning">
              <strong>{{ userToDelete.name }}</strong><br>
              <small class="text-muted">{{ userToDelete.username }} - {{ userToDelete.email }}</small>
            </div>
            <p class="text-danger"><small>This action cannot be undone.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="confirmDelete"
              :disabled="deleting"
            >
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';

export default {
  name: 'UserAdmin',
  data() {
    return {
      users: [],
      filteredUsers: [],
      loading: false,
      error: null,
      searchTerm: '',
      selectedRole: '',
      selectedStatus: '',
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 10,
      
      // Modals
      userModal: null,
      permissionsModal: null,
      deleteModal: null,
      
      // Form
      editingUser: null,
      userForm: {
        name: '',
        username: '',
        email: '',
        password: '',
        roles: [],
        isActive: true
      },
      saving: false,
      
      // Permissions
      editingUserPermissions: null,
      permissionForm: {
        allowedPages: []
      },
      savingPermissions: false,
      
      // Delete
      userToDelete: null,
      deleting: false,
      
      // Available pages for permissions
      availablePages: [
        { value: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
        { value: 'patients', label: 'Patient Management', icon: 'fas fa-user-injured' },
        { value: 'doctors', label: 'Doctor Management', icon: 'fas fa-user-md' },
        { value: 'consultations', label: 'Consultations', icon: 'fas fa-notes-medical' },
        { value: 'queue', label: 'Queue Management', icon: 'fas fa-list-ol' },
        { value: 'registration', label: 'Patient Registration', icon: 'fas fa-user-plus' },
        { value: 'financial', label: 'Financial Dashboard', icon: 'fas fa-chart-line' },
        { value: 'appointments', label: 'Appointments', icon: 'fas fa-calendar-alt' },
        { value: 'medications', label: 'Medication Admin', icon: 'fas fa-pills' },
        { value: 'users', label: 'User Management', icon: 'fas fa-users' },
        { value: 'reports', label: 'Reports', icon: 'fas fa-chart-bar' },
        { value: 'settings', label: 'System Settings', icon: 'fas fa-cog' }
      ]
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.filteredUsers.length / this.itemsPerPage);
    },
    paginatedUsers() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredUsers.slice(start, end);
    },
    visiblePages() {
      const current = this.currentPage;
      const total = this.totalPages;
      const pages = [];
      
      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  async created() {
    await this.loadUsers();
  },
  mounted() {
    this.userModal = new bootstrap.Modal(document.getElementById('userModal'));
    this.permissionsModal = new bootstrap.Modal(document.getElementById('permissionsModal'));
    this.deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  },
  methods: {
    async loadUsers() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/users');
        this.users = response.data;
        this.filterUsers();
      } catch (error) {
        console.error('Error loading users:', error);
        this.error = 'Failed to load users';
      } finally {
        this.loading = false;
      }
    },
    
    filterUsers() {
      let filtered = [...this.users];
      
      // Search filter
      if (this.searchTerm) {
        const search = this.searchTerm.toLowerCase();
        filtered = filtered.filter(u => 
          u.name.toLowerCase().includes(search) ||
          u.username.toLowerCase().includes(search) ||
          u.email.toLowerCase().includes(search)
        );
      }
      
      // Role filter
      if (this.selectedRole) {
        filtered = filtered.filter(u => u.roles && u.roles.includes(this.selectedRole));
      }
      
      // Status filter
      if (this.selectedStatus) {
        const isActive = this.selectedStatus === 'active';
        filtered = filtered.filter(u => u.isActive === isActive);
      }
      
      this.filteredUsers = filtered;
      this.currentPage = 1; // Reset to first page
    },
    
    clearFilters() {
      this.searchTerm = '';
      this.selectedRole = '';
      this.selectedStatus = '';
      this.filterUsers();
    },
    
    openAddModal() {
      this.editingUser = null;
      this.userForm = {
        name: '',
        username: '',
        email: '',
        password: '',
        roles: [],
        isActive: true
      };
      this.userModal.show();
    },
    
    editUser(user) {
      this.editingUser = user;
      this.userForm = {
        name: user.name,
        username: user.username,
        email: user.email,
        password: '',
        roles: [...(user.roles || [])],
        isActive: user.isActive
      };
      this.userModal.show();
    },
    
    async saveUser() {
      if (!this.userForm.name || !this.userForm.username || !this.userForm.email) {
        alert('Please fill in required fields');
        return;
      }
      
      if (!this.editingUser && !this.userForm.password) {
        alert('Password is required for new users');
        return;
      }
      
      this.saving = true;
      try {
        const userData = {
          name: this.userForm.name,
          username: this.userForm.username,
          email: this.userForm.email,
          roles: this.userForm.roles,
          isActive: this.userForm.isActive
        };
        
        if (!this.editingUser) {
          userData.password = this.userForm.password;
        }
        
        if (this.editingUser) {
          await axios.put(`/api/users/${this.editingUser.id}`, userData);
        } else {
          await axios.post('/api/users', userData);
        }
        
        this.userModal.hide();
        await this.loadUsers();
        
      } catch (error) {
        console.error('Error saving user:', error);
        alert('Error saving user: ' + (error.response?.data?.message || error.message));
      } finally {
        this.saving = false;
      }
    },
    
    managePermissions(user) {
      this.editingUserPermissions = user;
      this.permissionForm = {
        allowedPages: [...(user.allowedPages || [])]
      };
      this.permissionsModal.show();
    },
    
    async savePermissions() {
      if (!this.editingUserPermissions) return;
      
      this.savingPermissions = true;
      try {
        await axios.put(`/api/users/${this.editingUserPermissions.id}/permissions`, {
          allowedPages: this.permissionForm.allowedPages
        });
        
        this.permissionsModal.hide();
        await this.loadUsers();
        
      } catch (error) {
        console.error('Error saving permissions:', error);
        alert('Error saving permissions: ' + (error.response?.data?.message || error.message));
      } finally {
        this.savingPermissions = false;
      }
    },
    
    async toggleUserStatus(user) {
      try {
        await axios.put(`/api/users/${user.id}`, {
          ...user,
          isActive: !user.isActive
        });
        await this.loadUsers();
      } catch (error) {
        console.error('Error updating user status:', error);
        alert('Error updating user status: ' + (error.response?.data?.message || error.message));
      }
    },
    
    deleteUser(user) {
      if (user.roles && user.roles.includes('ROLE_SUPER_ADMIN')) {
        alert('Cannot delete super admin users');
        return;
      }
      this.userToDelete = user;
      this.deleteModal.show();
    },
    
    async confirmDelete() {
      if (!this.userToDelete) return;
      
      this.deleting = true;
      try {
        await axios.delete(`/api/users/${this.userToDelete.id}`);
        this.deleteModal.hide();
        await this.loadUsers();
      } catch (error) {
        console.error('Error deleting user:', error);
        alert('Error deleting user: ' + (error.response?.data?.message || error.message));
      } finally {
        this.deleting = false;
      }
    },
    
    getRoleBadgeClass(role) {
      const classes = {
        'ROLE_SUPER_ADMIN': 'bg-danger',
        'ROLE_DOCTOR': 'bg-primary',
        'ROLE_ASSISTANT': 'bg-info',
        'ROLE_USER': 'bg-secondary'
      };
      return classes[role] || 'bg-secondary';
    },
    
    formatRole(role) {
      const roleNames = {
        'ROLE_SUPER_ADMIN': 'Super Admin',
        'ROLE_DOCTOR': 'Doctor',
        'ROLE_ASSISTANT': 'Assistant',
        'ROLE_USER': 'User'
      };
      return roleNames[role] || role;
    },
    
    formatPageName(page) {
      const pageObj = this.availablePages.find(p => p.value === page);
      return pageObj ? pageObj.label : page;
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: 'short',
          day: '2-digit'
        });
      } catch {
        return '-';
      }
    }
  }
};
</script>

<style scoped>
.badge {
  font-size: 0.75rem;
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
}

.table td {
  vertical-align: middle;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
}

.allowed-pages .badge {
  font-size: 0.7rem;
  margin-bottom: 0.2rem;
}

.pagination .page-link {
  border-radius: 0.375rem;
  margin: 0 0.125rem;
  border: 1px solid #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.form-check-label {
  cursor: pointer;
}
</style> 