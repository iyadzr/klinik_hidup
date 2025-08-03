<template>
  <div class="patient-header-bar sticky-patient-topbar shadow-lg" role="region" aria-label="Patient Information">
    <div class="container-fluid">
      <div class="row align-items-center py-3">
        <div class="col-md-12">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <!-- Patient Info Section -->
            <div class="d-flex align-items-center flex-grow-1 patient-info-main">
              <div class="patient-avatar me-4 flex-shrink-0">
                <i class="fas fa-user-circle"></i>
              </div>
              <div class="patient-summary flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
                  <span class="patient-name">{{ patientName }}</span>
                  <span class="badge patient-badge-age">{{ patientGender }}, {{ patientAge }} years</span>
                  <span class="badge patient-badge-ic">{{ patientIC }}</span>
                </div>
                <div class="patient-meta d-flex gap-4 flex-wrap">
                  <span v-if="queueNumber" class="patient-meta-item">
                    <i class="fas fa-list-ol me-1"></i>
                    <span class="patient-meta-label">Queue</span>
                    <span class="patient-meta-value">#{{ formatQueueNumber(queueNumber) }}</span>
                  </span>
                  <span class="patient-meta-item">
                    <i class="fas fa-phone me-1"></i>
                    <span class="patient-meta-label">Phone</span>
                    <span class="patient-meta-value">{{ patientPhone }}</span>
                  </span>
                  <span class="patient-meta-item">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <span class="patient-meta-label">Address</span>
                    <span class="patient-meta-value">{{ patientAddress }}</span>
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Enhanced Group Patient Selector -->
            <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="patient-selector ms-3 mt-3 mt-md-0">
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
                    style="min-width: 400px; border-radius: 15px; max-height: 500px; overflow-y: auto; z-index: 1050;">
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
                       :class="{ 'active': patient.id === selectedPatient?.id }"
                       style="border-left: 4px solid transparent; transition: all 0.2s ease;">
                      <div class="d-flex align-items-start">
                        <div class="patient-avatar me-3 mt-1">
                          <div class="rounded-circle d-flex align-items-center justify-content-center"
                               :class="patient.id === selectedPatient?.id ? 'bg-warning text-white' : 'bg-primary text-white'"
                               style="width: 50px; height: 50px; font-size: 1.2rem;">
                            <i class="fas fa-user"></i>
                          </div>
                        </div>
                        <div class="patient-info flex-grow-1">
                          <div class="patient-name fw-bold mb-2" style="font-size: 1.1rem; color: #2c3e50;">
                            {{ patient.name || patient.displayName }}
                            <i v-if="patient.id === selectedPatient?.id" class="fas fa-check-circle text-success ms-2"></i>
                          </div>
                          <div class="patient-details">
                            <div class="mb-1">
                              <span class="badge bg-info me-2" style="font-size: 0.75rem;">{{ patient.relationship || 'N/A' }}</span>
                              <span class="text-dark fw-medium">{{ patient.gender }}, {{ calculateAge(patient.dateOfBirth) }} years</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.85rem;">
                              <i class="fas fa-id-card me-1"></i>{{ patient.nric || 'No IC' }}
                            </div>
                          </div>
                        </div>
                        <div class="patient-status ms-2">
                          <span v-if="patient.id === selectedPatient?.id" 
                                class="badge bg-success px-3 py-2"
                                style="font-size: 0.8rem;">
                            <i class="fas fa-user-check me-1"></i>Current
                          </span>
                          <span v-else class="badge bg-outline-secondary px-3 py-2"
                                style="font-size: 0.8rem; border: 1px solid #6c757d; color: #6c757d;">
                            <i class="fas fa-arrow-right me-1"></i>Switch
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

<style scoped>
.patient-dropdown {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
}

.patient-item {
  transition: all 0.2s ease;
  border-radius: 8px;
  margin: 2px 8px;
}

.patient-item:hover {
  background-color: #f8f9fa !important;
  transform: translateX(2px);
  border-left-color: #007bff !important;
}

.patient-item.active {
  background-color: #fff3cd !important;
  border-left-color: #ffc107 !important;
}

.patient-item.active:hover {
  background-color: #ffeaa7 !important;
}

.patient-option {
  margin-bottom: 2px;
}

.patient-name {
  line-height: 1.2;
}

.patient-details {
  line-height: 1.4;
}

.badge {
  font-weight: 500;
}

.bg-outline-secondary {
  background-color: transparent !important;
}
</style>

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
  top: 10px; /* Moved down slightly from top edge */
  left: 250px; /* Account for sidebar width */
  right: 0;
  z-index: 1100;
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3498db 100%);
  color: #fff;
  border-radius: 0 0 18px 18px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.18);
  margin-bottom: 3rem; /* Increased margin for better spacing */
  padding: 1.5rem 2rem;
}

.sticky-patient-topbar .patient-name {
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
  font-size: 2.5rem;
  font-weight: 800;
}

.sticky-patient-topbar .badge {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 700;
  font-size: 1.3rem;
  padding: 0.6em 1.2em;
}

.sticky-patient-topbar .patient-meta {
  font-size: 1.3rem;
  gap: 2.5rem;
}

.sticky-patient-topbar .patient-meta-label {
  font-weight: 600;
  color: #dbeafe;
  margin-right: 0.3em;
}

.sticky-patient-topbar .patient-meta-value {
  font-weight: 700;
  color: #fff;
}

.sticky-patient-topbar .patient-avatar i {
  font-size: 4rem;
  color: #fff;
  background: #1e40af;
  border-radius: 50%;
  padding: 0.5rem;
}

.sticky-patient-topbar .patient-info-main {
  min-width: 0;
}

.sticky-patient-topbar .patient-summary {
  min-width: 0;
}

.sticky-patient-topbar .patient-badge-age, .sticky-patient-topbar .patient-badge-ic {
  font-size: 1.2rem;
  font-weight: 700;
  background: #1e40af;
  color: #fff;
  border-radius: 12px;
  padding: 0.5em 1.2em;
}

.sticky-patient-topbar .patient-meta-item {
  display: flex;
  align-items: center;
  gap: 0.5em;
  margin-bottom: 0.25em;
}

.sticky-patient-topbar .patient-meta-item i {
  font-size: 1.2em;
  color: #fbbf24;
  margin-right: 0.3em;
}

@media (max-width: 768px) {
  .sticky-patient-topbar {
    left: 0; /* Full width on mobile */
    padding: 0.5rem 0.2rem;
    top: 5px; /* Less space from top on mobile */
  }
  .sticky-patient-topbar .patient-name {
    font-size: 1.7rem;
  }
  .sticky-patient-topbar .patient-meta {
    font-size: 1.1rem;
    gap: 1.2rem;
  }
  .sticky-patient-topbar .badge {
    font-size: 1rem;
    padding: 0.3em 0.7em;
  }
  .sticky-patient-topbar .patient-badge-age, .sticky-patient-topbar .patient-badge-ic {
    font-size: 1rem;
    padding: 0.3em 0.7em;
  }
}

/* Additional responsive breakpoints for better zoom handling */
@media (max-width: 991px) {
  .sticky-patient-topbar {
    top: 8px; /* Slightly less space on tablets */
    padding: 1.2rem 1.5rem;
  }
  .sticky-patient-topbar .patient-name {
    font-size: 2.2rem;
  }
  .sticky-patient-topbar .patient-meta {
    font-size: 1.2rem;
    gap: 2rem;
  }
}

/* For larger screens with potential zoom */
@media (min-width: 1200px) {
  .sticky-patient-topbar {
    top: 15px; /* More space on larger screens */
    padding: 1.8rem 2.5rem;
  }
}
</style> 