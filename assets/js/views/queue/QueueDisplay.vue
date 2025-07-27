<template>
  <div :class="['queue-display', { fullscreen: isFullscreen }]">
    <div class="container-fluid h-100">
      <!-- Digital Date & Clock -->
      <div class="clock-display text-center">
        <div class="clock-date">{{ currentDate }}</div>
        <span class="clock-time">{{ currentTime }}</span>
      </div>
      
      <!-- Dynamic Layout Based on Active Consultations -->
      <div v-if="activeConsultations.length === 0" class="row h-100">
        <!-- No Active Consultations - Show Single Row -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
          <div class="text-center">
            <h2 class="display-6 mb-4">Now Serving</h2>
            <div class="no-queue">
              <div class="queue-number display-1 fw-bold mb-3 text-muted">
                ---
              </div>
              <div class="patient-name h4 text-muted">
                No active consultation
              </div>
            </div>
          </div>
        </div>
        <!-- Right Column - Waiting Count -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-warning text-dark">
          <div class="text-center">
            <h2 class="display-6 mb-4">Waiting</h2>
            <div class="waiting-count display-1 fw-bold mb-3">
              {{ waitingCount }}
            </div>
            <div class="waiting-label h4">
              {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else-if="activeConsultations.length === 1" class="row h-100">
        <!-- Single Active Consultation - Original Layout -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
          <div class="text-center">
            <h2 class="display-6 mb-4">Now Serving</h2>
            <div class="current-queue-number">
              <div class="queue-number display-1 fw-bold mb-3">
                {{ formatQueueNumber(activeConsultations[0].queueNumber) }}
              </div>
              <div class="patient-name h4 mb-3">
                {{ getFormattedPatientName(activeConsultations[0].patient) }}
              </div>
              <div class="doctor-info">
                <div class="doctor-name h5 mb-1">
                  Dr. {{ getFormattedDoctorName(activeConsultations[0].doctor) }}
                </div>
                <div class="room-number h6 text-light">
                  {{ getDoctorRoom(activeConsultations[0].doctor) }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Right Column - Waiting Count -->
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-warning text-dark">
          <div class="text-center">
            <h2 class="display-6 mb-4">Waiting</h2>
            <div class="waiting-count display-1 fw-bold mb-3">
              {{ waitingCount }}
            </div>
            <div class="waiting-label h4">
              {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="multiple-consultations">
        <!-- Multiple Active Consultations - Stacked Layout -->
        <div v-for="(consultation, index) in activeConsultations" :key="consultation.id" 
             class="row consultation-row" 
             :class="{ 'mb-3': index < activeConsultations.length - 1 }">
          <div class="col-md-8 d-flex align-items-center justify-content-center bg-primary text-white">
            <div class="text-center">
              <h3 class="h4 mb-3">Now Serving</h3>
              <div class="current-queue-number">
                <div class="queue-number display-4 fw-bold mb-2">
                  {{ formatQueueNumber(consultation.queueNumber) }}
                </div>
                <div class="patient-name h5 mb-2">
                  {{ getFormattedPatientName(consultation.patient) }}
                </div>
                <div class="doctor-info">
                  <div class="doctor-name h6 mb-1">
                    Dr. {{ getFormattedDoctorName(consultation.doctor) }}
                  </div>
                  <div class="room-number small text-light">
                    {{ getDoctorRoom(consultation.doctor) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="index === 0" class="col-md-4 d-flex align-items-center justify-content-center bg-warning text-dark">
            <div class="text-center">
              <h3 class="h4 mb-3">Waiting</h3>
              <div class="waiting-count display-4 fw-bold mb-2">
                {{ waitingCount }}
              </div>
              <div class="waiting-label h6">
                {{ waitingCount === 1 ? 'Patient' : 'Patients' }}
              </div>
            </div>
          </div>
          <div v-else class="col-md-4"></div>
        </div>
      </div>
      
      
      <!-- Real-time Status Indicator -->
      <div class="realtime-status" :class="{ 'connected': eventSource && eventSource.readyState === 1, 'polling': pollingInterval }">
        <i class="fas fa-circle"></i>
        <span v-if="eventSource && eventSource.readyState === 1">Live</span>
        <span v-else-if="pollingInterval">Polling</span>
        <span v-else>Offline</span>
      </div>
      
      <button v-if="!isFullscreen" @click="enterFullscreen" class="fullscreen-btn">
        <i class="fas fa-expand"></i> Fullscreen
      </button>
      <button v-if="isFullscreen" @click="exitFullscreen" class="fullscreen-btn exit">
        <i class="fas fa-compress"></i> Exit Fullscreen
      </button>
      
      <!-- Sound Control Panel -->
      <div class="sound-controls" :class="{ 'sound-controls-fullscreen': isFullscreen }">
        <button @click="toggleSoundPanel" class="sound-toggle-btn" :title="soundPanelVisible ? 'Hide Sound Controls' : 'Show Sound Controls'">
          <i :class="soundService.isEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute'"></i>
        </button>
        
        <div v-if="soundPanelVisible" class="sound-panel">
          <div class="sound-panel-header">
            <h6><i class="fas fa-cog me-2"></i>Sound Settings</h6>
            <button @click="soundPanelVisible = false" class="btn-close-panel">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <div class="sound-panel-body">
            <!-- Audio Status -->
            <div class="sound-setting">
              <div class="sound-status" :class="{ 'status-ready': audioContextReady, 'status-waiting': !audioContextReady }">
                <i :class="audioContextReady ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                <span>{{ audioContextReady ? 'Audio Ready' : 'Click to activate audio' }}</span>
              </div>
            </div>
            
            <!-- Master Enable/Disable -->
            <div class="sound-setting">
              <label class="sound-label">
                <input type="checkbox" v-model="soundEnabled" @change="updateSoundEnabled">
                <span>Enable Sound Notifications</span>
              </label>
            </div>
            
            <!-- Volume Control -->
            <div class="sound-setting" v-if="soundEnabled">
              <label class="sound-label">Volume</label>
              <input type="range" v-model="volume" @input="updateVolume" min="0" max="100" class="sound-slider">
              <span class="sound-value">{{ volume }}%</span>
            </div>
            
            <!-- Text-to-Speech -->
            <div class="sound-setting" v-if="soundEnabled">
              <label class="sound-label">
                <input type="checkbox" v-model="textToSpeechEnabled" @change="updateTextToSpeech">
                <span>Voice Announcements</span>
              </label>
            </div>
            
            <!-- Speech Rate -->
            <div class="sound-setting" v-if="soundEnabled && textToSpeechEnabled">
              <label class="sound-label">Speech Speed</label>
              <input type="range" v-model="speechRate" @input="updateSpeechRate" min="0.5" max="2" step="0.1" class="sound-slider">
              <span class="sound-value">{{ speechRate }}x</span>
            </div>
            
            <!-- Test Button -->
            <div class="sound-setting" v-if="soundEnabled">
              <button @click="testSounds" class="btn btn-sm btn-outline-primary" :disabled="testingSound">
                <i class="fas fa-play me-1"></i>
                {{ testingSound ? 'Testing...' : 'Test Sounds' }}
              </button>
            </div>
            
            <!-- Manual Queue Call Test -->
            <div class="sound-setting" v-if="soundEnabled && audioContextReady">
              <button @click="testQueueCall" class="btn btn-sm btn-outline-success" :disabled="testingSound">
                <i class="fas fa-bullhorn me-1"></i>
                Test Queue Call
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { MALAYSIA_TIMEZONE } from '../../utils/timezoneUtils.js';
import soundService from '../../services/SoundService.js';
import requestDebouncer from '../../utils/requestDebouncer.js';
import sseMonitor from '../../utils/SSEMonitor.js';

export default {
  name: 'QueueDisplay',
  data() {
    return {
      queueList: [],
      doctorList: [],
      lastUpdated: new Date().toLocaleTimeString(),
      // Removed refreshInterval - using pure SSE instead of polling
      eventSource: null,
      pollingInterval: null,
      lastHeartbeat: null,
      heartbeatMonitor: null,
      isFullscreen: false,
      isLoading: false,
      currentTime: '',
      currentDate: '',
      
      // Sound-related data
      soundService: soundService,
      soundPanelVisible: false,
      soundEnabled: soundService.isEnabled,
      volume: Math.round(soundService.volume * 100),
      textToSpeechEnabled: soundService.textToSpeechEnabled,
      speechRate: soundService.speechRate,
      testingSound: false,
      audioContextReady: false,
      
      // Track previous active consultations for change detection
      previousActiveConsultations: [],
      
      // Announcement tracking
      lastAnnouncedConsultations: new Set()
    };
  },
  computed: {
    activeConsultations() {
      // Get all consultations that are currently in progress
      const inConsultation = this.queueList.filter(q => q.status === 'in_consultation');
      
      // Group by doctor to avoid duplicates and ensure one per doctor
      const byDoctor = {};
      inConsultation.forEach(consultation => {
        const doctorId = consultation.doctor?.id || 'unknown';
        if (!byDoctor[doctorId]) {
          byDoctor[doctorId] = consultation;
        }
      });
      
      // Return as array sorted by doctor name
      return Object.values(byDoctor).sort((a, b) => {
        const nameA = this.getFormattedDoctorName(a.doctor);
        const nameB = this.getFormattedDoctorName(b.doctor);
        return nameA.localeCompare(nameB);
      });
    },
    waitingQueue() {
      // Only count each queue entry once, regardless of group size
      return this.queueList.filter(q => q.status === 'waiting');
    },
    waitingCount() {
      // Count each group or single queue as 1
      return this.waitingQueue.length;
    },
    totalToday() {
      return this.queueList.length;
    },
    completedToday() {
      return this.queueList.filter(q => q.status === 'completed').length;
    }
  },
  created() {
    // Load data once on creation
    this.loadData();
    // Initialize SSE for real-time updates (no polling needed)
    this.initializeSSE();
  },
  mounted() {
    // Don't automatically enter fullscreen - let user choose
    // this.enterFullscreen();
    document.body.classList.add('queue-fullscreen');
    window.addEventListener('keydown', this.handleKeydown);
    
    // Add fullscreen change event listeners to sync state
    document.addEventListener('fullscreenchange', this.handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', this.handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', this.handleFullscreenChange);
    
    this.updateClock();
    this.clockInterval = setInterval(this.updateClock, 1000);
    
    // Check initial audio context state
    this.checkAudioContextState();
    
    // Add click listener to activate audio on any user interaction
    document.addEventListener('click', this.activateSoundSystem, { once: true });
  },
  beforeUnmount() {
    console.log('🔄 QueueDisplay: beforeUnmount - starting cleanup');
    
    // Cancel all pending requests
    requestDebouncer.cancelAll();
    
    // Use aggressive cleanup method
    this.cleanupAllSSEConnections();
    
    if (this.clockInterval) {
      clearInterval(this.clockInterval);
      this.clockInterval = null;
    }
    
    document.body.classList.remove('queue-fullscreen');
    window.removeEventListener('keydown', this.handleKeydown);
    
    // Remove fullscreen event listeners
    document.removeEventListener('fullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', this.handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', this.handleFullscreenChange);
    
    console.log('✅ QueueDisplay: beforeUnmount - cleanup completed');
  },
  methods: {
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '---';
      // Ensure string
      queueNumber = queueNumber.toString();
      // Pad to 4 digits (e.g., 8001 -> 8001, 801 -> 0801)
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    async loadQueueList() {
      // Use debounced request to prevent overlapping calls
      return requestDebouncer.debounce('queue-list', async (signal) => {
        this.isLoading = true;
        try {
          // Always request today's queue
          const now = new Date();
          const options = { timeZone: MALAYSIA_TIMEZONE, year: 'numeric', month: '2-digit', day: '2-digit' };
          const todayMYT = now.toLocaleDateString('en-CA', options); // YYYY-MM-DD
          const response = await axios.get(`/api/queue?date=${todayMYT}`, { 
            signal,
            timeout: 30000 // Increased to 30 seconds
          });
          console.log('Queue API response:', response);
        
          if (response.data && Array.isArray(response.data)) {
            this.queueList = response.data;
            this.lastUpdated = new Date().toLocaleTimeString();
            console.log('Queue list loaded:', this.queueList.length, 'items');
            return response.data;
          } else {
            console.error('Unexpected queue data format:', response.data);
            this.queueList = [];
            return [];
          }
        } catch (error) {
          console.error('Error loading queue:', error);
          this.queueList = [];
          throw error;
        } finally {
          this.isLoading = false;
        }
      }, 500); // 500ms debounce
    },
    async loadData() {
      await Promise.all([
        this.loadQueueList(),
        this.loadDoctors()
      ]);
    },
    async refreshData() {
      console.log('🔄 Manual refresh requested');
      await this.loadData();
    },
    async loadDoctors() {
      return requestDebouncer.debounce('doctors-list', async (signal) => {
        try {
          const response = await axios.get('/api/doctors', { 
            signal,
            timeout: 20000 // Increased to 20 seconds
          });
          console.log('Doctor API response:', response);
        
          if (response.data && Array.isArray(response.data)) {
            this.doctorList = response.data;
            console.log('Doctor list loaded:', this.doctorList.length, 'items');
            return response.data;
          } else {
            console.error('Unexpected doctor data format:', response.data);
            this.doctorList = [];
            return [];
          }
        } catch (error) {
          console.error('Error loading doctors:', error);
          this.doctorList = [];
          throw error;
        }
      }, 1000); // 1s debounce for doctors (less frequent changes)
    },
    getFormattedPatientName(patient) {
      if (!patient) return 'Unknown Patient';
      
      // For group consultations, show the main patient name only
      if (patient.isGroupConsultation && patient.groupPatients && Array.isArray(patient.groupPatients)) {
        // Find the main patient (relationship === 'self') or use the first patient
        const mainPatient = patient.groupPatients.find(p => p.relationship === 'self') || patient.groupPatients[0];
        if (mainPatient) {
          const mainName = mainPatient.displayName || mainPatient.name || 
                          (mainPatient.firstName && mainPatient.lastName ? `${mainPatient.firstName} ${mainPatient.lastName}` : null);
          if (mainName) {
            return `${mainName} (+${patient.groupPatients.length - 1} family)`;
          }
        }
      }
      
      // Handle different patient name formats for single patients
      if (patient.displayName) {
        return patient.displayName;
      }
      
      if (patient.firstName && patient.lastName) {
        return `${patient.firstName} ${patient.lastName}`;
      }
      
      if (patient.name) {
        return patient.name;
      }
      
      return 'Unknown Patient';
    },
    getFormattedDoctorName(doctor) {
      if (!doctor) return 'Unknown Doctor';
      
      // Handle different doctor name formats
      if (doctor.displayName) {
        return doctor.displayName;
      }
      
      if (doctor.firstName && doctor.lastName) {
        return `${doctor.firstName} ${doctor.lastName}`;
      }
      
      if (doctor.name) {
        return doctor.name;
      }
      
      return 'Unknown Doctor';
    },
    getDoctorRoom(doctor) {
      if (!doctor || !doctor.id) return 'Room TBA';
      
      // Find the doctor's position in the sorted doctor list to determine room number
      const sortedDoctors = [...this.doctorList].sort((a, b) => a.id - b.id);
      const doctorIndex = sortedDoctors.findIndex(d => d.id === doctor.id);
      
      if (doctorIndex !== -1) {
        return `Room ${doctorIndex + 1}`;
      }
      
      // Fallback: use doctor ID to determine room
      return `Room ${((doctor.id - 1) % 10) + 1}`;
    },
    cleanupAllSSEConnections() {
      console.log('🧹 QueueDisplay: Cleaning up ALL SSE connections');
      
      // Unregister from SSE monitor
      sseMonitor.unregister('queue-display');
      
      // Close current instance connection
      if (this.eventSource) {
        console.log('🧹 Closing current eventSource');
        this.eventSource.close();
        this.eventSource = null;
      }
      
      // Close global reference
      if (window._queueDisplayEventSource) {
        console.log('🧹 Closing global _queueDisplayEventSource');
        window._queueDisplayEventSource.close();
        window._queueDisplayEventSource = null;
      }
      
      // Kill any remaining EventSource connections via requestKiller
      if (window.requestKiller) {
        window.requestKiller.killAllEventSources();
      }
      
      // Clear any polling intervals
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval);
        this.pollingInterval = null;
      }
      
      if (this.heartbeatMonitor) {
        clearInterval(this.heartbeatMonitor);
        this.heartbeatMonitor = null;
      }
    },
    initializeSSE() {
      // Skip SSE if not supported or in development
      if (!window.EventSource) {
        console.warn('EventSource not supported, skipping SSE initialization');
        return;
      }
      
      // AGGRESSIVE CLEANUP: Close ANY existing connections first
      this.cleanupAllSSEConnections();
      
      // Initialize Server-Sent Events for real-time queue updates
      try {
        console.log('🔌 QueueDisplay: Initializing new SSE connection');
        this.eventSource = new EventSource('/api/sse/queue-updates');
        
        // Register with SSE monitor for tracking
        sseMonitor.register('queue-display', this.eventSource, 'QueueDisplay');
        
        // Store globally for emergency cleanup
        window._queueDisplayEventSource = this.eventSource;
        
        this.eventSource.onmessage = (event) => {
          try {
            const update = JSON.parse(event.data);
            
            if (update.type === 'refresh_needed') {
              console.log('🔄 SSE refresh signal received, updating queue data');
              // Refresh data when server indicates changes
              this.loadData().catch(error => {
                console.warn('Failed to refresh data after SSE signal:', error);
              });
            } else if (update.type === 'queue_status_update') {
              this.handleQueueUpdate(update.data);
            } else if (update.type === 'queue_count_update') {
              this.handleQueueCountUpdate(update.data);
            }
          } catch (error) {
            console.error('Error parsing SSE message:', error);
          }
        };
        
        this.eventSource.addEventListener('heartbeat', (event) => {
          // Just keep the connection alive
          console.log('Queue Display SSE heartbeat received');
          this.lastHeartbeat = Date.now();
        });
        
        this.eventSource.onerror = (error) => {
          console.warn('Queue Display SSE connection error:', error);
          
          if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
          }
          
          // Smart reconnection with exponential backoff
          const reconnectDelay = Math.min(1000 * Math.pow(2, this.reconnectAttempts || 0), 30000);
          this.reconnectAttempts = (this.reconnectAttempts || 0) + 1;
          
          console.log(`Attempting SSE reconnection in ${reconnectDelay}ms (attempt ${this.reconnectAttempts})`);
          
          setTimeout(() => {
            if (!this.eventSource && this.reconnectAttempts < 10) { // Max 10 attempts
              this.initializeSSE();
            }
          }, reconnectDelay);
        };
        
        this.eventSource.onopen = () => {
          console.log('Queue Display SSE connection established');
          this.reconnectAttempts = 0; // Reset on successful connection
          
          // Stop fallback polling since SSE is working
          if (this.pollingInterval) {
            console.log('SSE working, stopping fallback polling');
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
          }
          
          // Start heartbeat monitoring
          this.lastHeartbeat = Date.now();
          this.startHeartbeatMonitor();
        };
      } catch (error) {
        console.warn('SSE initialization failed, setting up fallback polling:', error);
        this.setupFallbackPolling();
      }
    },
    startHeartbeatMonitor() {
      if (this.heartbeatMonitor) {
        clearInterval(this.heartbeatMonitor);
      }
      
      // Check for heartbeat every 15 seconds
      this.heartbeatMonitor = setInterval(() => {
        const now = Date.now();
        const timeSinceHeartbeat = now - (this.lastHeartbeat || now);
        
        // If no heartbeat for 30 seconds, assume SSE is dead
        if (timeSinceHeartbeat > 30000) {
          console.warn('SSE heartbeat timeout, switching to fallback polling');
          if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
          }
          this.setupFallbackPolling();
          
          // Stop heartbeat monitoring
          clearInterval(this.heartbeatMonitor);
          this.heartbeatMonitor = null;
        }
      }, 15000);
    },
    setupFallbackPolling() {
      // Set up polling as fallback when SSE fails
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval);
      }
      
      console.log('Setting up fallback polling every 5 seconds');
      this.pollingInterval = setInterval(() => {
        console.log('🔄 Fallback polling refresh');
        this.loadData().catch(error => {
          console.warn('Fallback polling failed:', error);
        });
      }, 5000); // Poll every 5 seconds
    },
    handleQueueUpdate(queueData) {
      // Always refresh the list for any update to ensure consistency and avoid double refresh/blink
      this.loadData();
    },
    handleQueueCountUpdate(updateData) {
      console.log('Queue count update received:', updateData);
      
      // For patient registration or status changes, refresh the queue list
      if (updateData.action === 'patient_registered' || updateData.newStatus) {
        console.log('Patient registered or status changed, refreshing queue data');
        this.loadData();
      }
    },
    enterFullscreen() {
      this.isFullscreen = true;
      document.body.classList.add('queue-fullscreen');
      
      // Try browser fullscreen API
      const el = document.documentElement;
      try {
        if (el.requestFullscreen) {
          el.requestFullscreen().catch(err => {
            console.warn('Failed to enter fullscreen:', err);
            this.isFullscreen = false;
            document.body.classList.remove('queue-fullscreen');
          });
        } else if (el.webkitRequestFullscreen) {
          el.webkitRequestFullscreen();
        } else if (el.mozRequestFullScreen) {
          el.mozRequestFullScreen();
        } else if (el.msRequestFullscreen) {
          el.msRequestFullscreen();
        }
      } catch (error) {
        console.warn('Error entering fullscreen:', error);
        this.isFullscreen = false;
        document.body.classList.remove('queue-fullscreen');
      }
    },
    exitFullscreen() {
      this.isFullscreen = false;
      document.body.classList.remove('queue-fullscreen');
      
      // Check if document is actually in fullscreen mode before trying to exit
      if (document.fullscreenElement || document.webkitFullscreenElement || 
          document.mozFullScreenElement || document.msFullscreenElement) {
        try {
          if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => {
              console.warn('Failed to exit fullscreen:', err);
            });
          } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
          }
        } catch (error) {
          console.warn('Error exiting fullscreen:', error);
        }
      }
    },
    handleKeydown(e) {
      if (e.key === 'Escape' && this.isFullscreen) {
        this.exitFullscreen();
      }
    },
    handleFullscreenChange() {
      // Sync our internal state with the actual fullscreen state
      const isCurrentlyFullscreen = !!(
        document.fullscreenElement || 
        document.webkitFullscreenElement || 
        document.mozFullScreenElement || 
        document.msFullscreenElement
      );
      
      this.isFullscreen = isCurrentlyFullscreen;
      
      if (isCurrentlyFullscreen) {
        document.body.classList.add('queue-fullscreen');
      } else {
        document.body.classList.remove('queue-fullscreen');
      }
    },
    updateClock() {
      const now = new Date();
      // Asia/Kuala_Lumpur time for clock (12-hour with AM/PM)
      const timeOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Kuala_Lumpur'
      };
      this.currentTime = now.toLocaleTimeString('en-MY', timeOptions);
      // Asia/Kuala_Lumpur time for date
      const dateOptions = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        timeZone: 'Asia/Kuala_Lumpur'
      };
      this.currentDate = now.toLocaleDateString('en-MY', dateOptions);
    },
    getStatusClass(status) {
      const statusClasses = {
        'waiting': 'status-waiting',
        'in_consultation': 'status-consultation',
        'completed_consultation': 'status-completed-consultation',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled'
      };
      return statusClasses[status] || 'status-unknown';
    },
    toggleSoundPanel() {
      this.soundPanelVisible = !this.soundPanelVisible;
      // Activate sound system when user interacts with sound panel
      this.activateSoundSystem();
    },
    updateSoundEnabled() {
      this.soundService.isEnabled = this.soundEnabled;
    },
    updateVolume() {
      this.soundService.volume = this.volume / 100;
    },
    updateTextToSpeech() {
      this.soundService.textToSpeechEnabled = this.textToSpeechEnabled;
    },
    updateSpeechRate() {
      this.soundService.speechRate = this.speechRate;
    },
    async testSounds() {
      this.testingSound = true;
      try {
        // Ensure audio context is activated
        if (this.soundService.audioContext && this.soundService.audioContext.state === 'suspended') {
          await this.soundService.audioContext.resume();
        }
        
        await this.soundService.testSounds();
        
        // Also test a queue announcement
        setTimeout(async () => {
          await this.soundService.announceQueueCall(
            "A001",
            "Test Patient",
            "Test Doctor",
            "Room 1"
          );
        }, 2000);
        
      } catch (error) {
        console.error('Error testing sounds:', error);
      } finally {
        this.testingSound = false;
      }
    },
    
    /**
     * Check for new consultations and announce them
     */
    checkForNewConsultations() {
      const currentConsultations = this.activeConsultations;
      const previousConsultations = this.previousActiveConsultations;
      
      // Find newly started consultations
      const newConsultations = currentConsultations.filter(current => 
        !previousConsultations.some(previous => 
          previous.id === current.id && previous.status === 'in_consultation'
        )
      );
      
      // Announce new consultations
      newConsultations.forEach(consultation => {
        this.announceNewConsultation(consultation);
      });
      
      // Update previous consultations for next comparison
      this.previousActiveConsultations = [...currentConsultations];
    },
    
    /**
     * Announce a new consultation
     */
    async announceNewConsultation(consultation) {
      if (!this.soundService.isEnabled) return;
      
      const consultationKey = `${consultation.id}-${consultation.queueNumber}`;
      
      // Prevent duplicate announcements for the same consultation
      if (this.lastAnnouncedConsultations.has(consultationKey)) {
        return;
      }
      
      this.lastAnnouncedConsultations.add(consultationKey);
      
      // Clean up old announcements (keep only last 10)
      if (this.lastAnnouncedConsultations.size > 10) {
        const announcements = Array.from(this.lastAnnouncedConsultations);
        this.lastAnnouncedConsultations.clear();
        announcements.slice(-5).forEach(key => this.lastAnnouncedConsultations.add(key));
      }
      
      try {
        const queueNumber = consultation.queueNumber;
        const patientName = this.getFormattedPatientName(consultation.patient);
        const doctorName = this.getFormattedDoctorName(consultation.doctor);
        const roomNumber = this.getDoctorRoom(consultation.doctor);
        
        console.log('🔊 Announcing new consultation:', {
          queueNumber,
          patientName,
          doctorName,
          roomNumber
        });
        
        await this.soundService.announceQueueCall(
          queueNumber,
          patientName,
          doctorName,
          roomNumber
        );
      } catch (error) {
        console.warn('⚠️ Error announcing consultation:', error);
      }
    },
    
    /**
     * Save sound settings to localStorage
     */
    saveSoundSettings() {
      this.soundService.saveSettings();
    },
    /**
     * Activate sound system on user interaction
     */
    async activateSoundSystem() {
      if (this.soundService.audioContext && this.soundService.audioContext.state === 'suspended') {
        try {
          await this.soundService.audioContext.resume();
          console.log('🔊 Audio context activated');
          this.audioContextReady = true;
        } catch (error) {
          console.warn('⚠️ Failed to activate audio context:', error);
        }
      } else if (this.soundService.audioContext && this.soundService.audioContext.state === 'running') {
        this.audioContextReady = true;
      }
    },
    checkAudioContextState() {
      if (this.soundService.audioContext) {
        this.audioContextReady = this.soundService.audioContext.state === 'running';
        console.log('🔊 Audio context state:', this.soundService.audioContext.state);
      } else {
        this.audioContextReady = false;
        console.log('⚠️ No audio context available');
      }
    },
    async testQueueCall() {
      try {
        await this.soundService.announceQueueCall(
          "A001",
          "Test Patient",
          "Test Doctor",
          "Room 1"
        );
        console.log('🔊 Queue call test successful');
      } catch (error) {
        console.error('⚠️ Error testing queue call:', error);
      }
    }
  },
  
  watch: {
    // Watch for changes in active consultations to trigger sound notifications
    activeConsultations: {
      handler(newConsultations, oldConsultations) {
        // Only check for new consultations if we have previous data
        if (oldConsultations && oldConsultations.length >= 0) {
          this.checkForNewConsultations();
        }
      },
      deep: true
    },
    
    // Save sound settings when they change
    soundEnabled() {
      this.saveSoundSettings();
    },
    volume() {
      this.saveSoundSettings();
    },
    textToSpeechEnabled() {
      this.saveSoundSettings();
    },
    speechRate() {
      this.saveSoundSettings();
    }
  }
};
</script>

<style scoped>
.queue-display {
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(90deg, #007bff 50%, #ffc107 50%);
}

.container-fluid {
  height: 100%;
}

.row {
  margin: 0;
}

.col-md-6 {
  padding: 2rem;
  min-height: calc(100vh - 60px); /* Account for status bar */
}

.queue-number {
  font-size: 8rem !important;
  line-height: 1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.patient-name {
  font-weight: 500;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.doctor-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid rgba(255, 255, 255, 0.3);
}

.doctor-name {
  font-weight: 600;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.room-number {
  font-weight: 500;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
  opacity: 0.9;
}

.waiting-count {
  font-size: 8rem !important;
  line-height: 1;
  color: #856404 !important;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.waiting-label {
  font-weight: 500;
  color: #856404 !important;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.no-queue .queue-number {
  color: rgba(255, 255, 255, 0.6) !important;
}

.status-bar {
  z-index: 1000;
  height: 60px;
  display: flex;
  align-items: center;
}

.bg-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-warning {
  background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

@media (max-width: 768px) {
  .col-md-6 {
    min-height: 50vh;
    padding: 1rem;
  }
  
  .queue-number,
  .waiting-count {
    font-size: 4rem !important;
  }
  
  .display-6 {
    font-size: 2rem !important;
  }
  
  .h4 {
    font-size: 1.2rem !important;
  }
  
  .h5 {
    font-size: 1.1rem !important;
  }
  
  .h6 {
    font-size: 1rem !important;
  }
}

/* Fullscreen styles */
body.queue-fullscreen {
  overflow: hidden;
}

.queue-display.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  background: linear-gradient(90deg, #007bff 50%, #ffc107 50%);
}

.fullscreen-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 10001;
  background: rgba(0,0,0,0.2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1.2rem;
  cursor: pointer;
  transition: background 0.2s;
}

.fullscreen-btn.exit {
  right: 180px;
  background: rgba(0,0,0,0.4);
}


.realtime-status {
  position: absolute;
  top: 20px;
  right: 220px;
  z-index: 10001;
  background: rgba(108, 117, 125, 0.8);
  color: #fff;
  border-radius: 20px;
  padding: 8px 12px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.realtime-status.connected {
  background: rgba(40, 167, 69, 0.8);
}

.realtime-status.connected .fa-circle {
  color: #28a745;
  animation: pulse 2s infinite;
}

.realtime-status.polling {
  background: rgba(255, 193, 7, 0.8);
}

.realtime-status.polling .fa-circle {
  color: #ffc107;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}

.clock-display {
  width: 100vw;
  padding-top: 2rem;
  padding-bottom: 1.5rem;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10000;
  pointer-events: none;
}
.clock-date {
  font-size: 2.2rem;
  font-weight: 600;
  color: #fff;
  text-shadow: 2px 2px 8px rgba(0,0,0,0.18);
  margin-bottom: 0.2em;
  letter-spacing: 0.04em;
}
.clock-time {
  font-size: 3.5rem;
  font-weight: 700;
  color: #fff;
  text-shadow: 2px 2px 8px rgba(0,0,0,0.25);
  letter-spacing: 0.1em;
}
.queue-display.fullscreen .clock-display {
  position: fixed;
}

.status-completed-consultation {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: bold;
  animation: pulse 2s infinite;
}

.status-completed {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  font-weight: bold;
}

/* Multiple Consultations Layout */
.multiple-consultations {
  padding-top: 8rem; /* Account for clock */
  height: calc(100vh - 8rem);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.consultation-row {
  margin: 0;
  flex: 1;
  min-height: 200px;
}

.consultation-row .col-md-8 {
  padding: 1.5rem;
  min-height: 200px;
}

.consultation-row .col-md-4 {
  padding: 1.5rem;
  min-height: 200px;
}

.multiple-consultations .queue-number {
  font-size: 5rem !important;
  line-height: 1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.multiple-consultations .waiting-count {
  font-size: 5rem !important;
  line-height: 1;
  color: #856404 !important;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
  .multiple-consultations {
    padding-top: 6rem;
    height: calc(100vh - 6rem);
  }
  
  .consultation-row .col-md-8,
  .consultation-row .col-md-4 {
    padding: 1rem;
    min-height: 150px;
  }
  
  .multiple-consultations .queue-number,
  .multiple-consultations .waiting-count {
    font-size: 3rem !important;
  }
  
  .multiple-consultations .h4 {
    font-size: 1.1rem !important;
  }
  
  .multiple-consultations .h5 {
    font-size: 1rem !important;
  }
  
  .multiple-consultations .h6 {
    font-size: 0.9rem !important;
  }
}

/* Sound Control Panel Styles */
.sound-controls {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10001;
  background: rgba(0,0,0,0.7);
  border-radius: 8px;
  padding: 0.5rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

.sound-controls.sound-controls-fullscreen {
  top: 20px;
  right: 20px;
}

.sound-toggle-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.sound-toggle-btn:hover {
  background: rgba(255,255,255,0.1);
}

.sound-panel {
  position: absolute;
  top: 100%;
  right: 0;
  width: 280px;
  max-height: 400px;
  background: rgba(0,0,0,0.9);
  padding: 1rem;
  border-radius: 8px;
  margin-top: 0.5rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.sound-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.sound-panel-header h6 {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.btn-close-panel {
  background: none;
  border: none;
  color: #fff;
  font-size: 1rem;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.btn-close-panel:hover {
  background: rgba(255,255,255,0.1);
}

.sound-panel-body {
  color: #fff;
}

.sound-setting {
  margin-bottom: 1rem;
}

.sound-label {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  cursor: pointer;
}

.sound-label input[type="checkbox"] {
  margin-right: 0.5rem;
}

.sound-label span {
  flex: 1;
}

.sound-slider {
  width: 100%;
  margin: 0.5rem 0;
}

.sound-value {
  font-size: 0.8rem;
  color: #ccc;
  float: right;
}

.sound-setting .btn {
  width: 100%;
  font-size: 0.85rem;
}

.sound-status {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
}

.sound-status.status-ready {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.sound-status.status-waiting {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.sound-status i {
  margin-right: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .sound-controls {
    top: 10px;
    right: 10px;
  }
  
  .sound-panel {
    width: 250px;
    max-height: 350px;
  }
  
  .sound-panel-header h6 {
    font-size: 0.9rem;
  }
  
  .sound-label {
    font-size: 0.8rem;
  }
}
</style> 