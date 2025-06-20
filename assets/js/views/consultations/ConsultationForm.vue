<template>
  <div class="consultation-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Consultation</h2>
        <small v-if="$route.query.queueNumber" class="text-muted">
          <i class="fas fa-link me-1"></i>Started from Queue #{{ formatQueueNumber($route.query.queueNumber) }}
        </small>
      </div>
      <div class="d-flex gap-2">

      </div>
    </div>

    <form @submit.prevent="saveConsultation" class="row g-4">
      <!-- Group Consultation Toggle and Patient Selector -->
      <div v-if="isGroupConsultation" class="col-12">
        <div class="card border-info mb-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">
              <i class="fas fa-users me-2"></i>
              Group Consultation - Queue #{{ queueNumber }}
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Select Patient for Consultation:</label>
                <select 
                  class="form-select" 
                  v-model="consultation.patientId" 
                  @change="fetchPatientDetails"
                  required
                >
                  <option value="">Choose patient to consult...</option>
                  <option 
                    v-for="patient in groupPatients" 
                    :key="patient.id" 
                    :value="patient.id"
                  >
                    {{ patient.name }} ({{ patient.relationship || 'N/A' }})
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <div class="group-members-info">
                  <label class="form-label fw-bold">Group Members ({{ groupPatients.length }}):</label>
                  <div class="members-list">
                    <span 
                      v-for="(member, index) in groupPatients" 
                      :key="member.id"
                      class="badge me-2 mb-1"
                      :class="member.id === consultation.patientId ? 'bg-primary' : 'bg-secondary'"
                    >
                      {{ member.name }}
                      <small v-if="member.relationship" class="ms-1">({{ member.relationship }})</small>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Patient Information -->
      <div class="col-12 col-lg-8">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-user-injured text-primary me-2"></i>
              Patient Information
              <span v-if="isGroupConsultation && selectedPatient" class="badge bg-info ms-2">
                {{ selectedPatient.relationship || 'Group Member' }}
              </span>
            </h5>
            <div class="row g-3">
              <div class="col-12" v-if="selectedPatient">
                <div class="patient-details p-3 bg-light rounded w-100 d-flex align-items-start gap-3">
                  <div>
                    <i class="fas fa-user-circle fa-3x text-secondary"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="row g-2">
                      <div class="col-md-6">
                        <small class="text-muted d-block">Name</small>
                        <span class="fw-bold">{{ selectedPatient?.name || selectedPatient?.displayName || 'N/A' }}</span>
                      </div>

                      <div class="col-md-6">
                        <small class="text-muted d-block">IC/Passport</small>
                        <span>{{ selectedPatient?.nric || selectedPatient?.ic || 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Age</small>
                        <span>{{ selectedPatient && selectedPatient.dateOfBirth ? calculateAge(selectedPatient.dateOfBirth) + ' years' : 'N/A' }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Date of Birth</small>
                        <span>{{ formatDateOfBirth(selectedPatient?.dateOfBirth) }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Gender</small>
                        <span>{{ getFullGender(selectedPatient?.gender) }}</span>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted d-block">Phone Number</small>
                        <span>{{ selectedPatient?.phoneNumber || selectedPatient?.phone || 'N/A' }}</span>
                      </div>
                      <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span>{{ selectedPatient?.address || 'N/A' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12" v-else>
                <div class="patient-details p-3 bg-light rounded w-100 text-center text-muted">
                  <i class="fas fa-user-circle fa-2x mb-2"></i>
                  <div>No patient selected. Please search and select a patient.</div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Medical History Section -->
      <div class="col-12 col-lg-8">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-history text-primary me-2"></i>
              Medical History
            </h5>
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

      <!-- Pre-Informed Illness -->
      <div class="col-12 col-lg-4">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-clipboard-check text-primary me-2"></i>
              Pre-Informed Illness
            </h5>
            <div v-if="selectedPatient && selectedPatient.preInformedIllness && selectedPatient.preInformedIllness.trim()">
              <div class="pre-illness-content p-3 bg-light rounded">
                <div class="d-flex align-items-start gap-3">
                  <div>
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="fw-bold text-dark mb-2">Patient's Initial Symptoms/Complaint</h6>
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

      <!-- Consultation Details -->
      <div class="col-12">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-notes-medical text-primary me-2"></i>
              Consultation Details
            </h5>
            <div class="row g-4">
              <div class="col-md-12">
                <div class="form-floating">
                  <textarea class="form-control" id="remark" v-model="consultation.notes" style="height: 100px" required></textarea>
                  <label for="remark">Remark</label>
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
                    <div class="col-md-5">
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
                        
                        <!-- Medication Suggestions Dropdown -->
                        <div v-if="medItem.suggestions && medItem.suggestions.length > 0" class="medication-suggestions">
                          <div class="suggestions-header">
                            <small class="text-muted"><i class="fas fa-search me-1"></i>Select from existing medications:</small>
                          </div>
                          <div class="suggestion-item" v-for="(suggestion, sIndex) in medItem.suggestions" :key="sIndex"
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
                          
                          <!-- Create new medication option - Always show if there's a name and no exact match -->
                          <div v-if="medItem.name && medItem.name.length >= 2 && !medItem.suggestions.some(s => s.name.toLowerCase() === medItem.name.toLowerCase())" 
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
                    
                    <div class="col-md-2">
                      <div class="form-floating">
                        <input
                          type="number"
                          class="form-control"
                          :id="`quantity-${index}`"
                          v-model.number="medItem.quantity"
                          min="1"
                          required
                        >
                        <label :for="`quantity-${index}`">Qty</label>
                      </div>
                      <small class="text-muted">{{ medItem.unitDescription || medItem.unitType || 'pieces' }}</small>
                    </div>
                    
                    <div class="col-md-2">
                      <div class="form-floating">
                        <input
                          type="number"
                          class="form-control"
                          :id="`price-${index}`"
                          v-model.number="medItem.actualPrice"
                          min="0"
                          step="0.01"
                          placeholder="0.00"
                        >
                        <label :for="`price-${index}`">Price (RM)</label>
                      </div>
                      <small class="text-muted">Final price</small>
                    </div>
                    
                    <div class="col-md-1">
                      <button type="button" class="btn btn-outline-danger btn-sm h-100" @click="removeMedicationRow(index)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                    
                    <div class="col-md-12" v-if="medItem.showInstructions">
                      <div class="form-floating">
                        <textarea
                          class="form-control"
                          :id="`instructions-${index}`"
                          v-model="medItem.instructions"
                          style="height: 60px"
                          placeholder="Dosage instructions..."
                        ></textarea>
                        <label :for="`instructions-${index}`">Instructions</label>
                      </div>
                    </div>
                    
                    <div class="col-md-12">
                      <button 
                        type="button" 
                        class="btn btn-link btn-sm p-0" 
                        @click="medItem.showInstructions = !medItem.showInstructions"
                      >
                        {{ medItem.showInstructions ? 'Hide' : 'Add' }} Instructions
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Legacy text area for backward compatibility -->
                <div class="mt-3">
                  <div class="form-floating">
                    <textarea class="form-control" id="medications-legacy" v-model="consultation.medications" style="height: 100px"></textarea>
                    <label for="medications-legacy">Additional Notes (Legacy)</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MC Checkbox and Dates: visible for doctor, after consultation details -->
      <div class="col-12 col-md-6">
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
              <button 
                type="button" 
                class="btn btn-outline-info btn-sm" 
                @click="showMCPreview" 
                v-if="consultation.hasMedicalCertificate && selectedPatient"
                :disabled="!consultation.mcStartDate || !consultation.mcEndDate">
                <i class="fas fa-eye me-1"></i> Review MC
              </button>
            </div>
            <div v-if="consultation.hasMedicalCertificate">
              <!-- If group consultation, show checkboxes for each patient -->
              <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="mb-3">
                <label class="form-label fw-bold">Select patients to print MC for:</label>
                <div v-for="patient in groupPatients" :key="patient.id" class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    :id="'mc-patient-' + patient.id" 
                    :value="patient.id" 
                    v-model="mcSelectedPatientIds"
                  >
                  <label class="form-check-label" :for="'mc-patient-' + patient.id">
                    {{ patient.name || patient.displayName || 'Unknown' }}
                    <small v-if="patient.relationship" class="text-muted">({{ patient.relationship }})</small>
                  </label>
                </div>
                <small class="text-muted d-block mt-2">
                  <i class="fas fa-info-circle me-1"></i>
                  Each selected patient will get a separate MC with the same dates
                </small>
              </div>
              
              <!-- Single patient MC (default behavior) -->
              <div v-else-if="!isGroupConsultation && selectedPatient" class="mb-3">
                <div class="alert alert-info">
                  <i class="fas fa-user me-2"></i>
                  MC will be issued for: <strong>{{ selectedPatient.name }}</strong>
                </div>
              </div>
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
          <div v-for="patient in groupPatients" :key="patient.id" v-if="mcSelectedPatientIds.includes(patient.id)">
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

      <!-- Payment Information -->
      <div class="col-12 col-md-6">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-money-bill text-primary me-2"></i>
              Payment Information
            </h5>
            <div class="row g-4">
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="number" class="form-control" id="totalAmount" v-model="consultation.totalAmount" step="0.10" min="0" required>
                  <label for="totalAmount">Total Amount (RM)</label>
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
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-check me-2"></i>
          Complete Consultation
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
            <!-- MC Preview Content -->
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
                    <label>Date:</label>
                    <span>{{ new Date(selectedVisit.consultationDate).toLocaleDateString() }}</span>
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

              <div class="info-card" v-if="selectedVisit.medications">
                <div class="info-header">
                  <i class="fas fa-pills text-primary"></i>
                  <span>Medications</span>
                </div>
                <div class="info-content">
                  <div class="medications-table">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Medication</th>
                          <th>Dosage</th>
                          <th>Frequency</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(med, index) in JSON.parse(selectedVisit.medications || '[]')" :key="index">
                          <td>{{ med.name || 'N/A' }}</td>
                          <td>{{ med.dosage || 'N/A' }}</td>
                          <td>{{ med.frequency || 'N/A' }}</td>
                        </tr>
                      </tbody>
                    </table>
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
// Helper to get today's date in YYYY-MM-DD npm npmormat
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

export default {
  name: 'ConsultationForm',
  components: {
    MedicalCertificateForm,
    PrescriptionForm
  },
  emits: [
    'patientAdded',
    'patientUpdated',
    'patientDeleted',
    'loginSuccess'
  ],
  data() {
    const today = new Date().toISOString().split('T')[0];
    return {
      consultation: {
        patientId: null,
        doctorId: null,
        consultationDate: today,
        diagnosis: '',
        notes: '',
        consultationFee: 0,
        hasMedicalCertificate: true,
        medicalCertificateDays: 1,
        mcStartDate: today,
        mcEndDate: today,
        medications: [],
        status: 'completed'
      },
      isEditing: !!this.$route.params.id,
      patients: [],
      doctors: [],
      groupPatients: [],
      mcSelectedPatientIds: [],
      isGroupConsultation: false,
      queueNumber: null,
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
      medicationSearchTimeout: null,
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
    };
  },
  computed: {
    selectedPatient() {
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      
      // For group consultations, check group patients first
      if (this.isGroupConsultation && this.groupPatients.length > 0) {
        return this.groupPatients.find(p => p.id === this.consultation.patientId);
      }
      
      return this.patients.find(p => p.id === this.consultation.patientId) || null;
    }
  },
  methods: {
    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return '';
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
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
          diagnosis: visit.diagnosis || '',
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
      try {
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
          diagnosis: this.consultation.diagnosis || '',
          status: this.consultation.status || 'pending',
          consultationFee: parseFloat(this.consultation.consultationFee) || 0,
          medications: JSON.stringify(this.consultation.medications || []), // Convert array to JSON string
          prescribedMedications: this.prescribedMedications.filter(med => med.name && med.quantity), // Send structured medications
          mcStartDate: this.consultation.mcStartDate || null,
          mcEndDate: this.consultation.mcEndDate || null,
          mcNotes: this.consultation.mcNotes || '',
          queueNumber: this.queueNumber,
          groupId: this.isGroupConsultation ? this.groupId : null,
          isGroupConsultation: this.isGroupConsultation
        };

        console.log('Sending consultation data:', consultationData);

        const response = await axios.post('/api/consultations', consultationData);
        console.log('Consultation saved successfully:', response.data);

        // Show success message
        alert('Consultation saved successfully');
        
        // Redirect to consultations list
        this.$router.push('/consultations');
      } catch (error) {
        console.error('Error saving consultation:', error);
        alert(error.response?.data?.message || error.message || 'Error saving consultation');
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
        suggestions: [],
        selectedSuggestionIndex: -1
      });
    },
    
    removeMedicationRow(index) {
      this.prescribedMedications.splice(index, 1);
    },
    
    async searchMedications(medItem, event) {
      const searchTerm = event.target.value;
      
      if (this.medicationSearchTimeout) {
        clearTimeout(this.medicationSearchTimeout);
      }
      
      if (searchTerm.length < 2) {
        medItem.suggestions = [];
        medItem.selectedSuggestionIndex = -1;
        return;
      }
      
      this.medicationSearchTimeout = setTimeout(async () => {
        try {
          const response = await axios.get(`/api/medications?search=${encodeURIComponent(searchTerm)}`);
          medItem.suggestions = response.data;
          medItem.selectedSuggestionIndex = 0; // Pre-select first suggestion
          
          // Always ensure the "create new" option is visible by adding a flag
          medItem.showCreateNew = true;
        } catch (error) {
          console.error('Error searching medications:', error);
          medItem.suggestions = [];
          medItem.selectedSuggestionIndex = -1;
          medItem.showCreateNew = true; // Still show create new on error
        }
      }, 300);
    },
    
    selectMedication(medItem, medication) {
      medItem.id = medication.id;
      medItem.medicationId = medication.id;
      medItem.name = medication.name;
      medItem.unitType = medication.unitType;
      medItem.unitDescription = medication.unitDescription;
      medItem.category = medication.category;
      medItem.actualPrice = medication.sellingPrice ? parseFloat(medication.sellingPrice) : 0.00;
      medItem.suggestions = [];
      medItem.selectedSuggestionIndex = -1;
    },
    
    handleMedicationBlur(medItem) {
      // Delay hiding suggestions to allow clicking on them
      setTimeout(() => {
        if (!medItem.medicationId && medItem.name && medItem.name.length >= 2) {
          // Check if exact match exists in current suggestions
          const exactMatch = medItem.suggestions.find(med => 
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
        medItem.suggestions = [];
        medItem.selectedSuggestionIndex = -1;
      }, 200);
    },
    
    selectFirstSuggestion(medItem) {
      if (medItem.suggestions && medItem.suggestions.length > 0) {
        this.selectMedication(medItem, medItem.suggestions[0]);
      }
    },
    
    clearSuggestions(medItem) {
      medItem.suggestions = [];
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
      medItem.suggestions = [];
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
    showVisitDetails(visit) {
      this.selectedVisit = visit;
      this.visitDetailsModal.show();
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
      // Ensure string
      queueNumber = queueNumber.toString();
      // Pad to 4 digits (e.g., 8001 -> 8001, 801 -> 0801)
      if (queueNumber.length === 4) return queueNumber;
      if (queueNumber.length === 3) return '0' + queueNumber;
      if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
      return queueNumber;
    },
    
    async loadGroupPatients() {
      if (!this.groupId) return;
      
      try {
        const response = await axios.get(`/api/queue/group/${this.groupId}`);
        const queueEntry = response.data;
        
        if (queueEntry && queueEntry.metadata && queueEntry.metadata.patients) {
          this.groupPatients = queueEntry.metadata.patients.map(patient => ({
            id: patient.id,
            name: patient.name,
            displayName: patient.name,
            nric: patient.nric,
            age: patient.age,
            gender: patient.gender,
            phone: patient.phone,
            relationship: patient.relationship,
            symptoms: patient.symptoms || ''
          }));
          
          // Set the currently selected patient to the primary patient if not already set
          if (!this.consultation.patientId && this.groupPatients.length > 0) {
            const primaryPatient = this.groupPatients.find(p => p.relationship === 'self') || this.groupPatients[0];
            this.consultation.patientId = primaryPatient.id;
          }
        }
      } catch (error) {
        console.error('Error loading group patients:', error);
        this.groupPatients = [];
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
    await this.loadPatients();
    await this.loadDoctors();
    this.medicalCertificateModal = new bootstrap.Modal(document.getElementById('medicalCertificateModal'));
    
    // Check if we're coming from the queue with patient/doctor info
    const routeQuery = this.$route.query;
    if (routeQuery.queueNumber && routeQuery.patientId && routeQuery.doctorId) {
      // Auto-fill from queue information
      this.consultation.patientId = parseInt(routeQuery.patientId);
      this.consultation.doctorId = parseInt(routeQuery.doctorId);
      this.queueNumber = routeQuery.queueNumber;
      
      // Check if this is a group consultation
      if (routeQuery.groupId) {
        this.isGroupConsultation = true;
        this.groupId = routeQuery.groupId;
        await this.loadGroupPatients();
      }
      
      // Load patient details
      await this.fetchPatientDetails();
    } else {
      // Set the doctor ID from the logged-in user or use the first doctor
      const user = JSON.parse(localStorage.getItem('user'));
      if (user && user.id) {
        this.consultation.doctorId = user.id;
      } else if (this.doctors && this.doctors.length > 0) {
        // Temporarily use the first doctor until auth is implemented
        this.consultation.doctorId = this.doctors[0].id;
        console.log('Using first doctor as default:', this.doctors[0]);
      }
    }
    
    if (this.$route.params.id) {
      await this.loadConsultation();
    } else {
      // Default the current date for MC
      this.consultation.mcStartDate = getTodayDate();
      this.consultation.mcEndDate = getTodayDate();
    }
  },
  
  mounted() {
    // Initialize modals - DOM manipulation should happen in mounted, not created
    const prescriptionModalEl = document.getElementById('prescriptionModal');
    if (prescriptionModalEl) { 
      this.prescriptionModal = new Modal(prescriptionModalEl);
    }
    
    // Initialize the MC preview modal as well
    const mcPreviewModalEl = document.getElementById('mcPreviewModal');
    if (mcPreviewModalEl) {
      this.mcPreviewModal = new Modal(mcPreviewModalEl);
    }

    // Initialize patient details if needed
    if (this.consultation.patientId) {
      this.fetchPatientDetails();
    }

    // Initialize visit details modal
    this.visitDetailsModal = new bootstrap.Modal(document.getElementById('visitDetailsModal'));
    
    // Initialize with one empty medication row
    this.addMedicationRow();
  }
}
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
</style>
