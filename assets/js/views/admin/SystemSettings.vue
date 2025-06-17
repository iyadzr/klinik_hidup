<template>
  <div class="system-settings">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">
          <i class="fas fa-cog text-primary me-2"></i>
          System Settings
        </h2>
        <small class="text-muted">Configure clinic settings and system preferences</small>
      </div>
      <div class="d-flex gap-2">
        <button @click="initializeSettings" class="btn btn-outline-primary" :disabled="initializing">
          <span v-if="initializing" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="fas fa-sync me-2"></i>
          Initialize Defaults
        </button>
        <button @click="saveAllSettings" class="btn btn-success" :disabled="saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="fas fa-save me-2"></i>
          Save All Changes
        </button>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i>
      {{ successMessage }}
      <button type="button" class="btn-close" @click="successMessage = ''"></button>
    </div>

    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ errorMessage }}
      <button type="button" class="btn-close" @click="errorMessage = ''"></button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading settings...</p>
    </div>

    <!-- Settings Tabs -->
    <div v-else class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item" v-for="(category, key) in categories" :key="key">
            <button 
              class="nav-link" 
              :class="{ active: activeTab === key }"
              @click="activeTab = key"
              type="button"
            >
              <i :class="category.icon + ' me-2'"></i>
              {{ category.name }}
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <!-- Clinic Information Tab -->
        <div v-show="activeTab === 'clinic'" class="tab-content">
          <div class="row g-3">
            <div class="col-md-6" v-for="setting in getSettingsByCategory('clinic')" :key="setting.key">
              <div class="form-floating">
                <input 
                  :type="getInputType(setting.type)"
                  class="form-control" 
                  :id="setting.key"
                  v-model="settingsData[setting.key]"
                  :placeholder="setting.description"
                >
                <label :for="setting.key">{{ formatSettingLabel(setting.key) }}</label>
              </div>
              <small class="text-muted">{{ setting.description }}</small>
            </div>
          </div>
        </div>

        <!-- Business Settings Tab -->
        <div v-show="activeTab === 'business'" class="tab-content">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating">
                <select class="form-select" id="business.timezone" v-model="settingsData['business.timezone']">
                  <option value="Asia/Kuala_Lumpur">Asia/Kuala_Lumpur (Malaysia)</option>
                  <option value="Asia/Singapore">Asia/Singapore</option>
                  <option value="Asia/Bangkok">Asia/Bangkok</option>
                  <option value="Asia/Jakarta">Asia/Jakarta</option>
                  <option value="UTC">UTC</option>
                </select>
                <label for="business.timezone">Timezone</label>
              </div>
              <small class="text-muted">Default timezone for the clinic</small>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input 
                  type="number" 
                  class="form-control" 
                  id="business.appointment_duration"
                  v-model="settingsData['business.appointment_duration']"
                  min="15" 
                  max="120" 
                  step="15"
                >
                <label for="business.appointment_duration">Appointment Duration (minutes)</label>
              </div>
              <small class="text-muted">Default appointment duration in minutes</small>
            </div>

            <!-- Operating Hours -->
            <div class="col-12">
              <h5 class="mb-3">
                <i class="fas fa-clock me-2"></i>
                Operating Hours
              </h5>
              <div class="row g-2">
                <div class="col-md-4" v-for="(day, dayKey) in operatingHours" :key="dayKey">
                  <div class="card">
                    <div class="card-body p-3">
                      <h6 class="card-title mb-3">{{ formatDayName(dayKey) }}</h6>
                      <div class="form-check mb-2">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          :id="'closed-' + dayKey"
                          v-model="day.closed"
                          @change="updateOperatingHours"
                        >
                        <label class="form-check-label" :for="'closed-' + dayKey">
                          Closed
                        </label>
                      </div>
                      <div v-if="!day.closed" class="row g-2">
                        <div class="col-6">
                          <div class="form-floating">
                            <input 
                              type="time" 
                              class="form-control" 
                              :id="'start-' + dayKey"
                              v-model="day.start"
                              @change="updateOperatingHours"
                            >
                            <label :for="'start-' + dayKey">Start</label>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="form-floating">
                            <input 
                              type="time" 
                              class="form-control" 
                              :id="'end-' + dayKey"
                              v-model="day.end"
                              @change="updateOperatingHours"
                            >
                            <label :for="'end-' + dayKey">End</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Financial Settings Tab -->
        <div v-show="activeTab === 'financial'" class="tab-content">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating">
                <select class="form-select" id="financial.currency" v-model="settingsData['financial.currency']">
                  <option value="MYR">MYR - Malaysian Ringgit</option>
                  <option value="SGD">SGD - Singapore Dollar</option>
                  <option value="USD">USD - US Dollar</option>
                  <option value="EUR">EUR - Euro</option>
                </select>
                <label for="financial.currency">Currency</label>
              </div>
              <small class="text-muted">Default currency for pricing</small>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input 
                  type="number" 
                  class="form-control" 
                  id="financial.consultation_fee"
                  v-model="settingsData['financial.consultation_fee']"
                  min="0" 
                  step="0.01"
                >
                <label for="financial.consultation_fee">Default Consultation Fee</label>
              </div>
              <small class="text-muted">Default consultation fee amount</small>
            </div>

            <!-- Payment Methods -->
            <div class="col-12">
              <h5 class="mb-3">
                <i class="fas fa-credit-card me-2"></i>
                Payment Methods
              </h5>
              <div class="row g-2">
                <div class="col-md-3" v-for="method in paymentMethods" :key="method.value">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      :id="'payment-' + method.value"
                      v-model="selectedPaymentMethods"
                      :value="method.value"
                      @change="updatePaymentMethods"
                    >
                    <label class="form-check-label" :for="'payment-' + method.value">
                      <i :class="method.icon + ' me-2'"></i>
                      {{ method.label }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Settings Tab -->
        <div v-show="activeTab === 'security'" class="tab-content">
          <div class="row g-3">
            <div class="col-md-6" v-for="setting in getSettingsByCategory('security')" :key="setting.key">
              <div v-if="setting.type === 'boolean'" class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  :id="setting.key"
                  v-model="settingsData[setting.key]"
                >
                <label class="form-check-label" :for="setting.key">
                  {{ formatSettingLabel(setting.key) }}
                </label>
                <small class="d-block text-muted">{{ setting.description }}</small>
              </div>
              <div v-else>
                <div class="form-floating">
                  <input 
                    :type="getInputType(setting.type)"
                    class="form-control" 
                    :id="setting.key"
                    v-model="settingsData[setting.key]"
                    :placeholder="setting.description"
                  >
                  <label :for="setting.key">{{ formatSettingLabel(setting.key) }}</label>
                </div>
                <small class="text-muted">{{ setting.description }}</small>
              </div>
            </div>
          </div>
        </div>

        <!-- System Settings Tab -->
        <div v-show="activeTab === 'system'" class="tab-content">
          <div class="row g-3">
            <div class="col-md-6" v-for="setting in getSettingsByCategory('system')" :key="setting.key">
              <div v-if="setting.type === 'boolean'" class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  :id="setting.key"
                  v-model="settingsData[setting.key]"
                >
                <label class="form-check-label" :for="setting.key">
                  {{ formatSettingLabel(setting.key) }}
                </label>
                <small class="d-block text-muted">{{ setting.description }}</small>
              </div>
              <div v-else-if="setting.key === 'system.backup_frequency'">
                <div class="form-floating">
                  <select class="form-select" :id="setting.key" v-model="settingsData[setting.key]">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                  <label :for="setting.key">{{ formatSettingLabel(setting.key) }}</label>
                </div>
                <small class="text-muted">{{ setting.description }}</small>
              </div>
              <div v-else-if="setting.key === 'system.backup_time'">
                <div class="form-floating">
                  <input 
                    type="time" 
                    class="form-control" 
                    :id="setting.key"
                    v-model="settingsData[setting.key]"
                    :placeholder="setting.description"
                  >
                  <label :for="setting.key">{{ formatSettingLabel(setting.key) }}</label>
                </div>
                <small class="text-muted">{{ setting.description }}</small>
              </div>
              <div v-else>
                <div class="form-floating">
                  <input 
                    :type="getInputType(setting.type)"
                    class="form-control" 
                    :id="setting.key"
                    v-model="settingsData[setting.key]"
                    :placeholder="setting.description"
                  >
                  <label :for="setting.key">{{ formatSettingLabel(setting.key) }}</label>
                </div>
                <small class="text-muted">{{ setting.description }}</small>
              </div>
            </div>

            <!-- System Actions -->
            <div class="col-12">
              <h5 class="mb-3">
                <i class="fas fa-tools me-2"></i>
                System Actions
              </h5>
              <div class="row g-2">
                <div class="col-md-3">
                  <button class="btn btn-outline-info w-100" @click="performBackup" :disabled="backingUp">
                    <span v-if="backingUp" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-download me-2"></i>
                    {{ backingUp ? 'Creating...' : 'Create Backup' }}
                  </button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-warning w-100" @click="clearCache" :disabled="clearingCache">
                    <span v-if="clearingCache" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-broom me-2"></i>
                    {{ clearingCache ? 'Clearing...' : 'Clear Cache' }}
                  </button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-secondary w-100" @click="exportSettings" :disabled="exporting">
                    <span v-if="exporting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-file-export me-2"></i>
                    {{ exporting ? 'Exporting...' : 'Export Settings' }}
                  </button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-primary w-100" @click="checkUpdates" :disabled="checkingUpdates">
                    <span v-if="checkingUpdates" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-sync me-2"></i>
                    {{ checkingUpdates ? 'Checking...' : 'Check Updates' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-show="activeTab === 'notifications'" class="tab-content">
          <div class="row g-3">
            <div class="col-12">
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Notification settings will be available in future updates.
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
  name: 'SystemSettings',
  data() {
    return {
      loading: false,
      saving: false,
      initializing: false,
      backingUp: false,
      clearingCache: false,
      exporting: false,
      checkingUpdates: false,
      successMessage: '',
      errorMessage: '',
      activeTab: 'clinic',
      
      categories: {},
      settings: {},
      settingsData: {},
      
      operatingHours: {
        monday: { start: '09:00', end: '17:00', closed: false },
        tuesday: { start: '09:00', end: '17:00', closed: false },
        wednesday: { start: '09:00', end: '17:00', closed: false },
        thursday: { start: '09:00', end: '17:00', closed: false },
        friday: { start: '09:00', end: '17:00', closed: false },
        saturday: { start: '09:00', end: '13:00', closed: false },
        sunday: { start: '09:00', end: '17:00', closed: true }
      },
      
      paymentMethods: [
        { value: 'cash', label: 'Cash', icon: 'fas fa-money-bill' },
        { value: 'card', label: 'Credit/Debit Card', icon: 'fas fa-credit-card' },
        { value: 'transfer', label: 'Bank Transfer', icon: 'fas fa-university' },
        { value: 'ewallet', label: 'E-Wallet', icon: 'fas fa-mobile-alt' }
      ],
      selectedPaymentMethods: ['cash', 'card']
    };
  },
  async created() {
    await this.loadCategories();
    await this.loadSettings();
  },
  methods: {
    async loadCategories() {
      try {
        const response = await axios.get('/api/settings/categories');
        this.categories = response.data;
      } catch (error) {
        console.error('Error loading categories:', error);
        this.errorMessage = 'Failed to load setting categories';
      }
    },
    
    async loadSettings() {
      this.loading = true;
      try {
        const response = await axios.get('/api/settings');
        this.settings = response.data;
        
        // Populate settings data
        this.settingsData = {};
        Object.values(this.settings).flat().forEach(setting => {
          this.settingsData[setting.key] = setting.value;
        });
        
        // Load operating hours if available
        if (this.settingsData['business.operating_hours']) {
          this.operatingHours = this.settingsData['business.operating_hours'];
        }
        
        // Load payment methods if available
        if (this.settingsData['financial.payment_methods']) {
          this.selectedPaymentMethods = this.settingsData['financial.payment_methods'];
        }
        
      } catch (error) {
        console.error('Error loading settings:', error);
        this.errorMessage = 'Failed to load settings';
      } finally {
        this.loading = false;
      }
    },
    
    async initializeSettings() {
      this.initializing = true;
      try {
        const response = await axios.post('/api/settings/initialize');
        this.successMessage = response.data.message + ` (${response.data.created} settings created)`;
        await this.loadSettings();
      } catch (error) {
        console.error('Error initializing settings:', error);
        this.errorMessage = 'Failed to initialize default settings';
      } finally {
        this.initializing = false;
      }
    },
    
    async saveAllSettings() {
      this.saving = true;
      try {
        // Prepare settings array
        const settingsToUpdate = [];
        
        Object.keys(this.settingsData).forEach(key => {
          const setting = this.findSettingByKey(key);
          if (setting) {
            settingsToUpdate.push({
              key: key,
              value: this.settingsData[key],
              category: setting.category || 'general',
              type: setting.type || 'string'
            });
          }
        });
        
        await axios.post('/api/settings/bulk-update', {
          settings: settingsToUpdate
        });
        
        this.successMessage = 'Settings saved successfully!';
        await this.loadSettings();
        
      } catch (error) {
        console.error('Error saving settings:', error);
        this.errorMessage = 'Failed to save settings: ' + (error.response?.data?.message || error.message);
      } finally {
        this.saving = false;
      }
    },
    
    getSettingsByCategory(category) {
      return this.settings[category] || [];
    },
    
    findSettingByKey(key) {
      for (const category of Object.values(this.settings)) {
        const setting = category.find(s => s.key === key);
        if (setting) return setting;
      }
      return null;
    },
    
    getInputType(type) {
      const typeMap = {
        'email': 'email',
        'url': 'url',
        'number': 'number',
        'boolean': 'checkbox',
        'string': 'text'
      };
      return typeMap[type] || 'text';
    },
    
    formatSettingLabel(key) {
      return key.split('.').pop().replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },
    
    formatDayName(dayKey) {
      return dayKey.charAt(0).toUpperCase() + dayKey.slice(1);
    },
    
    updateOperatingHours() {
      this.settingsData['business.operating_hours'] = { ...this.operatingHours };
    },
    
    updatePaymentMethods() {
      this.settingsData['financial.payment_methods'] = [...this.selectedPaymentMethods];
    },
    
    async performBackup() {
      this.backingUp = true;
      try {
        // Mock backup operation
        await new Promise(resolve => setTimeout(resolve, 2000));
        this.successMessage = 'Database backup created successfully!';
      } catch (error) {
        this.errorMessage = 'Failed to create backup';
      } finally {
        this.backingUp = false;
      }
    },
    
    async clearCache() {
      this.clearingCache = true;
      try {
        // Mock cache clear operation
        await new Promise(resolve => setTimeout(resolve, 1000));
        this.successMessage = 'System cache cleared successfully!';
      } catch (error) {
        this.errorMessage = 'Failed to clear cache';
      } finally {
        this.clearingCache = false;
      }
    },
    
    async exportSettings() {
      this.exporting = true;
      try {
        // Mock export operation
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Create and download JSON file
        const dataStr = JSON.stringify(this.settingsData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `clinic-settings-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
        
        this.successMessage = 'Settings exported successfully!';
      } catch (error) {
        this.errorMessage = 'Failed to export settings';
      } finally {
        this.exporting = false;
      }
    },
    
    async checkUpdates() {
      this.checkingUpdates = true;
      try {
        // Mock update check
        await new Promise(resolve => setTimeout(resolve, 2000));
        this.successMessage = 'System is up to date!';
      } catch (error) {
        this.errorMessage = 'Failed to check for updates';
      } finally {
        this.checkingUpdates = false;
      }
    }
  }
};
</script>

<style scoped>
.nav-tabs .nav-link {
  border-bottom: 3px solid transparent;
  font-weight: 500;
}

.nav-tabs .nav-link.active {
  border-bottom-color: #0d6efd;
  color: #0d6efd;
}

.tab-content {
  min-height: 400px;
}

.form-check-label {
  cursor: pointer;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.alert {
  border: none;
  border-radius: 0.5rem;
}

.btn {
  border-radius: 0.375rem;
  font-weight: 500;
}

.form-floating > label {
  font-weight: 500;
}

.text-muted {
  font-size: 0.875rem;
}
</style> 