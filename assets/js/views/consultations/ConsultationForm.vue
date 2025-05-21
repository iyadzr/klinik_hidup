<template>
  <div class="consultation-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">{{ isEditing ? 'Edit' : 'New' }} Consultation</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" @click="generateMedicalCertificate" v-if="isEditing">
          <i class="fas fa-file-medical me-2"></i>
          Generate Medical Certificate
        </button>
        <button class="btn btn-outline-primary" @click="showPrescriptionForm = true" v-if="isEditing">
          <i class="fas fa-prescription me-2"></i>
          Generate Prescription
        </button>
      </div>
    </div>

    <form @submit.prevent="saveConsultation" class="row g-4">
      <!-- Patient Information -->
      <div class="col-md-6">
        <div class="glass-card shadow h-100">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2 mb-4">
              <i class="fas fa-user-injured text-primary me-2"></i>
              Patient Information
            </h5>
            <div class="row g-3">
              <div class="col-12">
                <div class="form-floating">
                  <select class="form-select" id="patientId" v-model="consultation.patientId" @change="fetchPatientDetails" required>
                    <option value="">Select Patient</option>
                    <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                      {{ patient.name || patient.displayName || 'Unknown' }}
                    </option>
                  </select>
                  <label for="patientId">Patient</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-floating mt-2">
                  <select class="form-select" id="doctorId" v-model="consultation.doctorId" required>
                    <option value="">Select Doctor</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                      {{ doctor.name || doctor.displayName || ((doctor.firstName ? (doctor.firstName + ' ' + (doctor.lastName || '')) : doctor.lastName || 'Unknown')) + (doctor.specialization ? ' (' + doctor.specialization + ')' : '') }}
                    </option>
                  </select>
                  <label for="doctorId">Doctor</label>
                </div>
              </div>
              <div class="col-12" v-if="selectedPatient">
                <div class="patient-details p-3 bg-light rounded">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <small class="text-muted d-block">Age</small>
                      <span>{{ calculateAge(selectedPatient.dateOfBirth) }} years</span>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted d-block">Contact</small>
                      <span>{{ selectedPatient.phone }}</span>
                    </div>
                    <div class="col-12">
                      <small class="text-muted d-block">Medical History</small>
                      <p class="mb-0">{{ selectedPatient.medicalHistory || 'No medical history recorded' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vital Signs -->
      <div class="col-md-6">
        <div class="glass-card shadow h-100">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2 mb-4">
              <i class="fas fa-heartbeat text-primary me-2"></i>
              Vital Signs
            </h5>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="number" class="form-control" id="temperature" v-model="consultation.temperature" step="0.1" required>
                  <label for="temperature">Temperature (°C)</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="bloodPressure" v-model="consultation.bloodPressure" required>
                  <label for="bloodPressure">Blood Pressure</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="number" class="form-control" id="heartRate" v-model="consultation.heartRate" required>
                  <label for="heartRate">Heart Rate (bpm)</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="number" class="form-control" id="weight" v-model="consultation.weight" step="0.1" required>
                  <label for="weight">Weight (kg)</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Consultation Details -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2 mb-4">
              <i class="fas fa-stethoscope text-primary me-2"></i>
              Consultation Details
            </h5>
            <div class="row g-4">
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="symptoms" v-model="consultation.symptoms" style="height: 100px" required></textarea>
                  <label for="symptoms">Symptoms</label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="diagnosis" v-model="consultation.diagnosis" style="height: 100px" required></textarea>
                  <label for="diagnosis">Diagnosis</label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="treatment" v-model="consultation.treatment" style="height: 100px" required></textarea>
                  <label for="treatment">Treatment Plan</label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="medications" v-model="consultation.medications" style="height: 100px" required></textarea>
                  <label for="medications">Prescribed Medications</label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="notes" v-model="consultation.notes" style="height: 100px"></textarea>
                  <label for="notes">Additional Notes</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="card-title text-gold d-flex align-items-center gap-2 mb-4">
              <i class="fas fa-money-bill text-primary me-2"></i>
              Payment Information
            </h5>
            <div class="row g-4">
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="number" class="form-control" id="consultationFee" v-model="consultation.consultationFee" step="0.01" min="0" required>
                  <label for="consultationFee">Consultation Fee ($)</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="number" class="form-control" id="medicinesFee" v-model="consultation.medicinesFee" step="0.01" min="0" required>
                  <label for="medicinesFee">Medicines Fee ($)</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="text" class="form-control bg-light" id="totalAmount" :value="formatCurrency(totalAmount)" readonly>
                  <label for="totalAmount">Total Amount ($)</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" @click="$router.back()">
            <i class="fas fa-times me-2"></i>
            Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>
            {{ isEditing ? 'Update' : 'Save' }} Consultation
          </button>
        </div>
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
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';
import MedicalCertificateForm from '../certificates/MedicalCertificateForm.vue';
import PrescriptionForm from '../prescriptions/PrescriptionForm.vue';

export default {
  name: 'ConsultationForm',
  components: {
    MedicalCertificateForm,
    PrescriptionForm
  },
  data() {
    return {
      consultation: {
        patientId: '',
        doctorId: '',
        temperature: '',
        bloodPressure: '',
        heartRate: '',
        weight: '',
        symptoms: '',
        diagnosis: '',
        treatment: '',
        medications: '',
        notes: '',
        consultationFee: 0,
        medicinesFee: 0
      },
      patients: [],
      doctors: [],
      fullPatientDetails: null,
      medicalCertificateModal: null,
      prescriptionModal: null,
      isLoading: false,
      showPrescriptionForm: false
    };
  },
  computed: {
    isEditing() {
      return !!this.$route.params.id;
    },
    selectedPatient() {
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      return this.patients.find(p => p.id === this.consultation.patientId) || null;
    },
    totalAmount() {
      return (parseFloat(this.consultation.consultationFee || 0) + 
              parseFloat(this.consultation.medicinesFee || 0));
    }
  },
  methods: {
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
        return;
      }
      try {
        const response = await axios.get(`/api/patients/${this.consultation.patientId}`);
        this.fullPatientDetails = response.data;
      } catch (error) {
        console.error('Error fetching patient details:', error);
        this.fullPatientDetails = null;
      }
    },
    async loadConsultation() {
      if (!this.isEditing) return;
      
      try {
        const response = await axios.get(`/api/consultations/${this.$route.params.id}`);
        this.consultation = response.data;
      } catch (error) {
        console.error('Error loading consultation:', error);
      }
    },
    async saveConsultation() {
      try {
        this.isLoading = true;
        if (this.isEditing) {
          await axios.put(`/api/consultations/${this.$route.params.id}`, this.consultation);
        } else {
          await axios.post('/api/consultations', this.consultation);
          try {
            await axios.post('/api/queue', {
              patientId: this.consultation.patientId,
              doctorId: this.consultation.doctorId
            });
          } catch (queueError) {
            console.error('Failed to add patient to queue:', queueError);
            this.$toast && this.$toast.error ? this.$toast.error('Consultation saved but failed to add to queue.') : alert('Consultation saved but failed to add to queue.');
          }
        }
        // After saving consultation, mark the queue entry as completed
        try {
          // Fetch the queue entry for this patient and doctor with status 'in_consultation' or 'waiting'
          const queueRes = await axios.get('/api/queue', {
            params: {
              patientId: this.consultation.patientId,
              doctorId: this.consultation.doctorId,
              status: 'in_consultation'
            }
          });
          const queueEntry = Array.isArray(queueRes.data) ? queueRes.data[0] : null;
          if (queueEntry && queueEntry.id) {
            await axios.put(`/api/queue/${queueEntry.id}/status`, { status: 'completed' });
          }
        } catch (queueCompleteError) {
          console.error('Failed to update queue status to completed:', queueCompleteError);
          // Optionally, show an error toast/alert here
        }
        this.$router.push('/consultations');
      } catch (error) {
        console.error('Error saving consultation:', error);
      } finally {
        this.isLoading = false;
      }
    },
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
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(value);
    },
    generateMedicalCertificate() {
      this.medicalCertificateModal.show();
    },
    generatePrescription() {
      this.showPrescriptionForm = true;
      const modal = new bootstrap.Modal(this.$refs.prescriptionModal);
      modal.show();
    },
    handleCertificateGenerated() {
      this.medicalCertificateModal.hide();
    },
    onPrescriptionGenerated(prescriptionData) {
      // Here you would typically make an API call to save the prescription
      console.log('Prescription generated:', prescriptionData);
      this.showPrescriptionForm = false;
      this.$refs.prescriptionModal.hide();
      this.$toast.success('Prescription generated successfully');
    }
  },
  async mounted() {
    await this.loadPatients();
    await this.loadDoctors();
    await this.loadConsultation();
    this.medicalCertificateModal = new Modal(document.getElementById('medicalCertificateModal'));
    this.prescriptionModal = new Modal(document.getElementById('prescriptionModal'));
    if (this.consultation.patientId) {
      await this.fetchPatientDetails();
    }
  }
};
</script>

<style scoped>
.consultation-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
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
  font-size: 0.9rem;
}

.btn {
  padding: 0.5rem 1rem;
  font-weight: 500;
}

.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

.btn-primary:hover {
  background-color: var(--secondary-color);
  border-color: var(--secondary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
}

.modal-dialog {
  max-width: 800px;
}

@media (max-width: 768px) {
  .consultation-form {
    padding: 1rem;
  }
}
</style>
