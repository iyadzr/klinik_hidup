<template>
  <div class="ongoing-consultations-container">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>Patient Consultation
          </h4>
          <div class="d-flex gap-2">
            <button @click="refreshData" class="btn btn-outline-primary btn-sm" :disabled="loading || isRateLimited">
              <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i> Refresh
            </button>
            <span v-if="lastUpdated" class="badge bg-secondary">
              Last updated: {{ formatTime(lastUpdated) }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="card-body">
        <!-- Rate Limit Warning -->
        <div v-if="isRateLimited" class="alert alert-warning">
          <i class="fas fa-clock me-2"></i>Too many requests. Please wait {{ rateLimitCooldown }}s before refreshing.
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading patient consultations...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
          <button @click="retryWithBackoff" class="btn btn-sm btn-outline-danger ms-2">
            Retry ({{ retryCount }}/3)
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="!ongoingConsultations.length" class="empty-state">
          <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
          <h5>No Patients Waiting</h5>
          <p class="text-muted">No patients are currently waiting for consultation.</p>
        </div>

        <!-- Ongoing Consultations List -->
        <div v-else>
          <div class="row g-3">
            <div v-for="consultation in ongoingConsultations" :key="consultation.queueId || consultation.id" class="col-md-6 col-lg-4">
              <div class="consultation-card card h-100 border-start border-warning border-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0">
                      <i class="fas fa-user me-2 text-primary"></i>
                      <template v-if="consultation.isGroupConsultation">
                        Group Consultation
                      </template>
                      <template v-else>
                        {{ consultation.patientName }}
                      </template>
                    </h6>
                    <span v-if="consultation.isQueueEntry && consultation.status === 'waiting'" class="badge bg-info">
                      <i class="fas fa-hourglass-half me-1"></i>Waiting
                    </span>
                    <span v-else-if="consultation.isQueueEntry && consultation.status === 'in_consultation'" class="badge bg-warning text-dark">
                      <i class="fas fa-stethoscope me-1"></i>In Consultation
                    </span>
                    <span v-else class="badge bg-success">
                      <i class="fas fa-clock me-1"></i>Ongoing
                    </span>
                  </div>
                  <div class="consultation-details">
                    <p class="card-text text-muted mb-2">
                      <i class="fas fa-user-md me-2"></i>{{ consultation.doctorName }}
                    </p>
                    <p class="card-text text-muted mb-2">
                      <i class="fas fa-calendar me-2"></i>{{ formatDate(consultation.consultationDate) }}
                    </p>
                    <p v-if="consultation.queueNumber" class="card-text text-muted mb-2">
                      <i class="fas fa-list-ol me-2"></i>Queue #{{ consultation.queueNumber }}
                    </p>
                    <template v-if="consultation.isGroupConsultation">
                      <div class="mb-2">
                        <label class="form-label">Select Patient:</label>
                        <select v-model="selectedPatientId[consultation.queueId]" class="form-select form-select-sm mb-2">
                          <option v-for="patient in consultation.patients" :key="patient.patientId" :value="patient.patientId">
                            {{ patient.patientName }}
                          </option>
                        </select>
                        <div v-if="selectedPatient(consultation)">
                          <div class="mb-2">
                            <strong>Symptoms:</strong>
                            <span class="text-muted">{{ truncateText(selectedPatient(consultation).symptoms, 80) }}</span>
                          </div>
                          <button
                            class="btn btn-success btn-sm w-100"
                            @click="startConsultationForPatient(consultation, selectedPatient(consultation))"
                          >
                            <i class="fas fa-play me-1"></i>Start Consultation
                          </button>
                        </div>
                      </div>
                    </template>
                    <template v-else>
                      <p v-if="consultation.symptoms" class="card-text mb-2">
                        <strong>Symptoms:</strong> {{ truncateText(consultation.symptoms, 80) }}
                      </p>
                      <button
                        v-if="consultation.isQueueEntry && consultation.status === 'waiting'"
                        @click="startConsultation(consultation)"
                        class="btn btn-success btn-sm w-100"
                      >
                        <i class="fas fa-play me-1"></i>Start Consultation
                      </button>
                      <button
                        v-else-if="consultation.isQueueEntry && consultation.status === 'in_consultation'"
                        @click="continueConsultation(consultation)"
                        class="btn btn-warning btn-sm w-100"
                      >
                        <i class="fas fa-stethoscope me-1"></i>Continue Consultation
                      </button>
                      <button
                        v-else
                        @click="continueConsultation(consultation)"
                        class="btn btn-primary btn-sm w-100"
                      >
                        <i class="fas fa-arrow-right me-1"></i>Continue
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div v-if="!loading && ongoingConsultations.length" class="row mt-4 g-3">
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-body text-center">
            <i class="fas fa-hourglass-half fa-2x text-info mb-2"></i>
            <h6>Patients Waiting</h6>
            <h4 class="text-info">{{ ongoingConsultations.filter(c => c.isQueueEntry && c.status === 'waiting').length }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-body text-center">
            <i class="fas fa-stethoscope fa-2x text-warning mb-2"></i>
            <h6>In Progress</h6>
            <h4 class="text-warning">{{ ongoingConsultations.filter(c => (c.isQueueEntry && c.status === 'in_consultation') || !c.isQueueEntry).length }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-body text-center">
            <i class="fas fa-calendar-day fa-2x text-success mb-2"></i>
            <h6>Total Today</h6>
            <h4 class="text-success">{{ todayTotal }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { getTodayInMYT } from '../../utils/dateUtils';
import AuthService from '../../services/AuthService';

export default {
  name: 'PatientConsultation',
  setup() {
    const router = useRouter();
    const ongoingConsultations = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const todayTotal = ref(0);
    const lastUpdated = ref(null);
    const retryCount = ref(0);
    const isRateLimited = ref(false);
    const rateLimitCooldown = ref(0);
    
    // Request cancellation
    let currentRequest = null;
    let refreshInterval = null;
    let retryTimeout = null;
    let rateLimitTimeout = null;
    
    // Client-side cache
    const cache = ref({
      data: null,
      timestamp: null,
      ttl: 30000 // 30 seconds cache
    });

    const currentUser = computed(() => AuthService.getCurrentUser());
    const isDoctor = computed(() => AuthService.hasRole('ROLE_DOCTOR'));
    const isSuperAdmin = computed(() => AuthService.hasRole('ROLE_SUPER_ADMIN'));
    
    // Rate limiting helpers
    const requestLog = ref([]);
    const maxRequestsPerMinute = 10;

    const checkRateLimit = () => {
      const now = Date.now();
      const oneMinuteAgo = now - 60000;
      
      // Clean old requests
      requestLog.value = requestLog.value.filter(time => time > oneMinuteAgo);
      
      if (requestLog.value.length >= maxRequestsPerMinute) {
        isRateLimited.value = true;
        rateLimitCooldown.value = 60;
        
        if (rateLimitTimeout) clearInterval(rateLimitTimeout);
        rateLimitTimeout = setInterval(() => {
          rateLimitCooldown.value--;
          if (rateLimitCooldown.value <= 0) {
            isRateLimited.value = false;
            clearInterval(rateLimitTimeout);
            rateLimitTimeout = null;
          }
        }, 1000);
        
        return false;
      }
      
      requestLog.value.push(now);
      return true;
    };

    const isDataFresh = () => {
      if (!cache.value.data || !cache.value.timestamp) return false;
      return (Date.now() - cache.value.timestamp) < cache.value.ttl;
    };

    const fetchOngoingConsultations = async (useCache = true) => {
      // Check rate limiting
      if (!checkRateLimit()) {
        console.warn('Rate limited - skipping request');
        return;
      }
      
      // Use cache if available and fresh
      if (useCache && isDataFresh()) {
        console.log('Using cached data');
        ongoingConsultations.value = cache.value.data;
        return;
      }
      
      // Cancel previous request
      if (currentRequest) {
        currentRequest.abort();
        currentRequest = null;
      }
      
      // Prevent multiple simultaneous requests
      if (loading.value) return;
      
      loading.value = true;
      error.value = null;
      
      // Create abort controller
      currentRequest = new AbortController();
      const { signal } = currentRequest;
      
      try {
        let doctorId = null;
        if (isDoctor.value && currentUser.value) {
          doctorId = await AuthService.getDoctorId();
          console.log('Using doctor ID:', doctorId, 'for user:', currentUser.value.email);
        }

        const response = await axios.get('/api/consultations/ongoing', {
          signal,
          params: {
            doctorId: doctorId
          }
        });
        
        ongoingConsultations.value = response.data.ongoing;
        todayTotal.value = response.data.todayTotal;
        lastUpdated.value = new Date();
        error.value = null; // Clear error on success
        retryCount.value = 0; // Reset retry count
        
        // Update cache
        cache.value.data = response.data.ongoing;
        cache.value.timestamp = Date.now();
        
      } catch (err) {
        if (axios.isCancel(err)) {
          console.log('Request canceled');
        } else {
          error.value = 'Failed to load ongoing consultations.';
          console.error(err);
        }
      } finally {
        loading.value = false;
        currentRequest = null;
      }
    };
    
    const retryWithBackoff = async () => {
      if (retryCount.value >= 3) {
        error.value = 'Maximum retry attempts reached. Please refresh the page.';
        return;
      }
      
      const backoffDelay = Math.min(1000 * Math.pow(2, retryCount.value), 10000); // Max 10 seconds
      console.log(`Retrying in ${backoffDelay}ms (attempt ${retryCount.value + 1}/3)`);
      
      if (retryTimeout) clearTimeout(retryTimeout);
      retryTimeout = setTimeout(() => {
        fetchOngoingConsultations(false); // Don't use cache on retry
      }, backoffDelay);
    };

    const startConsultation = async (consultation) => {
      try {
        await axios.put(`/api/queue/${consultation.queueId}/status`, { status: 'in_consultation' });
        
        router.push({
          path: '/consultations/new',
          query: {
            queueNumber: consultation.queueNumber,
            patientId: consultation.patientId,
            doctorId: consultation.doctorId,
            queueId: consultation.queueId,
            mode: 'start'
          }
        });
      } catch (error) {
        console.error('Error starting consultation:', error);
        alert('Error starting consultation. Please try again.');
      }
    };

    const continueConsultation = (consultation) => {
      if (consultation.isQueueEntry) {
        router.push({
          path: '/consultations/new',
          query: {
            queueNumber: consultation.queueNumber,
            patientId: consultation.patientId,
            doctorId: consultation.doctorId,
            queueId: consultation.queueId,
            mode: 'continue'
          }
        });
      } else {
        router.push(`/consultations/${consultation.id}`);
      }
    };

    const refreshData = () => {
      if (isRateLimited.value) return;
      fetchOngoingConsultations(false); // Force refresh without cache
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          month: 'short',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        return 'Invalid Date';
      }
    };
    
    const formatTime = (date) => {
      try {
        return date.toLocaleTimeString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
      } catch (error) {
        return 'Invalid Time';
      }
    };

    const truncateText = (text, length) => {
      if (!text || text.length <= length) return text;
      return text.substr(0, length) + '...';
    };

    // Setup auto-refresh with intelligent intervals
    const setupAutoRefresh = () => {
      // Clear existing interval
      if (refreshInterval) {
        clearInterval(refreshInterval);
      }
      
      // Auto-refresh every 45 seconds (less frequent to reduce load)
      refreshInterval = setInterval(() => {
        if (!loading.value && !isRateLimited.value && !error.value) {
          fetchOngoingConsultations(true); // Allow cache usage
        }
      }, 45000);
    };

    const selectedPatientId = ref({});
    const selectedPatient = (consultation) => {
      if (!consultation.isGroupConsultation) return null;
      const id = selectedPatientId.value[consultation.queueId];
      return consultation.patients.find(p => p.patientId === id);
    };
    const startConsultationForPatient = (consultation, patient) => {
      // Use the same logic as startConsultation, but for the selected patient
      startConsultation({
        ...consultation,
        patientId: patient.patientId,
        patientName: patient.patientName,
        symptoms: patient.symptoms
      });
    };

    onMounted(() => {
      console.log('OngoingConsultations component mounted');
      fetchOngoingConsultations();
      setupAutoRefresh();
    });

    onUnmounted(() => {
      // Cleanup
      if (currentRequest) {
        currentRequest.abort();
      }
      if (refreshInterval) {
        clearInterval(refreshInterval);
      }
      if (retryTimeout) {
        clearTimeout(retryTimeout);
      }
      if (rateLimitTimeout) {
        clearInterval(rateLimitTimeout);
      }
    });

    // This is the fix: reload data when navigating back to the component
    watch(
      () => router.currentRoute.value.name,
      (newName) => {
        if (newName === 'OngoingConsultations') {
          fetchOngoingConsultations(false); // Force fetch new data
        }
      }
    );

    return {
      ongoingConsultations,
      loading,
      error,
      todayTotal,
      lastUpdated,
      retryCount,
      isRateLimited,
      rateLimitCooldown,
      currentUser,
      isDoctor,
      isSuperAdmin,
      startConsultation,
      continueConsultation,
      refreshData,
      retryWithBackoff,
      formatDate,
      formatTime,
      truncateText,
      selectedPatientId,
      selectedPatient,
      startConsultationForPatient,
    };
  }
};
</script>

<style scoped>
.ongoing-consultations-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

.card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  background: white;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px 16px 0 0 !important;
  padding: 1.5rem;
  border: none;
}

.consultation-card {
  transition: all 0.3s ease;
}

.consultation-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
}

.consultation-details {
  font-size: 0.9rem;
}

.empty-state {
  color: #64748b;
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.badge {
  padding: 0.5em 0.75em;
  font-size: 0.75em;
  border-radius: 8px;
  font-weight: 600;
}

.btn {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.bg-light {
  background-color: #f8f9fa !important;
}
</style> 