<template>
  <div class="ongoing-consultations-container">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>Patient Consultation
          </h4>
          <div class="d-flex gap-2">
            <button @click="refreshData" class="btn btn-outline-primary btn-sm" :disabled="loading">
              <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i> Refresh
            </button>
            <button @click="goToNewConsultation" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> New Consultation
            </button>
          </div>
        </div>
      </div>
      
      <div class="card-body">
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
        </div>

                 <!-- Empty State -->
         <div v-else-if="!ongoingConsultations.length" class="empty-state">
           <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
           <h5>No Active Consultations</h5>
           <p class="text-muted">No consultations are currently in progress.</p>
         </div>

        <!-- Ongoing Consultations List -->
        <div v-else>
          <div class="row g-3">
            <div v-for="consultation in ongoingConsultations" :key="consultation.id" class="col-md-6 col-lg-4">
              <div class="consultation-card card h-100 border-start border-warning border-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0">
                      <i class="fas fa-user me-2 text-primary"></i>{{ consultation.patientName }}
                    </h6>
                    <span class="badge bg-warning text-dark">
                      <i class="fas fa-clock me-1"></i>In Progress
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
                    <p v-if="consultation.symptoms" class="card-text mb-2">
                      <strong>Symptoms:</strong> {{ truncateText(consultation.symptoms, 80) }}
                    </p>
                  </div>
                  
                  <div class="mt-3 d-flex gap-2">
                    <button 
                      @click="continueConsultation(consultation)"
                      class="btn btn-primary btn-sm flex-fill"
                    >
                      <i class="fas fa-arrow-right me-1"></i>Continue
                    </button>
                    <button 
                      @click="viewDetails(consultation)"
                      class="btn btn-outline-secondary btn-sm"
                      title="View Details"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
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
            <i class="fas fa-users fa-2x text-primary mb-2"></i>
            <h6>Active Consultations</h6>
            <h4 class="text-primary">{{ ongoingConsultations.length }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-body text-center">
            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
            <h6>Average Time</h6>
            <h4 class="text-warning">{{ calculateAverageTime() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-body text-center">
            <i class="fas fa-calendar-day fa-2x text-success mb-2"></i>
            <h6>Today's Total</h6>
            <h4 class="text-success">{{ todayTotal }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AuthService from '../../services/AuthService';
import { getTodayInMYT } from '../../utils/dateUtils';

export default {
  name: 'PatientConsultation',
  setup() {
    const router = useRouter();
    const ongoingConsultations = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const todayTotal = ref(0);

    const currentUser = computed(() => AuthService.getCurrentUser());
    const isDoctor = computed(() => AuthService.hasRole('ROLE_DOCTOR'));

    const fetchOngoingConsultations = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        // Fetch today's consultations and filter for ongoing ones
        const today = getTodayInMYT();
        const response = await axios.get(`/api/consultations?date=${today}`);
        
        let consultations = response.data || [];
        
        // Filter for ongoing consultations (not paid, in progress)
        let ongoing = consultations.filter(consultation => 
          !consultation.isPaid && consultation.status !== 'completed'
        );
        
        // If user is a doctor, filter by their consultations
        if (isDoctor.value && currentUser.value) {
          ongoing = ongoing.filter(consultation => 
            consultation.doctorName && (
              consultation.doctorName.toLowerCase().includes(currentUser.value.name.toLowerCase()) ||
              currentUser.value.name.toLowerCase().includes(consultation.doctorName.toLowerCase())
            )
          );
        }
        
        ongoingConsultations.value = ongoing;
        todayTotal.value = consultations.length;
        
        console.log('✅ Loaded ongoing consultations:', ongoing.length);
      } catch (err) {
        console.error('Error fetching ongoing consultations:', err);
        error.value = err.response?.data?.message || err.message || 'Failed to fetch patient consultations';
      } finally {
        loading.value = false;
      }
    };

    const continueConsultation = (consultation) => {
      router.push(`/consultations/${consultation.id}`);
    };

    const viewDetails = (consultation) => {
      router.push(`/consultations/${consultation.id}`);
    };

    const goToNewConsultation = () => {
      router.push('/consultations/form');
    };

    const refreshData = () => {
      fetchOngoingConsultations();
    };

    const formatDate = (date) => {
      if (!date) return 'N/A';
      try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          month: 'short',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    };

    const truncateText = (text, maxLength) => {
      if (!text) return 'N/A';
      if (text.length <= maxLength) return text;
      return text.substring(0, maxLength) + '...';
    };

    const calculateAverageTime = () => {
      if (!ongoingConsultations.value.length) return '0 min';
      
      const now = new Date();
      let totalMinutes = 0;
      
      ongoingConsultations.value.forEach(consultation => {
        const startTime = new Date(consultation.consultationDate || consultation.createdAt);
        const diffMs = now - startTime;
        const diffMinutes = Math.max(0, Math.floor(diffMs / (1000 * 60)));
        totalMinutes += diffMinutes;
      });
      
      const averageMinutes = Math.floor(totalMinutes / ongoingConsultations.value.length);
      
      if (averageMinutes < 60) {
        return `${averageMinutes} min`;
      } else {
        const hours = Math.floor(averageMinutes / 60);
        const minutes = averageMinutes % 60;
        return `${hours}h ${minutes}m`;
      }
    };

    onMounted(() => {
      fetchOngoingConsultations();
      
      // Auto-refresh every 30 seconds
      const interval = setInterval(fetchOngoingConsultations, 30000);
      
      // Cleanup interval on unmount
      return () => clearInterval(interval);
    });

    return {
      ongoingConsultations,
      loading,
      error,
      todayTotal,
      continueConsultation,
      viewDetails,
      goToNewConsultation,
      refreshData,
      formatDate,
      truncateText,
      calculateAverageTime
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