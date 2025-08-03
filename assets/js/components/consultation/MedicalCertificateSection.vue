<template>
  <div class="card section-card mb-4">
    <div class="card-body">
      <h5 class="section-title mb-4">
        <i class="fas fa-file-medical text-primary me-2"></i>
        Medical Certificate
      </h5>
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="form-check">
          <input 
            class="form-check-input" 
            type="checkbox" 
            id="hasMedicalCertificate" 
            :checked="hasMedicalCertificate" 
            @change="onMCCheckboxChange"
          >
          <label class="form-check-label fw-bold" for="hasMedicalCertificate">
            Issue Medical Certificate (MC) for this visit
          </label>
        </div>
        <button 
          type="button" 
          class="btn btn-outline-info btn-sm" 
          @click="showMCPreview" 
          v-if="hasMedicalCertificate && selectedPatient" 
          :disabled="!mcStartDate || !mcEndDate"
        >
          <i class="fas fa-eye me-1"></i> Review MC
        </button>
      </div>
      <div v-if="hasMedicalCertificate">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Start Date (DD/MM/YYYY)</label>
            <input 
              type="text" 
              class="form-control" 
              :value="formatDateForDisplay(mcStartDate)" 
              @input="updateMCStartDate"
              placeholder="DD/MM/YYYY"
              maxlength="10"
              required
            >
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date (DD/MM/YYYY)</label>
            <input 
              type="text" 
              class="form-control" 
              :value="formatDateForDisplay(mcEndDate)" 
              @input="updateMCEndDate"
              placeholder="DD/MM/YYYY"
              maxlength="10"
              required
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateForAPI, formatDateOnlyMalaysia } from '../../utils/timezoneUtils.js';

export default {
  name: 'MedicalCertificateSection',
  props: {
    hasMedicalCertificate: {
      type: Boolean,
      default: false
    },
    mcStartDate: {
      type: String,
      default: null
    },
    mcEndDate: {
      type: String,
      default: null
    },
    selectedPatient: {
      type: Object,
      default: null
    }
  },
  emits: ['update:hasMedicalCertificate', 'update:mcStartDate', 'update:mcEndDate', 'show-mc-preview', 'mc-checkbox-change'],
  methods: {
    onMCCheckboxChange(event) {
      this.$emit('update:hasMedicalCertificate', event.target.checked);
      this.$emit('mc-checkbox-change', event.target.checked);
    },
    updateMCStartDate(event) {
      const inputValue = event.target.value;
      const formattedDate = this.parseAndFormatDate(inputValue);
      if (formattedDate) {
        this.$emit('update:mcStartDate', formatDateForAPI(formattedDate));
      }
    },
    updateMCEndDate(event) {
      const inputValue = event.target.value;
      const formattedDate = this.parseAndFormatDate(inputValue);
      if (formattedDate) {
        this.$emit('update:mcEndDate', formatDateForAPI(formattedDate));
      }
    },
    formatDateForDisplay(date) {
      if (!date) return '';
      try {
        return formatDateOnlyMalaysia(date);
      } catch (error) {
        return '';
      }
    },
    parseAndFormatDate(inputValue) {
      // Remove any non-digit characters
      const cleanValue = inputValue.replace(/\D/g, '');
      
      // Check if we have exactly 8 digits (DDMMYYYY)
      if (cleanValue.length === 8) {
        const day = cleanValue.substring(0, 2);
        const month = cleanValue.substring(2, 4);
        const year = cleanValue.substring(4, 8);
        
        // Validate the date
        const date = new Date(year, month - 1, day);
        if (date.getFullYear() == year && 
            date.getMonth() == month - 1 && 
            date.getDate() == day) {
          return date;
        }
      }
      
      // Try to parse DD/MM/YYYY format
      const parts = inputValue.split('/');
      if (parts.length === 3) {
        const day = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const year = parseInt(parts[2]);
        
        if (!isNaN(day) && !isNaN(month) && !isNaN(year)) {
          const date = new Date(year, month - 1, day);
          if (date.getFullYear() == year && 
              date.getMonth() == month - 1 && 
              date.getDate() == day) {
            return date;
          }
        }
      }
      
      return null;
    },
    showMCPreview() {
      this.$emit('show-mc-preview');
    }
  }
};
</script>

<style scoped>
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

.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-outline-info {
  border-color: #0dcaf0;
  color: #0dcaf0;
}

.btn-outline-info:hover {
  background-color: #0dcaf0;
  border-color: #0dcaf0;
  color: #fff;
}
</style> 