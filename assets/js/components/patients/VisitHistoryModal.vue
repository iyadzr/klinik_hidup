<template>
  <!-- Visit History Modal -->
  <div class="modal fade" :class="{ show: visible }" tabindex="-1" v-if="visible">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="fas fa-history me-2"></i>
            Visit History - {{ patient?.name }}
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="close"></button>
        </div>
        <div class="modal-body">
          <!-- Patient Summary -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card bg-light">
                <div class="card-body">
                  <div class="row" v-if="patient">
                    <div class="col-md-3">
                      <strong>Name:</strong><br>
                      {{ patient.name }}
                    </div>
                    <div class="col-md-3">
                      <strong>NRIC:</strong><br>
                      {{ formatDisplayNRIC(patient.nric) || 'N/A' }}
                    </div>
                    <div class="col-md-3">
                      <strong>Phone:</strong><br>
                      {{ patient.phone || 'N/A' }}
                    </div>
                    <div class="col-md-3">
                      <strong>Date of Birth:</strong><br>
                      {{ formatDateOfBirth(patient.dateOfBirth) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Visit History Table -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Doctor</th>
                  <th>Diagnosis</th>
                  <th>Medicines</th>
                  <th>MC Required</th>
                  <th>Payment</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading visit history...</div>
                  </td>
                </tr>
                <tr v-else-if="visitHistories.length === 0">
                  <td colspan="7" class="text-center py-4">
                    <i class="fas fa-file-medical-alt fa-2x text-muted mb-2"></i>
                    <div>No visit history found</div>
                    <small class="text-muted">This patient has no recorded visits</small>
                  </td>
                </tr>
                <tr v-else v-for="visit in visitHistories" :key="visit.id">
                  <td>{{ formatDate(visit.consultationDate) }}</td>
                  <td>Dr. {{ visit.doctor?.name || 'Unknown' }}</td>
                  <td>{{ visit.diagnosis || 'No diagnosis recorded' }}</td>
                  <td>
                    <div v-if="visit.medicines && visit.medicines.length > 0">
                      <div v-for="(medicine, index) in getMedicinesList(visit.medicines).slice(0, 2)" :key="index">
                        <strong>{{ medicine.name }}</strong>
                        <span v-if="medicine.quantity" class="text-muted">({{ medicine.quantity }})</span>
                      </div>
                      <small v-if="getMedicinesList(visit.medicines).length > 2" class="text-muted">
                        +{{ getMedicinesList(visit.medicines).length - 2 }} more...
                      </small>
                    </div>
                    <span v-else class="text-muted">No medicines</span>
                  </td>
                  <td>
                    <span v-if="visit.mcRequired" class="badge bg-warning text-dark">
                      <i class="fas fa-clock me-1"></i>MC Required
                    </span>
                    <span v-else class="badge bg-secondary">
                      <i class="fas fa-times me-1"></i>No MC
                    </span>
                  </td>
                  <td>
                    <div v-if="visit.totalAmount">
                      <strong>RM {{ formatCurrency(visit.totalAmount) }}</strong>
                      <br>
                      <small :class="visit.isPaid ? 'text-success' : 'text-danger'">
                        {{ visit.isPaid ? 'Paid' : 'Unpaid' }}
                      </small>
                    </div>
                    <span v-else class="text-muted">No payment info</span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-outline-primary" @click="$emit('view-visit-details', visit)" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button v-if="visit.isPaid && visit.receiptNumber" class="btn btn-sm btn-outline-success" @click="$emit('view-receipt', visit)" title="View Receipt">
                        <i class="fas fa-receipt"></i>
                      </button>
                      <button v-if="visit.mcRequired && visit.mcRunningNumber" class="btn btn-sm btn-outline-warning" @click="$emit('view-medical-certificate', visit)" title="View MC">
                        <i class="fas fa-file-medical-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close">Close</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-if="visible"></div>
</template>

<script>
import { formatNRIC } from '../../utils/nricFormatter.js';
import timezoneUtils from '../../utils/timezoneUtils.js';

export default {
  name: 'VisitHistoryModal',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    patient: {
      type: Object,
      default: null
    },
    visitHistories: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'view-visit-details', 'view-receipt', 'view-medical-certificate'],
  methods: {
    close() {
      this.$emit('close');
    },
    formatDisplayNRIC(nric) {
      return formatNRIC(nric);
    },
    formatDateOfBirth(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const dateObj = new Date(dateOfBirth);
        return dateObj.toLocaleDateString('en-GB', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    formatDate(date) {
      if (!date) return '';
      return timezoneUtils.formatDateMalaysia(new Date(date));
    },
    formatCurrency(amount) {
      if (!amount) return '0.00';
      return parseFloat(amount).toFixed(2);
    },
    getMedicinesList(medicines) {
      if (!medicines) return [];
      
      try {
        // Handle different medicine data formats
        let medicineList = [];
        
        if (Array.isArray(medicines)) {
          medicineList = medicines;
        } else if (typeof medicines === 'string') {
          medicineList = JSON.parse(medicines);
        } else if (typeof medicines === 'object') {
          medicineList = [medicines];
        }
        
        return medicineList.filter(med => med && (med.name || med.medicationName));
      } catch (error) {
        console.error('Error parsing medicines:', error);
        return [];
      }
    }
  }
};
</script>

<style scoped>
.modal.show {
  display: block;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: #000;
  opacity: 0.5;
}

.btn-group .btn {
  border-radius: 0.25rem;
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}

.badge {
  font-size: 0.75rem;
}
</style> 