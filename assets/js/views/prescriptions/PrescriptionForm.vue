<template>
  <div class="prescription-form">
    <form @submit.prevent="generatePrescription">
      <!-- Clinic Information -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title d-flex align-items-center mb-4">
            <i class="fas fa-clinic-medical text-primary me-2"></i>
            Clinic Information
          </h5>
          <div class="row g-3">
            <div class="col-12">
              <div class="form-floating">
                <input type="text" class="form-control" id="clinicName" v-model="prescriptionData.clinicName" required>
                <label for="clinicName">Clinic Name</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <textarea class="form-control" id="clinicAddress" v-model="prescriptionData.clinicAddress" required></textarea>
                <label for="clinicAddress">Clinic Address</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="tel" class="form-control" id="clinicPhone" v-model="prescriptionData.clinicPhone" required>
                <label for="clinicPhone">Phone Number</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="email" class="form-control" id="clinicEmail" v-model="prescriptionData.clinicEmail" required>
                <label for="clinicEmail">Email</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Prescription Details -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title d-flex align-items-center mb-4">
            <i class="fas fa-prescription text-primary me-2"></i>
            Prescription Details
          </h5>
          
          <!-- Medications List -->
          <div class="medications-list mb-4">
            <div v-for="(medication, index) in prescriptionData.medications" :key="index" class="medication-item card bg-light mb-3">
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input type="text" class="form-control" :id="'medicationName' + index" v-model="medication.name" required>
                      <label :for="'medicationName' + index">Medication Name</label>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-floating">
                      <input type="text" class="form-control" :id="'dosage' + index" v-model="medication.dosage" required>
                      <label :for="'dosage' + index">Dosage</label>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-floating">
                      <input type="text" class="form-control" :id="'frequency' + index" v-model="medication.frequency" required>
                      <label :for="'frequency' + index">Frequency</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input type="text" class="form-control" :id="'duration' + index" v-model="medication.duration" required>
                      <label :for="'duration' + index">Duration</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input type="text" class="form-control" :id="'instructions' + index" v-model="medication.instructions">
                      <label :for="'instructions' + index">Special Instructions</label>
                    </div>
                  </div>
                  <div class="col-12 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeMedication(index)">
                      <i class="fas fa-trash me-2"></i>Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button type="button" class="btn btn-outline-primary w-100" @click="addMedication">
            <i class="fas fa-plus me-2"></i>Add Medication
          </button>
        </div>
      </div>

      <!-- Additional Instructions -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title d-flex align-items-center mb-4">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Additional Instructions
          </h5>
          <div class="form-floating">
            <textarea class="form-control" id="additionalInstructions" v-model="prescriptionData.additionalInstructions" style="height: 100px"></textarea>
            <label for="additionalInstructions">Additional Instructions or Notes</label>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title d-flex align-items-center mb-4">
            <i class="fas fa-eye text-primary me-2"></i>
            Preview
          </h5>
          <div class="prescription-preview bg-light p-4 rounded">
            <div class="text-center mb-4">
              <h4>{{ prescriptionData.clinicName }}</h4>
              <p class="mb-0">{{ prescriptionData.clinicAddress }}</p>
              <small>Tel: {{ prescriptionData.clinicPhone }} | Email: {{ prescriptionData.clinicEmail }}</small>
            </div>

            <div class="patient-info mb-4">
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Patient:</strong> {{ patient ? `${patient.firstName} ${patient.lastName}` : '' }}</p>
                  <p><strong>Age:</strong> {{ patient ? calculateAge(patient.dateOfBirth) : '' }} years</p>
                </div>
                <div class="col-md-6 text-md-end">
                  <p><strong>Date:</strong> {{ formatDate(new Date()) }}</p>
                  <p><strong>Prescription No:</strong> {{ generatePrescriptionNumber() }}</p>
                </div>
              </div>
            </div>

            <div class="medications-preview mb-4">
              <h6 class="border-bottom pb-2 mb-3">Prescribed Medications</h6>
              <div v-for="(medication, index) in prescriptionData.medications" :key="index" class="mb-3">
                <div class="d-flex align-items-start">
                  <span class="me-2">{{ index + 1 }}.</span>
                  <div>
                    <div class="fw-bold">{{ medication.name }}</div>
                    <div>{{ medication.dosage }} - {{ medication.frequency }}</div>
                    <div>Duration: {{ medication.duration }}</div>
                    <div v-if="medication.instructions" class="text-muted">
                      Note: {{ medication.instructions }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="prescriptionData.additionalInstructions" class="additional-instructions mb-4">
              <h6 class="border-bottom pb-2 mb-3">Additional Instructions</h6>
              <p class="mb-0">{{ prescriptionData.additionalInstructions }}</p>
            </div>

            <div class="doctor-signature text-end mt-5">
              <div class="mb-4">____________________</div>
              <div>Doctor's Signature</div>
              <div>{{ consultation.doctorName }}</div>
              <div>MCR No: {{ consultation.doctorMcr }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" @click="$emit('cancel')">
          <i class="fas fa-times me-2"></i>Cancel
        </button>
        <button type="submit" class="btn btn-primary" :disabled="!prescriptionData.medications.length">
          <i class="fas fa-file-download me-2"></i>Generate Prescription
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'PrescriptionForm',
  props: {
    consultation: {
      type: Object,
      required: true
    },
    patient: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      prescriptionData: {
        clinicName: localStorage.getItem('clinic_name') || '',
        clinicAddress: localStorage.getItem('clinic_address') || '',
        clinicPhone: localStorage.getItem('clinic_phone') || '',
        clinicEmail: localStorage.getItem('clinic_email') || '',
        medications: [],
        additionalInstructions: ''
      }
    };
  },
  methods: {
    addMedication() {
      this.prescriptionData.medications.push({
        name: '',
        dosage: '',
        frequency: '',
        duration: '',
        instructions: ''
      });
    },
    removeMedication(index) {
      this.prescriptionData.medications.splice(index, 1);
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
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    generatePrescriptionNumber() {
      return 'RX-' + new Date().getTime().toString().slice(-8);
    },
    async generatePrescription() {
      try {
        // Save clinic information for future use
        localStorage.setItem('clinic_name', this.prescriptionData.clinicName);
        localStorage.setItem('clinic_address', this.prescriptionData.clinicAddress);
        localStorage.setItem('clinic_phone', this.prescriptionData.clinicPhone);
        localStorage.setItem('clinic_email', this.prescriptionData.clinicEmail);

        // Here you would typically make an API call to save the prescription
        // and generate a PDF
        const prescriptionData = {
          ...this.prescriptionData,
          consultationId: this.consultation.id,
          patientId: this.patient.id,
          prescriptionNumber: this.generatePrescriptionNumber(),
          date: new Date()
        };

        // Emit the generated event with the prescription data
        this.$emit('generated', prescriptionData);
      } catch (error) {
        console.error('Error generating prescription:', error);
      }
    }
  },
  mounted() {
    // Pre-fill medications from consultation if available
    if (this.consultation.medications) {
      const medications = this.consultation.medications.split('\n').filter(med => med.trim());
      medications.forEach(medication => {
        this.prescriptionData.medications.push({
          name: medication,
          dosage: '',
          frequency: '',
          duration: '',
          instructions: ''
        });
      });
    }

    // If no medications were added, add an empty one
    if (!this.prescriptionData.medications.length) {
      this.addMedication();
    }
  }
};
</script>

<style scoped>
.prescription-form {
  max-width: 100%;
}

.form-floating > textarea.form-control {
  height: 100px;
}

.medication-item {
  transition: all 0.3s ease;
}

.medication-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
}

.prescription-preview {
  font-family: 'Times New Roman', Times, serif;
}

.prescription-preview h4 {
  color: var(--primary-color);
}

.prescription-preview .medications-preview {
  font-size: 0.95rem;
}

.doctor-signature {
  font-size: 0.9rem;
}

@media print {
  .prescription-preview {
    background-color: white !important;
    padding: 20px !important;
  }

  .btn,
  .card {
    display: none !important;
  }

  .prescription-preview {
    display: block !important;
  }
}
</style>
