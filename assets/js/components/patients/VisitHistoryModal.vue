<template>
  <!-- Visit History Modal -->
  <div class="modal" :class="{ show: visible }" v-if="visible" @click.self="close">
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
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="6" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading visit history...</div>
                  </td>
                </tr>
                <tr v-else-if="visitHistories.length === 0">
                  <td colspan="6" class="text-center py-4">
                    <i class="fas fa-file-medical-alt fa-2x text-muted mb-2"></i>
                    <div>No visit history found</div>
                    <small class="text-muted">This patient has no recorded visits</small>
                  </td>
                </tr>
                <tr v-else v-for="visit in visitHistories" :key="visit.id" class="clickable-row" @click="$emit('view-visit-details', visit)" title="Click to view details">
                  <td>{{ formatDate(visit.consultationDate) }}</td>
                  <td>Dr. {{ visit.doctor?.name || 'Unknown' }}</td>
                  <td>{{ visit.diagnosis || 'No diagnosis recorded' }}</td>
                  <td>
                    <div v-if="(visit.prescribedMedications && visit.prescribedMedications.length > 0) || (visit.medications && visit.medications.length > 0)">
                      <!-- Show prescribed medications first if available -->
                      <div v-if="visit.prescribedMedications && visit.prescribedMedications.length > 0">
                        <div v-for="(medicine, index) in visit.prescribedMedications.slice(0, 2)" :key="'prescribed-' + index">
                          <strong>{{ medicine.medication?.name || medicine.name || 'Unknown Medicine' }}</strong>
                          <span v-if="medicine.quantity" class="text-muted">({{ medicine.quantity }})</span>
                        </div>
                        <small v-if="visit.prescribedMedications.length > 2" class="text-muted">
                          +{{ visit.prescribedMedications.length - 2 }} more...
                        </small>
                      </div>
                      <!-- Fallback to legacy medications format -->
                      <div v-else-if="visit.medications && visit.medications.length > 0">
                        <div v-for="(medicine, index) in getMedicinesList(visit.medications).slice(0, 2)" :key="'legacy-' + index">
                          <strong>{{ medicine.name || medicine.medicationName || 'Unknown Medicine' }}</strong>
                          <span v-if="medicine.quantity" class="text-muted">({{ medicine.quantity }})</span>
                        </div>
                        <small v-if="getMedicinesList(visit.medications).length > 2" class="text-muted">
                          +{{ getMedicinesList(visit.medications).length - 2 }} more...
                        </small>
                      </div>
                    </div>
                    <span v-else class="text-muted">No medicines</span>
                  </td>
                  <td>
                    <span v-if="visit.mcRequired" class="badge bg-warning text-dark">
                      <i class="fas fa-certificate me-1"></i>MC Required
                    </span>
                    <span v-else class="badge bg-secondary">
                      <i class="fas fa-times me-1"></i>No MC
                    </span>
                  </td>
                  <td>
                    <div v-if="visit.totalAmount && visit.totalAmount > 0">
                      <strong>RM {{ formatCurrency(visit.totalAmount) }}</strong>
                      <br>
                      <small :class="visit.isPaid ? 'text-success' : 'text-danger'">
                        <i :class="visit.isPaid ? 'fas fa-check' : 'fas fa-exclamation-triangle'" class="me-1"></i>
                        {{ visit.isPaid ? 'Paid' : 'Unpaid' }}
                      </small>
                    </div>
                    <div v-else class="text-muted">
                      <small>No payment required</small>
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
        
        // Filter out empty entries and ensure we have valid medicine data
        return medicineList.filter(med => {
          if (!med) return false;
          return med.name || med.medicationName || med.medication;
        });
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
  z-index: 1200 !important; /* Higher than sticky patient header (1100) */
  background-color: rgba(0, 0, 0, 0.5); /* Add backdrop dimming to modal itself */
}

.clickable-row {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.clickable-row:hover {
  background-color: #f8f9fa !important;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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