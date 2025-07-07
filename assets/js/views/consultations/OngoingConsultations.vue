<template>
  <div class="ongoing-consultations-container">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>Patient Consultation
          </h4>
          <div class="d-flex gap-2">
            <span v-if="lastUpdated" class="badge bg-secondary">
              Last updated: {{ formatTime(lastUpdated) }}
            </span>
            <span :class="sseConnected ? 'badge bg-success' : 'badge bg-warning'" :title="sseConnected ? 'Real-time updates connected' : 'Real-time updates disconnected'">
              <i class="fas fa-broadcast-tower me-1"></i>{{ sseConnected ? 'Live' : 'Offline' }}
            </span>
            <button @click="refreshData" class="btn btn-sm btn-outline-primary" :disabled="loading || isRateLimited" title="Refresh data">
              <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
              <span class="d-none d-sm-inline ms-1">Refresh</span>
            </button>
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
        <div v-else-if="!ongoingConsultations.length" class="empty-state text-center py-5">
          <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
          <h5>No Patients Waiting</h5>
          <p class="text-muted mb-3">No patients are currently waiting for consultation.</p>
          <div class="mt-3">
            <small class="text-muted">
              <i class="fas fa-info-circle me-1"></i>
              New patients will appear here automatically when they register and join the queue.
            </small>
          </div>
          <div class="mt-2">
            <button @click="refreshData" class="btn btn-sm btn-outline-primary" :disabled="loading">
              <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
              Check for Updates
            </button>
          </div>
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
                    <span v-else-if="consultation.status === 'completed' || consultation.status === 'completed_consultation'" class="badge bg-success">
                      <i class="fas fa-check me-1"></i>Completed
                    </span>
                    <span v-else class="badge bg-primary">
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
                      <i class="fas fa-list-ol me-2"></i>Queue #{{ formatQueueNumber(consultation.queueNumber) }}
                    </p>
                    <template v-if="consultation.isGroupConsultation">
                      <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <h6 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Group Members ({{ consultation.patients?.length || 0 }})
                          </h6>
                          <span class="badge bg-primary">{{ consultation.patients?.length || 0 }} {{ (consultation.patients?.length || 0) === 1 ? 'patient' : 'patients' }}</span>
                        </div>
                        
                        <!-- Debug Info (remove in production) -->
                        <div v-if="consultation.patients?.length === 0" class="alert alert-warning small mb-2">
                          <i class="fas fa-exclamation-triangle me-1"></i>
                          No patients found in group. Queue: {{ consultation.queueNumber }}
                        </div>
                        
                        <!-- Simple Patient Name List -->
                        <div class="mb-3">
                          <div v-for="(patient, index) in consultation.patients?.slice(0, 6)" 
                               :key="patient.patientId || index" 
                               class="d-flex align-items-center justify-content-between py-2 px-3 mb-2 bg-light rounded">
                            <div class="d-flex align-items-center">
                              <span class="badge bg-info text-white me-2">{{ index + 1 }}</span>
                              <span class="fw-medium">{{ patient.patientName }}</span>
                            </div>
                            <i class="fas fa-user-circle text-muted"></i>
                          </div>
                        </div>
                        
                        <!-- Show "and X more" if too many patients -->
                        <div v-if="consultation.patients?.length > 6" class="text-center mb-2">
                          <small class="text-muted">
                            <i class="fas fa-ellipsis-h me-1"></i>
                            Showing first 6 of {{ consultation.patients.length }} {{ consultation.patients.length === 1 ? 'patient' : 'patients' }}
                          </small>
                        </div>
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
                        <div
                          v-else-if="consultation.status === 'completed' || consultation.status === 'completed_consultation'"
                          class="text-center"
                        >
                          <span class="badge bg-success fs-6 py-2 px-3">
                            <i class="fas fa-check me-1"></i>Completed
                          </span>
                        </div>
                        <button
                          v-else
                          @click="continueConsultation(consultation)"
                          class="btn btn-primary btn-sm w-100"
                        >
                          <i class="fas fa-arrow-right me-1"></i>Continue
                        </button>
                      </div>
                    </template>
                    <template v-else>
                      <p v-if="consultation.symptoms" class="card-text mb-2">
                        <strong>Remarks:</strong> {{ truncateText(consultation.symptoms, 80) }}
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
                      <div
                        v-else-if="consultation.status === 'completed' || consultation.status === 'completed_consultation'"
                        class="text-center"
                      >
                        <span class="badge bg-success fs-6 py-2 px-3">
                          <i class="fas fa-check me-1"></i>Completed
                        </span>
                      </div>
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
    <div v-if="!loading && (ongoingConsultations.length || todayTotal > 0)" class="row mt-4 g-3">
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
        <div class="card bg-light clickable-card" @click="showTodayPatientsModal" title="Click to view all patients for today">
          <div class="card-body text-center">
            <i class="fas fa-calendar-day fa-2x text-success mb-2"></i>
            <h6>Total Today</h6>
            <h4 class="text-success">{{ todayTotal }}</h4>
            <small class="text-muted">Click to view all</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Patients Modal -->
    <div class="modal fade" id="todayPatientsModal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-calendar-day me-2"></i>
              Today's Patients ({{ todayTotal }})
              <span v-if="pendingPaymentsCount > 0" class="badge bg-warning text-dark ms-2">
                {{ pendingPaymentsCount }} pending payment{{ pendingPaymentsCount > 1 ? 's' : '' }}
              </span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="loadingTodayPatients" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Loading today's patients...</p>
            </div>
            <div v-else-if="todayPatients.length === 0" class="text-center py-4">
              <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
              <h6>No Patients Today</h6>
              <p class="text-muted">No patients have been registered for consultations today.</p>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Time</th>
                      <th>Patient</th>
                      <th>Doctor</th>
                      <th>Status</th>
                      <th>Payment</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="patient in deduplicatedTodayPatients" :key="patient.id + '-' + patient.type">
                      <td>{{ formatTime(patient.time) }}</td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                            <i class="fas fa-user text-secondary"></i>
                          </div>
                          <div>
                            <div class="fw-medium">{{ patient.patientName }}</div>
                            <small class="text-muted">{{ patient.type === 'queue' ? 'Queue #' + formatQueueNumber(patient.queueNumber) : 'Consultation' }}</small>
                          </div>
                        </div>
                      </td>
                      <td>{{ patient.doctorName }}</td>
                      <td>
                        <span class="badge" :class="getTodayPatientStatusClass(patient.status)">
                          {{ formatTodayPatientStatus(patient.status) }}
                        </span>
                      </td>
                      <td>
                        <div v-if="patient.status === 'completed' || patient.status === 'completed_consultation'">
                          <span v-if="patient.isPaid" class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Paid
                          </span>
                          <span v-else class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i>Pending
                          </span>
                          <div v-if="patient.totalAmount && parseFloat(patient.totalAmount) > 0" class="small text-muted mt-1">
                            RM {{ parseFloat(patient.totalAmount || 0).toFixed(2) }}
                          </div>
                        </div>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td>
                        <button 
                          v-if="patient.status === 'waiting'" 
                          @click="startPatientConsultation(patient)"
                          class="btn btn-sm btn-success"
                          title="Start Consultation"
                        >
                          <i class="fas fa-play me-1"></i>Start
                        </button>
                        <button 
                          v-else-if="patient.status === 'in_consultation'" 
                          @click="continuePatientConsultation(patient)"
                          class="btn btn-sm btn-warning"
                          title="Continue Consultation"
                        >
                          <i class="fas fa-arrow-right me-1"></i>Continue
                        </button>
                        <div v-else-if="patient.status === 'completed' || patient.status === 'completed_consultation'" class="d-flex flex-column gap-1">
                          <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Completed
                          </span>
                          <button 
                            v-if="!patient.isPaid && patient.totalAmount && parseFloat(patient.totalAmount) > 0" 
                            @click="processPayment(patient)"
                            class="btn btn-sm btn-outline-primary"
                            title="Process Payment"
                          >
                            <i class="fas fa-credit-card me-1"></i>Pay
                          </button>
                        </div>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
import { getTodayInMYT, formatQueueNumber } from '../../utils/dateUtils';
import AuthService from '../../services/AuthService';
import { MALAYSIA_TIMEZONE } from '../../utils/timezoneUtils.js';
import { makeNavigationRequest } from '../../utils/requestManager.js';

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
    
    // Today's Patients Modal
    const todayPatients = ref([]);
    const loadingTodayPatients = ref(false);
    const todayPatientsModal = ref(null);
    
    // SSE connection status
    const sseConnected = ref(false);
    
    // Request cancellation
    let currentRequest = null;
    let refreshInterval = null;
    let retryTimeout = null;
    let rateLimitTimeout = null;
    
    // SSE connection for real-time updates
    let eventSource = null;
    let sseReconnectAttempts = 0;
    const maxSseReconnectAttempts = 5;
    const sseReconnectDelay = 1000;
    
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
    const manualRequestLog = ref([]);
    const maxRequestsPerMinute = 10;
    const maxManualRequestsPerMinute = 5; // Lower limit for manual requests

    const checkRateLimit = (isManual = false) => {
      const now = Date.now();
      const oneMinuteAgo = now - 60000;
      
      // Clean old requests
      requestLog.value = requestLog.value.filter(time => time > oneMinuteAgo);
      manualRequestLog.value = manualRequestLog.value.filter(time => time > oneMinuteAgo);
      
      if (isManual) {
        // Check manual request limit
        if (manualRequestLog.value.length >= maxManualRequestsPerMinute) {
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
        manualRequestLog.value.push(now);
      } else {
        // Check overall request limit for automatic requests
        if (requestLog.value.length >= maxRequestsPerMinute) {
          console.warn('⚠️ Automatic request rate limit reached, skipping request');
          return false;
        }
      }
      
      requestLog.value.push(now);
      return true;
    };

    const isDataFresh = () => {
      if (!cache.value.data || !cache.value.timestamp) return false;
      return (Date.now() - cache.value.timestamp) < cache.value.ttl;
    };

    const fetchOngoingConsultations = async (useCache = true, isManual = false) => {
      // Check rate limiting
      if (!checkRateLimit(isManual)) {
        if (isManual) {
          console.warn('Manual request rate limited - skipping request');
        }
        return;
      }
      
      // Use cache if available and fresh
      if (useCache && isDataFresh()) {
        console.log('Using cached data');
        ongoingConsultations.value = cache.value.data || [];
        return;
      }
      
      // Don't cancel previous request if already loading - just wait for it to complete
      if (loading.value) {
        console.log('Request already in progress, skipping...');
        return;
      }
      
      loading.value = true;
      error.value = null;
      
      // Create abort controller for this specific request
      const abortController = new AbortController();
      currentRequest = abortController;
      
      try {
        let doctorId = null;
        if (isDoctor.value && currentUser.value) {
          doctorId = await AuthService.getDoctorId();
          console.log('Using doctor ID:', doctorId, 'for user:', currentUser.value.email);
        }

        console.log('🔄 Fetching ongoing consultations...');
        const response = await makeNavigationRequest(
          'ongoing-consultations',
          async (signal) => {
            return await axios.get('/api/consultations/ongoing', {
              signal: signal || abortController.signal,
              params: {
                doctorId: doctorId
              }
            });
          },
          {
            timeout: 8000, // Faster timeout for ongoing consultations
            maxRetries: 1
          }
        );
        
        console.log('✅ Successfully fetched ongoing consultations:', response.data);
        ongoingConsultations.value = response.data.ongoing || [];
        todayTotal.value = response.data.todayTotal || 0;
        lastUpdated.value = new Date();
        error.value = null; // Clear error on success
        retryCount.value = 0; // Reset retry count
        
        // Update cache
        cache.value.data = response.data.ongoing || [];
        cache.value.timestamp = Date.now();
        
      } catch (err) {
        // Only handle error if this request wasn't aborted
        if (currentRequest === abortController) {
          if (axios.isCancel(err)) {
            console.log('Request was canceled');
          } else {
            // Enhanced error handling with specific messages
            let errorMessage = 'Failed to load ongoing consultations.';
            
            console.error('🔍 API Error Details:', {
              status: err.response?.status,
              statusText: err.response?.statusText,
              data: err.response?.data,
              url: err.config?.url,
              method: err.config?.method,
              headers: err.config?.headers ? Object.keys(err.config.headers) : 'none',
              hasAuthHeader: !!err.config?.headers?.Authorization
            });
            
            if (err.response?.status === 401) {
              errorMessage = 'Authentication required. Please log in again.';
              console.log('🔐 401 Unauthorized - checking authentication status...');
              console.log('🔐 Current user:', AuthService.getCurrentUser());
              console.log('🔐 Is authenticated:', AuthService.isAuthenticated());
              console.log('🔐 Token valid:', !AuthService.isTokenExpired(AuthService.getCurrentUser()?.token));
              
              // Check if user is still authenticated
              if (!AuthService.isAuthenticated()) {
                console.log('🔐 User is not authenticated, forcing logout...');
                // Force logout and redirect to login
                AuthService.logout();
                router.push('/login');
                return;
              } else {
                console.log('🔐 User appears authenticated but got 401 - token might be expired');
                // Token might be expired, try to refresh
                console.log('🔄 Token might be expired, attempting to refresh data...');
                setTimeout(() => {
                  fetchOngoingConsultations(false, false);
                }, 1000);
              }
            } else if (err.response?.status === 429) {
              errorMessage = 'Too many requests. Please wait before refreshing.';
            } else if (err.response?.status >= 500) {
              errorMessage = 'Server error occurred. Retrying automatically...';
              retryCount.value++;
              if (retryCount.value < 3) {
                setTimeout(() => retryWithBackoff(), 2000);
              }
            } else if (err.response?.status === 404) {
              errorMessage = 'Consultation data not found.';
            } else if (err.code === 'NETWORK_ERROR' || !err.response) {
              errorMessage = 'Network connection error. Check your internet connection.';
            }
            
            error.value = errorMessage;
            console.error('Fetch ongoing consultations error:', err);
          }
        }
      } finally {
        // Only clear loading if this is still the current request
        if (currentRequest === abortController) {
          loading.value = false;
          currentRequest = null;
        }
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
        fetchOngoingConsultations(false, false); // Don't use cache on retry, not manual
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
      if (isRateLimited.value) {
        console.warn('⚠️ Refresh blocked due to rate limiting');
        return;
      }
      console.log('🔄 Manual refresh triggered');
      fetchOngoingConsultations(false, true); // Force refresh without cache, manual request
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-MY', {
          timeZone: MALAYSIA_TIMEZONE,
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
    
    const formatTime = (dateInput) => {
      try {
        // Handle null, undefined, or empty values
        if (!dateInput || dateInput === null || dateInput === undefined || dateInput === '') {
          return 'N/A';
        }
        
        let date;
        if (dateInput instanceof Date) {
          date = dateInput;
        } else if (typeof dateInput === 'string') {
          // Clean the string and try to parse it
          const cleanInput = dateInput.trim();
          if (cleanInput === '') return 'N/A';
          date = new Date(cleanInput);
        } else if (typeof dateInput === 'number') {
          // Handle timestamp
          date = new Date(dateInput);
        } else {
          console.warn('Unexpected time input type:', typeof dateInput, dateInput);
          return 'Invalid Time';
        }
        
        // Validate the parsed date
        if (isNaN(date.getTime())) {
          console.warn('Invalid date parsed from:', dateInput);
          return 'Invalid Time';
        }
        
        // Format the time for Malaysia timezone
        return date.toLocaleTimeString('en-MY', {
          timeZone: MALAYSIA_TIMEZONE,
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        console.error('Error formatting time:', error, 'Input:', dateInput, 'Type:', typeof dateInput);
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
      
      // Auto-refresh every 60 seconds (less frequent to reduce load)
      // Only refresh if SSE is not connected (fallback mechanism)
      refreshInterval = setInterval(() => {
        if (!loading.value && !isRateLimited.value && !error.value && !sseConnected.value) {
          console.log('🔄 Auto-refresh triggered (SSE not connected)');
          fetchOngoingConsultations(true, false); // Allow cache usage, not manual
        }
      }, 60000);
    };

    // Initialize SSE connection for real-time updates
    const initializeSSE = () => {
      // Close existing connection
      if (eventSource) {
        console.log('🔌 Closing existing SSE connection...');
        eventSource.close();
        eventSource = null;
      }

      // Don't initialize SSE if user is not authenticated
      if (!AuthService.isAuthenticated()) {
        console.log('🔐 User not authenticated, skipping SSE initialization');
        sseConnected.value = false;
        return;
      }

      try {
        // Get authentication token for SSE connection
        const user = AuthService.getCurrentUser();
        const token = user?.token;
        
        // Build SSE URL with token parameter if available
        let sseUrl = '/api/sse/queue-updates';
        if (token) {
          sseUrl += `?token=${encodeURIComponent(token)}`;
        }
        
        console.log('🔌 Initializing SSE connection for ongoing consultations...');
        console.log('🔌 SSE URL:', sseUrl.replace(/token=[^&]+/, 'token=[REDACTED]'));
        console.log('🔌 Base URL:', window.location.origin);
        console.log('🔌 Has token:', !!token);
        
        eventSource = new EventSource(sseUrl);
        
        eventSource.onopen = () => {
          console.log('✅ SSE connection established for ongoing consultations');
          console.log('✅ SSE readyState:', eventSource.readyState);
          sseReconnectAttempts = 0;
          sseConnected.value = true;
        };
        
        eventSource.onmessage = (event) => {
          try {
            const queueData = JSON.parse(event.data);
            console.log('📡 SSE queue update received for ongoing consultations:', queueData);
            handleQueueUpdate(queueData);
          } catch (error) {
            console.error('❌ Error parsing SSE data:', error);
          }
        };
        
        eventSource.addEventListener('connected', (event) => {
          try {
            const connectionData = JSON.parse(event.data);
            console.log('✅ SSE connection established for ongoing consultations:', connectionData);
          } catch (error) {
            console.error('❌ Error parsing SSE connection data:', error);
          }
        });
        
        eventSource.addEventListener('heartbeat', (event) => {
          try {
            const heartbeatData = JSON.parse(event.data);
            console.log('💓 SSE heartbeat received for ongoing consultations:', heartbeatData);
          } catch (error) {
            console.error('❌ Error parsing SSE heartbeat data:', error);
          }
        });
        
        eventSource.onerror = (error) => {
          console.error('❌ SSE connection error for ongoing consultations:', error);
          console.error('❌ SSE readyState:', eventSource?.readyState);
          console.error('❌ SSE url:', eventSource?.url);
          console.error('❌ Error details:', {
            type: error.type,
            target: error.target,
            currentTarget: error.currentTarget,
            eventPhase: error.eventPhase
          });
          
          sseConnected.value = false;
          
          // Close the connection if it's in an error state
          if (eventSource) {
            console.log('🔌 Closing failed SSE connection...');
            eventSource.close();
            eventSource = null;
          }
          
          // Always attempt to reconnect after an error
          console.log('🔄 SSE connection error, attempting to reconnect...');
          handleSSEReconnect();
        };
        
      } catch (error) {
        console.error('❌ Failed to initialize SSE for ongoing consultations:', error);
        sseConnected.value = false;
      }
    };

    const handleSSEReconnect = () => {
      if (sseReconnectAttempts >= maxSseReconnectAttempts) {
        console.warn('⚠️ Max SSE reconnection attempts reached for ongoing consultations');
        return;
      }

      sseReconnectAttempts++;
      const delay = sseReconnectDelay * Math.pow(2, sseReconnectAttempts - 1); // Exponential backoff
      
      console.log(`🔄 Attempting SSE reconnection ${sseReconnectAttempts}/${maxSseReconnectAttempts} in ${delay}ms`);
      
      setTimeout(() => {
        initializeSSE();
      }, delay);
    };

    // Handle real-time queue updates
    const handleQueueUpdate = (queueData) => {
      console.log('📡 SSE queue update received for ongoing consultations:', queueData);
      
      // ALWAYS refresh the list for ANY queue update to ensure immediate updates
      // This includes new patient registrations, status changes, etc.
      console.log('🔄 Queue update detected, refreshing ongoing consultations immediately');
      fetchOngoingConsultations(false, false); // Force refresh without cache, not manual
      
      // Show notification for new patient registrations
      if (queueData.status === 'waiting' && queueData.patient && queueData.patient.name) {
        console.log('🆕 New patient registered:', queueData.patient.name);
        // Optional: Show a toast notification
        // this.$toast?.info?.(`New patient registered: ${queueData.patient.name}`);
      }
    };

    onMounted(() => {
      console.log('OngoingConsultations component mounted');
      
      // Debug authentication state
      const user = AuthService.getCurrentUser();
      console.log('🔐 Current user:', user);
      console.log('🔐 Is authenticated:', AuthService.isAuthenticated());
      console.log('🔐 Has ROLE_DOCTOR:', AuthService.hasRole('ROLE_DOCTOR'));
      
      if (user && user.token) {
        console.log('🔐 Token exists, length:', user.token.length);
        console.log('🔐 Token expiration:', AuthService.getTokenExpirationTime());
        console.log('🔐 Token expires soon:', AuthService.isTokenExpiringSoon());
        
        // Test token validity by making a simple API call
        console.log('🔐 Testing token validity...');
        axios.get('/api/profile')
          .then(response => {
            console.log('✅ Token is valid, user profile:', response.data);
          })
          .catch(error => {
            console.error('❌ Token validation failed:', error);
            if (error.response?.status === 401) {
              console.log('🔐 Token is invalid, logging out...');
              AuthService.logout();
              router.push('/login');
              return;
            }
          });
      } else {
        console.log('🔐 No token found in user data');
        console.log('🔐 Redirecting to login...');
        AuthService.logout();
        router.push('/login');
        return;
      }
      
      // Only proceed if authenticated
      if (AuthService.isAuthenticated()) {
        fetchOngoingConsultations(true, false); // Use cache, not manual
        setupAutoRefresh();
        initializeSSE(); // Initialize SSE for real-time updates
      }
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
      
      // Cleanup SSE connection
      if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
      sseConnected.value = false;
    });

    // Today's Patients Modal functionality
    const showTodayPatientsModal = async () => {
      loadingTodayPatients.value = true;
      todayPatients.value = [];
      
      try {
        // Import Bootstrap if not already available
        const { Modal } = await import('bootstrap');
        
        // Show modal
        if (!todayPatientsModal.value) {
          todayPatientsModal.value = new Modal(document.getElementById('todayPatientsModal'));
        }
        todayPatientsModal.value.show();
        
        // Fetch today's patients data
        await fetchTodayPatients();
      } catch (error) {
        console.error('Error showing today patients modal:', error);
      }
    };

    const fetchTodayPatients = async () => {
      try {
        let doctorId = null;
        if (isDoctor.value && currentUser.value) {
          doctorId = await AuthService.getDoctorId();
        }

        const response = await axios.get('/api/consultations/today-all', {
          params: { doctorId: doctorId }
        });
        
        todayPatients.value = response.data.patients || [];
      } catch (error) {
        console.error('Error fetching today patients:', error);
        todayPatients.value = [];
      } finally {
        loadingTodayPatients.value = false;
      }
    };

    const getTodayPatientStatusClass = (status) => {
      const statusClasses = {
        'waiting': 'bg-info',
        'in_consultation': 'bg-warning text-dark',
        'completed_consultation': 'bg-primary',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
      };
      return statusClasses[status] || 'bg-secondary';
    };

    const formatTodayPatientStatus = (status) => {
      const statusMap = {
        'waiting': 'Waiting',
        'in_consultation': 'In Consultation',
        'completed_consultation': 'Completed',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
      };
      return statusMap[status] || status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ');
    };

    const startPatientConsultation = async (patient) => {
      if (patient.type === 'queue') {
        await startConsultation(patient);
      }
    };

    const continuePatientConsultation = async (patient) => {
      if (patient.type === 'queue') {
        continueConsultation(patient);
      } else {
        router.push(`/consultations/${patient.id}`);
      }
    };

    const processPayment = async (patient) => {
      try {
        // Navigate to payment processing for this patient
        if (patient.type === 'queue') {
          // For queue entries, redirect to queue management with payment focus
          router.push(`/queue?payment=${patient.queueId}`);
        } else {
          // For consultations, redirect to consultation list with payment focus
          router.push(`/consultations?payment=${patient.id}`);
        }
      } catch (error) {
        console.error('Error processing payment:', error);
      }
    };
    
    const getPatientCardClasses = (patientCount) => {
      // Responsive classes based on number of patients
      if (patientCount === 1) {
        return 'col-12'; // Full width for single patient
      } else if (patientCount === 2) {
        return 'col-md-6 col-12'; // 2 cards per row on medium screens+
      } else if (patientCount === 3) {
        return 'col-lg-4 col-md-6 col-12'; // 3 cards per row on large screens+
      } else if (patientCount === 4) {
        return 'col-xl-3 col-lg-4 col-md-6 col-12'; // 4 cards per row on xl screens+
      } else {
        // For 5+ patients, show max 4 in responsive grid
        return 'col-xl-3 col-lg-4 col-md-6 col-12';
      }
    };

    // This is the fix: reload data when navigating back to the component
    watch(
      () => router.currentRoute.value.name,
      (newName) => {
        if (newName === 'OngoingConsultations') {
          fetchOngoingConsultations(false, false); // Force fetch new data, not manual
        }
      }
    );

    // Deduplicate today's patients: prefer 'consultation' over 'queue' for same patient
    const deduplicatedTodayPatients = computed(() => {
      const map = new Map();
      for (const patient of todayPatients.value) {
        const key = patient.patientId || patient.id;
        if (!key) continue;
        // If already exists, prefer 'consultation' type
        if (!map.has(key) || patient.type === 'consultation') {
          map.set(key, patient);
        }
      }
      return Array.from(map.values());
    });

    // Count patients with pending payments
    const pendingPaymentsCount = computed(() => {
      return deduplicatedTodayPatients.value.filter(patient => 
        (patient.status === 'completed' || patient.status === 'completed_consultation') &&
        !patient.isPaid && 
        patient.totalAmount && 
        parseFloat(patient.totalAmount) > 0
      ).length;
    });

    // Watch for logout/login and reset rate limit
    watch(currentUser, (newUser, oldUser) => {
      requestLog.value = [];
      manualRequestLog.value = [];
      isRateLimited.value = false;
      rateLimitCooldown.value = 0;
      if (rateLimitTimeout) {
        clearInterval(rateLimitTimeout);
        rateLimitTimeout = null;
      }
      
      // If user just logged in (was null/undefined, now has value), fetch data
      if (!oldUser && newUser) {
        console.log('🔐 User logged in, fetching ongoing consultations...');
        error.value = null; // Clear any auth errors
        fetchOngoingConsultations(false, false);
      }
      
      // If user logged out, clear data and close SSE
      if (oldUser && !newUser) {
        console.log('🔐 User logged out, clearing ongoing consultations...');
        ongoingConsultations.value = [];
        todayTotal.value = 0;
        if (eventSource) {
          eventSource.close();
          eventSource = null;
          sseConnected.value = false;
        }
      }
    });

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
      formatQueueNumber,
      // Today's Patients Modal
      todayPatients,
      loadingTodayPatients,
      showTodayPatientsModal,
      getTodayPatientStatusClass,
      formatTodayPatientStatus,
      startPatientConsultation,
      continuePatientConsultation,
      processPayment,
      getPatientCardClasses,
      // SSE methods
      initializeSSE,
      handleQueueUpdate,
      sseConnected,
      deduplicatedTodayPatients,
      pendingPaymentsCount,
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

.clickable-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.clickable-card:hover {
  transform: translateY(-4px) !important;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15) !important;
}

.avatar-sm {
  width: 32px;
  height: 32px;
}

/* Group Consultation Styles */
.symptoms-text {
  font-size: 0.75rem;
  line-height: 1.3;
}

.text-xs {
  font-size: 0.65rem !important;
}

.group-member-card {
  transition: all 0.2s ease;
  border-radius: 8px !important;
}

.group-member-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.card-body.p-2 {
  padding: 0.75rem !important;
}

/* Responsive text sizing */
@media (max-width: 768px) {
  .symptoms-text {
    font-size: 0.7rem;
  }
  
  .card-title {
    font-size: 0.9rem !important;
  }
}

/* Ensure cards have consistent height */
.consultation-card .card {
  min-height: 120px;
}

.consultation-card .bg-light {
  background-color: #f8f9fa !important;
  border: 1px solid #e9ecef !important;
}

/* Professional hover effects for patient cards */
.consultation-card .bg-light:hover {
  background-color: #e2e6ea !important;
  border-color: #6c757d !important;
}

/* Group badge styling */
.badge.bg-primary {
  background-color: #0d6efd !important;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
}

.badge.bg-info {
  background-color: #0dcaf0 !important;
  color: #000 !important;
  font-weight: 600;
}
</style> 