<template>
  <div class="consultation-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Consultation</h2>
        <small v-if="$route.query.queueNumber" class="text-muted">
                      <i class="fas fa-link me-1"></i>Started from Queue #{{ $route.query.queueNumber ? ($route.query.queueNumber.toString().length === 4 ? $route.query.queueNumber : $route.query.queueNumber.toString().length === 3 ? '0' + $route.query.queueNumber : $route.query.queueNumber.toString().padStart(4, '0')) : '' }}
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
                    :key="patient?.id || 'option-' + Math.random()" 
                    :value="patient?.id"
                    v-if="patient && patient.id"
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
                      :key="member?.id || 'member-' + index"
                      class="badge me-2 mb-1"
                      :class="member && member.id === consultation.patientId ? 'bg-primary' : 'bg-secondary'"
                      v-if="member && member.id"
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

      <!-- Pre-Informed Illness - First Row (Full Width) -->
      <div class="col-12">
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

      <!-- Patient Information - Second Row (Full Width) -->
      <div class="col-12">
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

      <!-- Medical History Section - Third Row (Full Width) -->
      <div class="col-12">
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

                    <div class="col-md-1">
                      <button class="btn btn-success me-2" @click="printMedicationLabel(medItem)" title="Print Medication Label">
                        <i class="fas fa-print"></i> Print Label
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

      <!-- MC Checkbox and Dates: visible for doctor, after remarks/diagnosis -->
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
              <button type="button" class="btn btn-outline-info btn-sm" @click="showMCPreview" v-if="consultation.hasMedicalCertificate && selectedPatient" :disabled="!consultation.mcStartDate || !consultation.mcEndDate">
                <i class="fas fa-eye me-1"></i> Review MC
              </button>
            </div>
            <div v-if="consultation.hasMedicalCertificate">
              <!-- If group consultation, show checkboxes for each patient -->
              <div v-if="isGroupConsultation && groupPatients && groupPatients.length > 1" class="mb-3">
                <label class="form-label fw-bold">Select patients to print MC for:</label>
                <div v-for="patient in groupPatients" :key="patient?.id || 'patient-' + Math.random()" class="form-check" v-if="patient && patient.id">
                  <input class="form-check-input" type="checkbox" :id="'mc-patient-' + patient.id" :value="patient.id" v-model="mcSelectedPatientIds">
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

      <!-- Medication Label -->
      <div class="col-12 col-md-6" v-if="selectedPatient">
        <div class="card section-card mb-4">
          <div class="card-body">
            <h5 class="section-title mb-4">
              <i class="fas fa-tags text-primary me-2"></i>
              Medication Label
            </h5>
            <p class="text-muted mb-3">
              Print medication label to stick on medication bags/bottles
            </p>
            <MedicationLabel :patient-name="selectedPatient.name || selectedPatient.displayName" />
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
                    <div class="medication-actions">
                      <button type="button" class="btn btn-sm btn-outline-primary" @click="printSingleMedicationLabel(medication)">
                        <i class="fas fa-print me-1"></i>Print Label
                      </button>
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
                  <div class="step-item" v-if="consultationSummary.hasMedications">
                    <i class="fas fa-tags text-warning me-2"></i>
                    <span>Print and attach medication labels</span>
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
import MedicationLabel from '../../components/MedicationLabel.vue';
import * as bootstrap from 'bootstrap';
import { makeProtectedRequest, cancelAllRequests } from '../../utils/requestManager.js';
import searchDebouncer from '../../utils/searchDebouncer';
import AuthService from '../../services/AuthService';

export default {
  name: 'ConsultationForm',
  components: {
    MedicalCertificateForm,
    PrescriptionForm,
    MedicationLabel
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
        hasMedicalCertificate: true,
        medicalCertificateDays: 1,
        mcStartDate: getTodayDate(),
        mcEndDate: getTodayDate(),
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
      if (this.fullPatientDetails && this.fullPatientDetails.id === this.consultation.patientId) {
        return this.fullPatientDetails;
      }
      
      // For group consultations, check group patients first
      if (this.isGroupConsultation && Array.isArray(this.groupPatients) && this.groupPatients.length > 0) {
        return this.groupPatients.find(p => p.id === this.consultation.patientId);
      }
      
      // Ensure patients is an array before calling find
      if (Array.isArray(this.patients)) {
        return this.patients.find(p => p.id === this.consultation.patientId) || null;
      }
      
      return null;
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
          groupId: this.isGroupConsultation ? this.groupId : null,
          isGroupConsultation: this.isGroupConsultation
        };

        console.log('Sending consultation data:', consultationData);
        console.log('Prescribed medications details:', this.prescribedMedications.map(med => ({
          name: med.name,
          medicationId: med.medicationId,
          quantity: med.quantity,
          actualPrice: med.actualPrice,
          unitType: med.unitType
        })));

        const response = await axios.post('/api/consultations', consultationData);
        console.log('Consultation saved successfully:', response.data);

        // Show success message
        alert('✅ Consultation saved successfully!');
        
        // Clear loading state before redirect
        this.isLoading = false;
        
        // Redirect based on user role instead of showing modal
        const currentUser = AuthService.getCurrentUser();
        if (currentUser && currentUser.roles && currentUser.roles.includes('ROLE_DOCTOR')) {
          // Doctors should go back to ongoing consultations
          this.$router.push('/consultations/ongoing');
        } else {
          // Others go to consultations list for payment processing
          this.$router.push('/consultations');
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
      queueNumber = queueNumber.toString();
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
        
        // Load group patients if applicable
        if (this.isGroupConsultation) {
          await this.loadGroupPatients();
        }
        
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
        hasMedicalCertificate: this.consultation.hasMedicalCertificate || false,
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
          await this.loadGroupPatients();
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
</style>
