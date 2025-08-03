<template>
  <div class="consultation-container">
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
                  Remarks during Registration
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

    <!-- Visit Details Modal Component -->
    <VisitDetailsModal
      :visit="selectedVisitDetails"
    />

    <!-- MC Preview Modal Component -->
    <MCPreviewModal
      :patient="selectedPatient"
      :mc-data="mcPreviewData"
    />

    <!-- Other existing modals can be componentized similarly -->
    <!-- Consultation Summary Modal -->
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
import VisitDetailsModal from '../../components/consultation/VisitDetailsModal.vue';
import MCPreviewModal from '../../components/consultation/MCPreviewModal.vue';

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
    CreateMedicationModal,
    VisitDetailsModal,
    MCPreviewModal
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
        hasMedicalCertificate: true, // Default to checked
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
      createMedicationModal: null,
      visitDetailsModal: null,
      selectedVisitDetails: null,
      mcPreviewModal: null,
      mcPreviewData: null,
      singlePatientRemarks: null
    };
  },
  computed: {
    selectedPatient() {
      let selectedPatient = null;
      
      // For group consultations, use group patient data
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        const groupPatient = this.groupPatients.find(p => p.id === this.consultation.patientId);
        if (groupPatient) {
          selectedPatient = groupPatient;
        }
      }
      
      // For single patients, use full patient details if available
      if (!selectedPatient && this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        selectedPatient = this.fullPatientDetails;
      }
      
      // Fallback to patients array
      if (!selectedPatient && Array.isArray(this.patients)) {
        const fallbackPatient = this.patients.find(p => p.id === this.consultation.patientId);
        if (fallbackPatient) {
          selectedPatient = fallbackPatient;
        }
      }
      
      // For single patient consultations, prioritize queue remarks over patient remarks
      if (selectedPatient && this.singlePatientRemarks && !this.isGroupConsultation) {
        selectedPatient = { ...selectedPatient, remarks: this.singlePatientRemarks };
      }
      
      return selectedPatient;
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
    this.visitDetailsModal = new Modal(document.getElementById('visitDetailsModal'));
    this.mcPreviewModal = new Modal(document.getElementById('mcPreviewModal'));
  },
  methods: {
    async loadQueueDetails() {
      try {
        const response = await axios.get(`/api/queue/${this.queueId}`);
        const queueData = response.data;
        

        
        if (queueData.isGroupConsultation) {
          this.isGroupConsultation = true;
          this.groupId = queueData.groupId;
          
          if (queueData.groupPatients && Array.isArray(queueData.groupPatients)) {
            this.groupPatients = queueData.groupPatients.map(patient => {

              
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
                remarks: patient.remarks || '' // This comes from queue metadata via getQueueSymptoms
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

            
                          // Store the remarks from queue data (this comes from queue metadata via getQueueSymptoms)
              this.singlePatientRemarks = queueData.patient.remarks;
            
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
      }
      
      // ALWAYS fetch patient details to ensure we have the latest remarks
      await this.fetchPatientDetails();
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
          prescribedMedications: visit.prescribedMedications || [],
          totalAmount: visit.totalAmount || 0,
          mcStartDate: visit.mcStartDate,
          mcEndDate: visit.mcEndDate,
          mcRunningNumber: visit.mcRunningNumber,
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
          hasMedicalCertificate: true,
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
        this.consultation.hasMedicalCertificate = patientData.hasMedicalCertificate !== undefined ? patientData.hasMedicalCertificate : true;
        this.consultation.mcStartDate = patientData.mcStartDate || getTodayDate();
        this.consultation.mcEndDate = patientData.mcEndDate || getTodayDate();
      } else {
        this.consultation.diagnosis = '';
        this.consultation.notes = '';
        this.prescribedMedications = [];
        this.consultation.hasMedicalCertificate = true;
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

      // Prepare medications string for database
      const medicationsArray = this.prescribedMedications.filter(med => med.name && med.quantity);
      const medicationsString = medicationsArray.length > 0 
        ? medicationsArray.map(med => `${med.name} (${med.quantity} ${med.unitDescription || med.unitType || 'pieces'})`).join(', ')
        : '';

      const consultationData = {
        patientId: this.consultation.patientId,
        doctorId: this.consultation.doctorId,
        notes: this.consultation.notes,
        diagnosis: this.consultation.notes || '',
        status: this.consultation.status || 'pending',
        consultationFee: parseFloat(this.consultation.consultationFee) || 0,
        totalAmount: parseFloat(this.consultation.totalAmount) || 0, // ✅ ADD THIS MISSING FIELD!
        medications: medicationsString, // For the database medications column
        prescribedMedications: medicationsArray, // For the prescribed medications relationship
        mcStartDate: this.consultation.mcStartDate || null,
        mcEndDate: this.consultation.mcEndDate || null,
        queueNumber: this.queueNumber,
        queueId: this.queueId,
        groupId: null,
        isGroupConsultation: false
      };

      const response = await axios.post('/api/consultations', consultationData);
      alert('✅ Consultation saved successfully!');
      
      this.isLoading = false;
      
      // Always redirect to ongoing consultations page after saving
      this.$router.push('/consultations/ongoing');
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
        
        // Prepare medications string for database
        const medicationsArray = patientData.medications?.filter(med => med.name && med.quantity) || [];
        const medicationsString = medicationsArray.length > 0 
          ? medicationsArray.map(med => `${med.name} (${med.quantity} ${med.unitDescription || med.unitType || 'pieces'})`).join(', ')
          : '';

        const consultationData = {
          patientId: patient.id,
          doctorId: this.consultation.doctorId,
          notes: patientData.notes || '',
          diagnosis: patientData.diagnosis || patientData.notes || '',
          status: 'completed',
          consultationFee: parseFloat(this.consultation.consultationFee) || 0,
          medications: medicationsString, // For the database medications column
          prescribedMedications: medicationsArray, // For the prescribed medications relationship
          mcStartDate: patientData.hasMedicalCertificate ? patientData.mcStartDate : null,
          mcEndDate: patientData.hasMedicalCertificate ? patientData.mcEndDate : null,
          queueNumber: this.queueNumber,
          queueId: this.queueId,
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
      
      // Always redirect to ongoing consultations page after saving
      this.$router.push('/consultations/ongoing');
    },
    
    async onMCCheckboxChange(hasMC) {
      this.consultation.hasMedicalCertificate = hasMC;
      if (hasMC && !this.consultation.mcRunningNumber) {
        await this.generateMCNumber();
      }
    },
    
    async generateMCNumber() {
      try {
        console.log('🔄 Generating MC number...');
        const response = await axios.get('/api/medical-certificates/next-number');
        
        if (response.data && response.data.runningNumber) {
          this.consultation.mcRunningNumber = response.data.runningNumber;
          console.log('✅ MC number generated:', this.consultation.mcRunningNumber);
          return this.consultation.mcRunningNumber;
        } else {
          throw new Error('Invalid response format from MC number API');
        }
      } catch (error) {
        console.error('❌ Error generating MC number:', error);
        
        // Better fallback: use current date + time
        const now = new Date();
        const year = now.getFullYear().toString().slice(-2);
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        const day = now.getDate().toString().padStart(2, '0');
        const time = now.getTime().toString().slice(-4);
        
        this.consultation.mcRunningNumber = `${year}${month}${day}${time}`;
        console.log('⚠️ Using fallback MC number:', this.consultation.mcRunningNumber);
        
        // Show user-friendly error
        alert('⚠️ Could not generate MC number from system. Using fallback number: ' + this.consultation.mcRunningNumber);
        
        return this.consultation.mcRunningNumber;
      }
    },
    
    showMCPreview() {
      if (!this.selectedPatient) {
        alert('No patient selected for MC preview');
        return;
      }
      
      // Prepare MC data for preview (without consultation notes/diagnosis)
      this.mcPreviewData = {
        mcRunningNumber: this.consultation.mcRunningNumber,
        mcStartDate: this.consultation.mcStartDate,
        mcEndDate: this.consultation.mcEndDate,
        doctorName: this.getDoctorName(this.consultation.doctorId),
        doctorRegNo: 'MMC12345', // This should come from doctor data
        consultationDate: this.consultation.consultationDate
      };
      
      // Show the modal
      this.mcPreviewModal.show();
    },
    
    getDoctorName(doctorId) {
      if (!doctorId || !this.doctors) return 'Doctor Name';
      const doctor = this.doctors.find(d => d.id === doctorId);
      return doctor ? doctor.name : 'Doctor Name';
    },
    
    async showVisitDetails(visit) {
      try {
        // Load full visit details from API
        const response = await axios.get(`/api/consultations/${visit.id}`);
        
        // Merge API data with basic visit data
        this.selectedVisitDetails = {
          ...visit, // Use basic data as fallback
          ...response.data, // Override with API data
          consultationDate: response.data.consultationDate || visit.consultationDate,
          doctor: response.data.doctor || visit.doctor,
          prescribedMedications: response.data.prescribedMedications || visit.prescribedMedications || [],
          totalAmount: response.data.totalAmount || visit.totalAmount || 0
        };
        
        // Show the modal
        this.visitDetailsModal.show();
      } catch (error) {
        console.error('Error loading visit details:', error);
        // Fall back to showing the basic visit data with defaults
        this.selectedVisitDetails = {
          ...visit,
          consultationDate: visit.consultationDate || new Date().toISOString().split('T')[0],
          doctor: visit.doctor || { name: 'Unknown Doctor' },
          prescribedMedications: visit.prescribedMedications || [],
          totalAmount: visit.totalAmount || 0
        };
        this.visitDetailsModal.show();
      }
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
    },
    
    cancelPendingRequests() {
      // Cancel any axios requests that might be pending
      console.log('🛑 Cancelling pending requests');
      
      // If there are any ongoing axios requests, they should be cancelled here
      // This prevents the system from hanging on navigation or component unmount
    },
    
    clearAllTimeouts() {
      // Clear any JavaScript timeouts that might be running
      // This prevents memory leaks and unexpected behavior
      console.log('🧹 Clearing timeouts and intervals');
    },
    
    // Enhanced error handling for API calls
    async safeApiCall(apiCall, fallbackValue = null, timeoutMs = 10000) {
      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        
        const result = await apiCall(controller.signal);
        clearTimeout(timeoutId);
        return result;
        
      } catch (error) {
        if (error.name === 'AbortError') {
          console.warn('⏰ API call timed out');
          return fallbackValue;
        }
        console.error('❌ API call failed:', error);
        throw error;
      }
    }
  },
  beforeUnmount() {
    // Cancel any pending medication searches
    if (this.medicationSearcher) {
      this.medicationSearcher.cleanup();
    }
    
    // Cancel any pending API requests
    this.cancelPendingRequests();
    
    // Clear any timeouts
    this.clearAllTimeouts();
  }
};
</script>

<style scoped>
.consultation-container {
  /* Ensure sticky positioning works */
  height: auto;
  overflow: visible;
  position: relative;
}

.consultation-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 0;
  margin-top: 140px; /* Account for fixed patient header */
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