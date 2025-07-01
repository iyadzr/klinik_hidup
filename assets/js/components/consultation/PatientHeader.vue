<template>
  <div class="patient-header-bar sticky-patient-topbar shadow-lg">
    <div class="container-fluid">
      <div class="row align-items-center py-3">
        <div class="col-md-12">
          <div class="d-flex align-items-center justify-content-between">
            <!-- Patient Info Section -->
            <div class="d-flex align-items-center flex-grow-1">
              <div class="patient-avatar me-3">
                <i class="fas fa-user-circle fa-3x text-white"></i>
              </div>
              <div class="patient-summary flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-1">
                  <h4 class="mb-0 patient-name text-white fw-bold">{{ patientName }}</h4>
                  <span class="badge bg-light text-dark fw-bold">{{ patientGender }}, {{ patientAge }} years</span>
                  <span class="badge bg-warning text-dark fw-bold">{{ patientIC }}</span>
                </div>
                <div class="patient-meta text-white small d-flex gap-4">
                  <span v-if="queueNumber">
                    <i class="fas fa-list-ol me-1"></i>Queue #{{ formatQueueNumber(queueNumber) }}
                  </span>
                  <span>
                    <i class="fas fa-phone me-1"></i>{{ patientPhone }}
                  </span>
                  <span>
                    <i class="fas fa-map-marker-alt me-1"></i>{{ patientAddress }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Enhanced Group Patient Selector -->
            <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="patient-selector ms-3">
              <div class="dropdown">
                <button class="btn btn-warning btn-lg dropdown-toggle shadow-sm fw-bold patient-select-btn" 
                        type="button" 
                        data-bs-toggle="dropdown"
                        style="border-radius: 25px; padding: 0.75rem 1.5rem; animation: pulse 2s infinite;">
                  <i class="fas fa-user-friends me-2"></i>
                  <span class="d-none d-sm-inline">🔄 Switch Patient </span>
                  <span class="badge bg-white text-warning ms-2 px-2 py-1">
                    {{ currentPatientIndex + 1 }}/{{ groupPatients.length }}
                  </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 patient-dropdown" 
                    style="min-width: 350px; border-radius: 15px; max-height: 400px; overflow-y: auto; z-index: 1050;">
                  <li class="px-3 py-2 bg-light border-bottom">
                    <div class="text-center">
                      <small class="text-muted fw-bold">
                        <i class="fas fa-users me-1"></i>
                        GROUP CONSULTATION - SELECT PATIENT
                      </small>
                    </div>
                  </li>
                  <li v-for="(patient, index) in groupPatients" :key="patient.id" class="patient-option">
                    <a class="dropdown-item patient-item py-3" 
                       href="#" 
                       @click.prevent.stop="handlePatientSwitch(patient)" 
                       :class="{ 'active': patient.id === selectedPatient?.id }">
                      <div class="d-flex align-items-center">
                        <div class="patient-avatar me-3">
                          <i class="fas fa-user-circle fa-2x" 
                             :class="patient.id === selectedPatient?.id ? 'text-warning' : 'text-primary'"></i>
                        </div>
                        <div class="patient-info flex-grow-1">
                          <div class="patient-name fw-bold mb-1">
                            {{ patient.name || patient.displayName }}
                            <i v-if="patient.id === selectedPatient?.id" class="fas fa-check-circle text-success ms-2"></i>
                          </div>
                          <div class="patient-details">
                            <small class="text-muted">
                              <span class="badge bg-info me-1">{{ patient.relationship || 'N/A' }}</span>
                              <span class="me-2">{{ patient.gender }}, {{ calculateAge(patient.dateOfBirth) }} years</span>
                              <br>
                              <i class="fas fa-id-card me-1"></i>{{ patient.nric || 'No IC' }}
                            </small>
                          </div>
                        </div>
                        <div class="patient-status">
                          <span v-if="patient.id === selectedPatient?.id" 
                                class="badge bg-success">
                            Current
                          </span>
                          <span v-else class="badge bg-secondary">
                            Switch
                          </span>
                        </div>
                      </div>
                    </a>
                  </li>
                  <li class="border-top">
                    <div class="px-3 py-2 text-center">
                      <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Click on any patient to switch consultation
                      </small>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PatientHeader',
  props: {
    selectedPatient: {
      type: Object,
      default: null
    },
    groupPatients: {
      type: Array,
      default: () => []
    },
    isGroupConsultation: {
      type: Boolean,
      default: false
    },
    queueNumber: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['patient-switch'],
  computed: {
    patientName() {
      return this.selectedPatient?.name || this.selectedPatient?.displayName || 'No Patient Selected';
    },
    patientGender() {
      return this.selectedPatient?.gender || 'N/A';
    },
    patientAge() {
      return this.calculateAge(this.selectedPatient?.dateOfBirth) || 'N/A';
    },
    patientIC() {
      return this.selectedPatient?.nric || this.selectedPatient?.ic || 'No IC';
    },
    patientPhone() {
      return this.selectedPatient?.phone || this.selectedPatient?.phoneNumber || 'No phone';
    },
    patientAddress() {
      return this.selectedPatient?.address || 'No address';
    },
    currentPatientIndex() {
      if (!this.groupPatients || !this.selectedPatient) return 0;
      return this.groupPatients.findIndex(p => p.id === this.selectedPatient.id);
    }
  },
  methods: {
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        if (isNaN(birthDate.getTime())) {
          return 'N/A';
        }
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        return age > 0 ? age : 'N/A';
      } catch (error) {
        console.error('Error calculating age:', error);
        return 'N/A';
      }
    },
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      queueNumber = queueNumber.toString();
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    handlePatientSwitch(patient) {
      // Close the Bootstrap dropdown manually
      try {
        const dropdownElement = document.querySelector('.patient-selector .dropdown-toggle');
        if (dropdownElement) {
          const dropdown = window.bootstrap?.Dropdown?.getInstance(dropdownElement);
          if (dropdown) {
            dropdown.hide();
          } else {
            dropdownElement.setAttribute('aria-expanded', 'false');
            const dropdownMenu = dropdownElement.nextElementSibling;
            if (dropdownMenu) {
              dropdownMenu.classList.remove('show');
            }
          }
        }
      } catch (error) {
        console.log('Note: Could not close dropdown, continuing anyway:', error);
      }
      
      this.$emit('patient-switch', patient);
    }
  }
};
</script>

<style scoped>
/* Professional Fixed Patient Topbar */
.sticky-patient-topbar {
  position: fixed !important;
  top: 60px !important; /* Just below main navigation */
  left: 250px !important; /* More gap from sidebar border */
  right: 15px !important; /* Small margin from right edge */
  z-index: 1025; /* Below main nav but above content */
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3498db 100%) !important;
  border-bottom: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 0 0 16px 16px !important; /* Rounded bottom corners */
  transition: all 0.3s ease;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(15px);
}

/* Card styling to match other sections */
.patient-header-card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3498db 100%);
  overflow: visible !important;
}

.sticky-patient-topbar:hover {
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.25);
}

.sticky-patient-topbar .patient-name {
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
  font-size: 1.5rem;
}

.sticky-patient-topbar .badge {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 600;
}

.sticky-patient-topbar .patient-meta i {
  opacity: 0.9;
}

/* Enhanced Patient Avatar */
.sticky-patient-topbar .patient-avatar {
  position: relative;
}

.sticky-patient-topbar .patient-avatar::before {
  content: '';
  position: absolute;
  top: -5px;
  left: -5px;
  right: -5px;
  bottom: -5px;
  background: linear-gradient(45deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
  border-radius: 50%;
  z-index: -1;
}

/* Patient Dropdown Styling */
.patient-dropdown .dropdown-item {
  border: none !important;
  padding: 0.75rem 1rem !important;
  transition: all 0.2s ease;
  color: #333 !important;
}

.patient-dropdown .dropdown-item:hover {
  background-color: #f8f9fa !important;
  color: #000 !important;
}

.patient-dropdown .dropdown-item.active {
  background-color: #fff3cd !important;
  color: #856404 !important;
  border-left: 4px solid #ffc107 !important;
}

.patient-select-btn {
  font-size: 0.9rem !important;
  white-space: nowrap;
  position: relative;
  overflow: hidden;
  border: 3px solid #fff !important;
  box-shadow: 0 4px 15px rgba(255,193,7,0.4) !important;
}

.patient-select-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.patient-select-btn:hover::before {
  left: 100%;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

/* Ensure dropdown shows above everything */
.dropdown-menu {
  z-index: 1055 !important;
}

/* Professional Animation */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.sticky-patient-topbar {
  animation: fadeInDown 0.5s ease-out;
}

/* Mobile responsive adjustments */
@media (max-width: 991px) {
  .sticky-patient-topbar {
    left: 0 !important; /* Full width when sidebar collapses */
    top: 55px !important; /* Adjust for mobile sidebar collapse */
  }
}

@media (max-width: 768px) {
  .sticky-patient-topbar {
    left: 0 !important; /* Full width on mobile */
    top: 50px !important; /* Mobile nav height */
  }
  
  .sticky-patient-topbar .container-fluid {
    padding: 0 1rem !important;
  }
  
  .sticky-patient-topbar .patient-name {
    font-size: 1.2rem;
  }
  
  .sticky-patient-topbar .patient-meta {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .sticky-patient-topbar .badge {
    font-size: 0.75rem;
  }
  
  .patient-select-btn {
    padding: 0.5rem 1rem !important;
  }
  
  .patient-dropdown {
    min-width: 300px !important;
  }
  
  .patient-select-btn .d-none.d-sm-inline {
    display: none !important;
  }
}
</style> 