<template>
  <div class="payment-form">
    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Payment Processing</h4>
      </div>
      <div class="card-body">
        <div class="mb-4">
          <h5>Consultation Details</h5>
          <div class="row">
            <div class="col-md-6">
              <p><strong>Patient:</strong> {{ consultation.patientName }}</p>
              <p><strong>Doctor:</strong> {{ consultation.doctorName }}</p>
              <p><strong>Date:</strong> {{ formatDate(consultation.createdAt) }}</p>
            </div>
            <div class="col-md-6">
              <p><strong>Consultation Fee:</strong> RM {{ consultation.consultationFee }}</p>
              <p><strong>Medicines Fee:</strong> RM {{ consultation.medicinesFee }}</p>
              <p><strong>Total Amount:</strong> RM {{ consultation.totalAmount }}</p>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <h5>Payment Status</h5>
          <div class="form-check">
            <input class="form-check-input" 
                   type="checkbox" 
                   v-model="consultation.isPaid" 
                   :disabled="consultation.isPaid"
                   @change="markAsPaid">
            <label class="form-check-label">
              Mark as Paid
            </label>
          </div>
          <div v-if="consultation.isPaid" class="alert alert-success mt-2">
            <i class="fas fa-check-circle me-2"></i>
            Payment received on {{ formatDate(consultation.paidAt) }}
          </div>
        </div>

        <div class="mb-4">
          <h5>Prescription and Treatment</h5>
          <div class="alert alert-info">
            <p><strong>Diagnosis:</strong> {{ consultation.diagnosis }}</p>
            <p><strong>Treatment:</strong> {{ consultation.treatment }}</p>
            <p v-if="consultation.prescription"><strong>Prescription:</strong> {{ consultation.prescription }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { formatDate } from '../../utils/dateUtils';

export default {
  name: 'PaymentForm',
  props: {
    consultationId: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      consultation: {
        patientName: '',
        doctorName: '',
        createdAt: null,
        consultationFee: 0,
        medicinesFee: 0,
        totalAmount: 0,
        isPaid: false,
        paidAt: null,
        diagnosis: '',
        treatment: '',
        prescription: ''
      }
    };
  },
  methods: {
    formatDate,
    async loadConsultation() {
      try {
        const response = await axios.get(`/api/consultations/${this.consultationId}`);
        this.consultation = {
          ...response.data,
          totalAmount: parseFloat(response.data.consultationFee || 0) + 
                      parseFloat(response.data.medicinesFee || 0)
        };
      } catch (error) {
        console.error('Error loading consultation:', error);
        // Handle error appropriately
      }
    },
    async markAsPaid() {
      try {
        await axios.post(`/api/consultations/${this.consultationId}/payment`);
        this.consultation.isPaid = true;
        this.consultation.paidAt = new Date();
      } catch (error) {
        console.error('Error processing payment:', error);
        this.consultation.isPaid = false;
        // Handle error appropriately
      }
    }
  },
  created() {
    this.loadConsultation();
  }
};
</script>

<style scoped>
.payment-form {
  max-width: 800px;
  margin: 0 auto;
}
</style>
