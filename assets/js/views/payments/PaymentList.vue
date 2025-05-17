<template>
  <div class="payment-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Payment Management</h2>
      <div class="d-flex gap-2">
        <div class="btn-group">
          <button class="btn btn-outline-secondary" 
                  :class="{ active: filterStatus === 'all' }"
                  @click="filterStatus = 'all'">
            All
          </button>
          <button class="btn btn-outline-secondary" 
                  :class="{ active: filterStatus === 'pending' }"
                  @click="filterStatus = 'pending'">
            Pending
          </button>
          <button class="btn btn-outline-secondary" 
                  :class="{ active: filterStatus === 'paid' }"
                  @click="filterStatus = 'paid'">
            Paid
          </button>
        </div>
        <input type="date" 
               class="form-control" 
               v-model="filterDate" 
               :max="today">
      </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="consultation in filteredConsultations" :key="consultation.id">
                <td>{{ formatDate(consultation.createdAt) }}</td>
                <td>{{ consultation.patientName }}</td>
                <td>{{ consultation.doctorName }}</td>
                <td>${{ consultation.totalAmount }}</td>
                <td>
                  <span :class="getStatusBadgeClass(consultation)">
                    {{ consultation.isPaid ? 'Paid' : 'Pending' }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-primary" 
                          @click="openPaymentForm(consultation)">
                    {{ consultation.isPaid ? 'View Details' : 'Process Payment' }}
                  </button>
                </td>
              </tr>
              <tr v-if="filteredConsultations.length === 0">
                <td colspan="6" class="text-center py-4">
                  No consultations found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payment Form Modal -->
    <div class="modal fade" id="paymentFormModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Payment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <payment-form 
              v-if="selectedConsultationId" 
              :consultation-id="selectedConsultationId"
              @payment-processed="onPaymentProcessed" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';
import PaymentForm from './PaymentForm.vue';
import { formatDate } from '../../utils/dateUtils';

export default {
  name: 'PaymentList',
  components: {
    PaymentForm
  },
  data() {
    return {
      consultations: [],
      filterStatus: 'all',
      filterDate: '',
      selectedConsultationId: null,
      paymentModal: null,
      today: new Date().toISOString().split('T')[0]
    };
  },
  computed: {
    filteredConsultations() {
      return this.consultations.filter(consultation => {
        const matchesStatus = this.filterStatus === 'all' ||
          (this.filterStatus === 'paid' && consultation.isPaid) ||
          (this.filterStatus === 'pending' && !consultation.isPaid);

        const matchesDate = !this.filterDate ||
          consultation.createdAt.startsWith(this.filterDate);

        return matchesStatus && matchesDate;
      });
    }
  },
  methods: {
    formatDate,
    async loadConsultations() {
      try {
        const response = await axios.get('/api/consultations');
        this.consultations = response.data.map(consultation => ({
          ...consultation,
          totalAmount: (parseFloat(consultation.consultationFee || 0) +
                       parseFloat(consultation.medicinesFee || 0)).toFixed(2)
        }));
      } catch (error) {
        console.error('Error loading consultations:', error);
        // Handle error appropriately
      }
    },
    getStatusBadgeClass(consultation) {
      return {
        'badge': true,
        'bg-success': consultation.isPaid,
        'bg-warning': !consultation.isPaid
      };
    },
    openPaymentForm(consultation) {
      this.selectedConsultationId = consultation.id;
      this.paymentModal.show();
    },
    onPaymentProcessed() {
      this.loadConsultations();
      this.paymentModal.hide();
    }
  },
  mounted() {
    this.paymentModal = new Modal(document.getElementById('paymentFormModal'));
    this.loadConsultations();
  }
};
</script>

<style scoped>
.payment-list {
  padding: 20px;
}

.badge {
  padding: 6px 12px;
  border-radius: 20px;
}
</style>
