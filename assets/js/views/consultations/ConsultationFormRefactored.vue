<template>
  <div>
    <!-- Patient Header Component -->
    <PatientHeader
      :selected-patient="selectedPatient"
      :group-patients="groupPatients"
      :is-group-consultation="isGroupConsultation"
      :queue-number="queueNumber"
      @patient-switch="handlePatientSwitch"
    />

    <div class="consultation-form consultation-dashboard glass-card shadow p-4 mt-4 mb-4" style="margin-top: 220px !important; padding-top: 30px !important;">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-0">Consultation</h2>
          <small v-if="queueNumber" class="text-muted">
            <i class="fas fa-link me-1"></i>Started from Queue #{{ formatQueueNumber(queueNumber) }}
          </small>
        </div>
      </div>

      <form @submit.prevent="saveConsultation" class="row g-4">
        <!-- Remarks Section -->
        <div class="col-12">
          <div class="card section-card dashboard-card mb-4">
            <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
              <h5 class="mb-0 d-flex align-items-center justify-content-between">
                <span>
                  <i class="fas fa-clipboard-check text-warning me-2"></i>
                  Remarks
                </span>
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-secondary"
                  @click="refreshPatientData"
                  title="Refresh patient data to get latest remarks"
                >
                  <i class="fas fa-sync-alt"></i>
                </button>
              </h5>
            </div>
            <div class="card-body">
              <div v-if="selectedPatient && selectedPatient.remarks && selectedPatient.remarks.trim()">
                <div class="pre-illness-content p-3 bg-light rounded">
                  <div class="d-flex align-items-start gap-3">
                    <div>
                      <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                      <p class="mb-0 text-dark">{{ selectedPatient.remarks }}</p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted py-4">
                <i class="fas fa-clipboard fa-2x mb-2"></i>
                <div>No remarks available</div>
                <small class="text-muted">No initial remarks or symptoms were provided during registration</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Medical History Section Component -->
        <div class="col-12">
          <MedicalHistorySection
            :visit-histories="visitHistories"
            @show-visit-details="showVisitDetails"
          />
        </div>

        <!-- Remarks/Diagnosis -->
        <div class="col-12">
          <div class="card section-card mb-4">
            <div class="card-body">
              <h5 class="section-title mb-4">
                <i class="fas fa-notes-medical text-primary me-2"></i>
                Remarks/Diagnosis
              </h5>
              <div class="row g-4">
                <div class="col-md-12">
                  <div class="form-floating">
                    <textarea 
                      class="form-control" 
                      id="remark" 
                      v-model="consultation.notes" 
                      style="height: 100px" 
                      required
                    ></textarea>
                    <label for="remark">Remarks/Diagnosis</label>
                  </div>
                </div>
                
                <!-- Medication Section Component -->
                <div class="col-md-12">
                  <MedicationSection
                    v-model="prescribedMedications"
                    @create-medication="showCreateMedicationModal"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Medical Certificate Section Component -->
        <div class="col-12">
          <MedicalCertificateSection
            v-model:has-medical-certificate="consultation.hasMedicalCertificate"
            v-model:mc-start-date="consultation.mcStartDate"
            v-model:mc-end-date="consultation.mcEndDate"
            :selected-patient="selectedPatient"
            @mc-checkbox-change="onMCCheckboxChange"
            @show-mc-preview="showMCPreview"
          />
        </div>

        <!-- Total Payment -->
        <div class="col-12 col-md-6">
          <div class="card section-card mb-4">
            <div class="card-body">
              <h5 class="section-title mb-4">
                <i class="fas fa-money-bill text-primary me-2"></i>
                Total Payment
                <small v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="badge bg-info ms-2">Group Consultation</small>
              </h5>
              
              <div class="row g-4">
                <div class="col-md-8">
                  <div class="input-group">
                    <span class="input-group-text">RM</span>
                    <input 
                      type="number" 
                      class="form-control" 
                      id="totalAmount" 
                      :value="consultation.totalAmount && consultation.totalAmount > 0 ? consultation.totalAmount : ''" 
                      @input="updateTotalAmount" 
                      step="0.10" 
                      min="0" 
                      placeholder="Enter amount" 
                      required
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" @click="$router.back()">
            <i class="fas fa-times me-2"></i>
            Cancel
          </button>
          
          <!-- Save Current Patient Button (for group consultations) -->
          <button 
            v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" 
            type="button" 
            class="btn btn-success btn-lg shadow-sm save-patient-btn" 
            @click="saveCurrentPatient" 
            :disabled="isLoading || !currentPatientId"
            style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-weight: 600; padding: 0.75rem 2rem;"
          >
            <i v-if="!isLoading" class="fas fa-user-check me-2"></i>
            <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
            {{ isLoading ? 'Saving...' : `💾 Save ${getPatientName(currentPatientId)}` }}
          </button>
          
          <button type="submit" class="btn btn-primary" :disabled="isLoading">
            <i v-if="!isLoading" class="fas fa-check me-2"></i>
            <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
            {{ isLoading ? 'Saving...' : 'Complete Consultation' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Create New Medication Modal Component -->
    <CreateMedicationModal
      :medication-item="currentMedicationItem"
      @medication-created="handleMedicationCreated"
    />

    <!-- Other existing modals can be componentized similarly -->
    <!-- MC Preview Modal, Visit Details Modal, Consultation Summary Modal -->
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';
import AuthService from '../../services/AuthService';
import searchDebouncer from '../../utils/searchDebouncer';

// Import new components
import PatientHeader from '../../components/consultation/PatientHeader.vue';
import MedicalHistorySection from '../../components/consultation/MedicalHistorySection.vue';
import MedicationSection from '../../components/consultation/MedicationSection.vue';
import MedicalCertificateSection from '../../components/consultation/MedicalCertificateSection.vue';
import CreateMedicationModal from '../../components/consultation/CreateMedicationModal.vue';

// Helper to get today's date in YYYY-MM-DD format
function getTodayDate() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

export default {
  name: 'ConsultationForm',
  components: {
    PatientHeader,
    MedicalHistorySection,
    MedicationSection,
    MedicalCertificateSection,
    CreateMedicationModal
  },
  data() {
    return {
      consultation: {
        patientId: null,
        doctorId: null,
        consultationDate: getTodayDate(),
        diagnosis: '',
        notes: '',
        consultationFee: 0,
        hasMedicalCertificate: false,
        mcStartDate: getTodayDate(),
        mcEndDate: getTodayDate(),
        totalAmount: 0,
        status: 'completed'
      },
      isEditing: !!this.$route.params.id,
      patients: [],
      doctors: [],
      groupPatients: [],
      isGroupConsultation: false,
      patientConsultationData: {},
      currentPatientId: null,
      queueNumber: null,
      queueId: null,
      groupId: null,
      visitHistories: [],
      fullPatientDetails: null,
      isLoading: false,
      prescribedMedications: [],
      selectedVisit: null,
      currentMedicationItem: null,
      createMedicationModal: null
    };
  },
  computed: {
    selectedPatient() {
      if (this.isGroupConsultation && Array.isArray(this.groupPatients) && this.groupPatients.length > 0) {
        const groupPatient = this.groupPatients.find(p => p.id === this.consultation.patientId);
        if (groupPatient) {
          return groupPatient;
        }
      }
      
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      
      if (Array.isArray(this.patients)) {
        return this.patients.find(p => p.id === this.consultation.patientId) || null;
      }
      
      return null;
    }
  },
  async mounted() {
    console.log('🔥 ConsultationForm mounted');
    
    // Initialize medication searcher
    searchDebouncer;
    
    // Extract URL parameters
    const { patientId, doctorId, queueNumber, queueId, mode } = this.$route.query;
    
    if (patientId) {
      this.consultation.patientId = parseInt(patientId);
    }
    
    if (doctorId) {
      this.consultation.doctorId = parseInt(doctorId);
    }
    
    if (queueNumber) {
      this.queueNumber = queueNumber;
    }
    
    if (queueId) {
      this.queueId = parseInt(queueId);
      await this.loadQueueDetails();
    }
    
    // Load initial data
    await this.loadInitialData();
    
    // Initialize modals
    this.createMedicationModal = new Modal(document.getElementById('createMedicationModal'));
  },
  methods: {
    async loadQueueDetails() {
      try {
        const response = await axios.get(`/api/queue/${this.queueId}`);
        const queueData = response.data;
        
        console.log('🔍 Queue data loaded:', {
          queueId: this.queueId,
          isGroupConsultation: queueData.isGroupConsultation,
          singlePatientRemarks: queueData.patient?.remarks,
          groupPatientsCount: queueData.groupPatients?.length || 0
        });
        
        if (queueData.isGroupConsultation) {
          this.isGroupConsultation = true;
          this.groupId = queueData.groupId;
          
          if (queueData.groupPatients && Array.isArray(queueData.groupPatients)) {
            this.groupPatients = queueData.groupPatients.map(patient => {
              console.log('🔍 Processing group patient:', {
                id: patient.id,
                name: patient.name,
                remarks: patient.remarks,
                relationship: patient.relationship
              });
              
              return {
                id: patient.id,
                name: patient.name || patient.displayName,
                displayName: patient.displayName || patient.name,
                nric: patient.nric,
                dateOfBirth: patient.dateOfBirth,
                gender: patient.gender,
                phone: patient.phone,
                address: patient.address,
                relationship: patient.relationship || 'N/A',
                remarks: patient.remarks || ''
              };
            });
            
            if (!this.consultation.patientId && this.groupPatients.length > 0) {
              const primaryPatient = this.groupPatients.find(p => p.relationship === 'self') || this.groupPatients[0];
              this.consultation.patientId = primaryPatient.id;
              this.currentPatientId = primaryPatient.id;
              
              for (const patient of this.groupPatients) {
                this.initializePatientData(patient.id);
              }
              
              this.loadPatientDataToForm(primaryPatient.id);
            }
          }
        } else {
          // For single patient consultations, use the patient data from queue
          if (queueData.patient) {
            console.log('🔍 Processing single patient from queue:', {
              id: queueData.patient.id,
              name: queueData.patient.name,
              remarks: queueData.patient.remarks
            });
            
            // Update the consultation patient ID if not set
            if (!this.consultation.patientId) {
              this.consultation.patientId = queueData.patient.id;
            }
          }
        }
      } catch (error) {
        console.error('❌ Error loading queue details:', error);
      }
    },
    
    async loadInitialData() {
      await Promise.all([
        this.loadPatients(),
        this.loadDoctors()
      ]);
      
      if (this.$route.params.id) {
        await this.loadConsultation();
      } else {
        await this.fetchPatientDetails();
      }
    },
    
    async refreshPatientData() {
      // Force refresh patient data to get the latest remarks
      console.log('🔄 Refreshing patient data to get latest remarks...');
      
      if (this.queueId) {
        // If we have a queue ID, reload queue details first
        await this.loadQueueDetails();
      }
      
      // Then fetch patient details
      await this.fetchPatientDetails();
    },
    
    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        this.patients = Array.isArray(response.data) ? response.data : (response.data.patients || []);
      } catch (error) {
        console.error('Error loading patients:', error);
      }
    },
    
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        this.doctors = Array.isArray(response.data) ? response.data : (response.data.doctors || []);
      } catch (error) {
        console.error('Error loading doctors:', error);
      }
    },
    
    async fetchPatientDetails() {
      if (!this.consultation.patientId) {
        this.fullPatientDetails = null;
        this.visitHistories = [];
        return;
      }
      
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        const groupPatient = this.groupPatients.find(p => p.id === this.consultation.patientId);
        if (groupPatient) {
          this.fullPatientDetails = groupPatient;
          await this.loadVisitHistories();
          return;
        }
      }
      
      try {
        const response = await axios.get(`/api/patients/${this.consultation.patientId}`);
        this.fullPatientDetails = response.data;
        await this.loadVisitHistories();
      } catch (error) {
        console.error('Error fetching patient details:', error);
        this.fullPatientDetails = null;
        this.visitHistories = [];
      }
    },
    
    async loadVisitHistories() {
      if (!this.consultation.patientId) {
        this.visitHistories = [];
        return;
      }
      try {
        // Use the proper visit history endpoint
        const response = await axios.get(`/api/patients/${this.consultation.patientId}/visit-history`);
        const visits = response.data.visits || response.data || [];
        this.visitHistories = visits.map(visit => ({
          id: visit.id,
          consultationDate: visit.consultationDate,
          doctor: visit.doctor,
          diagnosis: visit.diagnosis || visit.notes || '',
          notes: visit.notes || '',
          status: visit.status || 'Completed'
        }));
        console.log('📋 Medical history loaded for patient:', this.consultation.patientId, 'visits:', this.visitHistories.length);
      } catch (error) {
        console.error('Error loading visit histories:', error);
        this.visitHistories = [];
      }
    },
    
    handlePatientSwitch(patient) {
      this.switchToPatient(patient.id);
    },
    
    async switchToPatient(patientId) {
      try {
        if (this.currentPatientId && this.currentPatientId !== patientId) {
          this.saveFormDataToPatient(this.currentPatientId);
        }
        
        if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
          const groupPatient = this.groupPatients.find(p => p.id === patientId);
          if (groupPatient) {
            this.fullPatientDetails = groupPatient;
          }
        }
        
        this.currentPatientId = patientId;
        this.consultation.patientId = patientId;
        
        this.initializePatientData(patientId);
        this.loadPatientDataToForm(patientId);
        
        if (!this.isGroupConsultation) {
          await this.fetchPatientDetails();
        }
      } catch (error) {
        console.error('❌ Error switching patients:', error);
      }
    },
    
    initializePatientData(patientId) {
      if (!this.patientConsultationData[patientId]) {
        this.patientConsultationData[patientId] = {
          diagnosis: '',
          notes: '',
          medications: [],
          hasMedicalCertificate: false,
          mcStartDate: getTodayDate(),
          mcEndDate: getTodayDate(),
          saved: false
        };
      }
    },
    
    saveFormDataToPatient(patientId) {
      if (!this.patientConsultationData[patientId]) {
        this.initializePatientData(patientId);
      }
      
      const existingData = this.patientConsultationData[patientId];
      this.patientConsultationData[patientId] = {
        ...existingData,
        diagnosis: this.consultation.diagnosis,
        notes: this.consultation.notes,
        medications: [...(this.prescribedMedications || [])],
        hasMedicalCertificate: this.consultation.hasMedicalCertificate,
        mcStartDate: this.consultation.mcStartDate,
        mcEndDate: this.consultation.mcEndDate
      };
    },
    
    loadPatientDataToForm(patientId) {
      const patientData = this.patientConsultationData[patientId];
      if (patientData) {
        this.consultation.diagnosis = patientData.diagnosis || '';
        this.consultation.notes = patientData.notes || '';
        this.prescribedMedications = [...(patientData.medications || [])];
        this.consultation.hasMedicalCertificate = patientData.hasMedicalCertificate !== undefined ? patientData.hasMedicalCertificate : false;
        this.consultation.mcStartDate = patientData.mcStartDate || getTodayDate();
        this.consultation.mcEndDate = patientData.mcEndDate || getTodayDate();
      } else {
        this.consultation.diagnosis = '';
        this.consultation.notes = '';
        this.prescribedMedications = [];
        this.consultation.hasMedicalCertificate = false;
        this.consultation.mcStartDate = getTodayDate();
        this.consultation.mcEndDate = getTodayDate();
      }
    },
    
    getPatientName(patientId) {
      if (!patientId) return 'No Patient Selected';
      
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        const patient = this.groupPatients.find(p => p.id === patientId);
        if (patient) {
          return patient.name || patient.displayName || 'Unnamed Patient';
        }
      }
      
      if (this.selectedPatient && this.selectedPatient.id === patientId) {
        return this.selectedPatient.name || this.selectedPatient.displayName || 'Unnamed Patient';
      }
      
      return 'Patient';
    },
    
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      queueNumber = queueNumber.toString();
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    
    async saveCurrentPatient() {
      if (!this.currentPatientId) {
        alert('No patient selected');
        return;
      }
      
      try {
        this.isLoading = true;
        this.saveFormDataToPatient(this.currentPatientId);
        this.patientConsultationData[this.currentPatientId].saved = true;
        
        const patientName = this.selectedPatient?.name || this.selectedPatient?.displayName || 'Patient';
        alert(`Successfully saved data for ${patientName}!`);
      } catch (error) {
        console.error('Error saving patient data:', error);
        alert('Error saving patient data: ' + error.message);
      } finally {
        this.isLoading = false;
      }
    },
    
    async saveConsultation() {
      if (this.isLoading) return;
      
      this.isLoading = true;
      
      try {
        if (this.isGroupConsultation && this.groupPatients && this.groupPatients.length > 1) {
          await this.saveAllGroupPatients();
        } else {
          await this.saveSingleConsultation();
        }
      } catch (error) {
        console.error('Error saving consultation:', error);
        const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message || 'Error saving consultation';
        alert('❌ ' + errorMessage);
      } finally {
        this.isLoading = false;
      }
    },
    
    async saveSingleConsultation() {
      if (!this.consultation.patientId) {
        throw new Error('Patient is required');
      }
      if (!this.consultation.doctorId) {
        throw new Error('Doctor is required');
      }
      if (!this.consultation.notes) {
        throw new Error('Notes are required');
      }

      const consultationData = {
        patientId: this.consultation.patientId,
        doctorId: this.consultation.doctorId,
        notes: this.consultation.notes,
        diagnosis: this.consultation.notes || '',
        status: this.consultation.status || 'pending',
        consultationFee: parseFloat(this.consultation.consultationFee) || 0,
        prescribedMedications: this.prescribedMedications.filter(med => med.name && med.quantity),
        mcStartDate: this.consultation.mcStartDate || null,
        mcEndDate: this.consultation.mcEndDate || null,
        queueNumber: this.queueNumber,
        groupId: null,
        isGroupConsultation: false
      };

      const response = await axios.post('/api/consultations', consultationData);
      alert('✅ Consultation saved successfully!');
      
      this.isLoading = false;
      
      const currentUser = AuthService.getCurrentUser();
      if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
        this.$router.push('/consultations/ongoing');
      } else {
        this.$router.push('/consultations');
      }
    },
    
    async saveAllGroupPatients() {
      if (this.currentPatientId) {
        this.saveFormDataToPatient(this.currentPatientId);
      }
      
      const savedConsultations = [];
      const mainPatient = this.groupPatients.find(p => p.relationship === 'self') || this.groupPatients[0];
      
      for (const patient of this.groupPatients) {
        if (!patient || !patient.id) continue;
        
        const patientData = this.patientConsultationData[patient.id];
        if (!patientData) continue;
        
        const isMainPatient = patient.id === mainPatient?.id;
        
        const consultationData = {
          patientId: patient.id,
          doctorId: this.consultation.doctorId,
          notes: patientData.notes || '',
          diagnosis: patientData.diagnosis || patientData.notes || '',
          status: 'completed',
          consultationFee: parseFloat(this.consultation.consultationFee) || 0,
          prescribedMedications: patientData.medications?.filter(med => med.name && med.quantity) || [],
          mcStartDate: patientData.hasMedicalCertificate ? patientData.mcStartDate : null,
          mcEndDate: patientData.hasMedicalCertificate ? patientData.mcEndDate : null,
          queueNumber: this.queueNumber,
          groupId: this.groupId,
          isGroupConsultation: true,
          totalAmount: isMainPatient ? (parseFloat(this.consultation.totalAmount) || 0) : 0,
          isMainPatient: isMainPatient
        };
        
        const response = await axios.post('/api/consultations', consultationData);
        savedConsultations.push({ patient: patient, consultation: response.data });
      }
      
      const mainPatientName = mainPatient?.name || mainPatient?.displayName || 'Main Patient';
      alert(`✅ Successfully saved consultations for ${savedConsultations.length} patients!\n💰 Payment (RM${this.consultation.totalAmount || 0}) will be charged to: ${mainPatientName}`);
      
      this.isLoading = false;
      
      const currentUser = AuthService.getCurrentUser();
      if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
        this.$router.push('/consultations/ongoing');
      } else {
        this.$router.push('/consultations');
      }
    },
    
    async onMCCheckboxChange(hasMC) {
      this.consultation.hasMedicalCertificate = hasMC;
      if (hasMC && !this.consultation.mcRunningNumber) {
        await this.generateMCNumber();
      }
    },
    
    async generateMCNumber() {
      try {
        const response = await axios.get('/api/medical-certificates/next-number');
        this.consultation.mcRunningNumber = response.data.runningNumber;
        return this.consultation.mcRunningNumber;
      } catch (error) {
        console.error('Error generating MC number:', error);
        const now = new Date();
        this.consultation.mcRunningNumber = now.getTime().toString().slice(-6);
        return this.consultation.mcRunningNumber;
      }
    },
    
    showMCPreview() {
      // Implement MC preview logic
      console.log('Show MC Preview');
    },
    
    showVisitDetails(visit) {
      // Implement visit details modal logic
      console.log('Show visit details:', visit);
    },
    
    showCreateMedicationModal(medicationItem) {
      this.currentMedicationItem = medicationItem;
      this.createMedicationModal.show();
    },
    
    handleMedicationCreated(newMedication) {
      if (this.currentMedicationItem) {
        // Auto-select the newly created medication
        this.currentMedicationItem.id = newMedication.id;
        this.currentMedicationItem.medicationId = newMedication.id;
        this.currentMedicationItem.name = newMedication.name;
        this.currentMedicationItem.unitType = newMedication.unitType;
        this.currentMedicationItem.unitDescription = newMedication.unitDescription;
        this.currentMedicationItem.category = newMedication.category;
        this.currentMedicationItem.actualPrice = newMedication.sellingPrice || 0.00;
      }
      
      this.createMedicationModal.hide();
      this.currentMedicationItem = null;
    },
    
    async loadConsultation() {
      // Implement load consultation for editing
      if (!this.isEditing) return;
      try {
        const response = await axios.get(`/api/consultations/${this.$route.params.id}`);
        // Handle loaded consultation data
      } catch (error) {
        console.error('Error loading consultation:', error);
      }
    },
    
    updateTotalAmount(event) {
      if (event && event.target) {
        // Handle manual input from user
        const value = event.target.value;
        this.consultation.totalAmount = value === '' ? 0 : parseFloat(value) || 0;
      }
    }
  }
};
</script>

<style scoped>
.consultation-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 0;
}

.section-card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  background: #fff;
}

.section-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #222;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-body {
  padding: 2rem 1.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 1rem;
  border-radius: 8px;
  border: none;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: #fff;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.save-patient-btn {
  position: relative;
  overflow: hidden;
  animation: saveButtonGlow 2s ease-in-out infinite alternate;
}

.save-patient-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4) !important;
}

@keyframes saveButtonGlow {
  0% { 
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  }
  100% { 
    box-shadow: 0 4px 25px rgba(40, 167, 69, 0.6);
  }
}

@media (max-width: 991px) {
  .col-lg-7, .col-lg-5 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  .consultation-form {
    margin-top: 180px !important; /* Increased for tablet screens */
  }
}

@media (max-width: 768px) {
  .consultation-form {
    padding: 1rem 0;
    margin-top: 160px !important; /* Increased for mobile screens */
  }
  .card-body {
    padding: 1rem;
  }
  .btn {
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
  }
}

/* For larger screens and potential zoom scenarios */
@media (min-width: 1200px) {
  .consultation-form {
    margin-top: 250px !important; /* Much more space for larger screens and zoom */
  }
}

/* CRITICAL: Enhanced z-index fixes for ALL modals */
.modal,
.modal.fade,
.modal.show,
.modal[style*="display: block"] {
  z-index: 1300 !important;
}

.modal-backdrop,
.modal-backdrop.fade,
.modal-backdrop.show,
.modal-backdrop.fade.show {
  z-index: 1250 !important;
}

/* Global Bootstrap modal override */
.modal {
  z-index: 1300 !important;
}

.modal-backdrop {
  z-index: 1250 !important;
}
</style> 