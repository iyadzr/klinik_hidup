<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body bg-gradient-primary text-white rounded">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-1">
                  <i class="fas fa-database me-2"></i>Backup Management
                </h2>
                <p class="mb-0 opacity-75">Create, manage, and restore system backups</p>
              </div>
              <div class="text-end">
                <div class="btn-group">
                  <button 
                    @click="createBackup" 
                    :disabled="isCreatingBackup"
                    class="btn btn-light btn-lg"
                  >
                    <i class="fas fa-plus me-2"></i>
                    {{ isCreatingBackup ? 'Creating...' : 'Create Manual Backup' }}
                  </button>
                  <button 
                    @click="updateBackupSchedule" 
                    :disabled="isUpdatingSchedule"
                    class="btn btn-outline-light btn-lg"
                    title="Update automated backup schedule based on admin settings"
                  >
                    <i class="fas fa-clock me-2"></i>
                    {{ isUpdatingSchedule ? 'Updating...' : 'Update Schedule' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Settings Info -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="mb-2">
                  <i class="fas fa-cog me-2"></i>Automated Backup Settings
                </h5>
                <div class="row g-3">
                  <div class="col-md-3">
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fas fa-power-off" :class="backupSettings.enabled ? 'text-success' : 'text-danger'"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Status</small>
                        <span class="fw-medium" :class="backupSettings.enabled ? 'text-success' : 'text-danger'">
                          {{ backupSettings.enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fas fa-clock text-info"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Schedule</small>
                        <span class="fw-medium">{{ formatBackupTime(backupSettings.time) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fas fa-calendar text-primary"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Frequency</small>
                        <span class="fw-medium">{{ backupSettings.frequency || 'Daily' }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fas fa-history text-warning"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Retention</small>
                        <span class="fw-medium">{{ backupSettings.retention_count || 10 }} backups</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-end">
                <router-link to="/system-settings" class="btn btn-outline-primary">
                  <i class="fas fa-cog me-2"></i>Configure Settings
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Status Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-primary mb-2">
              <i class="fas fa-archive fa-2x"></i>
            </div>
            <h4 class="mb-1">{{ backupStatus.total_backups || 0 }}</h4>
            <p class="text-muted mb-0">Total Backups</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-success mb-2">
              <i class="fas fa-hdd fa-2x"></i>
            </div>
            <h4 class="mb-1">{{ backupStatus.total_size_formatted || '0 B' }}</h4>
            <p class="text-muted mb-0">Total Size</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-info mb-2">
              <i class="fas fa-clock fa-2x"></i>
            </div>
            <h4 class="mb-1">{{ latestBackupAge }}</h4>
            <p class="text-muted mb-0">Latest Backup</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-warning mb-2">
              <i class="fas fa-server fa-2x"></i>
            </div>
            <h4 class="mb-1">{{ formatBytes(backupStatus.disk_space_available || 0) }}</h4>
            <p class="text-muted mb-0">Free Space</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup List -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Backup History
              </h5>
              <div class="d-flex gap-2">
                <button @click="refreshBackups" class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
                <button @click="showCleanupModal" class="btn btn-outline-warning btn-sm">
                  <i class="fas fa-broom me-1"></i>Cleanup
                </button>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Loading backups...</p>
            </div>
            
            <div v-else-if="backups.length === 0" class="text-center py-5">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No backups found</h5>
              <p class="text-muted">Create your first backup to get started</p>
            </div>
            
            <div v-else class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Backup Name</th>
                    <th>Created</th>
                    <th>Size</th>
                    <th>Age</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="backup in backups" :key="backup.filename">
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-file-archive text-primary me-2"></i>
                        <span class="fw-medium">{{ backup.filename }}</span>
                      </div>
                    </td>
                    <td>
                      <small class="text-muted">{{ formatDate(backup.created_at) }}</small>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">{{ formatBytes(backup.size) }}</span>
                    </td>
                    <td>
                      <span class="text-muted">{{ backup.age_days }} day(s) ago</span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button 
                          @click="downloadBackup(backup.filename)" 
                          class="btn btn-outline-primary"
                          title="Download"
                        >
                          <i class="fas fa-download"></i>
                        </button>
                        <button 
                          @click="showRestoreModal(backup)" 
                          class="btn btn-outline-success"
                          title="Restore"
                        >
                          <i class="fas fa-undo"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-exclamation-triangle text-warning me-2"></i>
              Confirm Restore
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <strong>Warning:</strong> This will restore the database from the selected backup. 
              All current data will be replaced. This action cannot be undone.
            </div>
            <p><strong>Backup:</strong> {{ selectedBackup?.filename }}</p>
            <p><strong>Created:</strong> {{ formatDate(selectedBackup?.created_at) }}</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-warning" 
              @click="confirmRestore"
              :disabled="isRestoring"
            >
              <i class="fas fa-undo me-1"></i>
              {{ isRestoring ? 'Restoring...' : 'Restore Database' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cleanup Modal -->
    <div class="modal fade" id="cleanupModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-broom text-warning me-2"></i>
              Cleanup Old Backups
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>How many recent backups would you like to keep?</p>
            <div class="form-floating">
              <select class="form-select" v-model="keepCount">
                <option value="5">Keep 5 most recent</option>
                <option value="10">Keep 10 most recent</option>
                <option value="15">Keep 15 most recent</option>
                <option value="20">Keep 20 most recent</option>
              </select>
              <label>Backups to keep</label>
            </div>
            <div class="mt-3">
              <small class="text-muted">
                This will delete {{ Math.max(0, backups.length - keepCount) }} backup(s)
              </small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-warning" 
              @click="cleanupBackups"
              :disabled="isCleaning"
            >
              <i class="fas fa-broom me-1"></i>
              {{ isCleaning ? 'Cleaning...' : 'Cleanup' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';

export default {
  name: 'BackupManagement',
  data() {
    return {
      backups: [],
      backupStatus: {},
      backupSettings: {
        enabled: true,
        time: '18:30',
        frequency: 'daily',
        retention_count: 10,
        retention_days: 30
      },
      loading: true,
      isCreatingBackup: false,
      isRestoring: false,
      isCleaning: false,
      isUpdatingSchedule: false,
      selectedBackup: null,
      keepCount: 10,
      restoreModal: null,
      cleanupModal: null
    };
  },
  computed: {
    latestBackupAge() {
      if (!this.backupStatus.latest_backup) return 'Never';
      const days = this.backupStatus.latest_backup.age_days;
      if (days === 0) return 'Today';
      if (days === 1) return 'Yesterday';
      return `${days} days ago`;
    }
  },
  async mounted() {
    await this.loadData();
    this.initModals();
  },
  methods: {
    initModals() {
      this.restoreModal = new Modal(document.getElementById('restoreModal'));
      this.cleanupModal = new Modal(document.getElementById('cleanupModal'));
    },
    
    async loadData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadBackups(),
          this.loadBackupStatus(),
          this.loadBackupSettings()
        ]);
      } catch (error) {
        console.error('Error loading backup data:', error);
        this.$toast.error('Failed to load backup data');
      } finally {
        this.loading = false;
      }
    },
    
    async loadBackups() {
      try {
        const response = await axios.get('/api/backup/list');
        this.backups = response.data.data || [];
      } catch (error) {
        console.error('Error loading backups:', error);
        throw error;
      }
    },
    
    async loadBackupStatus() {
      try {
        const response = await axios.get('/api/backup/status');
        this.backupStatus = response.data.data || {};
      } catch (error) {
        console.error('Error loading backup status:', error);
        throw error;
      }
    },
    
    async loadBackupSettings() {
      try {
        const response = await axios.get('/api/settings');
        const systemSettings = response.data.system || [];
        
        // Extract backup settings
        systemSettings.forEach(setting => {
          switch (setting.key) {
            case 'system.backup_enabled':
              this.backupSettings.enabled = setting.value;
              break;
            case 'system.backup_time':
              this.backupSettings.time = setting.value;
              break;
            case 'system.backup_frequency':
              this.backupSettings.frequency = setting.value;
              break;
            case 'system.backup_retention_count':
              this.backupSettings.retention_count = setting.value;
              break;
            case 'system.backup_retention_days':
              this.backupSettings.retention_days = setting.value;
              break;
          }
        });
      } catch (error) {
        console.error('Error loading backup settings:', error);
        // Don't throw error, use defaults
      }
    },
    
    async createBackup() {
      this.isCreatingBackup = true;
      try {
        const response = await axios.post('/api/backup/create');
        if (response.data.success) {
          this.$toast.success('Backup created successfully!');
          await this.loadData();
        } else {
          throw new Error(response.data.message || 'Backup creation failed');
        }
      } catch (error) {
        console.error('Error creating backup:', error);
        this.$toast.error('Failed to create backup: ' + (error.response?.data?.message || error.message));
      } finally {
        this.isCreatingBackup = false;
      }
    },
    
    async downloadBackup(filename) {
      try {
        const response = await axios.get(`/api/backup/download/${filename}`, {
          responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
        this.$toast.success('Backup download started');
      } catch (error) {
        console.error('Error downloading backup:', error);
        this.$toast.error('Failed to download backup');
      }
    },
    
    showRestoreModal(backup) {
      this.selectedBackup = backup;
      this.restoreModal.show();
    },
    
    async confirmRestore() {
      this.isRestoring = true;
      try {
        const response = await axios.post('/api/backup/restore', {
          filename: this.selectedBackup.filename
        });
        
        if (response.data.success) {
          this.$toast.success('Database restored successfully!');
          this.restoreModal.hide();
        } else {
          throw new Error(response.data.message || 'Restore failed');
        }
      } catch (error) {
        console.error('Error restoring backup:', error);
        this.$toast.error('Failed to restore backup: ' + (error.response?.data?.message || error.message));
      } finally {
        this.isRestoring = false;
      }
    },
    
    showCleanupModal() {
      this.cleanupModal.show();
    },
    
    async cleanupBackups() {
      this.isCleaning = true;
      try {
        const response = await axios.post('/api/backup/clean', {
          keep_count: parseInt(this.keepCount)
        });
        
        if (response.data.success) {
          const deletedCount = response.data.data.deleted_count;
          this.$toast.success(`Cleaned up ${deletedCount} old backup(s)`);
          this.cleanupModal.hide();
          await this.loadData();
        } else {
          throw new Error(response.data.message || 'Cleanup failed');
        }
      } catch (error) {
        console.error('Error cleaning backups:', error);
        this.$toast.error('Failed to cleanup backups: ' + (error.response?.data?.message || error.message));
      } finally {
        this.isCleaning = false;
      }
    },
    
    async refreshBackups() {
      await this.loadData();
      this.$toast.success('Backup list refreshed');
    },
    
    async updateBackupSchedule() {
      this.isUpdatingSchedule = true;
      try {
        const response = await axios.post('/api/backup/update-schedule');
        if (response.data.success) {
          this.$toast.success('Backup schedule updated successfully!');
        } else {
          throw new Error(response.data.message || 'Schedule update failed');
        }
      } catch (error) {
        console.error('Error updating backup schedule:', error);
        this.$toast.error('Failed to update backup schedule: ' + (error.response?.data?.message || error.message));
      } finally {
        this.isUpdatingSchedule = false;
      }
    },
    
    formatBytes(bytes, precision = 2) {
      if (bytes === 0) return '0 B';
      
      const units = ['B', 'KB', 'MB', 'GB', 'TB'];
      let size = bytes;
      
      for (let i = 0; size > 1024 && i < units.length - 1; i++) {
        size /= 1024;
      }
      
      return Math.round(size * Math.pow(10, precision)) / Math.pow(10, precision) + ' ' + units[Math.floor(Math.log(bytes) / Math.log(1024))];
    },
    
    formatDate(dateString) {
      if (!dateString) return 'Unknown';
      return new Date(dateString).toLocaleString();
    },
    
    formatBackupTime(time) {
      if (!time) return 'Not set';
      try {
        const [hours, minutes] = time.split(':');
        const hour12 = hours % 12 || 12;
        const ampm = hours >= 12 ? 'PM' : 'AM';
        return `${hour12}:${minutes} ${ampm}`;
      } catch (e) {
        return time;
      }
    }
  }
};
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
  transition: transform 0.2s ease-in-out;
}

.card:hover {
  transform: translateY(-2px);
}

.table th {
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.modal-content {
  border: none;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.alert-warning {
  border-left: 4px solid #ffc107;
}
</style> 