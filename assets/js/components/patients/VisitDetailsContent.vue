<template>
  <div class="row g-4" v-if="visit">
    <!-- Visit Information -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Visit Information</h6>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <strong>Date & Time:</strong><br>
            {{ formatDate(visit.consultationDate) }} at {{ formatTime(visit.consultationDate) }}
          </div>
          <div class="mb-2">
            <strong>Doctor:</strong><br>
            Dr. {{ visit.doctor?.name || 'Unknown' }}
          </div>
          <div class="mb-2">
            <strong>Status:</strong><br>
            <span :class="getStatusBadgeClass(visit.status)">
              {{ visit.status || 'Completed' }}
            </span>
          </div>
          <div v-if="visit.queueNumber">
            <strong>Queue Number:</strong><br>
            {{ visit.queueNumber }}
          </div>
        </div>
      </div>
    </div>

    <!-- Medical Information -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Medical Information</h6>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <strong>Remarks/Diagnosis:</strong><br>
            <div v-if="visit.notes" class="bg-light p-2 rounded">
              {{ visit.notes }}
            </div>
            <span v-else class="text-muted">No remarks/diagnosis recorded</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Medications -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="fas fa-pills me-2"></i>Prescribed Medications
            <span class="badge bg-primary ms-2">{{ (visit.medications && visit.medications.length > 0) ? visit.medications.length : 0 }}</span>
          </h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Medication</th>
                  <th>Quantity</th>
                  <th>Price (RM)</th>
                  <th>Instructions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!visit.medications || visit.medications.length === 0">
                  <td><strong>No medications prescribed</strong></td>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                <tr v-else v-for="(med, index) in visit.medications" :key="index">
                  <td>
                    <strong>{{ med.name }}</strong>
                    <small v-if="med.category" class="text-muted d-block">({{ med.category }})</small>
                  </td>
                  <td>{{ med.quantity }} {{ med.unitType || 'units' }}</td>
                  <td>{{ med.actualPrice ? parseFloat(med.actualPrice).toFixed(2) : 'N/A' }}</td>
                  <td>{{ med.instructions || 'No instructions' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial Information -->
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-success text-white">
          <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Financial Information</h6>
        </div>
        <div class="card-body text-center">
          <div class="fw-bold fs-4">
            <strong>Total Amount:</strong><br>
            <span class="text-success">RM {{ visit.totalAmount ? parseFloat(visit.totalAmount).toFixed(2) : '0.00' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Medical Certificate -->
    <div class="col-12" v-if="visit.mcStartDate || visit.mcEndDate || visit.hasMedicalCertificate">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-certificate me-2"></i>Medical Certificate</h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-12 mb-3">
              <strong>MC Status:</strong>
              <span v-if="visit.mcStartDate && visit.mcEndDate" class="badge bg-success ms-2">
                <i class="fas fa-check me-1"></i>MC Issued
              </span>
              <span v-else-if="visit.hasMedicalCertificate" class="badge bg-warning ms-2">
                <i class="fas fa-clock me-1"></i>MC Required
              </span>
              <span v-else class="badge bg-secondary ms-2">
                <i class="fas fa-times me-1"></i>No MC
              </span>
            </div>
            <div class="col-md-6" v-if="visit.mcStartDate">
              <strong>Start Date:</strong> {{ formatDate(visit.mcStartDate) }}
            </div>
            <div class="col-md-6" v-if="visit.mcEndDate">
              <strong>End Date:</strong> {{ formatDate(visit.mcEndDate) }}
            </div>
            <div class="col-md-6" v-if="visit.mcStartDate && visit.mcEndDate">
              <strong>Duration:</strong> {{ calculateMCDays(visit.mcStartDate, visit.mcEndDate) }} day(s)
            </div>
            <div class="col-md-6" v-if="visit.mcRunningNumber">
              <strong>MC Number:</strong> {{ visit.mcRunningNumber }}
            </div>
            <div class="col-12" v-if="visit.mcNotes">
              <strong>MC Notes:</strong><br>
              <div class="bg-light p-2 rounded">{{ visit.mcNotes }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateOnlyMalaysia, formatTimeOnlyMalaysia } from '../../utils/timezoneUtils.js';

export default {
  name: 'VisitDetailsContent',
  props: {
    visit: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return '';
      return formatDateOnlyMalaysia(date);
    },
    formatTime(date) {
      if (!date) return '';
      return formatTimeOnlyMalaysia(date);
    },
    getStatusBadgeClass(status) {
      const statusLower = (status || '').toLowerCase();
      switch (statusLower) {
        case 'completed':
          return 'badge bg-success';
        case 'pending':
          return 'badge bg-warning';
        case 'in_progress':
          return 'badge bg-info';
        case 'cancelled':
          return 'badge bg-danger';
        default:
          return 'badge bg-secondary';
      }
    },
    calculateMCDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      
      const start = new Date(startDate);
      const end = new Date(endDate);
      const timeDiff = end.getTime() - start.getTime();
      const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Include both start and end days
      
      return Math.max(1, dayDiff);
    }
  }
};
</script>

<style scoped>
.card-header h6 {
  font-weight: 600;
}

.badge {
  font-size: 0.75rem;
}

.bg-light {
  background-color: #f8f9fa !important;
}

.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
</style> 