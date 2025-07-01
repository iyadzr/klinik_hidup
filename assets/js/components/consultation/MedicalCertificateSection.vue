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
            <label class="form-label">Start Date</label>
            <input 
              type="date" 
              class="form-control" 
              :value="mcStartDate" 
              @input="updateMCStartDate"
              required
            >
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input 
              type="date" 
              class="form-control" 
              :value="mcEndDate" 
              @input="updateMCEndDate"
              required
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
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
      this.$emit('update:mcStartDate', event.target.value);
    },
    updateMCEndDate(event) {
      this.$emit('update:mcEndDate', event.target.value);
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