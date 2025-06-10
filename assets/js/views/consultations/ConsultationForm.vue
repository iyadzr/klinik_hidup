<template>
  <div class="consultation-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Consultation</h2>
        <small v-if="$route.query.queueNumber" class="text-muted">
          <i class="fas fa-link me-1"></i>Started from Queue #{{ formatQueueNumber($route.query.queueNumber) }}
        </small>
      </div>
      <div class="d-flex gap-2">

      </div>
    </div>

    <form @submit.prevent="saveConsultation" class="row g-4">
      <!-- Patient Information -->
      <div class="col-12 col-lg-8">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-user-injured text-primary me-2"></i>
              Patient Information
            </h5>
            <div class="row g-3">
              <div class="col-12" v-if="selectedPatient">
                <div class="patient-details p-3 bg-light rounded w-100 d-flex align-items-start gap-3">
                  <div>
                    <i class="fas fa-user-circle fa-3x text-secondary"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="row g-2">
                      <div class="col-md-6">
                        <small class="text-muted d-block">Name</small>
                        <span class="fw-bold">{{ selectedPatient?.name || selectedPatient?.displayName || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Registration Number</small>
                        <span>{{ selectedPatient?.registrationNumber || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">IC/Passport</small>
                        <span>{{ selectedPatient?.nric || selectedPatient?.ic || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Age</small>
                        <span>{{ selectedPatient && selectedPatient.dateOfBirth ? calculateAge(selectedPatient.dateOfBirth) + ' years' : 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Date of Birth</small>
                        <span>{{ selectedPatient?.dateOfBirth ? new Date(selectedPatient.dateOfBirth).toLocaleDateString() : 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Gender</small>
                        <span>{{ selectedPatient?.gender || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Race</small>
                        <span>{{ selectedPatient?.race || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Phone Number</small>
                        <span>{{ selectedPatient?.phoneNumber || selectedPatient?.phone || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Email</small>
                        <span>{{ selectedPatient?.email || 'N/A' }}</span>
                      </div>
                      <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span>{{ selectedPatient?.address || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Blood Type</small>
                        <span>{{ selectedPatient?.bloodType || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Allergies</small>
                        <span>{{ selectedPatient?.allergies || 'None' }}</span>
                      </div>
                      <div class="col-12">
                        <small class="text-muted d-block">Medical Conditions</small>
                        <span>{{ selectedPatient?.medicalConditions || 'None' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12" v-else>
                <div class="patient-details p-3 bg-light rounded w-100 text-center text-muted">
                  <i class="fas fa-user-circle fa-2x mb-2"></i>
                  <div>No patient selected. Please search and select a patient.</div>
                </div>
              </div>

              <div class="medical-history">
                <h4>Medical History</h4>
                <div v-if="visitHistories && visitHistories.length > 0" class="history-list">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Diagnosis</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="visit in visitHistories" 
                        :key="visit.id" 
                        class="history-item"
                        @click="showVisitDetails(visit)"
                        style="cursor: pointer;"
                      >
                        <td>{{ visit.consultationDate ? new Date(visit.consultationDate).toLocaleDateString() : 'N/A' }}</td>
                        <td>Dr. {{ visit.doctor?.name || 'Unknown' }}</td>
                        <td>{{ visit.diagnosis || 'No diagnosis recorded' }}</td>
                        <td>
                          <span :class="getStatusClass(visit.status)">
                            {{ visit.status || 'Completed' }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="no-history">
                  No medical history found
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Pre-Informed Illness -->
      <div class="col-12 col-lg-4">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-clipboard-check text-primary me-2"></i>
              Pre-Informed Illness
            </h5>
            <div v-if="selectedPatient && selectedPatient.preInformedIllness">
              <div class="pre-illness-content p-3 bg-light rounded">
                <div class="d-flex align-items-start gap-3">
                  <div>
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="fw-bold text-dark mb-2">Patient's Initial Symptoms/Complaint</h6>
                    <p class="mb-0 text-dark">{{ selectedPatient.preInformedIllness }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted py-4">
              <i class="fas fa-clipboard fa-2x mb-2"></i>
              <div>No pre-informed illness data available</div>
              <small class="text-muted">Patient did not provide initial symptoms during registration</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Consultation Details -->
      <div class="col-12">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-notes-medical text-primary me-2"></i>
              Consultation Details
            </h5>
            <div class="row g-4">
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="remark" v-model="consultation.notes" style="height: 100px" required></textarea>
                  <label for="remark">Remark</label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="medications" v-model="consultation.medications" style="height: 100px" required></textarea>
                  <label for="medications">Prescribed Medications</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MC Checkbox and Dates: visible for doctor, after consultation details -->
      <div class="col-12 col-md-6">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-file-medical text-primary me-2"></i>
              Medical Certificate
            </h5>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="hasMedicalCertificate" v-model="consultation.hasMedicalCertificate">
                <label class="form-check-label fw-bold" for="hasMedicalCertificate">
                  Issue Medical Certificate (MC) for this visit
                </label>
              </div>
              <button 
                type="button" 
                class="btn btn-outline-info btn-sm" 
                @click="showMCPreview" 
                v-if="consultation.hasMedicalCertificate && selectedPatient"
                :disabled="!consultation.mcStartDate || !consultation.mcEndDate">
                <i class="fas fa-eye me-1"></i> Review MC
              </button>
            </div>
            <div v-if="consultation.hasMedicalCertificate">
              <!-- If multiple patients, show checkboxes for each -->
              <div v-if="familyPatients && familyPatients.length > 1" class="mb-3">
                <label class="form-label fw-bold">Select patients to print MC for:</label>
                <div v-for="patient in familyPatients" :key="patient.id" class="form-check">
                  <input class="form-check-input" type="checkbox" :id="'mc-patient-' + patient.id" :value="patient.id" v-model="mcSelectedPatientIds">
                  <label class="form-check-label" :for="'mc-patient-' + patient.id">
                    {{ patient.name || patient.displayName || 'Unknown' }}
                  </label>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" v-model="consultation.mcStartDate" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">End Date</label>
                  <input type="date" class="form-control" v-model="consultation.mcEndDate" required>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden MC Print Template for Multiple Patients -->
      <div id="mc-print-content" style="display:none">
        <div v-if="familyPatients && familyPatients.length > 1">
          <div v-for="patient in familyPatients" :key="patient.id" v-if="mcSelectedPatientIds.includes(patient.id)">
            <div style="font-family: Arial, sans-serif; width: 400px; padding: 24px; border: 2px solid #222; page-break-after: always;">
              <h2 style="text-align:center; margin-bottom: 20px;">Medical Certificate</h2>
              <p><strong>MC Number:</strong> {{ consultation.mcRunningNumber }}</p>
              <p><strong>Patient Name:</strong> {{ patient.name || patient.displayName }}</p>
              <p><strong>MC Period:</strong> {{ consultation.mcStartDate }} to {{ consultation.mcEndDate }}</p>
              <p>This is to certify that the above patient is medically unfit for work during the stated period.</p>
              <p style="margin-top:40px;">Doctor's Signature: ____________________</p>
            </div>
          </div>
        </div>
        <div v-else>
          <div style="font-family: Arial, sans-serif; width: 400px; padding: 24px; border: 2px solid #222;">
            <h2 style="text-align:center; margin-bottom: 20px;">Medical Certificate</h2>
            <p><strong>MC Number:</strong> {{ consultation.mcRunningNumber }}</p>
            <p><strong>Patient Name:</strong> {{ selectedPatient?.name || selectedPatient?.displayName }}</p>
            <p><strong>MC Period:</strong> {{ consultation.mcStartDate }} to {{ consultation.mcEndDate }}</p>
            <p>This is to certify that the above patient is medically unfit for work during the stated period.</p>
            <p style="margin-top:40px;">Doctor's Signature: ____________________</p>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div class="col-12 col-md-6">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-money-bill text-primary me-2"></i>
              Payment Information
            </h5>
            <div class="row g-4">
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="number" class="form-control" id="totalAmount" v-model="consultation.totalAmount" step="0.10" min="0" required>
                  <label for="totalAmount">Total Amount (RM)</label>
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
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-check me-2"></i>
          Complete Consultation
        </button>
      </div>
    </form>

    <!-- Medical Certificate Modal -->
    <div class="modal fade" id="medicalCertificateModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Medical Certificate</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <medical-certificate-form 
              :consultation="consultation"
              :patient="selectedPatient"
              @generated="handleCertificateGenerated"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1" ref="prescriptionModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Prescription</h5>
            <button type="button" class="btn-close" @click="showPrescriptionForm = false"></button>
          </div>
          <div class="modal-body p-0">
            <prescription-form
              v-if="showPrescriptionForm"
              :consultation="consultation"
              :patient="selectedPatient"
              @generated="onPrescriptionGenerated"
              @cancel="showPrescriptionForm = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
    <!-- MC Preview Modal -->
    <div class="modal fade" id="mcPreviewModal" tabindex="-1" aria-labelledby="mcPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="mcPreviewModalLabel">Medical Certificate Preview</h5>
            <button type="button" class="btn-close" @click="hideMCPreview"></button>
          </div>
          <div class="modal-body">
            <!-- MC Preview Content -->
            <div class="mc-preview" style="background-color: #e8e5b0; padding: 20px; border: 1px solid #000;">
              <!-- Clinic Header -->
              <div class="text-center mb-3">
                <h3 class="mb-0" style="font-weight: bold;">KLINIK HIDUPsihat</h3>
                <p class="mb-1">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
                <p class="mb-1">Tel: 03-8740 0678</p>
              </div>
              
              <!-- MC Running Number -->
              <div class="d-flex justify-content-end mb-3">
                <div>
                  <p class="mb-0" style="font-size: 0.9rem; color: #a52a2a;">No: {{ consultation.mcRunningNumber || '426740' }}</p>
                </div>
              </div>
              
              <h4 class="text-center mb-4">SURAT AKUAN SAKIT (MC)</h4>
              
              <div class="d-flex justify-content-between">
                <p>Saya mengesahkan telah memeriksa;</p>
                <p><strong>Tarikh:</strong> {{ formatDate(new Date()) }}</p>
              </div>
              <p class="ms-3 mb-1"><strong>Nama dan No KP:</strong> {{ selectedPatient?.name || selectedPatient?.displayName || 'Unknown' }} ({{ selectedPatient?.nric || selectedPatient?.ic || '******' }})</p>
              <p class="ms-3 mb-4"><strong>dari:</strong> {{ selectedPatient?.company || 'yang berkenaan' }}</p>
              
              <p>Beliau didapati tidak sihat dan tidak dapat menjalankan tugas selama</p>
              <p class="ms-3 mb-3">
                <strong>{{ calculateMCDays() }}</strong> hari mulai <strong>{{ formatDate(consultation.mcStartDate) }}</strong> sehingga <strong>{{ formatDate(consultation.mcEndDate) }}</strong>
              </p>
              
              <div class="d-flex justify-content-end mt-5">
                <div style="text-align: center;">
                  <!-- Signature Line -->
                  <div style="border-bottom: 1px dotted #000; width: 150px; margin-bottom: 10px;">&nbsp;</div>
                  <p class="mb-0">Tandatangan</p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="hideMCPreview">Close</button>
            <button type="button" class="btn btn-primary" @click="printMC">
              <i class="fas fa-print me-2"></i> Print MC
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Visit Details Modal -->
    <div class="modal fade" id="visitDetailsModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content visit-details-modal">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="fas fa-file-medical-alt text-primary me-2"></i>
              Visit Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="selectedVisit">
            <div class="visit-info-grid">
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-calendar-alt text-primary"></i>
                  <span>Visit Information</span>
                </div>
                <div class="info-content">
                  <div class="info-item">
                    <label>Date:</label>
                    <span>{{ new Date(selectedVisit.consultationDate).toLocaleDateString() }}</span>
                  </div>
                  <div class="info-item">
                    <label>Doctor:</label>
                    <span>Dr. {{ selectedVisit.doctor?.name || 'Unknown' }}</span>
                  </div>
                  <div class="info-item">
                    <label>Status:</label>
                    <span :class="getStatusClass(selectedVisit.status)">{{ selectedVisit.status || 'Completed' }}</span>
                  </div>
                </div>
              </div>
              
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-stethoscope text-primary"></i>
                  <span>Medical Details</span>
                </div>
                <div class="info-content">
                  <div class="info-item full-width">
                    <label>Diagnosis:</label>
                    <span>{{ selectedVisit.diagnosis || 'No diagnosis recorded' }}</span>
                  </div>
                  <div class="info-item full-width">
                    <label>Notes:</label>
                    <span>{{ selectedVisit.notes || 'No notes recorded' }}</span>
                  </div>
                </div>
              </div>

              <div class="info-card" v-if="selectedVisit.medications">
                <div class="info-header">
                  <i class="fas fa-pills text-primary"></i>
                  <span>Medications</span>
                </div>
                <div class="info-content">
                  <div class="medications-table">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Medication</th>
                          <th>Dosage</th>
                          <th>Frequency</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(med, index) in JSON.parse(selectedVisit.medications || '[]')" :key="index">
                          <td>{{ med.name || 'N/A' }}</td>
                          <td>{{ med.dosage || 'N/A' }}</td>
                          <td>{{ med.frequency || 'N/A' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
// Helper to get today's date in YYYY-MM-DD npm npmormat
function getTodayDate() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}
import { Modal } from 'bootstrap';
import axios from 'axios';
import MedicalCertificateForm from '../certificates/MedicalCertificateForm.vue';
import PrescriptionForm from '../prescriptions/PrescriptionForm.vue';
import * as bootstrap from 'bootstrap';

export default {
  name: 'ConsultationForm',
  components: {
    MedicalCertificateForm,
    PrescriptionForm
  },
  emits: [
    'patientAdded',
    'patientUpdated',
    'patientDeleted',
    'appointmentAdded',
    'appointmentUpdated',
    'appointmentDeleted',
    'loginSuccess'
  ],
  data() {
    const today = new Date().toISOString().split('T')[0];
    return {
      consultation: {
        patientId: null,
        doctorId: null,
        consultationDate: today,
        diagnosis: '',
        notes: '',
        consultationFee: 0,
        hasMedicalCertificate: true,
        medicalCertificateDays: 1,
        mcStartDate: today,
        mcEndDate: today,
        medications: [],
        status: 'completed'
      },
      isEditing: !!this.$route.params.id,
      patients: [],
      doctors: [],
      familyPatients: [],
      mcSelectedPatientIds: [],
      visitHistories: [],
      fullPatientDetails: null,
      medicalCertificateModal: null,
      prescriptionModal: null,
      mcPreviewModal: null,
      isLoading: false,
      showPrescriptionForm: false,
      medications: [],
      selectedMedication: null,
      dosage: '',
      frequency: '',
      duration: '',
      instructions: '',
      selectedVisit: null,
      visitDetailsModal: null,
    };
  },
  computed: {
    selectedPatient() {
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      return this.patients.find(p => p.id === this.consultation.patientId) || null;
    }
  },
  methods: {
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return '';
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    },
    isEditing() {
      return !!this.$route.params.id;
    },
    totalAmount() {
      return (parseFloat(this.consultation.consultationFee || 0) + 
              parseFloat(this.consultation.medicinesFee || 0));
    },
    printMC() {
      // If multiple patients, print for all selected; else, print for selectedPatient
      const printContents = document.getElementById('mc-print-content').innerHTML;
      const printWindow = window.open('', '', 'height=600,width=600');
      printWindow.document.write('<html><head><title>Medical Certificate</title>');
      printWindow.document.write('</head><body >');
      printWindow.document.write(printContents);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 300);
    },
    showMCPreview() {
      if (!this.mcPreviewModal) {
        this.mcPreviewModal = new Modal(document.getElementById('mcPreviewModal'));
      }
      this.mcPreviewModal.show();
    },
    hideMCPreview() {
      if (this.mcPreviewModal) {
        this.mcPreviewModal.hide();
      }
    },
    calculateMCDays() {
      if (!this.consultation.mcStartDate || !this.consultation.mcEndDate) {
        return '0';
      }
      
      const start = new Date(this.consultation.mcStartDate);
      const end = new Date(this.consultation.mcEndDate);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
      
      return diffDays.toString();
    },
    formatDate(dateString) {
      if (!dateString) return '';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('ms-MY', {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric'
      });
    },
    generateMCNumber() {
      // Generate a clinic-specific identifier for the MC
      // Typically includes location code + date components
      const now = new Date();
      const locationCode = 'KHS'; // Klinik HIDUPsihat
      const year = now.getFullYear().toString().substring(2); // Last 2 digits of year
      const month = (now.getMonth() + 1).toString().padStart(2, '0');
      const day = now.getDate().toString().padStart(2, '0');
      
      if (!this.consultation.mcNumber) {
        this.consultation.mcNumber = `${locationCode}/${year}${month}${day}`;
      }
      
      return this.consultation.mcNumber;
    },
    showVisitHistoryModal() {
      if (!this.visitHistoryModalInstance) {
        const modalEl = this.$refs.visitHistoryModal;
        if (modalEl) {
          this.visitHistoryModalInstance = new Modal(modalEl);
        }
      }
      this.loadVisitHistories();
      this.visitHistoryModalInstance && this.visitHistoryModalInstance.show();
    },
    hideVisitHistoryModal() {
      this.visitHistoryModalInstance && this.visitHistoryModalInstance.hide();
    },
    async loadVisitHistories() {
      if (!this.consultation.patientId) {
        this.visitHistories = [];
        return;
      }
      try {
        const response = await axios.get(`/api/consultations/patient/${this.consultation.patientId}`);
        this.visitHistories = response.data.map(visit => ({
          id: visit.id,
          consultationDate: visit.consultationDate,
          doctor: visit.doctor,
          diagnosis: visit.diagnosis || '',
          notes: visit.notes || ''
        }));
      } catch (error) {
        console.error('Error loading visit histories:', error);
        this.visitHistories = [];
      }
    },

    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        console.log('Loaded patients:', response.data);
        this.patients = Array.isArray(response.data) ? response.data : (response.data.patients || []);
      } catch (error) {
        console.error('Error loading patients:', error);
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        console.log('Loaded doctors:', response.data);
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
    async loadConsultation() {
      if (!this.isEditing) return;
      try {
        const response = await axios.get(`/api/consultations/${this.$route.params.id}`);
        // ...
      } catch (error) {
        console.error('Error loading consultation:', error);
      }
    },
    async saveConsultation() {
      try {
        // Validate required fields
        if (!this.consultation.patientId) {
          throw new Error('Patient is required');
        }
        if (!this.consultation.doctorId) {
          throw new Error('Doctor is required');
        }
        if (!this.consultation.notes) {
          throw new Error('Notes are required');
        }

        // Prepare consultation data
        const consultationData = {
          patientId: this.consultation.patientId,
          doctorId: this.consultation.doctorId,
          notes: this.consultation.notes,
          diagnosis: this.consultation.diagnosis || '',
          status: this.consultation.status || 'pending',
          consultationFee: parseFloat(this.consultation.consultationFee) || 0,
          medications: JSON.stringify(this.consultation.medications || []), // Convert array to JSON string
          mcStartDate: this.consultation.mcStartDate || null,
          mcEndDate: this.consultation.mcEndDate || null,
          mcNotes: this.consultation.mcNotes || ''
        };

        console.log('Sending consultation data:', consultationData);

        const response = await axios.post('/api/consultations', consultationData);
        console.log('Consultation saved successfully:', response.data);

        // Show success message
        alert('Consultation saved successfully');
        
        // Redirect to consultations list
        this.$router.push('/consultations');
      } catch (error) {
        console.error('Error saving consultation:', error);
        alert(error.response?.data?.message || error.message || 'Error saving consultation');
      }
    },
    onPrescriptionGenerated(prescriptionData) {
      // Here you would typically make an API call to save the prescription
      console.log('Prescription generated:', prescriptionData);
      this.showPrescriptionForm = false;
      this.$refs.prescriptionModal.hide();
      this.$toast.success('Prescription generated successfully');
    },
    generatePrescription() {
      this.showPrescriptionForm = true;
      const modal = new bootstrap.Modal(this.$refs.prescriptionModal);
      modal.show();
    },
    handleCertificateGenerated() {
      if (this.medicalCertificateModal) {
        this.medicalCertificateModal.hide();
      }
    },
    selectPatient(patient) {
      this.consultation.patientId = patient.id;
      this.fetchPatientDetails();
    },
    showVisitDetails(visit) {
      this.selectedVisit = visit;
      this.visitDetailsModal.show();
    },
    getStatusClass(status) {
      const statusMap = {
        'Completed': 'badge bg-success',
        'In Progress': 'badge bg-warning',
        'Cancelled': 'badge bg-danger',
        'Pending': 'badge bg-info'
      };
      return statusMap[status] || 'badge bg-secondary';
    },
    reviewMC() {
      // Show MC modal or perform MC review logic
      // Placeholder: you can implement your modal logic here
      alert('Review MC clicked!');
    },
    async loadMedications() {
      try {
        const response = await axios.get('/api/medications');
        this.medications = response.data;
      } catch (error) {
        console.error('Error loading medications:', error);
        this.medications = [];
      }
    },
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      // Ensure string
      queueNumber = queueNumber.toString();
      // Pad to 4 digits (e.g., 8001 -> 8001, 801 -> 0801)
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    }
  },
  watch: {
    // Whenever familyPatients changes, default all selected for MC
    familyPatients: {
      handler(newVal) {
        if (Array.isArray(newVal) && newVal.length > 1) {
          this.mcSelectedPatientIds = newVal.map(p => p.id);
        } else if (Array.isArray(newVal) && newVal.length === 1) {
          this.mcSelectedPatientIds = [newVal[0].id];
        } else {
          this.mcSelectedPatientIds = [];
        }
      },
      immediate: true,
      deep: true
    }
  },
  async created() {
    await this.loadPatients();
    await this.loadDoctors();
    this.medicalCertificateModal = new bootstrap.Modal(document.getElementById('medicalCertificateModal'));
    
    // Check if we're coming from the queue with patient/doctor info
    const routeQuery = this.$route.query;
    if (routeQuery.queueNumber && routeQuery.patientId && routeQuery.doctorId) {
      // Auto-fill from queue information
      this.consultation.patientId = parseInt(routeQuery.patientId);
      this.consultation.doctorId = parseInt(routeQuery.doctorId);
      
      // Load patient details
      await this.fetchPatientDetails();
    } else {
      // Set the doctor ID from the logged-in user or use the first doctor
      const user = JSON.parse(localStorage.getItem('user'));
      if (user && user.id) {
        this.consultation.doctorId = user.id;
      } else if (this.doctors && this.doctors.length > 0) {
        // Temporarily use the first doctor until auth is implemented
        this.consultation.doctorId = this.doctors[0].id;
        console.log('Using first doctor as default:', this.doctors[0]);
      }
    }
    
    if (this.$route.params.id) {
      await this.loadConsultation();
    } else {
      // Default the current date for MC
      this.consultation.mcStartDate = getTodayDate();
      this.consultation.mcEndDate = getTodayDate();
    }
  },
  
  mounted() {
    // Initialize modals - DOM manipulation should happen in mounted, not created
    const prescriptionModalEl = document.getElementById('prescriptionModal');
    if (prescriptionModalEl) { 
      this.prescriptionModal = new Modal(prescriptionModalEl);
    }
    
    // Initialize the MC preview modal as well
    const mcPreviewModalEl = document.getElementById('mcPreviewModal');
    if (mcPreviewModalEl) {
      this.mcPreviewModal = new Modal(mcPreviewModalEl);
    }

    // Initialize patient details if needed
    if (this.consultation.patientId) {
      this.fetchPatientDetails();
    }

    // Initialize visit details modal
    this.visitDetailsModal = new bootstrap.Modal(document.getElementById('visitDetailsModal'));
  }
}
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

/* Enhanced Button Styling */
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
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}
.btn-outline-secondary {
  background: #fff;
  color: #6c757d;
  border: 2px solid #6c757d;
}
.btn-outline-secondary:hover {
  background: #6c757d;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(108,117,125,0.3);
}
.btn i {
  margin-right: 0.5rem;
}

@media (max-width: 991px) {
  .col-lg-7, .col-lg-5 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
@media (max-width: 768px) {
  .consultation-form {
    padding: 1rem 0;
  }
  .card-body {
    padding: 1rem;
  }
  .btn {
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
  }
}

.form-floating > .form-control,
.form-floating > .form-select {
  height: calc(3.5rem + 2px);
  line-height: 1.25;
}

.form-floating > textarea.form-control {
  height: 100px;
}

.form-floating > label {
  padding: 1rem 0.75rem;
}

.card {
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.patient-details {
  font-size: 0.95rem;
  padding: 2rem !important;
  min-width: 0;
  box-sizing: border-box;
  max-width: 700px;
  margin: 0 auto;
  background: #f8f9fa;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.patient-details .fa-user-circle {
  min-width: 56px;
  min-height: 56px;
  margin-right: 1.5rem;
}
@media (max-width: 768px) {
  .patient-details {
    flex-direction: column !important;
    padding: 1rem !important;
    max-width: 100%;
  }
  .patient-details .fa-user-circle {
    margin: 0 auto 1rem auto;
    display: block;
  }
}



/* Visit Details Modal Styling */
.visit-details-modal {
  border-radius: 16px;
  border: none;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}
.visit-details-modal .modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #dee2e6;
  border-radius: 16px 16px 0 0;
  padding: 1.5rem;
}
.visit-details-modal .modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #222;
}
.visit-details-modal .modal-body {
  padding: 2rem;
}
.visit-info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
.info-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.info-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: #222;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f8f9fa;
}
.info-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.info-item.full-width {
  grid-column: 1 / -1;
}
.info-item label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.info-item span {
  font-size: 1rem;
  color: #222;
  word-wrap: break-word;
}
.medications-table {
  grid-column: 1 / -1;
}
.medications-table .table {
  margin-bottom: 0;
  font-size: 0.9rem;
}
.medications-table .table th {
  background: #f8f9fa;
  font-weight: 600;
  border-top: none;
  border-bottom: 2px solid #dee2e6;
}
@media (max-width: 768px) {
  .visit-details-modal .modal-body {
    padding: 1rem;
  }
  .info-content {
    grid-template-columns: 1fr;
  }
  .info-card {
    padding: 1rem;
  }
}
</style>
