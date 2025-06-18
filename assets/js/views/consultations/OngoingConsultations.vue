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
           <h5>No Patients Waiting</h5>
           <p class="text-muted">No patients are currently waiting for consultation.</p>
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
                    <p v-if="consultation.symptoms" class="card-text mb-2">
                      <strong>Symptoms:</strong> {{ truncateText(consultation.symptoms, 80) }}
                    </p>
                  </div>
                  
                  <div class="mt-3">
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
    const isSuperAdmin = computed(() => AuthService.hasRole('ROLE_SUPER_ADMIN'));

    const fetchOngoingConsultations = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const today = getTodayInMYT();
        
        // Fetch both queue and consultations data
        console.log('🔍 Fetching data for date:', today);
        
        let queueResponse, consultationResponse;
        
        try {
          // Try with date filter first
          [queueResponse, consultationResponse] = await Promise.all([
            axios.get(`/api/queue?date=${today}`),
            axios.get(`/api/consultations?date=${today}`)
          ]);
        } catch (err) {
          console.error('❌ Error with date filter, trying without date:', err.response?.data || err.message);
          
          // Fallback: try without date filter to see if there's any data
          try {
            [queueResponse, consultationResponse] = await Promise.all([
              axios.get('/api/queue'),
              axios.get('/api/consultations')
            ]);
            console.log('✅ Fallback successful - fetched data without date filter');
          } catch (fallbackErr) {
            console.error('❌ Fallback also failed:', fallbackErr.response?.data || fallbackErr.message);
            throw fallbackErr;
          }
        }
        
        let consultations = consultationResponse.data || [];
        let queueList = queueResponse.data || [];
        
        console.log('📊 Raw API responses:', {
          consultationsCount: consultations.length,
          queueCount: queueList.length,
          consultationsSample: consultations.slice(0, 2),
          queueSample: queueList.slice(0, 2)
        });
        
        // Filter for ongoing consultations (not paid, in progress)
        let ongoing = consultations.filter(consultation => 
          !consultation.isPaid && consultation.status !== 'completed'
        );
        
        // Get patients from queue for this doctor (both waiting and in consultation)
        let queuePatients = queueList.filter(queue => 
          queue.status === 'waiting' || queue.status === 'in_consultation'
        );
        
        console.log('🔍 After initial filtering:', {
          ongoingCount: ongoing.length,
          queuePatientsCount: queuePatients.length,
          queueStatuses: queuePatients.map(q => ({ id: q.id, status: q.status, patient: q.patient?.name }))
        });
        
        // Filter by user role: Super Admin sees all, Doctors see only their assignments
        if (isSuperAdmin.value) {
          // Super Admin sees all patients and consultations - no filtering needed
          console.log('👑 Super Admin access - showing all data');
        } else if (isDoctor.value && currentUser.value) {
          // Doctor sees only their assigned patients and consultations
          console.log('👨‍⚕️ Doctor access - filtering by doctor assignments');
          ongoing = ongoing.filter(consultation => 
            consultation.doctorName && (
              consultation.doctorName.toLowerCase().includes(currentUser.value.name.toLowerCase()) ||
              currentUser.value.name.toLowerCase().includes(consultation.doctorName.toLowerCase())
            )
          );
          
          queuePatients = queuePatients.filter(queue => 
            queue.doctor && queue.doctor.name && (
              queue.doctor.name.toLowerCase().includes(currentUser.value.name.toLowerCase()) ||
              currentUser.value.name.toLowerCase().includes(queue.doctor.name.toLowerCase())
            )
          );
                 } else {
           // Other roles (like assistants) see no consultation data
           console.log('👤 Other role access - no consultation data visible');
           ongoing = [];
           queuePatients = [];
         }
        
        // Convert queue entries to consultation-like format
        const queueConsultations = queuePatients.map(queue => ({
          id: `queue_${queue.id}`,
          isQueueEntry: true,
          queueId: queue.id,
          patientId: queue.patient ? queue.patient.id : null,
          doctorId: queue.doctor ? queue.doctor.id : null,
          patientName: queue.patient ? queue.patient.name : 'Unknown Patient',
          doctorName: queue.doctor ? queue.doctor.name : 'Unknown Doctor',
          consultationDate: queue.queueDateTime,
          queueNumber: queue.queueNumber,
          status: queue.status, // Use actual queue status (waiting or in_consultation)
          symptoms: queue.status === 'waiting' ? 'Waiting for consultation' : 'Consultation in progress',
          isPaid: false
        }));
        
        // Combine queue patients and ongoing consultations
        ongoingConsultations.value = [...queueConsultations, ...ongoing];
        todayTotal.value = consultations.length + queueList.length;
        
        console.log('✅ Loaded patient data:', {
          queuePatients: queueConsultations.length,
          ongoing: ongoing.length,
          total: ongoingConsultations.value.length,
          userRole: {
            name: currentUser.value?.name,
            isDoctor: isDoctor.value,
            isSuperAdmin: isSuperAdmin.value,
            roles: currentUser.value?.roles
          },
          dataFiltering: {
            originalQueue: queueList.length,
            filteredQueue: queuePatients.length,
            originalConsultations: consultations.length,
            filteredOngoing: ongoing.length
          },
          finalData: ongoingConsultations.value.map(c => ({
            id: c.id,
            patient: c.patientName,
            doctor: c.doctorName,
            status: c.status,
            isQueueEntry: c.isQueueEntry
          }))
        });
      } catch (err) {
        console.error('Error fetching patient consultations:', err);
        error.value = err.response?.data?.message || err.message || 'Failed to fetch patient consultations';
      } finally {
        loading.value = false;
      }
    };

    const startConsultation = async (consultation) => {
      try {
        // Update queue status to 'in_consultation'
        await axios.put(`/api/queue/${consultation.queueId}/status`, { status: 'in_consultation' });
        
        // Navigate to new consultation form with queue information
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
        // For queue entries, navigate to consultation form with queue information
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
        // For actual consultation records, navigate to the consultation details
        router.push(`/consultations/${consultation.id}`);
      }
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
      startConsultation,
      continueConsultation,
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