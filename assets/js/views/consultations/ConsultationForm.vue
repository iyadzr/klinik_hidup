<template>
  <div>
    <!-- Sticky Patient Header Bar -->
    <div class="patient-header-bar sticky-patient-topbar shadow-lg">
      <div class="container-fluid">
        <div class="row align-items-center py-3">
          <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-between">
              <!-- Patient Info Section -->
              <div class="d-flex align-items-center flex-grow-1">
                <div class="patient-avatar me-3">
                  <i class="fas fa-user-circle fa-3x text-white"></i>
                </div>
                <div class="patient-summary flex-grow-1">
                  <div class="d-flex align-items-center gap-3 mb-1">
                    <h4 class="mb-0 patient-name text-white fw-bold">{{ getPatientName(consultation.patientId) }}</h4>
                    <span class="badge bg-light text-dark fw-bold">{{ selectedPatient?.gender || 'N/A' }}, {{ calculatePatientAge() }} years</span>
                    <span class="badge bg-warning text-dark fw-bold">{{ selectedPatient?.nric || selectedPatient?.ic || 'No IC' }}</span>
                  </div>
                  <div class="patient-meta text-white small d-flex gap-4">
                    <span v-if="$route.query.queueNumber">
                      <i class="fas fa-list-ol me-1"></i>Queue #{{ formatQueueNumber($route.query.queueNumber) }}
                    </span>
                    <span>
                      <i class="fas fa-phone me-1"></i>{{ selectedPatient?.phone || selectedPatient?.phoneNumber || 'No phone' }}
                    </span>
                    <span>
                      <i class="fas fa-map-marker-alt me-1"></i>{{ selectedPatient?.address || 'No address' }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Enhanced Group Patient Selector -->
              <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="patient-selector ms-3">
                <div class="dropdown">
                  <button class="btn btn-warning btn-lg dropdown-toggle shadow-sm fw-bold patient-select-btn" 
                          type="button" 
                          data-bs-toggle="dropdown"
                          style="border-radius: 25px; padding: 0.75rem 1.5rem; animation: pulse 2s infinite;">
                    <i class="fas fa-user-friends me-2"></i>
                    <span class="d-none d-sm-inline">🔄 Switch Patient </span>
                    <span class="badge bg-white text-warning ms-2 px-2 py-1">
                      {{ groupPatients.findIndex(p => p.id === selectedPatient?.id) + 1 }}/{{ groupPatients.length }}
                    </span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 patient-dropdown" style="min-width: 350px; border-radius: 15px; max-height: 400px; overflow-y: auto; z-index: 1050;">
                    <li class="px-3 py-2 bg-light border-bottom">
                      <div class="text-center">
                        <small class="text-muted fw-bold">
                          <i class="fas fa-users me-1"></i>
                          GROUP CONSULTATION - SELECT PATIENT
                        </small>
                      </div>
                    </li>
                    <li v-for="(patient, index) in groupPatients" :key="patient.id" class="patient-option">
                      <a class="dropdown-item patient-item py-3" 
                         href="#" 
                         @click.prevent.stop="handlePatientSwitch(patient)" 
                         :class="{ 'active': patient.id === selectedPatient?.id }">
                        <div class="d-flex align-items-center">
                          <div class="patient-avatar me-3">
                            <i class="fas fa-user-circle fa-2x" 
                               :class="patient.id === selectedPatient?.id ? 'text-warning' : 'text-primary'"></i>
                          </div>
                          <div class="patient-info flex-grow-1">
                            <div class="patient-name fw-bold mb-1">
                              {{ patient.name || patient.displayName }}
                              <i v-if="patient.id === selectedPatient?.id" class="fas fa-check-circle text-success ms-2"></i>
                            </div>
                            <div class="patient-details">
                              <small class="text-muted">
                                <span class="badge bg-info me-1">{{ patient.relationship || 'N/A' }}</span>
                                <span class="me-2">{{ patient.gender }}, {{ calculateAge(patient.dateOfBirth) }} years</span>
                                <br>
                                <i class="fas fa-id-card me-1"></i>{{ patient.nric || 'No IC' }}
                              </small>
                            </div>
                          </div>
                          <div class="patient-status">
                            <span v-if="patient.id === selectedPatient?.id" 
                                  class="badge bg-success">
                              Current
                            </span>
                            <span v-else class="badge bg-secondary">
                              Switch
                            </span>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li class="border-top">
                      <div class="px-3 py-2 text-center">
                        <small class="text-muted">
                          <i class="fas fa-info-circle me-1"></i>
                          Click on any patient to switch consultation
                        </small>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="consultation-form consultation-dashboard glass-card shadow p-4 mt-4 mb-4">

      <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Consultation</h2>
        <small v-if="$route.query.queueNumber" class="text-muted">
          <i class="fas fa-link me-1"></i>Started from Queue #{{ $route.query.queueNumber ? ($route.query.queueNumber.toString().length === 4 ? $route.query.queueNumber : $route.query.queueNumber.toString().length === 3 ? '0' + $route.query.queueNumber : $route.query.queueNumber.toString().padStart(4, '0')) : '' }}
        </small>
      </div>
    </div>

    <form @submit.prevent="saveConsultation" class="row g-4">


      <!-- Pre-Informed Illness - First Row (Full Width) -->
      <div class="col-12">
        <div class="card section-card dashboard-card mb-4">
          <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
            <h5 class="mb-0 d-flex align-items-center">
              <i class="fas fa-clipboard-check text-warning me-2"></i>
              Pre-Informed Illness
            </h5>
          </div>
          <div class="card-body">
            <div v-if="selectedPatient && selectedPatient.preInformedIllness && selectedPatient.preInformedIllness.trim()">
              <div class="pre-illness-content p-3 bg-light rounded">
                <div class="d-flex align-items-start gap-3">
                  <div>
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                  </div>
                  <div class="flex-grow-1">
                    <p class="mb-0 text-dark">{{ selectedPatient.preInformedIllness }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted py-4">
              <i class="fas fa-clipboard fa-2x mb-2"></i>
              <div>No pre-informed illness data available</div>
              <small class="text-muted">Patient did not provide initial symptoms during registration</small>
            </div>
          </div>
        </div>
      </div>



      <!-- Medical History Section - Third Row (Full Width) -->
      <div class="col-12">
        <div class="card section-card dashboard-card mb-4">
          <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">
            <h5 class="mb-0 d-flex align-items-center">
              <i class="fas fa-history text-secondary me-2"></i>
              Medical History
              <span v-if="visitHistories?.length" class="badge bg-secondary ms-2">{{ visitHistories.length }} visits</span>
            </h5>
          </div>
          <div class="card-body">
            <div v-if="visitHistories && visitHistories.length > 0" class="history-list">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="visit in visitHistories" 
                    :key="visit.id" 
                    class="history-item"
                    @click="showVisitDetails(visit)"
                    style="cursor: pointer;"
                  >
                    <td>{{ formatDateOfBirth(visit.consultationDate) }}</td>
                    <td>Dr. {{ visit.doctor?.name || 'Unknown' }}</td>
                    <td>{{ visit.diagnosis || 'No diagnosis recorded' }}</td>
                    <td>
                      <span :class="getStatusClass(visit.status)">
                        {{ visit.status || 'Completed' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center text-muted py-4">
              <i class="fas fa-file-medical-alt fa-2x mb-2"></i>
              <div>No medical history found</div>
              <small class="text-muted">This is the patient's first visit</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Remarks/Diagnosis -->
      <div class="col-12">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-notes-medical text-primary me-2"></i>
              Remarks/Diagnosis
            </h5>
            <div class="row g-4">
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="remark" v-model="consultation.notes" style="height: 100px" required></textarea>
                  <label for="remark">Remarks/Diagnosis</label>
                </div>
              </div>
              <!-- Enhanced Medication Prescription Section -->
              <div class="col-md-12">
                <h6 class="fw-bold mb-3">
                  <i class="fas fa-pills me-2"></i>
                  Prescribed Medications
                </h6>
                
                <!-- Add Medication Button -->
                <div class="mb-3">
                  <button type="button" class="btn btn-outline-primary btn-sm" @click="addMedicationRow">
                    <i class="fas fa-plus me-1"></i>Add Medication
                  </button>
                </div>

                <!-- Medication List -->
                <div v-for="(medItem, index) in prescribedMedications" :key="index" class="medication-row mb-3 p-3 border rounded">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="form-floating">
                        <input
                          type="text"
                          class="form-control"
                          :id="`medication-${index}`"
                          v-model="medItem.name"
                          @input="searchMedications(medItem, $event)"
                          @blur="handleMedicationBlur(medItem)"
                          @keydown.enter.prevent="selectFirstSuggestion(medItem)"
                          @keydown.escape="clearSuggestions(medItem)"
                          placeholder="Type to search medications..."
                          autocomplete="off"
                        >
                        <label :for="`medication-${index}`">Medication Name</label>
                        
                        <!-- Medication Suggestions Dropdown with Pagination -->
                        <div v-if="medItem.allSuggestions && medItem.allSuggestions.length > 0" class="medication-suggestions">
                          <div class="suggestions-header d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fas fa-search me-1"></i>Select from existing medications:</small>
                            <small class="text-muted">{{ medItem.allSuggestions.length }} results</small>
                          </div>
                          
                          <!-- Paginated Suggestions -->
                          <div class="suggestion-item" v-for="(suggestion, sIndex) in medItem.paginatedSuggestions" :key="sIndex"
                               @click="selectMedication(medItem, suggestion)"
                               :class="{ active: sIndex === medItem.selectedSuggestionIndex }">
                            <div class="suggestion-main">
                              <strong>{{ suggestion.name }}</strong>
                              <span class="badge bg-secondary ms-2">{{ suggestion.category || 'General' }}</span>
                            </div>
                            <div class="suggestion-details">
                              <small class="text-muted">
                                {{ suggestion.unitDescription || suggestion.unitType || 'Unit not specified' }}
                                <span v-if="suggestion.sellingPrice" class="text-success ms-2">
                                  <i class="fas fa-tag"></i> RM {{ parseFloat(suggestion.sellingPrice).toFixed(2) }}
                                </span>
                              </small>
                            </div>
                          </div>
                          
                          <!-- Pagination Controls -->
                          <div v-if="medItem.totalPages > 1" class="suggestions-pagination">
                            <div class="d-flex justify-content-between align-items-center py-2 px-3 border-top">
                              <small class="text-muted">
                                Page {{ medItem.currentPage }} of {{ medItem.totalPages }}
                              </small>
                              <div class="btn-group btn-group-sm">
                                <button 
                                  type="button" 
                                  class="btn btn-outline-secondary btn-sm"
                                  :disabled="medItem.currentPage === 1"
                                  @click="changeMedicationPage(medItem, medItem.currentPage - 1)"
                                >
                                  <i class="fas fa-chevron-left"></i>
                                </button>
                                <button 
                                  type="button" 
                                  class="btn btn-outline-secondary btn-sm"
                                  :disabled="medItem.currentPage === medItem.totalPages"
                                  @click="changeMedicationPage(medItem, medItem.currentPage + 1)"
                                >
                                  <i class="fas fa-chevron-right"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                          
                          <!-- Create new medication option - Always show if there's a name and no exact match -->
                          <div v-if="medItem.name && medItem.name.length >= 2 && !medItem.allSuggestions.some(s => s.name.toLowerCase() === medItem.name.toLowerCase())" 
                               class="suggestion-item suggestion-create-new"
                               @click="showCreateMedicationModal(medItem)">
                            <div class="suggestion-main">
                              <i class="fas fa-plus text-success me-2"></i>
                              <strong>Create new: "{{ medItem.name }}"</strong>
                            </div>
                            <small class="text-muted">Add this medication to the database</small>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-3">
                      <div class="form-floating">
                        <input type="number" class="form-control" :id="`quantity-${index}`" v-model.number="medItem.quantity" min="1" required>
                        <label :for="`quantity-${index}`">Qty</label>
                      </div>
                      <small class="text-muted">{{ medItem.unitDescription || medItem.unitType || 'pieces' }}</small>
                    </div>
                    
                    <div class="col-md-2">
                      <button type="button" class="btn btn-outline-danger btn-sm h-100" @click="removeMedicationRow(index)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                    
                    <div class="col-md-12" v-if="medItem.showInstructions">
                      <div class="form-floating">
                        <textarea class="form-control" :id="`instructions-${index}`" v-model="medItem.instructions" style="height: 60px" placeholder="Dosage instructions...">
                        </textarea>
                        <label :for="`instructions-${index}`">Instructions</label>
                      </div>
                    </div>
                    
                    <div class="col-md-12">
                      <button type="button" class="btn btn-link btn-sm p-0" @click="medItem.showInstructions = !medItem.showInstructions">
                        {{ medItem.showInstructions ? 'Hide' : 'Add' }} Instructions
                      </button>
                    </div>


                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MC Checkbox and Dates: visible for doctor, after remarks/diagnosis -->
      <div class="col-12">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-file-medical text-primary me-2"></i>
              Medical Certificate
            </h5>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="hasMedicalCertificate" v-model="consultation.hasMedicalCertificate" @change="onMCCheckboxChange">
                <label class="form-check-label fw-bold" for="hasMedicalCertificate">
                  Issue Medical Certificate (MC) for this visit
                </label>
              </div>
              <button type="button" class="btn btn-outline-info btn-sm" @click="showMCPreview" v-if="consultation.hasMedicalCertificate && selectedPatient" :disabled="!consultation.mcStartDate || !consultation.mcEndDate">
                <i class="fas fa-eye me-1"></i> Review MC
              </button>
            </div>
            <div v-if="consultation.hasMedicalCertificate">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" v-model="consultation.mcStartDate" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">End Date</label>
                  <input type="date" class="form-control" v-model="consultation.mcEndDate" required>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden MC Print Template for Multiple Patients -->
      <div id="mc-print-content" style="display:none">
        <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1">
          <div v-for="patient in groupPatients" :key="patient?.id || 'print-patient-' + Math.random()" v-if="patient && patient.id && mcSelectedPatientIds.includes(patient.id)">
            <div style="background-color: #e8e5b0; padding: 20px; border: 1px solid #000; font-family: Arial, sans-serif; page-break-after: always;">
              <!-- Clinic Header -->
              <div style="text-align: center; margin-bottom: 15px;">
                <h3 style="margin-bottom: 0; font-weight: bold;">KLINIK HIDUPsihat</h3>
                <p style="margin-bottom: 5px;">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
                <p style="margin-bottom: 5px;">Tel: 03-8740 0678</p>
              </div>
              
              <!-- MC Running Number -->
              <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                <div>
                  <p style="margin-bottom: 0; font-size: 0.9rem; color: #a52a2a;">No: {{ consultation.mcRunningNumber || '426740' }}</p>
                </div>
              </div>
              
              <h4 style="text-align: center; margin-bottom: 20px;">SURAT AKUAN SAKIT (MC)</h4>
              
              <div style="display: flex; justify-content: space-between;">
                <p>Saya mengesahkan telah memeriksa;</p>
                <p><strong>Tarikh:</strong> {{ formatDate(new Date()) }}</p>
              </div>
              <p style="margin-left: 15px; margin-bottom: 5px;"><strong>Nama dan No KP:</strong> {{ patient.name || patient.displayName || 'Unknown' }} ({{ patient.nric || patient.ic || '******' }})</p>
              <p style="margin-left: 15px; margin-bottom: 20px;"><strong>dari:</strong> {{ patient.company || 'yang berkenaan' }}</p>
              
              <p>Beliau didapati tidak sihat dan tidak dapat menjalankan tugas selama</p>
              <p style="margin-left: 15px; margin-bottom: 15px;">
                <strong>{{ calculateMCDaysForPatient(patient.id) }}</strong> hari mulai <strong>{{ formatDate(patientConsultationData[patient.id]?.mcStartDate) }}</strong> sehingga <strong>{{ formatDate(patientConsultationData[patient.id]?.mcEndDate) }}</strong>
              </p>
              
              <div style="display: flex; justify-content: flex-end; margin-top: 40px;">
                <div style="text-align: center;">
                  <!-- Signature Line -->
                  <div style="border-bottom: 1px dotted #000; width: 150px; margin-bottom: 10px;">&nbsp;</div>
                  <p style="margin-bottom: 0;">Tandatangan</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else>
          <div style="background-color: #e8e5b0; padding: 20px; border: 1px solid #000; font-family: Arial, sans-serif;">
            <!-- Clinic Header -->
            <div style="text-align: center; margin-bottom: 15px;">
              <h3 style="margin-bottom: 0; font-weight: bold;">KLINIK HIDUPsihat</h3>
              <p style="margin-bottom: 5px;">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
              <p style="margin-bottom: 5px;">Tel: 03-8740 0678</p>
            </div>
            
            <!-- MC Running Number -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
              <div>
                <p style="margin-bottom: 0; font-size: 0.9rem; color: #a52a2a;">No: {{ consultation.mcRunningNumber || '426740' }}</p>
              </div>
            </div>
            
            <h4 style="text-align: center; margin-bottom: 20px;">SURAT AKUAN SAKIT (MC)</h4>
            
            <div style="display: flex; justify-content: space-between;">
              <p>Saya mengesahkan telah memeriksa;</p>
              <p><strong>Tarikh:</strong> {{ formatDate(new Date()) }}</p>
            </div>
            <p style="margin-left: 15px; margin-bottom: 5px;"><strong>Nama dan No KP:</strong> {{ selectedPatient?.name || selectedPatient?.displayName || 'Unknown' }} ({{ selectedPatient?.nric || selectedPatient?.ic || '******' }})</p>
            <p style="margin-left: 15px; margin-bottom: 20px;"><strong>dari:</strong> {{ selectedPatient?.company || 'yang berkenaan' }}</p>
            
            <p>Beliau didapati tidak sihat dan tidak dapat menjalankan tugas selama</p>
            <p style="margin-left: 15px; margin-bottom: 15px;">
              <strong>{{ calculateMCDays() }}</strong> hari mulai <strong>{{ formatDate(consultation.mcStartDate) }}</strong> sehingga <strong>{{ formatDate(consultation.mcEndDate) }}</strong>
            </p>
            
            <div style="display: flex; justify-content: flex-end; margin-top: 40px;">
              <div style="text-align: center;">
                <!-- Signature Line -->
                <div style="border-bottom: 1px dotted #000; width: 150px; margin-bottom: 10px;">&nbsp;</div>
                <p style="margin-bottom: 0;">Tandatangan</p>
              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- Total Payment -->
      <div class="col-12 col-md-6">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-money-bill text-primary me-2"></i>
              Total Payment
              <small v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="badge bg-info ms-2">Group Consultation</small>
            </h5>
            
            <div class="row g-4">
              <div class="col-md-8">
                <div class="input-group">
                  <span class="input-group-text">RM</span>
                  <input type="number" class="form-control" id="totalAmount" v-model="consultation.totalAmount" step="0.10" min="0" placeholder="0.00" required>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="col-12 d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-outline-secondary" @click="$router.back()">
          <i class="fas fa-times me-2"></i>
          Cancel
        </button>
        
        <!-- Save Current Patient Button (for group consultations) -->
        <button v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" 
                type="button" 
                class="btn btn-success btn-lg shadow-sm save-patient-btn" 
                @click="saveCurrentPatient" 
                :disabled="isLoading || !currentPatientId"
                style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-weight: 600; padding: 0.75rem 2rem;">
          <i v-if="!isLoading" class="fas fa-user-check me-2"></i>
          <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
          {{ isLoading ? 'Saving...' : `💾 Save ${getPatientName(currentPatientId)}` }}
        </button>
        
        <button type="submit" class="btn btn-primary" :disabled="isLoading">
          <i v-if="!isLoading" class="fas fa-check me-2"></i>
          <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
          {{ isLoading ? 'Saving...' : 'Complete Consultation' }}
        </button>
      </div>
    </form>

    <!-- Medical Certificate Modal -->
    <div class="modal fade" id="medicalCertificateModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Medical Certificate</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <medical-certificate-form 
              :consultation="consultation"
              :patient="selectedPatient"
              @generated="handleCertificateGenerated"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1" ref="prescriptionModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Prescription</h5>
            <button type="button" class="btn-close" @click="showPrescriptionForm = false"></button>
          </div>
          <div class="modal-body p-0">
            <prescription-form
              v-if="showPrescriptionForm"
              :consultation="consultation"
              :patient="selectedPatient"
              @generated="onPrescriptionGenerated"
              @cancel="showPrescriptionForm = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
    <!-- MC Preview Modal -->
    <div class="modal fade" id="mcPreviewModal" tabindex="-1" aria-labelledby="mcPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="mcPreviewModalLabel">Medical Certificate Preview</h5>
            <button type="button" class="btn-close" @click="hideMCPreview"></button>
          </div>
                    <div class="modal-body">
            <!-- MC Preview Content (works for both single and group consultations) -->
            <div class="mc-preview" style="background-color: #e8e5b0; padding: 20px; border: 1px solid #000;">
              <!-- Clinic Header -->
              <div class="text-center mb-3">
                <h3 class="mb-0" style="font-weight: bold;">KLINIK HIDUPsihat</h3>
                <p class="mb-1">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
                <p class="mb-1">Tel: 03-8740 0678</p>
              </div>
              
              <!-- MC Running Number -->
              <div class="d-flex justify-content-end mb-3">
                <div>
                  <p class="mb-0" style="font-size: 0.9rem; color: #a52a2a;">No: {{ consultation.mcRunningNumber || '426740' }}</p>
                </div>
              </div>
              
              <h4 class="text-center mb-4">SURAT AKUAN SAKIT (MC)</h4>
              
              <div class="d-flex justify-content-between">
                <p>Saya mengesahkan telah memeriksa;</p>
                <p><strong>Tarikh:</strong> {{ formatDate(new Date()) }}</p>
              </div>
              <p class="ms-3 mb-1"><strong>Nama dan No KP:</strong> {{ selectedPatient?.name || selectedPatient?.displayName || 'Unknown' }} ({{ selectedPatient?.nric || selectedPatient?.ic || '******' }})</p>
              <p class="ms-3 mb-4"><strong>dari:</strong> {{ selectedPatient?.company || 'yang berkenaan' }}</p>
              
              <p>Beliau didapati tidak sihat dan tidak dapat menjalankan tugas selama</p>
              <p class="ms-3 mb-3">
                <strong>{{ calculateMCDays() }}</strong> hari mulai <strong>{{ formatDate(consultation.mcStartDate) }}</strong> sehingga <strong>{{ formatDate(consultation.mcEndDate) }}</strong>
              </p>
              
              <div class="d-flex justify-content-end mt-5">
                <div style="text-align: center;">
                  <!-- Signature Line -->
                  <div style="border-bottom: 1px dotted #000; width: 150px; margin-bottom: 10px;">&nbsp;</div>
                  <p class="mb-0">Tandatangan</p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="hideMCPreview">Close</button>
            <button type="button" class="btn btn-primary" @click="printMC">
              <i class="fas fa-print me-2"></i> Print MC
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Visit Details Modal -->
    <div class="modal fade" id="visitDetailsModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content visit-details-modal">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="fas fa-file-medical-alt text-primary me-2"></i>
              Visit Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="selectedVisit">
            <div class="visit-info-grid">
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-calendar-alt text-primary"></i>
                  <span>Visit Information</span>
                </div>
                <div class="info-content">
                  <div class="info-item">
                    <label>Patient:</label>
                    <span class="fw-bold">{{ selectedVisit.patient?.name || selectedVisit.patientName || 'N/A' }}</span>
                  </div>
                  <div class="info-item">
                    <label>Date:</label>
                    <span>{{ new Date(selectedVisit.consultationDate).toLocaleDateString() }}</span>
                  </div>
                  <div class="info-item">
                    <label>Time:</label>
                    <span>{{ new Date(selectedVisit.consultationDate).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true }) }}</span>
                  </div>
                  <div class="info-item">
                    <label>Doctor:</label>
                    <span>Dr. {{ selectedVisit.doctor?.name || 'Unknown' }}</span>
                  </div>
                  <div class="info-item">
                    <label>Status:</label>
                    <span :class="getStatusClass(selectedVisit.status)">{{ selectedVisit.status || 'Completed' }}</span>
                  </div>
                </div>
              </div>
              
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-stethoscope text-primary"></i>
                  <span>Medical Details</span>
                </div>
                <div class="info-content">
                  <div class="info-item full-width">
                    <label>Diagnosis:</label>
                    <span>{{ selectedVisit.diagnosis || 'No diagnosis recorded' }}</span>
                  </div>
                  <div class="info-item full-width">
                    <label>Notes:</label>
                    <span>{{ selectedVisit.notes || 'No notes recorded' }}</span>
                  </div>
                </div>
              </div>

              <div class="info-card" v-if="selectedVisit.medications || selectedVisit.prescribedMedications">
                <div class="info-header">
                  <i class="fas fa-pills text-primary"></i>
                  <span>Prescribed Medications</span>
                </div>
                <div class="info-content">
                  <div class="medications-table">
                    <table class="table table-sm table-striped">
                      <thead class="table-light">
                        <tr>
                          <th>Medication</th>
                          <th>Quantity</th>
                          <th>Dosage</th>
                          <th>Frequency</th>
                          <th>Price (RM)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(med, index) in getMedicationsList(selectedVisit)" :key="index">
                          <td>
                            <strong>{{ med.name || 'N/A' }}</strong>
                            <br>
                            <small class="text-muted">{{ med.category || 'General' }}</small>
                          </td>
                          <td>{{ med.quantity || 'N/A' }} {{ med.unitType || 'pcs' }}</td>
                          <td>{{ med.dosage || 'N/A' }}</td>
                          <td>{{ med.frequency || 'N/A' }}</td>
                          <td class="text-end">{{ med.actualPrice ? parseFloat(med.actualPrice).toFixed(2) : '0.00' }}</td>
                        </tr>
                      </tbody>
                      <tfoot v-if="getMedicationTotal(selectedVisit) > 0" class="table-light">
                        <tr>
                          <th colspan="4" class="text-end">Medications Total:</th>
                          <th class="text-end text-success">RM {{ getMedicationTotal(selectedVisit).toFixed(2) }}</th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Financial Information -->
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-dollar-sign text-success"></i>
                  <span>Payment Information</span>
                </div>
                <div class="info-content">
                  <div class="payment-summary">
                    <div class="row g-3">
                      <div class="col-12">
                        <div class="payment-item total">
                          <label>Total Amount Paid:</label>
                          <span class="amount total-amount text-success fs-4">RM {{ selectedVisit.totalAmount ? parseFloat(selectedVisit.totalAmount).toFixed(2) : (parseFloat(selectedVisit.consultationFee || 0) + getMedicationTotal(selectedVisit)).toFixed(2) }}</span>
                        </div>
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.paymentMethod">
                        <div class="payment-item">
                          <label>Payment Method:</label>
                          <span class="badge bg-info">{{ selectedVisit.paymentMethod }}</span>
                        </div>
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.paidAt">
                        <div class="payment-item">
                          <label>Payment Date:</label>
                          <span>{{ formatDateOfBirth(selectedVisit.paidAt) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Medical Certificate Information -->
              <div class="info-card">
                <div class="info-header">
                  <i class="fas fa-certificate text-warning"></i>
                  <span>Medical Certificate Status</span>
                </div>
                <div class="info-content">
                  <div v-if="selectedVisit.hasMedicalCertificate || selectedVisit.mcStartDate">
                    <div class="alert alert-success mb-3">
                      <i class="fas fa-check-circle me-2"></i>
                      <strong>Medical Certificate Issued</strong>
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6" v-if="selectedVisit.mcStartDate">
                        <div class="info-item">
                          <label>Start Date:</label>
                          <span class="fw-bold">{{ formatDateOfBirth(selectedVisit.mcStartDate) }}</span>
                        </div>
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.mcEndDate">
                        <div class="info-item">
                          <label>End Date:</label>
                          <span class="fw-bold">{{ formatDateOfBirth(selectedVisit.mcEndDate) }}</span>
                        </div>
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.mcStartDate && selectedVisit.mcEndDate">
                        <div class="info-item">
                          <label>Duration:</label>
                          <span class="badge bg-info">{{ calculateMCDays(selectedVisit.mcStartDate, selectedVisit.mcEndDate) }} day(s)</span>
                        </div>
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.mcRunningNumber">
                        <div class="info-item">
                          <label>MC Number:</label>
                          <span class="badge bg-warning text-dark">{{ selectedVisit.mcRunningNumber }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else>
                    <div class="alert alert-light mb-0">
                      <i class="fas fa-times-circle me-2 text-muted"></i>
                      <strong>No Medical Certificate issued for this visit</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
          </div>
    
    <!-- Consultation Summary Modal -->
    <div class="modal fade" id="consultationSummaryModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-check-circle me-2"></i>
              Consultation Completed Successfully
            </h5>
          </div>
          <div class="modal-body" v-if="consultationSummary">
            <div class="alert alert-info mb-4">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Please review the following details before proceeding:</strong>
              This consultation will now be processed by clinic assistants for payment and medication dispensing.
            </div>

            <!-- Patient Information -->
            <div class="summary-section mb-4">
              <h6 class="section-title">
                <i class="fas fa-user text-primary me-2"></i>
                Patient Information
              </h6>
              <div class="summary-card">
                <div class="summary-item">
                  <span class="label">Patient Name:</span>
                  <span class="value fw-bold">{{ consultationSummary.patientName }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Date:</span>
                  <span class="value">{{ consultationSummary.date }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Doctor:</span>
                  <span class="value">{{ consultationSummary.doctorName }}</span>
                </div>
              </div>
            </div>

            <!-- Financial Summary -->
            <div class="summary-section mb-4">
              <h6 class="section-title">
                <i class="fas fa-money-bill text-success me-2"></i>
                Financial Summary
              </h6>
              <div class="summary-card">
                <div class="summary-item">
                  <span class="label">Total Amount:</span>
                  <span class="value fw-bold text-success">RM {{ consultationSummary.totalAmount }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Payment Status:</span>
                  <span class="value">
                    <span class="badge bg-warning">Pending Payment</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Documents & Actions Checklist -->
            <div class="summary-section mb-4">
              <h6 class="section-title">
                <i class="fas fa-clipboard-check text-warning me-2"></i>
                Documents & Actions Checklist
              </h6>
              <div class="summary-card">
                <div class="checklist-item">
                  <div class="checklist-icon">
                    <i v-if="consultationSummary.hasMedicalCertificate" class="fas fa-check-circle text-success"></i>
                    <i v-else class="fas fa-times-circle text-muted"></i>
                  </div>
                  <div class="checklist-content">
                    <span class="checklist-label">Medical Certificate (MC)</span>
                    <div class="checklist-status">
                      <span v-if="consultationSummary.hasMedicalCertificate" class="badge bg-success">
                        <i class="fas fa-file-medical me-1"></i>MC Required ({{ consultationSummary.mcDays }} days)
                      </span>
                      <span v-else class="badge bg-secondary">
                        <i class="fas fa-times me-1"></i>No MC Required
                      </span>
                    </div>
                    <div v-if="consultationSummary.hasMedicalCertificate" class="checklist-details">
                      <small class="text-muted">
                        Period: {{ consultationSummary.mcStartDate }} to {{ consultationSummary.mcEndDate }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="checklist-item">
                  <div class="checklist-icon">
                    <i v-if="consultationSummary.hasMedications" class="fas fa-check-circle text-success"></i>
                    <i v-else class="fas fa-times-circle text-muted"></i>
                  </div>
                  <div class="checklist-content">
                    <span class="checklist-label">Medication Labels</span>
                    <div class="checklist-status">
                      <span v-if="consultationSummary.hasMedications" class="badge bg-info">
                        <i class="fas fa-tags me-1"></i>Labels Available for Printing
                      </span>
                      <span v-else class="badge bg-secondary">
                        <i class="fas fa-times me-1"></i>No Medications Prescribed
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Medications Summary -->
            <div class="summary-section mb-4" v-if="consultationSummary.hasMedications">
              <h6 class="section-title">
                <i class="fas fa-pills text-info me-2"></i>
                Prescribed Medications Summary
              </h6>
              <div class="summary-card">
                <div class="medications-list">
                  <div v-for="(medication, index) in consultationSummary.medications" :key="index" class="medication-item">
                    <div class="medication-info">
                      <div class="medication-name">{{ medication.name }}</div>
                      <div class="medication-details">
                        <span class="quantity">Qty: {{ medication.quantity }}</span>
                        <span v-if="medication.instructions" class="instructions">{{ medication.instructions }}</span>
                        <span v-if="medication.actualPrice" class="price">RM {{ parseFloat(medication.actualPrice).toFixed(2) }}</span>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Next Steps -->
            <div class="summary-section">
              <h6 class="section-title">
                <i class="fas fa-arrow-right text-primary me-2"></i>
                Next Steps for Clinic Assistants
              </h6>
              <div class="summary-card">
                <div class="next-steps">
                  <div class="step-item">
                    <i class="fas fa-money-check-alt text-success me-2"></i>
                    <span>Process payment (RM {{ consultationSummary.totalAmount }})</span>
                  </div>
                  <div class="step-item" v-if="consultationSummary.hasMedications">
                    <i class="fas fa-pills text-info me-2"></i>
                    <span>Dispense prescribed medications</span>
                  </div>

                  <div class="step-item" v-if="consultationSummary.hasMedicalCertificate">
                    <i class="fas fa-file-medical text-primary me-2"></i>
                    <span>Print and provide Medical Certificate</span>
                  </div>
                  <div class="step-item">
                    <i class="fas fa-receipt text-secondary me-2"></i>
                    <span>Provide receipt to patient</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="goBackToEdit">
              <i class="fas fa-edit me-1"></i>Edit Consultation
            </button>
            <button type="button" class="btn btn-success" @click="confirmAndProceed">
              <i class="fas fa-check me-1"></i>Confirm & Proceed to Payment
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create New Medication Modal -->
    <div class="modal fade" id="createMedicationModal" tabindex="-1" aria-labelledby="createMedicationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="createMedicationModalLabel">
              <i class="fas fa-plus-circle text-success me-2"></i>
              Add New Medication to Database
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              This medication will be added to the database and available for future prescriptions.
            </div>
            
            <form @submit.prevent="createNewMedication">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label fw-bold">Medication Name</label>
                  <input type="text" class="form-control" v-model="newMedicationForm.name" readonly>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold">Unit Type</label>
                  <select class="form-select" v-model="newMedicationForm.unitType" required>
                    <option value="">Select unit type</option>
                    <option value="pieces">Pieces (tablets, capsules)</option>
                    <option value="bottles">Bottles</option>
                    <option value="tubes">Tubes</option>
                    <option value="sachets">Sachets</option>
                    <option value="boxes">Boxes</option>
                    <option value="ml">Milliliters (ml)</option>
                    <option value="mg">Milligrams (mg)</option>
                    <option value="g">Grams (g)</option>
                    <option value="vials">Vials</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold">Unit Description</label>
                  <input type="text" class="form-control" v-model="newMedicationForm.unitDescription" 
                         placeholder="e.g., 500mg tablets, 75ml bottle">
                  <small class="text-muted">Optional: Specific description of the unit</small>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold">Category</label>
                  <select class="form-select" v-model="newMedicationForm.category">
                    <option value="">Select category (optional)</option>
                    <option value="pain reliever">Pain Reliever</option>
                    <option value="antibiotic">Antibiotic</option>
                    <option value="cough syrup">Cough Syrup</option>
                    <option value="fever reducer">Fever Reducer</option>
                    <option value="antacid">Antacid</option>
                    <option value="allergy medicine">Allergy Medicine</option>
                    <option value="vitamin">Vitamin/Supplement</option>
                    <option value="topical">Topical/External</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Custom Category</label>
                  <input type="text" class="form-control" v-model="newMedicationForm.customCategory" 
                         placeholder="Enter custom category"
                         :disabled="newMedicationForm.category !== 'other'">
                  <small class="text-muted">Only if "Other" is selected above</small>
                </div>
                
                <div class="col-md-12">
                  <label class="form-label">Description (Optional)</label>
                  <textarea class="form-control" v-model="newMedicationForm.description" rows="3"
                            placeholder="Additional notes about this medication..."></textarea>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" @click="createNewMedication" :disabled="!newMedicationForm.unitType">
              <i class="fas fa-plus me-2"></i>Add Medication
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
// Helper to get today's date in YYYY-MM-DD format
function getTodayDate() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}
import { Modal } from 'bootstrap';
import axios from 'axios';
import MedicalCertificateForm from '../certificates/MedicalCertificateForm.vue';
import PrescriptionForm from '../prescriptions/PrescriptionForm.vue';
import * as bootstrap from 'bootstrap';
import { makeProtectedRequest, cancelAllRequests } from '../../utils/requestManager.js';
import searchDebouncer from '../../utils/searchDebouncer';
import AuthService from '../../services/AuthService';

export default {
  name: 'ConsultationForm',
  components: {
    MedicalCertificateForm,
    PrescriptionForm
  },
  data() {
    return {
      consultation: {
        patientId: null,
        doctorId: null,
        consultationDate: getTodayDate(),
        diagnosis: '',
        notes: '',
        consultationFee: 0,
        hasMedicalCertificate: false,
        medicalCertificateDays: 1,
        mcStartDate: getTodayDate(),
        mcEndDate: getTodayDate(),
        medications: [],
        totalAmount: 0,
        status: 'completed'
      },
      isEditing: !!this.$route.params.id,
      patients: [],
      doctors: [],
      groupPatients: [],
      mcSelectedPatientIds: [],
      isGroupConsultation: false,
      patientConsultationData: {}, // Store individual consultation data for each patient
      currentPatientId: null, // Track which patient's data is currently being viewed/edited
      queueNumber: null,
      queueId: null,
      groupId: null,
      visitHistories: [],
      fullPatientDetails: null,
      medicalCertificateModal: null,
      prescriptionModal: null,
      mcPreviewModal: null,
      isLoading: false,
      showPrescriptionForm: false,
      medications: [],
      prescribedMedications: [],
      selectedMedication: null,
      dosage: '',
      frequency: '',
      duration: '',
      instructions: '',
      selectedVisit: null,
      visitDetailsModal: null,
      medicationSearcher: null,
      newMedicationForm: {
        name: '',
        unitType: '',
        unitDescription: '',
        category: '',
        customCategory: '',
        description: ''
      },
      currentMedicationItem: null,
      createMedicationModal: null,
      consultationSummary: null,
      consultationSummaryModal: null,
    };
  },
  computed: {
    selectedPatient() {
      // For group consultations, always use groupPatients data (it's already complete)
      if (this.isGroupConsultation && Array.isArray(this.groupPatients) && this.groupPatients.length > 0) {
        const groupPatient = this.groupPatients.find(p => p.id === this.consultation.patientId);
        if (groupPatient) {
          return groupPatient;
        }
      }
      
      // For single consultations, prefer fullPatientDetails if available
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      
      // Fallback to patients array
      if (Array.isArray(this.patients)) {
        return this.patients.find(p => p.id === this.consultation.patientId) || null;
      }
      
      return null;
    }
  },
  methods: {
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        if (isNaN(birthDate.getTime())) {
          return 'N/A';
        }
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        return age > 0 ? age : 'N/A';
      } catch (error) {
        console.error('Error calculating age:', error);
        return 'N/A';
      }
    },
    
    selectPatient(patient) {
      if (!patient || !patient.id) return;
      
      if (this.isGroupConsultation) {
        // For group consultations, switch to patient with data management
        this.switchToPatient(patient.id);
      } else {
        // For single consultations, simple selection
        this.consultation.patientId = patient.id;
        this.fetchPatientDetails();
      }
      
      // Add visual feedback
      this.$toast?.success?.(`Selected patient: ${patient.name || patient.displayName}`);
    },
    
    calculatePatientAge() {
      if (!this.selectedPatient?.dateOfBirth) return 'N/A';
      try {
        const today = new Date();
        const birthDate = new Date(this.selectedPatient.dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        return age > 0 ? age : 0;
      } catch (error) {
        console.error('Error calculating patient age:', error);
        return 'N/A';
      }
    },
    getFullGender(gender) {
      if (!gender) return 'N/A';
      return gender === 'M' ? 'Male' : gender === 'F' ? 'Female' : gender;
    },
    formatDateOfBirth(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      try {
        const dateObj = new Date(dateOfBirth);
        return dateObj.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        });
      } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
      }
    },
    formatTime(dateString) {
      if (!dateString) return 'N/A';
      try {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        console.error('Error formatting time:', error);
        return 'Invalid Time';
      }
    },
    isEditing() {
      return !!this.$route.params.id;
    },
    totalAmount() {
      return (parseFloat(this.consultation.consultationFee || 0) + 
              parseFloat(this.consultation.medicinesFee || 0));
    },
    printMC() {
      // If multiple patients, print for all selected; else, print for selectedPatient
      const printContents = document.getElementById('mc-print-content').innerHTML;
      const printWindow = window.open('', '', 'height=600,width=600');
      printWindow.document.write('<html><head><title>Medical Certificate</title>');
      printWindow.document.write('</head><body >');
      printWindow.document.write(printContents);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 300);
    },
    showMCPreview() {
      // For group consultations, check current patient has MC enabled and dates set
      if (this.isGroupConsultation) {
        if (!this.consultation.hasMedicalCertificate) {
          alert('Please enable Medical Certificate for preview.');
          return;
        }
        if (!this.consultation.mcStartDate || !this.consultation.mcEndDate) {
          alert('Please set MC start and end dates for preview.');
          return;
        }
      }
      
      // For single consultations, check the basic requirements
      if (!this.isGroupConsultation && (!this.consultation.mcStartDate || !this.consultation.mcEndDate)) {
        alert('Please set MC start and end dates for preview.');
        return;
      }
      
      if (!this.mcPreviewModal) {
        this.mcPreviewModal = new Modal(document.getElementById('mcPreviewModal'));
      }
      this.mcPreviewModal.show();
    },
    hideMCPreview() {
      if (this.mcPreviewModal) {
        this.mcPreviewModal.hide();
      }
    },
    calculateMCDays() {
      if (!this.consultation.mcStartDate || !this.consultation.mcEndDate) {
        return '0';
      }
      
      const start = new Date(this.consultation.mcStartDate);
      const end = new Date(this.consultation.mcEndDate);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
      
      return diffDays.toString();
    },
    
    calculateMCDaysForPatient(patientId) {
      const patientData = this.patientConsultationData[patientId];
      if (!patientData || !patientData.mcStartDate || !patientData.mcEndDate) {
        return '0';
      }
      
      const start = new Date(patientData.mcStartDate);
      const end = new Date(patientData.mcEndDate);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
      
      return diffDays.toString();
    },
    
    getPatientById(patientId) {
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        return this.groupPatients.find(p => p.id === patientId);
      }
      return this.selectedPatient;
    },
    
    getPatientName(patientId) {
      if (!patientId) return 'No Patient Selected';
      
      // For group consultations, try groupPatients first
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        const patient = this.groupPatients.find(p => p.id === patientId);
        if (patient) {
          return patient.name || patient.displayName || 'Unnamed Patient';
        }
      }
      
      // Try selectedPatient
      if (this.selectedPatient && this.selectedPatient.id === patientId) {
        return this.selectedPatient.name || this.selectedPatient.displayName || 'Unnamed Patient';
      }
      
      // Try fullPatientDetails
      if (this.fullPatientDetails && this.fullPatientDetails.id === patientId) {
        return this.fullPatientDetails.name || this.fullPatientDetails.displayName || 'Unnamed Patient';
      }
      
      // Fallback to patients array
      if (Array.isArray(this.patients)) {
        const patient = this.patients.find(p => p.id === patientId);
        if (patient) {
          return patient.name || patient.displayName || 'Unnamed Patient';
        }
      }
      
      return 'Patient';
    },
    
    getMainPatient() {
      if (!this.isGroupConsultation || !this.groupPatients || this.groupPatients.length === 0) {
        return this.selectedPatient;
      }
      // Find the main patient (relationship === 'self') or fallback to first patient
      return this.groupPatients.find(p => p.relationship === 'self') || this.groupPatients[0];
    },
    formatDate(dateString) {
      if (!dateString) return '';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('ms-MY', {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric'
      });
    },
    async generateMCNumber() {
      try {
        // Get the next MC number from the backend
        const response = await axios.get('/api/medical-certificates/next-number');
        this.consultation.mcRunningNumber = response.data.runningNumber;
        return this.consultation.mcRunningNumber;
      } catch (error) {
        console.error('Error generating MC number:', error);
        // Fallback to timestamp-based number if API fails
        const now = new Date();
        this.consultation.mcRunningNumber = now.getTime().toString().slice(-6);
        return this.consultation.mcRunningNumber;
      }
    },
    async onMCCheckboxChange() {
      if (this.consultation.hasMedicalCertificate && !this.consultation.mcRunningNumber) {
        await this.generateMCNumber();
      }
    },
    
    initializePatientData(patientId) {
      if (!this.patientConsultationData[patientId]) {
        // Use direct assignment instead of $set for Vue 3 compatibility
        this.patientConsultationData[patientId] = {
          diagnosis: '',
          notes: '',
          medications: [],
          hasMedicalCertificate: true,
          mcStartDate: getTodayDate(),
          mcEndDate: getTodayDate(),
          saved: false // Track if this patient's data has been saved
          // Note: totalAmount is shared for the group, not individual per patient
        };
      }
    },
    
    async switchToPatient(patientId) {
      console.log('🔄 SWITCH PATIENT CALLED - Patient ID:', patientId);
      console.log('🔄 Current patient ID:', this.currentPatientId);
      console.log('🔄 Is group consultation:', this.isGroupConsultation);
      console.log('🔄 Group patients:', this.groupPatients);
      
      try {
        // Save current form data to the previous patient
        if (this.currentPatientId && this.currentPatientId !== patientId) {
          console.log('💾 Saving data for previous patient:', this.currentPatientId);
          this.saveFormDataToPatient(this.currentPatientId);
        }
        
        // For group consultations, immediately set the patient details BEFORE changing IDs
        // This prevents the "Loading..." state
        if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
          const groupPatient = this.groupPatients.find(p => p.id === patientId);
          if (groupPatient) {
            this.fullPatientDetails = groupPatient;
            console.log('👤 Set patient details from group data:', groupPatient.name);
          } else {
            console.warn('⚠️ Patient not found in groupPatients:', patientId);
            return;
          }
        }
        
        // Now switch to new patient
        this.currentPatientId = patientId;
        this.consultation.patientId = patientId;
        
        // Initialize data for this patient if needed
        this.initializePatientData(patientId);
        
        // Load this patient's data into the form
        console.log('📋 Loading data for patient:', patientId);
        this.loadPatientDataToForm(patientId);
        
        // For non-group consultations, fetch patient details
        if (!this.isGroupConsultation) {
          await this.fetchPatientDetails();
        }
        
        console.log('✅ Successfully switched to patient:', patientId);
      } catch (error) {
        console.error('❌ Error switching patients:', error);
      }
    },
    
    handlePatientSwitch(patient) {
      console.log('🔧 Handle patient switch called for:', patient);
      
      // Close the Bootstrap dropdown manually using multiple methods
      try {
        const dropdownElement = document.querySelector('.patient-selector .dropdown-toggle');
        if (dropdownElement) {
          // Method 1: Try Bootstrap 5 instance
          const dropdown = window.bootstrap?.Dropdown?.getInstance(dropdownElement);
          if (dropdown) {
            dropdown.hide();
          } else {
            // Method 2: Use data-bs attributes
            dropdownElement.setAttribute('aria-expanded', 'false');
            const dropdownMenu = dropdownElement.nextElementSibling;
            if (dropdownMenu) {
              dropdownMenu.classList.remove('show');
            }
          }
        }
      } catch (error) {
        console.log('Note: Could not close dropdown, continuing anyway:', error);
      }
      
      // Call the actual switch method
      this.switchToPatient(patient.id);
    },
    
    saveFormDataToPatient(patientId) {
      if (!this.patientConsultationData[patientId]) {
        this.initializePatientData(patientId);
      }
      
      // Save current form state to patient data
      const existingData = this.patientConsultationData[patientId];
      this.patientConsultationData[patientId] = {
        ...existingData,
        diagnosis: this.consultation.diagnosis,
        notes: this.consultation.notes,
        medications: [...(this.consultation.medications || [])],
        hasMedicalCertificate: this.consultation.hasMedicalCertificate,
        mcStartDate: this.consultation.mcStartDate,
        mcEndDate: this.consultation.mcEndDate
        // Note: totalAmount is NOT saved per patient - it's shared for the group
      };
    },
    
    loadPatientDataToForm(patientId) {
      const patientData = this.patientConsultationData[patientId];
      if (patientData) {
        this.consultation.diagnosis = patientData.diagnosis || '';
        this.consultation.notes = patientData.notes || '';
        this.consultation.medications = [...(patientData.medications || [])];
        this.consultation.hasMedicalCertificate = patientData.hasMedicalCertificate !== undefined ? patientData.hasMedicalCertificate : false;
        this.consultation.mcStartDate = patientData.mcStartDate || getTodayDate();
        this.consultation.mcEndDate = patientData.mcEndDate || getTodayDate();
        // Note: totalAmount remains shared for the group consultation
      } else {
        // Initialize with defaults if no data exists
        this.consultation.diagnosis = '';
        this.consultation.notes = '';
        this.consultation.medications = [];
        this.consultation.hasMedicalCertificate = false;
        this.consultation.mcStartDate = getTodayDate();
        this.consultation.mcEndDate = getTodayDate();
        // Note: totalAmount remains unchanged when switching patients
      }
    },
    
    async saveCurrentPatient() {
      if (!this.currentPatientId) {
        alert('No patient selected');
        return;
      }
      
      try {
        this.isLoading = true;
        
        // Save current form data to patient data structure
        this.saveFormDataToPatient(this.currentPatientId);
        
        // Mark as saved
        this.patientConsultationData[this.currentPatientId].saved = true;
        
        // Show success message
        const patientName = this.selectedPatient?.name || this.selectedPatient?.displayName || 'Patient';
        alert(`Successfully saved data for ${patientName}!`);
        
      } catch (error) {
        console.error('Error saving patient data:', error);
        alert('Error saving patient data: ' + error.message);
      } finally {
        this.isLoading = false;
      }
    },
    showVisitHistoryModal() {
      if (!this.visitHistoryModalInstance) {
        const modalEl = this.$refs.visitHistoryModal;
        if (modalEl) {
          this.visitHistoryModalInstance = new Modal(modalEl);
        }
      }
      this.loadVisitHistories();
      this.visitHistoryModalInstance && this.visitHistoryModalInstance.show();
    },
    hideVisitHistoryModal() {
      this.visitHistoryModalInstance && this.visitHistoryModalInstance.hide();
    },
    async loadVisitHistories() {
      if (!this.consultation.patientId) {
        this.visitHistories = [];
        return;
      }
      try {
        const response = await axios.get(`/api/consultations/patient/${this.consultation.patientId}`);
        this.visitHistories = response.data.map(visit => ({
          id: visit.id,
          consultationDate: visit.consultationDate,
          doctor: visit.doctor,
          diagnosis: visit.notes || visit.diagnosis || '', // Use notes first, then diagnosis as fallback
          notes: visit.notes || ''
        }));
      } catch (error) {
        console.error('Error loading visit histories:', error);
        this.visitHistories = [];
      }
    },

    async loadPatients() {
      try {
        const response = await axios.get('/api/patients');
        console.log('Loaded patients:', response.data);
        this.patients = Array.isArray(response.data) ? response.data : (response.data.patients || []);
      } catch (error) {
        console.error('Error loading patients:', error);
      }
    },
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors');
        console.log('Loaded doctors:', response.data);
        this.doctors = Array.isArray(response.data) ? response.data : (response.data.doctors || []);
      } catch (error) {
        console.error('Error loading doctors:', error);
      }
    },
    async fetchPatientDetails() {
      if (!this.consultation.patientId) {
        this.fullPatientDetails = null;
        this.visitHistories = [];
        return;
      }
      
      // For group consultations, don't fetch from API - use existing groupPatients data
      if (this.isGroupConsultation && Array.isArray(this.groupPatients)) {
        const groupPatient = this.groupPatients.find(p => p.id === this.consultation.patientId);
        if (groupPatient) {
          this.fullPatientDetails = groupPatient;
          await this.loadVisitHistories();
          return;
        }
      }
      
      // For single consultations, fetch from API
      try {
        const response = await axios.get(`/api/patients/${this.consultation.patientId}`);
        this.fullPatientDetails = response.data;
        await this.loadVisitHistories();
      } catch (error) {
        console.error('Error fetching patient details:', error);
        this.fullPatientDetails = null;
        this.visitHistories = [];
      }
    },
    async loadConsultation() {
      if (!this.isEditing) return;
      try {
        const response = await axios.get(`/api/consultations/${this.$route.params.id}`);
        // ...
      } catch (error) {
        console.error('Error loading consultation:', error);
      }
    },
    async saveConsultation() {
      // Prevent multiple submissions
      if (this.isLoading) {
        return;
      }
      
      this.isLoading = true;
      
      try {
        if (this.isGroupConsultation && this.groupPatients && this.groupPatients.length > 1) {
          // Group consultation - save all patients
          await this.saveAllGroupPatients();
        } else {
          // Single consultation - normal save
          await this.saveSingleConsultation();
        }
      } catch (error) {
        console.error('Error saving consultation:', error);
        
        // Show user-friendly error message
        const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message || 'Error saving consultation';
        alert('❌ ' + errorMessage);
        
        // Clear any loading states
        this.isLoading = false;
      } finally {
        // Ensure loading state is always cleared
        this.isLoading = false;
      }
    },
    
    async saveSingleConsultation() {
      // Validate required fields
      if (!this.consultation.patientId) {
        throw new Error('Patient is required');
      }
      if (!this.consultation.doctorId) {
        throw new Error('Doctor is required');
      }
      if (!this.consultation.notes) {
        throw new Error('Notes are required');
      }

      // Prepare consultation data
      const consultationData = {
        patientId: this.consultation.patientId,
        doctorId: this.consultation.doctorId,
        notes: this.consultation.notes,
        diagnosis: this.consultation.notes || '', // Use notes as diagnosis
        status: this.consultation.status || 'pending',
        consultationFee: parseFloat(this.consultation.consultationFee) || 0,
        medications: JSON.stringify(this.consultation.medications || []), // Convert array to JSON string
        prescribedMedications: this.prescribedMedications.filter(med => med.name && med.quantity), // Send structured medications
        mcStartDate: this.consultation.mcStartDate || null,
        mcEndDate: this.consultation.mcEndDate || null,
        mcNotes: this.consultation.mcNotes || '',
        queueNumber: this.queueNumber,
        groupId: null,
        isGroupConsultation: false
      };

      console.log('Sending consultation data:', consultationData);

      const response = await axios.post('/api/consultations', consultationData);
      console.log('Consultation saved successfully:', response.data);

      // Show success message
      alert('✅ Consultation saved successfully!');
      
      // Clear loading state before redirect
      this.isLoading = false;
      
      // Redirect based on user role
      const currentUser = AuthService.getCurrentUser();
      if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
        this.$router.push('/consultations/ongoing');
      } else {
        this.$router.push('/consultations');
      }
    },
    
    async saveAllGroupPatients() {
      // Save current patient's form data first
      if (this.currentPatientId) {
        this.saveFormDataToPatient(this.currentPatientId);
      }
      
      const savedConsultations = [];
      const mainPatient = this.getMainPatient();
      
      // Save consultation for each patient in the group
      for (const patient of this.groupPatients) {
        if (!patient || !patient.id) continue;
        
        const patientData = this.patientConsultationData[patient.id];
        if (!patientData) {
          console.warn(`No data found for patient ${patient.name}, skipping...`);
          continue;
        }
        
        // Only the main patient gets the total payment amount, others get 0
        const isMainPatient = patient.id === mainPatient?.id;
        
        const consultationData = {
          patientId: patient.id,
          doctorId: this.consultation.doctorId,
          notes: patientData.notes || '',
          diagnosis: patientData.diagnosis || patientData.notes || '',
          status: 'completed',
          consultationFee: parseFloat(this.consultation.consultationFee) || 0,
          medications: JSON.stringify(patientData.medications || []),
          prescribedMedications: patientData.medications?.filter(med => med.name && med.quantity) || [],
          mcStartDate: patientData.hasMedicalCertificate ? patientData.mcStartDate : null,
          mcEndDate: patientData.hasMedicalCertificate ? patientData.mcEndDate : null,
          mcNotes: '',
          queueNumber: this.queueNumber,
          groupId: this.groupId,
          isGroupConsultation: true,
          totalAmount: isMainPatient ? (parseFloat(this.consultation.totalAmount) || 0) : 0,
          isMainPatient: isMainPatient // Flag to identify who is paying
        };
        
        const response = await axios.post('/api/consultations', consultationData);
        
        savedConsultations.push({
          patient: patient,
          consultation: response.data
        });
      }
      
      // Show success message
      const mainPatientName = mainPatient?.name || mainPatient?.displayName || 'Main Patient';
      alert(`✅ Successfully saved consultations for ${savedConsultations.length} patients!\n💰 Payment (RM${this.consultation.totalAmount || 0}) will be charged to: ${mainPatientName}`);
      
      // Clear loading state before redirect
      this.isLoading = false;
      
      // Redirect based on user role
      const currentUser = AuthService.getCurrentUser();
      if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
        this.$router.push('/consultations/ongoing');
      } else {
        this.$router.push('/consultations');
      }
    },
    // Enhanced Medication Methods
    addMedicationRow() {
      this.prescribedMedications.push({
        id: null,
        name: '',
        medicationId: null,
        quantity: 1,
        actualPrice: 0.00,
        unitType: 'pieces',
        unitDescription: '',
        category: '',
        instructions: '',
        showInstructions: false,
        allSuggestions: [],
        paginatedSuggestions: [],
        selectedSuggestionIndex: -1,
        currentPage: 1,
        itemsPerPage: 5,
        totalPages: 1
      });
    },
    
    removeMedicationRow(index) {
      this.prescribedMedications.splice(index, 1);
    },
    
    async searchMedications(medItem, event) {
      const searchTerm = event.target.value;
      
      try {
        // Ensure medicationSearcher is initialized
        if (!this.medicationSearcher) {
          console.warn('⚠️ MedicationSearcher not initialized, using direct search');
          const results = await this.performMedicationSearch(searchTerm);
          this.updateMedicationPagination(medItem, results || []);
          return;
        }

        const results = await this.medicationSearcher.search('medication', searchTerm, this.performMedicationSearch);
        
        if (results) {
          this.updateMedicationPagination(medItem, results);
        }
        
      } catch (error) {
        console.error('Medication search error:', error);
        this.updateMedicationPagination(medItem, []);
      }
    },
    
    updateMedicationPagination(medItem, allResults) {
      medItem.allSuggestions = allResults;
      medItem.currentPage = 1;
      medItem.totalPages = Math.ceil(allResults.length / medItem.itemsPerPage);
      medItem.selectedSuggestionIndex = allResults.length > 0 ? 0 : -1;
      this.updatePaginatedSuggestions(medItem);
    },
    
    updatePaginatedSuggestions(medItem) {
      const startIndex = (medItem.currentPage - 1) * medItem.itemsPerPage;
      const endIndex = startIndex + medItem.itemsPerPage;
      medItem.paginatedSuggestions = medItem.allSuggestions.slice(startIndex, endIndex);
    },
    
    changeMedicationPage(medItem, newPage) {
      if (newPage >= 1 && newPage <= medItem.totalPages) {
        medItem.currentPage = newPage;
        this.updatePaginatedSuggestions(medItem);
        medItem.selectedSuggestionIndex = 0; // Reset selection to first item
      }
    },

    // The actual search function used by SearchDebouncer
    async performMedicationSearch(searchTerm) {
      try {
        const response = await axios.get(`/api/medications?search=${encodeURIComponent(searchTerm)}`);
        return response.data;
        
      } catch (error) {
        console.error('❌ Error in medication search API:', error);
        throw error;
      }
    },
    
    selectMedication(medItem, medication) {
      medItem.id = medication.id;
      medItem.medicationId = medication.id;
      medItem.name = medication.name;
      medItem.unitType = medication.unitType;
      medItem.unitDescription = medication.unitDescription;
      medItem.category = medication.category;
      
      // Ensure actualPrice is set from sellingPrice, with fallback to 0
      if (medication.sellingPrice && medication.sellingPrice > 0) {
        medItem.actualPrice = parseFloat(medication.sellingPrice);
      } else {
        medItem.actualPrice = 0.00;
        console.warn(`⚠️ Medication "${medication.name}" has no selling price set. Please set it in Admin > Medications.`);
      }
      
      console.log(`✅ Selected medication: ${medication.name}, Price: RM${medItem.actualPrice}`);
      
      medItem.allSuggestions = [];
      medItem.paginatedSuggestions = [];
      medItem.selectedSuggestionIndex = -1;
    },
    
    handleMedicationBlur(medItem) {
      // Delay hiding suggestions to allow clicking on them
      setTimeout(() => {
        if (!medItem.medicationId && medItem.name && medItem.name.length >= 2) {
          // Check if exact match exists in current suggestions
          const exactMatch = medItem.allSuggestions.find(med => 
            med.name.toLowerCase() === medItem.name.toLowerCase()
          );
          
          if (exactMatch) {
            this.selectMedication(medItem, exactMatch);
          } else {
            // Always show create new option if no exact match and there's a name
            this.showCreateMedicationModal(medItem);
            return; // Don't clear suggestions immediately
          }
        }
        medItem.allSuggestions = [];
        medItem.paginatedSuggestions = [];
        medItem.selectedSuggestionIndex = -1;
      }, 200);
    },
    
    selectFirstSuggestion(medItem) {
      if (medItem.paginatedSuggestions && medItem.paginatedSuggestions.length > 0) {
        this.selectMedication(medItem, medItem.paginatedSuggestions[0]);
      }
    },
    
    clearSuggestions(medItem) {
      medItem.allSuggestions = [];
      medItem.paginatedSuggestions = [];
      medItem.selectedSuggestionIndex = -1;
    },
    
    showCreateMedicationModal(medItem) {
      this.currentMedicationItem = medItem;
      this.newMedicationForm = {
        name: medItem.name,
        unitType: '',
        unitDescription: '',
        category: '',
        customCategory: '',
        description: ''
      };
      
      if (!this.createMedicationModal) {
        this.createMedicationModal = new bootstrap.Modal(document.getElementById('createMedicationModal'));
      }
      this.createMedicationModal.show();
      
      // Clear suggestions when showing modal
      medItem.allSuggestions = [];
      medItem.paginatedSuggestions = [];
    },
    
    async createNewMedication() {
      try {
        if (!this.newMedicationForm.unitType) {
          alert('Please select a unit type.');
          return;
        }
        
        const finalCategory = this.newMedicationForm.category === 'other' 
          ? this.newMedicationForm.customCategory 
          : this.newMedicationForm.category;
        
        const newMedication = {
          name: this.newMedicationForm.name,
          unitType: this.newMedicationForm.unitType,
          unitDescription: this.newMedicationForm.unitDescription || null,
          category: finalCategory || null,
          description: this.newMedicationForm.description || null
        };
        
        const response = await axios.post('/api/medications', newMedication);
        
        // Select the newly created medication
        if (this.currentMedicationItem) {
          this.selectMedication(this.currentMedicationItem, response.data);
        }
        
        // Close modal
        this.createMedicationModal.hide();
        
        // Reset form
        this.newMedicationForm = {
          name: '',
          unitType: '',
          unitDescription: '',
          category: '',
          customCategory: '',
          description: ''
        };
        this.currentMedicationItem = null;
        
        console.log('New medication added to database:', response.data);
        alert('Medication successfully added to the database!');
        
      } catch (error) {
        console.error('Error creating new medication:', error);
        const errorMessage = error.response?.data?.message || 'Error adding medication to database. Please try again.';
        alert(errorMessage);
      }
    },
    
    onPrescriptionGenerated(prescriptionData) {
      // Here you would typically make an API call to save the prescription
      console.log('Prescription generated:', prescriptionData);
      this.showPrescriptionForm = false;
      this.$refs.prescriptionModal.hide();
      this.$toast.success('Prescription generated successfully');
    },
    generatePrescription() {
      this.showPrescriptionForm = true;
      const modal = new bootstrap.Modal(this.$refs.prescriptionModal);
      modal.show();
    },
    handleCertificateGenerated() {
      if (this.medicalCertificateModal) {
        this.medicalCertificateModal.hide();
      }
    },
    selectPatient(patient) {
      this.consultation.patientId = patient.id;
      this.fetchPatientDetails();
    },
    async showVisitDetails(visit) {
      try {
        // Fetch comprehensive visit details
        const response = await axios.get(`/api/consultations/${visit.id}`);
        this.selectedVisit = {
          ...visit,
          ...response.data,
          // Add any additional computed fields
          formattedDate: this.formatDateOfBirth(visit.consultationDate),
          formattedTime: this.formatTime(visit.consultationDate)
        };
        this.visitDetailsModal.show();
      } catch (error) {
        console.error('Error loading visit details:', error);
        // Fallback to basic visit data
        this.selectedVisit = visit;
        this.visitDetailsModal.show();
      }
    },
    getMedicationsList(visit) {
      if (!visit) return [];
      
      let medications = [];
      
      // Try to get from prescribedMedications first (structured data)
      if (visit.prescribedMedications && Array.isArray(visit.prescribedMedications)) {
        medications = visit.prescribedMedications;
      }
      // Fallback to medications field (JSON string)
      else if (visit.medications) {
        try {
          const parsed = typeof visit.medications === 'string' 
            ? JSON.parse(visit.medications) 
            : visit.medications;
          medications = Array.isArray(parsed) ? parsed : [];
        } catch (e) {
          console.warn('Error parsing medications:', e);
          medications = [];
        }
      }
      
      return medications;
    },
    getMedicationTotal(visit) {
      const medications = this.getMedicationsList(visit);
      return medications.reduce((total, med) => {
        const price = parseFloat(med.actualPrice || med.price || 0);
        return total + price;
      }, 0);
    },
    calculateMCDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      
      try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
        return diffDays;
      } catch (error) {
        console.error('Error calculating MC days:', error);
        return 0;
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
    },
    reviewMC() {
      // Show MC modal or perform MC review logic
      // Placeholder: you can implement your modal logic here
      alert('Review MC clicked!');
    },
    async loadMedications() {
      try {
        const response = await axios.get('/api/medications');
        this.medications = response.data;
      } catch (error) {
        console.error('Error loading medications:', error);
        this.medications = [];
      }
    },
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      queueNumber = queueNumber.toString();
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    
    // Removed duplicate switchToPatient method - using the async version above
    
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    },
    
    // loadGroupPatients() method removed - group patients are now loaded directly 
    // from the /api/queue/{id} response in the mounted() method
    printMedicationLabel(medItem) {
      // Use the exact format from your clinic's medication label
      const patientName = this.selectedPatient?.name || this.selectedPatient?.displayName || 'N/A';
      const today = new Date();
      const formattedDate = `${today.getDate().toString().padStart(2, '0')}/${(today.getMonth() + 1).toString().padStart(2, '0')}/${today.getFullYear()}`;
      
      const labelContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <title>Medication Label - ${patientName}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }
            
            body {
              font-family: Arial, sans-serif;
              margin: 10mm;
            }
            
            .medication-label {
              width: 85mm;
              height: 55mm;
              border: 2px solid #000;
              padding: 3mm;
              background: white;
              font-size: 9pt;
              line-height: 1.1;
            }
            
            .label-border {
              height: 100%;
              border: 1px solid #000;
              padding: 2mm;
            }
            
            .label-header {
              text-align: center;
              border-bottom: 1px solid #000;
              padding-bottom: 2mm;
              margin-bottom: 2mm;
            }
            
            .clinic-name {
              font-size: 12pt;
              font-weight: bold;
              margin-bottom: 1mm;
              letter-spacing: 0.5px;
            }
            
            .clinic-details {
              font-size: 7pt;
              line-height: 1.2;
            }
            
            .label-content {
              height: calc(100% - 15mm);
            }
            
            .row-item {
              display: flex;
              align-items: center;
              margin-bottom: 1.5mm;
              height: 4.5mm;
            }
            
            .label-text {
              font-size: 8pt;
              font-weight: bold;
              width: 15mm;
              flex-shrink: 0;
            }
            
            .field-box {
              flex: 1;
              height: 4mm;
              border: 1px solid #000;
              margin-left: 2mm;
              padding: 0 1mm;
              display: flex;
              align-items: center;
              font-size: 8pt;
            }
            
            .field-box.filled {
              background-color: transparent;
              font-weight: bold;
            }
            
            .checkbox-section {
              margin-top: 2mm;
              display: flex;
              align-items: center;
              flex-wrap: wrap;
            }
            
            .checkbox-section .label-text {
              font-size: 7pt;
              width: auto;
              margin-right: 3mm;
            }
            
            .checkbox-container {
              display: flex;
              gap: 5mm;
            }
            
            .checkbox-item {
              display: flex;
              align-items: center;
              gap: 1mm;
            }
            
            .checkbox {
              width: 3mm;
              height: 3mm;
              border: 1px solid #000;
              flex-shrink: 0;
            }
            
            .checkbox-item span {
              font-size: 7pt;
            }
            
            @media print {
              body {
                margin: 0;
              }
              
                .medication-label {
    margin: 0;
    width: 85mm;
    height: 55mm;
  }
}

/* Consultation Summary Modal Styles */
.summary-section {
  border-left: 4px solid #007bff;
  padding-left: 1rem;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
}

.summary-card {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item .label {
  font-weight: 500;
  color: #6c757d;
}

.summary-item .value {
  color: #333;
}

.checklist-item {
  display: flex;
  align-items: flex-start;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e9ecef;
}

.checklist-item:last-child {
  border-bottom: none;
}

.checklist-icon {
  width: 24px;
  margin-right: 0.75rem;
  text-align: center;
}

.checklist-content {
  flex: 1;
}

.checklist-label {
  font-weight: 600;
  color: #333;
  display: block;
  margin-bottom: 0.25rem;
}

.checklist-status {
  margin-bottom: 0.25rem;
}

.checklist-details {
  margin-top: 0.25rem;
}

.medications-list {
  max-height: 200px;
  overflow-y: auto;
}

.medication-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  background: white;
}

.medication-item:last-child {
  margin-bottom: 0;
}

.medication-info {
  flex: 1;
}

.medication-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 0.25rem;
}

.medication-details {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
}

.medication-details .quantity {
  color: #0d6efd;
  font-weight: 500;
}

.medication-details .instructions {
  color: #6c757d;
}

.medication-details .price {
  color: #198754;
  font-weight: 500;
}

.medication-actions {
  margin-left: 1rem;
}

.next-steps {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.step-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 0.9rem;
}

.step-item i {
  width: 20px;
  text-align: center;
}
          </style>
        </head>
        <body>
          <div class="medication-label">
            <div class="label-border">
              <!-- Header -->
              <div class="label-header">
                <h2 class="clinic-name">KLINIK HIDUP SIHAT</h2>
                <div class="clinic-details">
                  8, Jalan 2, Taman Sri Jarbu, 43000 Kajang, Selangor.<br>
                  Tel: 03-8740 0678
                </div>
              </div>

              <!-- Content -->
              <div class="label-content">
                <div class="row-item">
                  <span class="label-text">Nama :</span>
                  <div class="field-box filled">${patientName}</div>
                </div>

                <div class="row-item">
                  <span class="label-text">Tarikh :</span>
                  <div class="field-box filled">${formattedDate}</div>
                </div>

                <div class="row-item">
                  <span class="label-text">Bil :</span>
                  <div class="field-box"></div>
                </div>

                <div class="row-item">
                  <span class="label-text">Ubat :</span>
                  <div class="field-box"></div>
                </div>

                <div class="checkbox-section">
                  <span class="label-text">SEBELUM SELEPAS MAKAN</span>
                  <div class="checkbox-container">
                    <div class="checkbox-item">
                      <div class="checkbox"></div>
                      <span>Kal Sehari</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </body>
        </html>
      `;

      // Create a new window for printing
      const printWindow = window.open('', '_blank');
      printWindow.document.write(labelContent);
      printWindow.document.close();
      
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    },

    async loadInitialData() {
      console.log('🔄 Loading consultation form data...');
      
      try {
        await Promise.all([
          this.loadPatients(),
          this.loadDoctors(),
          this.loadMedications()
        ]);
        
        // Load consultation data if editing
        if (this.$route.params.id) {
          await this.loadConsultation();
        } else {
          await this.fetchPatientDetails();
        }
        
        // Group patients are now loaded directly from queue API response in mounted()
        // No need for separate loadGroupPatients() call
        
      } catch (error) {
        console.error('❌ Error loading initial data:', error);
        this.$toast?.error?.('Failed to load consultation data');
      }
    },

    cleanup() {
      // Clean up any ongoing requests or timers
      if (this.medicationSearcher) {
        this.medicationSearcher.cleanup();
      }
    },

    prepareConsultationSummary() {
      const doctor = this.doctors.find(d => d.id === this.consultation.doctorId);
      const patient = this.selectedPatient;
      
      // Calculate MC days
      let mcDays = 0;
      if (this.consultation.hasMedicalCertificate && this.consultation.mcStartDate && this.consultation.mcEndDate) {
        const startDate = new Date(this.consultation.mcStartDate);
        const endDate = new Date(this.consultation.mcEndDate);
        mcDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
      }

      // Filter valid medications
      const validMedications = this.prescribedMedications.filter(med => med.name && med.quantity);

      this.consultationSummary = {
        patientName: patient?.name || patient?.displayName || 'Unknown Patient',
        date: new Date().toLocaleDateString('en-MY', {
          year: 'numeric',
          month: 'long', 
          day: 'numeric'
        }),
        doctorName: doctor?.name || 'Unknown Doctor',
        totalAmount: parseFloat(this.consultation.totalAmount || 0).toFixed(2),
        hasMedicalCertificate: this.consultation.hasMedicalCertificate !== undefined ? this.consultation.hasMedicalCertificate : false,
        mcDays: mcDays,
        mcStartDate: this.consultation.mcStartDate ? new Date(this.consultation.mcStartDate).toLocaleDateString('en-MY') : '',
        mcEndDate: this.consultation.mcEndDate ? new Date(this.consultation.mcEndDate).toLocaleDateString('en-MY') : '',
        hasMedications: validMedications.length > 0,
        medications: validMedications
      };
    },

    printSingleMedicationLabel(medication) {
      // Use the existing printMedicationLabel method
      this.printMedicationLabel(medication);
    },

    goBackToEdit() {
      // Close the summary modal and allow editing
      this.consultationSummaryModal.hide();
      this.consultationSummary = null;
    },

    confirmAndProceed() {
      // Close modal and redirect based on user role
      this.consultationSummaryModal.hide();
      
      const currentUser = AuthService.getCurrentUser();
      if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
        // Doctors should go back to ongoing consultations
        this.$router.push('/consultations/ongoing');
      } else {
        // Others go to consultations list for payment processing
        this.$router.push('/consultations');
      }
    }
  },
  watch: {
    // Whenever groupPatients changes, default all selected for MC
    groupPatients: {
      handler(newVal) {
        if (Array.isArray(newVal) && newVal.length > 1) {
          this.mcSelectedPatientIds = newVal.map(p => p.id);
        } else if (Array.isArray(newVal) && newVal.length === 1) {
          this.mcSelectedPatientIds = [newVal[0].id];
        } else {
          this.mcSelectedPatientIds = [];
        }
      },
      immediate: true,
      deep: true
    }
  },
  
  async created() {
    console.log('🔥 ConsultationForm created - loading initial data');
    
    // Initialize medication searcher
    this.medicationSearcher = searchDebouncer;
    
    this.loadInitialData();
  },
  
  async mounted() {
    console.log('🔥 ConsultationForm mounted');
    console.log('🔍 URL Query Parameters:', this.$route.query);
    
    // Extract all URL parameters
    const { patientId, doctorId, queueNumber, queueId, mode } = this.$route.query;
    
    // Handle mode parameter
    if (mode) {
      console.log('🔍 Found mode in URL:', mode);
      if (mode === 'continue') {
        console.log('📋 Continuing existing consultation');
      } else if (mode === 'start') {
        console.log('🆕 Starting new consultation');
      }
    }
    
    // Extract and set patient ID
    if (patientId) {
      console.log('🔍 Found patientId in URL:', patientId);
      this.consultation.patientId = parseInt(patientId);
      
      // Load patient details immediately
      try {
        await this.fetchPatientDetails();
      } catch (error) {
        console.error('❌ Error loading patient from URL:', error);
      }
    }
    
    // Extract and set doctor ID
    if (doctorId) {
      console.log('🔍 Found doctorId in URL:', doctorId);
      this.consultation.doctorId = parseInt(doctorId);
    }
    
    // Extract and set queue number
    if (queueNumber) {
      console.log('🔍 Found queueNumber in URL:', queueNumber);
      this.queueNumber = queueNumber;
    }
    
    // Extract and set queue ID
    if (queueId) {
      console.log('🔍 Found queueId in URL:', queueId);
      this.queueId = parseInt(queueId);
      
      // If we have a queue ID, try to load queue details for additional context
      try {
        const response = await axios.get(`/api/queue/${queueId}`);
        console.log('✅ Queue details loaded:', response.data);
        
        // If patient ID wasn't in URL but is in queue data, use it
        if (!patientId && response.data.patientId) {
          console.log('🔄 Using patientId from queue data:', response.data.patientId);
          this.consultation.patientId = parseInt(response.data.patientId);
          await this.fetchPatientDetails();
        }
        
        // If doctor ID wasn't in URL but is in queue data, use it
        if (!doctorId && response.data.doctorId) {
          console.log('🔄 Using doctorId from queue data:', response.data.doctorId);
          this.consultation.doctorId = parseInt(response.data.doctorId);
        }
        
        // Set group consultation flag if applicable
        if (response.data.isGroupConsultation) {
          console.log('👥 Setting group consultation mode');
          this.isGroupConsultation = true;
          this.groupId = response.data.groupId;
          
          // Use the groupPatients data directly from the API response
          if (response.data.groupPatients && Array.isArray(response.data.groupPatients)) {
            console.log('👥 Loading group patients from API response:', response.data.groupPatients);
            this.groupPatients = response.data.groupPatients.map(patient => ({
              id: patient.id,
              name: patient.name || patient.displayName,
              displayName: patient.displayName || patient.name,
              nric: patient.nric,
              dateOfBirth: patient.dateOfBirth,
              gender: patient.gender,
              phone: patient.phone,
              address: patient.address,
              relationship: patient.relationship || 'N/A',
              preInformedIllness: patient.preInformedIllness || ''
            }));
            
            // Set the currently selected patient to the primary patient if not already set
            if (!this.consultation.patientId && this.groupPatients.length > 0) {
              const primaryPatient = this.groupPatients.find(p => p.relationship === 'self') || this.groupPatients[0];
              this.consultation.patientId = primaryPatient.id;
              this.currentPatientId = primaryPatient.id;
              console.log('👤 Auto-selected primary patient:', primaryPatient.name);
              
              // Initialize data for all group patients
              for (const patient of this.groupPatients) {
                this.initializePatientData(patient.id);
              }
              
              // Load the primary patient's data into the form
              this.loadPatientDataToForm(primaryPatient.id);
            }
                     } else {
             console.warn('⚠️ No groupPatients data in API response for group consultation');
             this.groupPatients = [];
           }
        }
        
      } catch (error) {
        console.error('❌ Error loading queue details:', error);
        // Continue anyway with the parameters we have
      }
    }
    
    // Load initial data after setting up parameters
    await this.loadInitialData();
    
    // Initialize modals
    this.consultationSummaryModal = new bootstrap.Modal(document.getElementById('consultationSummaryModal'));
    this.visitDetailsModal = new bootstrap.Modal(document.getElementById('visitDetailsModal'));
     }
     </div>
   </div>
 </template>

 <script>
 import axios from 'axios';
 import { ref, reactive } from 'vue';
 import AuthService from '../services/AuthService.js';

 export default {
   name: 'ConsultationForm',
   data() {
     return {
       consultation: {
         patientId: null,
         patientName: '',
         doctorId: null,
         diagnosis: '',
         notes: '',
         medicationId: null,
         medicationName: '',
         medicationQuantity: 1,
         medicationDosage: '',
         medicationFrequency: '',
         medicationCategory: 'Tablet',
         duration: 1,
         medicine: [],
         medicineTotal: 0,
         consultationFee: 30.00,
         totalAmount: 30.00,
         isCompleted: false,
         hasMedicalCertificate: false,
         mcStartDate: null,
         mcEndDate: null
       },
       isEditing: false,
       patients: [],
       doctors: [],
       medications: [],
       medicationSuggestions: [],
       showSuggestions: false,
       medicationInputValue: '',
       loading: false,
       saving: false,
       medicationSearchLoading: false,
       showCustomMedicationModal: false,
       customMedication: {
         name: '',
         category: 'Tablet',
         price: 0,
         unitType: 'pcs'
       },
       prescribedMedications: [],
       selectedPatient: null,
       visitHistories: [],
       loadingHistory: false,
       selectedVisit: null,
       showCreateModal: false,
       isGroupConsultation: false,
       groupPatients: [],
       createMedicationModal: null,
       consultationSummaryModal: null,
       visitDetailsModal: null,
       highlightedSuggestionIndex: -1,
       suggestionPages: {},
       currentSuggestionPage: 1,
       totalSuggestionPages: 1,
       suggestionsPerPage: 10,
       activeMedicationIndex: null,
       medicationSearchTimeout: null
     };
   },
   async mounted() {
     console.log('🎯 ConsultationForm mounted with route:', this.$route.query);
     
     // Load initial data
     await this.loadInitialData();
     
     // Initialize modals
     this.consultationSummaryModal = new bootstrap.Modal(document.getElementById('consultationSummaryModal'));
     this.visitDetailsModal = new bootstrap.Modal(document.getElementById('visitDetailsModal'));
   }
 };
 </script>

<style scoped>
.consultation-form {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 0;
}
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

/* Enhanced Button Styling */
.btn {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 1rem;
  border-radius: 8px;
  border: none;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: #fff;
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}
.btn-outline-secondary {
  background: #fff;
  color: #6c757d;
  border: 2px solid #6c757d;
}
.btn-outline-secondary:hover {
  background: #6c757d;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(108,117,125,0.3);
}
.btn i {
  margin-right: 0.5rem;
}

@media (max-width: 991px) {
  .col-lg-7, .col-lg-5 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
@media (max-width: 768px) {
  .consultation-form {
    padding: 1rem 0;
  }
  .card-body {
    padding: 1rem;
  }
  .btn {
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
  }
}

.form-floating > .form-control,
.form-floating > .form-select {
  height: calc(3.5rem + 2px);
  line-height: 1.25;
}

/* Patient Dropdown Styling */
.patient-dropdown .dropdown-item {
  border: none !important;
  padding: 0.75rem 1rem !important;
  transition: all 0.2s ease;
  color: #333 !important;
}

.patient-dropdown .dropdown-item:hover {
  background-color: #f8f9fa !important;
  color: #000 !important;
}

.patient-dropdown .dropdown-item.active {
  background-color: #fff3cd !important;
  color: #856404 !important;
  border-left: 4px solid #ffc107 !important;
}

.patient-dropdown .patient-name {
  color: #333 !important;
  font-size: 1rem;
}

.patient-dropdown .patient-details small {
  color: #666 !important;
}

.patient-dropdown .badge {
  font-size: 0.7rem;
}

.patient-select-btn {
  font-size: 0.9rem !important;
  white-space: nowrap;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

/* Ensure dropdown shows above everything */
.dropdown-menu {
  z-index: 1055 !important;
}

/* Enhanced Save Current Patient Button */
.save-patient-btn {
  position: relative;
  overflow: hidden;
  animation: saveButtonGlow 2s ease-in-out infinite alternate;
}

.save-patient-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4) !important;
}

.save-patient-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.save-patient-btn:hover::before {
  left: 100%;
}

@keyframes saveButtonGlow {
  0% { 
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  }
  100% { 
    box-shadow: 0 4px 25px rgba(40, 167, 69, 0.6);
  }
}

.form-floating > textarea.form-control {
  height: 100px;
}

.form-floating > label {
  padding: 1rem 0.75rem;
}

.card {
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.patient-details {
  font-size: 0.95rem;
  padding: 2rem !important;
  min-width: 0;
  box-sizing: border-box;
  max-width: 700px;
  margin: 0 auto;
}

/* Professional Sticky Patient Topbar */
.sticky-patient-topbar {
  position: fixed !important;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1040;
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3498db 100%) !important;
  border-bottom: 3px solid rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(15px);
}

.sticky-patient-topbar:hover {
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.25);
}

.sticky-patient-topbar .patient-name {
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
  font-size: 1.5rem;
}

.sticky-patient-topbar .badge {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 600;
}

.sticky-patient-topbar .patient-meta i {
  opacity: 0.9;
}

/* Enhanced Patient Avatar */
.sticky-patient-topbar .patient-avatar {
  position: relative;
}

.sticky-patient-topbar .patient-avatar::before {
  content: '';
  position: absolute;
  top: -5px;
  left: -5px;
  right: -5px;
  bottom: -5px;
  background: linear-gradient(45deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
  border-radius: 50%;
  z-index: -1;
}

/* Professional Animation */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.sticky-patient-topbar {
  animation: fadeInDown 0.5s ease-out;
}

/* Responsive Design */
@media (max-width: 768px) {
  .sticky-patient-topbar .patient-name {
    font-size: 1.2rem;
  }
  
  .sticky-patient-topbar .patient-meta {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .sticky-patient-topbar .badge {
    font-size: 0.75rem;
  }
}

.patient-details {
  background: #f8f9fa;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.patient-details .fa-user-circle {
  min-width: 56px;
  min-height: 56px;
  margin-right: 1.5rem;
}
@media (max-width: 768px) {
  .patient-details {
    flex-direction: column !important;
    padding: 1rem !important;
    max-width: 100%;
  }
  .patient-details .fa-user-circle {
    margin: 0 auto 1rem auto;
    display: block;
  }
}



/* Medication Prescription Styling */
.medication-row {
  background: #f8f9fa;
  border: 1px solid #dee2e6 !important;
  border-radius: 12px;
  position: relative;
}

.medication-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.suggestions-header {
  padding: 0.5rem 0.75rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  font-size: 0.85rem;
}

.suggestion-item {
  padding: 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f3f4;
  transition: all 0.2s ease;
}

.suggestion-item:hover,
.suggestion-highlighted {
  background-color: #e3f2fd;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-main {
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
}

.suggestion-create-new {
  background-color: #f0f9f0;
  border-top: 2px solid #28a745;
}

.suggestion-create-new:hover {
  background-color: #e8f5e8;
}

.suggestion-create-new .suggestion-main {
  color: #28a745;
  font-weight: 500;
}

.suggestions-pagination {
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.suggestions-pagination .btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  min-width: 30px;
}

.suggestions-pagination .btn-outline-secondary {
  border-color: #6c757d;
  color: #6c757d;
}

.suggestions-pagination .btn-outline-secondary:hover:not(:disabled) {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
}

.suggestions-pagination .btn-outline-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.medication-row .btn-outline-danger {
  border: 1px solid #dc3545;
  color: #dc3545;
}

.medication-row .btn-outline-danger:hover {
  background: #dc3545;
  color: white;
}

/* Visit Details Modal Styling */
.visit-details-modal {
  border-radius: 16px;
  border: none;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}
.visit-details-modal .modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #dee2e6;
  border-radius: 16px 16px 0 0;
  padding: 1.5rem;
}
.visit-details-modal .modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #222;
}
.visit-details-modal .modal-body {
  padding: 2rem;
}
.visit-info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
.info-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.info-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: #222;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f8f9fa;
}
.info-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.info-item.full-width {
  grid-column: 1 / -1;
}
.info-item label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.info-item span {
  font-size: 1rem;
  color: #222;
  word-wrap: break-word;
}
.medications-table {
  grid-column: 1 / -1;
}
.medications-table .table {
  margin-bottom: 0;
  font-size: 0.9rem;
}
.medications-table .table th {
  background: #f8f9fa;
  font-weight: 600;
  border-top: none;
  border-bottom: 2px solid #dee2e6;
}
@media (max-width: 768px) {
  .visit-details-modal .modal-body {
    padding: 1rem;
  }
  .info-content {
    grid-template-columns: 1fr;
  }
  .info-card {
    padding: 1rem;
  }
}



/* Enhanced Patient Selector in Header */
.patient-select-btn {
  position: relative;
  overflow: hidden;
  border: 3px solid #fff !important;
  box-shadow: 0 4px 15px rgba(255,193,7,0.4) !important;
}

.patient-select-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.patient-select-btn:hover::before {
  left: 100%;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

.patient-dropdown {
  border: 3px solid #ffc107 !important;
  backdrop-filter: blur(10px);
  max-height: 500px;
  overflow-y: auto;
}

.patient-item {
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.patient-item:hover {
  background: linear-gradient(135deg, #ffc107 0%, #ffcd39 100%) !important;
  color: #000 !important;
  border-left-color: #fff;
  transform: translateX(5px);
}

.patient-item.active {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
  color: #fff !important;
  border-left-color: #fff;
}

.patient-item.active:hover {
  background: linear-gradient(135deg, #20c997 0%, #28a745 100%) !important;
}

.patient-avatar i {
  transition: all 0.3s ease;
}

.patient-item:hover .patient-avatar i {
  transform: scale(1.1);
}

.patient-option {
  position: relative;
}

.patient-option::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 10%;
  right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, #dee2e6, transparent);
}

.patient-option:last-of-type::after {
  display: none;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .patient-select-btn {
    padding: 0.5rem 1rem !important;
  }
  
  .patient-dropdown {
    min-width: 300px !important;
  }
  
  .patient-select-btn .d-none.d-sm-inline {
    display: none !important;
  }
}
</style>

<script>
import axios from 'axios';
import { ref, reactive } from 'vue';
import AuthService from '../services/AuthService.js';

export default {
  name: 'ConsultationForm',
  data() {
    return {
      consultation: {
        patientId: null,
        patientName: '',
        doctorId: null,
        diagnosis: '',
        notes: '',
        medicationId: null,
        medicationName: '',
        medicationQuantity: 1,
        medicationDosage: '',
        medicationFrequency: '',
        medicationCategory: 'Tablet',
        duration: 1,
        medicine: [],
        medicineTotal: 0,
        consultationFee: 30.00,
        totalAmount: 30.00,
        isCompleted: false,
        hasMedicalCertificate: false,
        mcStartDate: null,
        mcEndDate: null
      },
      isEditing: false,
      patients: [],
      doctors: [],
      medications: [],
      medicationSuggestions: [],
      showSuggestions: false,
      medicationInputValue: '',
      loading: false,
      saving: false,
      medicationSearchLoading: false,
      showCustomMedicationModal: false,
      customMedication: {
        name: '',
        category: 'Tablet',
        price: 0,
        unitType: 'pcs'
      },
      prescribedMedications: [],
      selectedPatient: null,
      visitHistories: [],
      loadingHistory: false,
      selectedVisit: null,
      showCreateModal: false,
      isGroupConsultation: false,
      groupPatients: [],
      createMedicationModal: null,
      consultationSummaryModal: null,
      visitDetailsModal: null,
      highlightedSuggestionIndex: -1,
      suggestionPages: {},
      currentSuggestionPage: 1,
      totalSuggestionPages: 1,
      suggestionsPerPage: 10,
      activeMedicationIndex: null,
      medicationSearchTimeout: null
    };
  },
  computed: {
    selectedDoctor() {
      return this.doctors.find(doctor => doctor.id == this.$route.query.doctorId);
    },
    isDoctorSelected() {
      return this.selectedDoctor !== undefined;
    },
    totalMedicinesCost() {
      return this.prescribedMedications.reduce((total, med) => {
        return total + (parseFloat(med.actualPrice || med.price || 0) * parseInt(med.quantity || 1));
      }, 0);
    },
    formattedTotalCost() {
      const consultationFee = parseFloat(this.consultation.consultationFee || 0);
      const medicinesCost = this.totalMedicinesCost;
      const total = consultationFee + medicinesCost;
      return total.toFixed(2);
    }
  },
  watch: {
    '$route'(to, from) {
      if (to.query.queueId !== from.query.queueId || to.query.patientId !== from.query.patientId) {
        this.initializeFromRoute();
      }
    },
    consultation: {
      handler() {
        this.updateTotalAmount();
      },
      deep: true
    },
    prescribedMedications: {
      handler() {
        this.updateTotalAmount();
      },
      deep: true
    }
  },
  methods: {
    async initializeFromRoute() {
      console.log('🚀 INITIALIZING from route params:', this.$route.query);
      
      if (this.$route.query.queueId) {
        await this.loadFromQueue(this.$route.query.queueId);
      } else if (this.$route.query.patientId) {
        await this.loadPatientData(this.$route.query.patientId);
      }
    },
    
    updateTotalAmount() {
      const consultationFee = parseFloat(this.consultation.consultationFee || 0);
      const medicinesCost = this.totalMedicinesCost;
      this.consultation.totalAmount = consultationFee + medicinesCost;
    },
    
    async loadFromQueue(queueId) {
      try {
        console.log('Loading queue data for ID:', queueId);
        const response = await axios.get(`/api/queue/${queueId}`, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        const queueData = response.data;
        console.log('Queue data loaded:', queueData);
        
        if (queueData.patients && queueData.patients.length > 1) {
          this.isGroupConsultation = true;
          this.groupPatients = queueData.patients;
          this.selectedPatient = queueData.patients[0];
          console.log('🧑‍🧑‍🧒‍🧒 GROUP CONSULTATION detected with', queueData.patients.length, 'patients');
        } else if (queueData.patients && queueData.patients.length === 1) {
          this.selectedPatient = queueData.patients[0];
          this.isGroupConsultation = false;
        } else if (queueData.patient) {
          this.selectedPatient = queueData.patient;
          this.isGroupConsultation = false;
        }
        
        if (this.selectedPatient) {
          this.consultation.patientId = this.selectedPatient.id;
          this.consultation.patientName = this.selectedPatient.name || this.selectedPatient.displayName;
          await this.loadPatientVisitHistory(this.selectedPatient.id);
        }
        
        this.consultation.doctorId = queueData.doctorId || this.$route.query.doctorId;
        
      } catch (error) {
        console.error('Error loading queue data:', error);
        if (this.$route.query.patientId) {
          await this.loadPatientData(this.$route.query.patientId);
        }
      }
    },
    
    async loadPatientData(patientId) {
      try {
        console.log('Loading patient data for ID:', patientId);
        const response = await axios.get(`/api/patients/${patientId}`, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        this.selectedPatient = response.data;
        this.consultation.patientId = this.selectedPatient.id;
        this.consultation.patientName = this.selectedPatient.name;
        this.isGroupConsultation = false;
        
        await this.loadPatientVisitHistory(patientId);
        
      } catch (error) {
        console.error('Error loading patient data:', error);
      }
    },
    
    async loadPatientVisitHistory(patientId) {
      if (!patientId) return;
      
      try {
        this.loadingHistory = true;
        const response = await axios.get(`/api/patients/${patientId}/visit-history`, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        this.visitHistories = response.data || [];
        console.log('📋 Visit history loaded:', this.visitHistories.length, 'visits');
        
      } catch (error) {
        console.error('Error loading visit history:', error);
        this.visitHistories = [];
      } finally {
        this.loadingHistory = false;
      }
    },
    
    getPatientName(patientId) {
      if (this.selectedPatient) {
        return this.selectedPatient.name || this.selectedPatient.displayName || 'Unknown Patient';
      }
      return 'Unknown Patient';
    },
    
    calculatePatientAge() {
      if (!this.selectedPatient?.dateOfBirth) return 'N/A';
      
      const today = new Date();
      const birthDate = new Date(this.selectedPatient.dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      
      return age;
    },
    
    async handlePatientSwitch(patient) {
      console.log('🔄 SWITCH PATIENT CALLED - Patient ID:', patient.id);
      try {
        this.selectedPatient = patient;
        this.consultation.patientId = patient.id;
        this.consultation.patientName = patient.name || patient.displayName;
        
        await this.loadPatientVisitHistory(patient.id);
        
        console.log('✅ Successfully switched to patient:', patient.name);
        
      } catch (error) {
        console.error('❌ Error switching patient:', error);
      }
    },
    
    async loadDoctors() {
      try {
        const response = await axios.get('/api/doctors', {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        this.doctors = response.data;
      } catch (error) {
        console.error('Error loading doctors:', error);
        this.doctors = [];
      }
    },
    
    async loadMedications() {
      try {
        const response = await axios.get('/api/medications');
        this.medications = response.data;
      } catch (error) {
        console.error('Error loading medications:', error);
        this.medications = [];
      }
    },
    
    async searchMedications(query, targetIndex = null) {
      if (!query || query.length < 2) {
        this.medicationSuggestions = [];
        this.showSuggestions = false;
        return;
      }
      
      this.activeMedicationIndex = targetIndex;
      
      if (this.medicationSearchTimeout) {
        clearTimeout(this.medicationSearchTimeout);
      }
      
      this.medicationSearchTimeout = setTimeout(async () => {
        try {
          this.medicationSearchLoading = true;
          const response = await axios.get(`/api/medications/search`, {
            params: { 
              query: query,
              page: this.currentSuggestionPage,
              limit: this.suggestionsPerPage
            },
            headers: {
              'Authorization': `Bearer ${AuthService.getToken()}`
            }
          });
          
          const data = response.data;
          this.medicationSuggestions = data.medications || data.data || data;
          
          if (data.pagination) {
            this.totalSuggestionPages = data.pagination.totalPages || 1;
            this.currentSuggestionPage = data.pagination.currentPage || 1;
          } else {
            this.totalSuggestionPages = 1;
            this.currentSuggestionPage = 1;
          }
          
          this.showSuggestions = this.medicationSuggestions.length > 0;
          this.highlightedSuggestionIndex = -1;
          
        } catch (error) {
          console.error('Error searching medications:', error);
          this.medicationSuggestions = [];
          this.showSuggestions = false;
        } finally {
          this.medicationSearchLoading = false;
        }
      }, 300);
    },
    
    selectMedication(medication, targetIndex = null) {
      const targetMedication = targetIndex !== null ? this.prescribedMedications[targetIndex] : this.prescribedMedications[this.prescribedMedications.length - 1];
      
      if (targetMedication) {
        targetMedication.name = medication.name;
        targetMedication.category = medication.category;
        targetMedication.price = medication.price;
        targetMedication.actualPrice = medication.price;
        targetMedication.unitType = medication.unitType || 'pcs';
        targetMedication.medicationId = medication.id;
      }
      
      this.hideSuggestions();
    },
    
    async changeSuggestionPage(page) {
      if (page < 1 || page > this.totalSuggestionPages) return;
      
      this.currentSuggestionPage = page;
      const query = this.activeMedicationIndex !== null 
        ? this.prescribedMedications[this.activeMedicationIndex]?.name || ''
        : this.medicationInputValue;
      
      await this.searchMedications(query, this.activeMedicationIndex);
    },
    
    hideSuggestions() {
      this.showSuggestions = false;
      this.medicationSuggestions = [];
      this.activeMedicationIndex = null;
      this.highlightedSuggestionIndex = -1;
    },
    
    handleMedicationKeydown(event, index) {
      if (!this.showSuggestions || this.medicationSuggestions.length === 0) return;
      
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          this.highlightedSuggestionIndex = Math.min(
            this.highlightedSuggestionIndex + 1,
            this.medicationSuggestions.length - 1
          );
          break;
          
        case 'ArrowUp':
          event.preventDefault();
          this.highlightedSuggestionIndex = Math.max(this.highlightedSuggestionIndex - 1, -1);
          break;
          
        case 'Enter':
          event.preventDefault();
          if (this.highlightedSuggestionIndex >= 0 && this.highlightedSuggestionIndex < this.medicationSuggestions.length) {
            this.selectMedication(this.medicationSuggestions[this.highlightedSuggestionIndex], index);
          }
          break;
          
        case 'Escape':
          this.hideSuggestions();
          break;
      }
    },
    
    addMedication() {
      this.prescribedMedications.push({
        name: '',
        quantity: 1,
        dosage: '',
        frequency: '',
        duration: '1',
        category: 'Tablet',
        price: 0,
        actualPrice: 0,
        unitType: 'pcs',
        medicationId: null
      });
    },
    
    removeMedication(index) {
      this.prescribedMedications.splice(index, 1);
      this.updateTotalAmount();
    },
    
    openCreateMedicationModal() {
      this.customMedication = {
        name: '',
        category: 'Tablet',
        price: 0,
        unitType: 'pcs'
      };
      this.createMedicationModal.show();
    },
    
    async saveCustomMedication() {
      try {
        const response = await axios.post('/api/medications', this.customMedication, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        this.medications.push(response.data);
        this.createMedicationModal.hide();
        
        // Auto-select the newly created medication if we were adding one
        if (this.activeMedicationIndex !== null) {
          this.selectMedication(response.data, this.activeMedicationIndex);
        }
        
      } catch (error) {
        console.error('Error creating medication:', error);
        alert('Error creating medication. Please try again.');
      }
    },
    
    async showVisitDetails(visit) {
      try {
        // Load detailed visit information
        const response = await axios.get(`/api/consultations/${visit.id}/details`, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        this.selectedVisit = {
          ...visit,
          ...response.data,
          formattedDate: this.formatDateOfBirth(visit.consultationDate),
          formattedTime: this.formatTime(visit.consultationDate)
        };
        this.visitDetailsModal.show();
      } catch (error) {
        console.error('Error loading visit details:', error);
        // Fallback to basic visit data
        this.selectedVisit = visit;
        this.visitDetailsModal.show();
      }
    },
    getMedicationsList(visit) {
      if (!visit) return [];
      
      let medications = [];
      
      // Try to get from prescribedMedications first (structured data)
      if (visit.prescribedMedications && Array.isArray(visit.prescribedMedications)) {
        medications = visit.prescribedMedications;
      }
      // Fallback to medications field (JSON string)
      else if (visit.medications) {
        try {
          const parsed = typeof visit.medications === 'string' 
            ? JSON.parse(visit.medications) 
            : visit.medications;
          medications = Array.isArray(parsed) ? parsed : [];
        } catch (e) {
          console.warn('Error parsing medications:', e);
          medications = [];
        }
      }
      
      return medications;
    },
    getMedicationTotal(visit) {
      const medications = this.getMedicationsList(visit);
      return medications.reduce((total, med) => {
        const price = parseFloat(med.actualPrice || med.price || 0);
        return total + price;
      }, 0);
    },
    calculateMCDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      
      try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
        return diffDays;
      } catch (error) {
        console.error('Error calculating MC days:', error);
        return 0;
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
    },
    reviewMC() {
      // Show MC modal or perform MC review logic
      // Placeholder: you can implement your modal logic here
      alert('Review MC clicked!');
    },
    formatQueueNumber(queueNumber) {
      if (!queueNumber) return '';
      queueNumber = queueNumber.toString();
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 'N/A';
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    },
    
    printMedicationLabel(medItem) {
      // Use the exact format from your clinic's medication label
      const patientName = this.selectedPatient?.name || this.selectedPatient?.displayName || 'N/A';
      const today = new Date();
      const formattedDate = `${today.getDate().toString().padStart(2, '0')}/${(today.getMonth() + 1).toString().padStart(2, '0')}/${today.getFullYear()}`;
      
      const labelContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <title>Medication Label - ${patientName}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }
            
            body {
              font-family: Arial, sans-serif;
              margin: 10mm;
            }
            
            .medication-label {
              width: 85mm;
              height: 55mm;
              border: 2px solid #000;
              padding: 3mm;
              background: white;
              font-size: 9pt;
              line-height: 1.1;
            }
            
            .label-border {
              height: 100%;
              border: 1px solid #000;
              padding: 2mm;
            }
            
            .label-header {
              text-align: center;
              border-bottom: 1px solid #000;
              padding-bottom: 2mm;
              margin-bottom: 2mm;
            }
            
            .clinic-name {
              font-size: 12pt;
              font-weight: bold;
              margin-bottom: 1mm;
              letter-spacing: 0.5px;
            }
            
            .clinic-details {
              font-size: 7pt;
              line-height: 1.2;
            }
            
            .label-content {
              height: calc(100% - 15mm);
            }
            
            .row-item {
              display: flex;
              align-items: center;
              margin-bottom: 1.5mm;
              height: 4.5mm;
            }
            
            .label-text {
              font-size: 8pt;
              font-weight: bold;
              width: 15mm;
              flex-shrink: 0;
            }
            
            .field-box {
              flex: 1;
              height: 4mm;
              border: 1px solid #000;
              margin-left: 2mm;
              padding: 0 1mm;
              display: flex;
              align-items: center;
              font-size: 8pt;
            }
            
            .field-box.filled {
              background-color: transparent;
              font-weight: bold;
            }
            
            .checkbox-section {
              margin-top: 2mm;
              display: flex;
              align-items: center;
              gap: 5mm;
            }
            
            .checkbox-group {
              display: flex;
              align-items: center;
              gap: 1mm;
            }
            
            .checkbox {
              width: 3mm;
              height: 3mm;
              border: 1px solid #000;
              position: relative;
            }
            
            .checkbox.checked::after {
              content: '✓';
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              font-size: 6pt;
              font-weight: bold;
            }
            
            .checkbox-label {
              font-size: 7pt;
              font-weight: bold;
            }
            
            .dosage-section {
              margin-top: 2mm;
              border-top: 1px solid #000;
              padding-top: 2mm;
            }
            
            .dosage-grid {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 2mm;
              font-size: 7pt;
            }
            
            .dosage-item {
              display: flex;
              align-items: center;
              gap: 1mm;
            }
            
            .dosage-box {
              width: 8mm;
              height: 3mm;
              border: 1px solid #000;
              display: flex;
              align-items: center;
              justify-content: center;
              font-weight: bold;
            }
          </style>
        </head>
        <body onload="window.print(); window.close();">
          <div class="medication-label">
            <div class="label-border">
              <div class="label-header">
                <div class="clinic-name">KLINIK HIDUP SIHAT</div>
                <div class="clinic-details">
                  No. 123, Jalan Kesihatan,<br>
                  Kuala Lumpur, Malaysia<br>
                  Tel: 03-1234 5678
                </div>
              </div>
              
              <div class="label-content">
                <div class="row-item">
                  <span class="label-text">Name:</span>
                  <div class="field-box filled">${patientName}</div>
                </div>
                
                <div class="row-item">
                  <span class="label-text">IC:</span>
                  <div class="field-box filled">${this.selectedPatient?.nric || this.selectedPatient?.ic || 'N/A'}</div>
                </div>
                
                <div class="row-item">
                  <span class="label-text">Date:</span>
                  <div class="field-box filled">${formattedDate}</div>
                </div>
                
                <div class="row-item">
                  <span class="label-text">Med:</span>
                  <div class="field-box filled">${medItem.name}</div>
                </div>
                
                <div class="row-item">
                  <span class="label-text">Qty:</span>
                  <div class="field-box filled">${medItem.quantity} ${medItem.unitType || 'pcs'}</div>
                </div>
                
                <div class="checkbox-section">
                  <div class="checkbox-group">
                    <div class="checkbox ${medItem.category === 'Tablet' ? 'checked' : ''}"></div>
                    <span class="checkbox-label">Tab</span>
                  </div>
                  <div class="checkbox-group">
                    <div class="checkbox ${medItem.category === 'Syrup' ? 'checked' : ''}"></div>
                    <span class="checkbox-label">Syr</span>
                  </div>
                  <div class="checkbox-group">
                    <div class="checkbox ${medItem.category === 'Capsule' ? 'checked' : ''}"></div>
                    <span class="checkbox-label">Cap</span>
                  </div>
                  <div class="checkbox-group">
                    <div class="checkbox ${medItem.category === 'Injection' ? 'checked' : ''}"></div>
                    <span class="checkbox-label">Inj</span>
                  </div>
                </div>
                
                <div class="dosage-section">
                  <div class="dosage-grid">
                    <div class="dosage-item">
                      <span>Morning:</span>
                      <div class="dosage-box">${medItem.dosage || ''}</div>
                    </div>
                    <div class="dosage-item">
                      <span>Afternoon:</span>
                      <div class="dosage-box">${medItem.dosage || ''}</div>
                    </div>
                    <div class="dosage-item">
                      <span>Evening:</span>
                      <div class="dosage-box">${medItem.dosage || ''}</div>
                    </div>
                    <div class="dosage-item">
                      <span>Night:</span>
                      <div class="dosage-box">${medItem.dosage || ''}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </body>
        </html>
      `;
      
      const printWindow = window.open('', '_blank', 'width=400,height=300');
      printWindow.document.write(labelContent);
      printWindow.document.close();
    },
    
    async saveConsultation() {
      if (!this.selectedPatient) {
        alert('Please select a patient first.');
        return;
      }
      
      if (!this.consultation.diagnosis && !this.consultation.notes) {
        alert('Please enter either a diagnosis or notes for the consultation.');
        return;
      }
      
      try {
        this.saving = true;
        
        const formData = {
          patientId: this.consultation.patientId,
          doctorId: this.consultation.doctorId || this.$route.query.doctorId,
          queueId: this.$route.query.queueId,
          diagnosis: this.consultation.diagnosis,
          notes: this.consultation.notes,
          consultationFee: this.consultation.consultationFee,
          totalAmount: this.consultation.totalAmount,
          prescribedMedications: this.prescribedMedications.filter(med => med.name),
          hasMedicalCertificate: this.consultation.hasMedicalCertificate,
          mcStartDate: this.consultation.mcStartDate,
          mcEndDate: this.consultation.mcEndDate
        };
        
        console.log('💾 Saving consultation:', formData);
        
        const response = await axios.post('/api/consultations', formData, {
          headers: {
            'Authorization': `Bearer ${AuthService.getToken()}`
          }
        });
        
        console.log('✅ Consultation saved:', response.data);
        
        // Show success summary
        this.consultationSummary = {
          ...response.data,
          patient: this.selectedPatient,
          doctor: this.selectedDoctor,
          medications: this.prescribedMedications.filter(med => med.name)
        };
        
        this.consultationSummaryModal.show();
        
      } catch (error) {
        console.error('❌ Error saving consultation:', error);
        alert('Error saving consultation. Please try again.');
      } finally {
        this.saving = false;
      }
    },
    
    formatDateOfBirth(dateStr) {
      if (!dateStr) return 'N/A';
      try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } catch (error) {
        return 'Invalid Date';
      }
    },
    
    formatTime(dateStr) {
      if (!dateStr) return 'N/A';
      try {
        const date = new Date(dateStr);
        return date.toLocaleTimeString('en-MY', {
          timeZone: 'Asia/Kuala_Lumpur',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        return 'Invalid Time';
      }
    },
    
    async loadInitialData() {
      await Promise.all([
        this.loadDoctors(),
        this.loadMedications()
      ]);
      
      await this.initializeFromRoute();
      
      // Add initial medication row
      if (this.prescribedMedications.length === 0) {
        this.addMedication();
      }
    }
  },
  
  async mounted() {
    console.log('🎯 ConsultationForm mounted with route:', this.$route.query);
    
    // Load initial data after setting up parameters
    await this.loadInitialData();
    
    // Initialize modals
    this.consultationSummaryModal = new bootstrap.Modal(document.getElementById('consultationSummaryModal'));
    this.visitDetailsModal = new bootstrap.Modal(document.getElementById('visitDetailsModal'));
  }
};
</script>
