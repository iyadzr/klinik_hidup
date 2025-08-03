<template>
  <div class="modal fade" id="visitDetailsModal" tabindex="-1" aria-labelledby="visitDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="visitDetailsModalLabel">
            <i class="fas fa-file-medical-alt me-2"></i>
            Visit Details
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" v-if="visit">
          <!-- Visit Information -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="info-item">
                <label class="fw-bold text-muted small">CONSULTATION DATE</label>
                <div class="fw-bold">{{ formatDate(visit.consultationDate) }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-item">
                <label class="fw-bold text-muted small">DOCTOR</label>
                <div class="fw-bold">Dr. {{ visit.doctor?.name || 'Unknown' }}</div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-6">
              <div class="info-item">
                <label class="fw-bold text-muted small">STATUS</label>
                <div>
                  <span :class="getStatusClass(visit.status)">
                    {{ visit.status || 'Completed' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Diagnosis/Notes -->
          <div class="mb-4">
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Diagnosis & Notes</h6>
              </div>
              <div class="card-body">
                <p class="mb-0">{{ visit.diagnosis || visit.notes || 'No diagnosis or notes recorded' }}</p>
              </div>
            </div>
          </div>

          <!-- Prescribed Medications -->
          <div class="mb-4">
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="fas fa-pills me-2"></i>
                  Prescribed Medications
                  <span class="badge bg-primary ms-2">{{ (visit.prescribedMedications && visit.prescribedMedications.length > 0) ? visit.prescribedMedications.length : 0 }}</span>
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Medication</th>
                        <th>Quantity</th>
                        <th>Instructions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!visit.prescribedMedications || visit.prescribedMedications.length === 0">
                        <td><strong>No medications prescribed</strong></td>
                        <td>-</td>
                        <td>-</td>
                      </tr>
                      <tr v-else v-for="med in visit.prescribedMedications" :key="med.id">
                        <td>
                          <div class="fw-bold">{{ med.medication?.name || med.name }}</div>
                          <small class="text-muted">{{ med.medication?.category || med.category }}</small>
                          <div v-if="med.dosage || med.frequency || med.duration" class="mt-1">
                            <small class="badge bg-light text-dark me-1" v-if="med.dosage">{{ med.dosage }}</small>
                            <small class="badge bg-light text-dark me-1" v-if="med.frequency">{{ med.frequency }}</small>
                            <small class="badge bg-light text-dark" v-if="med.duration">{{ med.duration }}</small>
                          </div>
                        </td>
                        <td>{{ med.quantity }} {{ med.medication?.unitType || med.unitType || 'pcs' }}</td>
                        <td>
                          <div>{{ med.instructions || 'No instructions' }}</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Medical Certificate -->
          <div class="mb-4" v-if="visit.mcStartDate || visit.mcEndDate">
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-certificate me-2"></i>Medical Certificate</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6" v-if="visit.mcStartDate">
                    <label class="fw-bold text-muted small">START DATE</label>
                    <div>{{ formatDate(visit.mcStartDate) }}</div>
                  </div>
                  <div class="col-md-6" v-if="visit.mcEndDate">
                    <label class="fw-bold text-muted small">END DATE</label>
                    <div>{{ formatDate(visit.mcEndDate) }}</div>
                  </div>
                </div>
                <div v-if="visit.mcRunningNumber" class="mt-2">
                  <label class="fw-bold text-muted small">MC NUMBER</label>
                  <div class="fw-bold">{{ visit.mcRunningNumber }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Information -->
          <div v-if="visit.totalAmount || visit.payments">
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Information</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <label class="fw-bold text-muted small">TOTAL AMOUNT</label>
                    <div class="fw-bold text-success fs-5">RM {{ parseFloat(visit.totalAmount || 0).toFixed(2) }}</div>
                  </div>
                  <div class="col-md-6" v-if="visit.payments && visit.payments.length > 0">
                    <label class="fw-bold text-muted small">PAYMENT STATUS</label>
                    <div>
                      <span class="badge bg-success">Paid</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateOnlyMalaysia } from '../../utils/timezoneUtils.js';

export default {
  name: 'VisitDetailsModal',
  props: {
    visit: {
      type: Object,
      default: null
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        return formatDateOnlyMalaysia(dateString);
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    getStatusClass(status) {
      const statusMap = {
        'Completed': 'badge bg-success',
        'In Progress': 'badge bg-warning',
        'Cancelled': 'badge bg-danger',
        'Pending': 'badge bg-info'
      };
      return statusMap[status] || 'badge bg-secondary';
    }
  }
};
</script>

<style scoped>
.info-item {
  margin-bottom: 1rem;
}

.info-item label {
  display: block;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
}

.card-header {
  border-bottom: 1px solid #e9ecef;
  padding: 0.75rem 1rem;
}

.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.875rem;
}

.table td {
  vertical-align: middle;
  padding: 0.75rem 1rem;
}

.badge {
  font-size: 0.75rem;
  padding: 0.5rem 0.75rem;
}

.modal-lg {
  max-width: 900px;
}

@media (max-width: 768px) {
  .modal-lg {
    max-width: 95%;
    margin: 1rem auto;
  }
  
  .info-item {
    margin-bottom: 0.75rem;
  }
}
</style> 