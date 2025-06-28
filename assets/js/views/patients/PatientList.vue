<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Patients</h2>

    </div>

    <!-- Search Bar -->
    <div class="row mb-3">
      <div class="col-md-6">
        <div class="input-group">
          <input 
            type="text" 
            class="form-control" 
            placeholder="Search by name, NRIC, or phone" 
            v-model="searchQuery" 
            @input="onSearchInput"
          >
          <button class="btn btn-outline-primary" @click="searchPatients" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin me-1"></i>
            Search
          </button>
          <button class="btn btn-outline-secondary" @click="clearSearch" v-if="searchQuery">Clear</button>
        </div>
        <small v-if="searchQuery && !loading" class="text-muted mt-1 d-block">
          <i class="fas fa-filter me-1"></i>
          Showing results for "{{ searchQuery }}" ({{ patients.length }} found)
        </small>
      </div>
    </div>
    <!-- Patient List -->
    <div class="card">
      <div class="card-body">
        <div v-if="loading" class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>NRIC</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(patient, index) in patients" :key="patient.id">
                <td>{{ index + 1 }}</td>
                <td>{{ patient.name }}</td>
                <td>{{ patient.nric || 'N/A' }}</td>
                <td>{{ patient.phone }}</td>
                <td>{{ patient.gender === 'M' ? 'Male' : patient.gender === 'F' ? 'Female' : 'N/A' }}</td>
                                    <td>{{ formatDateOfBirth(patient.dateOfBirth) }}</td>
                <td>
                  <button class="btn btn-sm btn-secondary me-1" @click="showVisitHistory(patient)" title="View Visit History">
                    <i class="fas fa-history"></i>
                  </button>
                  <button class="btn btn-sm btn-info me-1" @click="editPatient(patient)" title="Edit Patient">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    v-if="isSuperAdmin" 
                    class="btn btn-sm btn-danger" 
                    @click="deletePatient(patient)" 
                    title="Delete Patient"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="patients.length === 0">
                <td colspan="7" class="text-center">
                  <div class="py-4">
                    <i class="fas fa-search fa-2x text-muted mb-2"></i>
                    <div v-if="searchQuery">
                      No patients found matching "{{ searchQuery }}"
                      <br>
                      <small class="text-muted">Try a different search term or clear the search to see all patients</small>
                    </div>
                    <div v-else>
                      No patients found
                      <br>
                      <small class="text-muted">Start by adding a new patient</small>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div class="d-flex justify-content-between align-items-center mt-4" v-if="totalPages > 1">
        <div class="d-flex align-items-center">
            <span class="text-muted me-3">
                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalPatients) }} of {{ totalPatients }} patients
            </span>
            <select v-model="perPage" @change="loadPatients" class="form-select form-select-sm" style="width: 75px;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <nav>
            <ul class="pagination mb-0">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                </li>
                <li class="page-item" v-for="page in pages" :key="page" :class="{ active: currentPage === page }">
                    <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" tabindex="-1" v-if="showAddModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingPatient ? 'Edit Patient' : 'Add Patient' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <form @submit.prevent="savePatient">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" v-model="form.name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">NRIC</label>
                    <input type="text" 
                           class="form-control" 
                           v-model="form.nric" 
                           :readonly="editingPatient"
                           :class="{ 'bg-light': editingPatient }"
                           @blur="checkNricUniqueness"
                           required>
                    <small class="text-muted" v-if="editingPatient">NRIC cannot be changed</small>
                    <small class="text-danger" v-if="nricError">{{ nricError }}</small>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" v-model="form.email">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" v-model="form.phone" required>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" v-model="form.dateOfBirth" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select class="form-select" v-model="form.gender" required>
                      <option value="">Select Gender</option>
                      <option value="M">Male</option>
                      <option value="F">Female</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" v-model="form.address">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Company</label>
                <input type="text" class="form-control" v-model="form.company">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Pre-informed Illness/Symptoms</label>
                <textarea class="form-control" v-model="form.preInformedIllness" rows="2" placeholder="Initial symptoms or complaints reported during registration"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Medical History</label>
                <textarea class="form-control" v-model="form.medicalHistory" rows="3" placeholder="Past medical conditions, surgeries, medications, etc."></textarea>
              </div>
              
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving || nricError">
                  {{ saving ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showAddModal"></div>

    <!-- Visit History Modal -->
    <div class="modal fade" :class="{ show: showVisitHistoryModal }" tabindex="-1" v-if="showVisitHistoryModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-history me-2"></i>
              Visit History - {{ selectedPatientForHistory?.name }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeVisitHistoryModal"></button>
          </div>
          <div class="modal-body">
            <!-- Patient Summary -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card bg-light">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-3">
                        <strong>Name:</strong> {{ selectedPatientForHistory?.name }}
                      </div>
                      <div class="col-md-3">
                        <strong>NRIC:</strong> {{ selectedPatientForHistory?.nric || 'N/A' }}
                      </div>
                      <div class="col-md-3">
                        <strong>Phone:</strong> {{ selectedPatientForHistory?.phone }}
                      </div>
                      <div class="col-md-3">
                        <strong>Age:</strong> {{ calculateAge(selectedPatientForHistory?.dateOfBirth) }} years
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Visit History -->
            <div v-if="loadingVisitHistory" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading visit history...</span>
              </div>
              <p class="mt-2">Loading visit history...</p>
            </div>

            <div v-else-if="visitHistory.length === 0" class="text-center py-5">
              <i class="fas fa-file-medical-alt fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Visit History</h5>
              <p class="text-muted">This patient has not visited the clinic yet.</p>
            </div>

            <div v-else>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Total Visits: <span class="badge bg-primary">{{ visitHistory.length }}</span></h6>
                <small class="text-muted">Click on any visit to view detailed information</small>
              </div>

              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-dark">
                    <tr>
                      <th>Date</th>
                      <th>Doctor</th>
                      <th>Consultation Details</th>
                      <th>Status</th>
                      <th>Fees</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="visit in visitHistory" :key="visit.id" class="cursor-pointer">
                      <td>
                        <strong>{{ formatDate(visit.consultationDate) }}</strong>
                        <br>
                        <small class="text-muted">{{ formatTime(visit.consultationDate) }}</small>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <i class="fas fa-user-md text-primary me-2"></i>
                          Dr. {{ visit.doctor?.name || 'Unknown' }}
                        </div>
                      </td>
                      <td>
                        <span v-if="visit.notes" class="text-truncate" style="max-width: 200px; display: inline-block;" :title="visit.notes">{{ visit.notes }}</span>
                        <span v-else class="text-muted">No consultation details recorded</span>
                      </td>
                      <td>
                        <span :class="getStatusBadgeClass(visit.status)">
                          {{ visit.status || 'Completed' }}
                        </span>
                      </td>
                      <td>
                        <div v-if="visit.consultationFee || visit.medicinesFee">
                          <div v-if="visit.consultationFee">
                            <small class="text-muted">Consultation:</small> RM{{ visit.consultationFee }}
                          </div>
                          <div v-if="visit.medicinesFee">
                            <small class="text-muted">Medicines:</small> RM{{ visit.medicinesFee }}
                          </div>
                          <div class="fw-bold">
                            <small class="text-muted">Total:</small> RM{{ (parseFloat(visit.consultationFee || 0) + parseFloat(visit.medicinesFee || 0)).toFixed(2) }}
                          </div>
                        </div>
                        <span v-else class="text-muted">No fee data</span>
                      </td>
                      <td>
                        <div class="btn-group">
                          <button class="btn btn-sm btn-primary" @click="viewVisitDetails(visit)">
                            <i class="fas fa-eye me-1"></i>Details
                          </button>
                          <button 
                            v-if="visit.receiptNumber" 
                            class="btn btn-sm btn-success" 
                            @click="viewReceipt(visit)"
                            title="View Receipt"
                          >
                            <i class="fas fa-receipt me-1"></i>Receipt
                          </button>
                          <button 
                            v-if="visit.hasMedicalCertificate && visit.mcRunningNumber" 
                            class="btn btn-sm btn-warning" 
                            @click="viewMedicalCertificate(visit)"
                            title="View Medical Certificate"
                          >
                            <i class="fas fa-certificate me-1"></i>MC
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeVisitHistoryModal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showVisitHistoryModal"></div>

    <!-- Visit Details Modal -->
    <div class="modal fade" :class="{ show: showVisitDetailsModal }" tabindex="-1" v-if="showVisitDetailsModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="fas fa-file-medical me-2"></i>
              Visit Details - {{ formatDate(selectedVisit?.consultationDate) }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeVisitDetailsModal"></button>
          </div>
          <div class="modal-body" v-if="selectedVisit">
            <div class="row g-4">
              <!-- Visit Information -->
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Visit Information</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-2">
                      <strong>Date & Time:</strong><br>
                      {{ formatDate(selectedVisit.consultationDate) }} at {{ formatTime(selectedVisit.consultationDate) }}
                    </div>
                    <div class="mb-2">
                      <strong>Doctor:</strong><br>
                      Dr. {{ selectedVisit.doctor?.name || 'Unknown' }}
                    </div>
                    <div class="mb-2">
                      <strong>Status:</strong><br>
                      <span :class="getStatusBadgeClass(selectedVisit.status)">
                        {{ selectedVisit.status || 'Completed' }}
                      </span>
                    </div>
                    <div v-if="selectedVisit.queueNumber">
                      <strong>Queue Number:</strong><br>
                      {{ selectedVisit.queueNumber }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Medical Information -->
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Medical Information</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <strong>Consultation Details:</strong><br>
                      <div v-if="selectedVisit.notes" class="bg-light p-2 rounded">
                        {{ selectedVisit.notes }}
                      </div>
                      <span v-else class="text-muted">No consultation details recorded</span>
                    </div>
                    <div v-if="selectedVisit.diagnosis">
                      <strong>Diagnosis:</strong><br>
                      <div class="bg-light p-2 rounded">
                        {{ selectedVisit.diagnosis }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Medications -->
              <div class="col-12" v-if="selectedVisit.medications && selectedVisit.medications.length > 0">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-pills me-2"></i>Prescribed Medications</h6>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Medication</th>
                            <th>Quantity</th>
                            <th>Price (RM)</th>
                            <th>Instructions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(med, index) in selectedVisit.medications" :key="index">
                            <td>
                              <strong>{{ med.name }}</strong>
                              <small v-if="med.category" class="text-muted d-block">({{ med.category }})</small>
                            </td>
                            <td>{{ med.quantity }} {{ med.unitType || 'units' }}</td>
                            <td>{{ med.actualPrice ? parseFloat(med.actualPrice).toFixed(2) : 'N/A' }}</td>
                            <td>{{ med.instructions || 'No instructions' }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Financial Information -->
              <div class="col-12">
                <div class="card">
                  <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Financial Information</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <strong>Consultation Fee:</strong><br>
                        RM {{ selectedVisit.consultationFee ? parseFloat(selectedVisit.consultationFee).toFixed(2) : '0.00' }}
                      </div>
                      <div class="col-md-4">
                        <strong>Medicines Fee:</strong><br>
                        RM {{ selectedVisit.medicinesFee ? parseFloat(selectedVisit.medicinesFee).toFixed(2) : '0.00' }}
                      </div>
                      <div class="col-md-4 fw-bold">
                        <strong>Total Amount:</strong><br>
                        RM {{ selectedVisit.totalAmount ? parseFloat(selectedVisit.totalAmount).toFixed(2) : '0.00' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Medical Certificate -->
              <div class="col-12" v-if="selectedVisit.mcStartDate || selectedVisit.mcEndDate">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-certificate me-2"></i>Medical Certificate</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6" v-if="selectedVisit.mcStartDate">
                        <strong>Start Date:</strong> {{ formatDate(selectedVisit.mcStartDate) }}
                      </div>
                      <div class="col-md-6" v-if="selectedVisit.mcEndDate">
                        <strong>End Date:</strong> {{ formatDate(selectedVisit.mcEndDate) }}
                      </div>
                      <div class="col-12" v-if="selectedVisit.mcNotes">
                        <strong>MC Notes:</strong><br>
                        <div class="bg-light p-2 rounded">{{ selectedVisit.mcNotes }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeVisitDetailsModal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showVisitDetailsModal"></div>

    <!-- Receipt Modal -->
    <div class="modal fade" :class="{ show: showReceiptModal }" tabindex="-1" v-if="showReceiptModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Receipt - {{ selectedVisitForReceipt?.receiptNumber }}</h5>
            <button type="button" class="btn-close" @click="closeReceiptModal"></button>
          </div>
          <div class="modal-body">
            <!-- Receipt content will be dynamically injected here -->
            <div id="receiptPrintContent" v-if="selectedVisitForReceipt">
              <div class="receipt-container">
                <!-- Clinic Header -->
                <div class="clinic-header">
                  <h2 class="clinic-name">KLINIK HIDUPsihat</h2>
                  <div class="clinic-address">
                    <p class="mb-1">No. 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor</p>
                    <p class="mb-3">Tel : 03-8740 0678</p>
                  </div>
                </div>

                <!-- Receipt Header -->
                <div class="receipt-header">
                  <div>
                    <h4 class="receipt-title mb-0">RESIT RASMI</h4>
                  </div>
                  <div class="text-end">
                    <div class="receipt-number text-danger fw-bold">
                      No. {{ selectedVisitForReceipt.receiptNumber }}
                    </div>
                    <div class="receipt-date">
                      Tarikh : {{ formatReceiptDate(selectedVisitForReceipt.paidAt || selectedVisitForReceipt.consultationDate) }}
                    </div>
                  </div>
                </div>

                <!-- Patient Information -->
                <div class="patient-info">
                  <div class="info-row">
                    <span class="label">Terima daripada :</span>
                    <span class="value">{{ selectedPatientForHistory?.name }}</span>
                  </div>
                  <div class="info-row">
                    <span class="label">Untuk Bayaran :</span>
                    <span class="value">Pemeriksaan kesihatan / rawatan</span>
                  </div>
                </div>

                <!-- Amount and Signature Section -->
                <div class="amount-section">
                  <div class="amount-box">
                    <div class="amount-label">RM</div>
                    <div class="amount-value">{{ formatAmount(selectedVisitForReceipt.totalAmount) }}</div>
                  </div>
                  <div class="signature-area">
                    <div class="signature-space"></div>
                    <div class="signature-label">
                      <p class="mb-0"><small>Tandatangan/Cop</small></p>
                      <p class="mb-0"><small>{{ selectedVisitForReceipt.doctor?.name || 'Doctor' }}</small></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReceiptModal">Close</button>
            <button type="button" class="btn btn-primary" @click="printReceipt">Print</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showReceiptModal"></div>

    <!-- Medical Certificate Modal -->
    <div class="modal fade" :class="{ show: showMCModal }" tabindex="-1" v-if="showMCModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Medical Certificate - {{ selectedVisitForMC?.mcRunningNumber }}</h5>
            <button type="button" class="btn-close" @click="closeMCModal"></button>
          </div>
          <div class="modal-body">
            <!-- Medical Certificate content will be dynamically injected here -->
            <div id="mcPrintContent" v-if="selectedVisitForMC">
              <div class="mc-container">
                <!-- Clinic Header -->
                <div class="clinic-header">
                  <h3 class="clinic-name">KLINIK HIDUPsihat</h3>
                  <p class="mb-1">No 6, Tingkat 1, Jalan 2, Taman Sri Jambu, 43000 Kajang, Selangor.</p>
                  <p class="mb-1">Tel: 03-8740 0678</p>
                </div>
                
                <!-- MC Running Number -->
                <div class="mc-number">
                  <p class="mb-0">No: {{ selectedVisitForMC.mcRunningNumber }}</p>
                </div>
                
                <h4 class="mc-title">SURAT AKUAN SAKIT (MC)</h4>
                
                <div class="mc-content">
                  <div class="d-flex justify-content-between">
                    <p>Saya mengesahkan telah memeriksa;</p>
                    <p><strong>Tarikh:</strong> {{ formatDate(selectedVisitForMC.consultationDate) }}</p>
                  </div>
                  <p class="ms-3 mb-1">
                    <strong>Nama dan No KP:</strong> {{ selectedPatientForHistory?.name }} ({{ selectedPatientForHistory?.nric || '******' }})
                  </p>
                  <p class="ms-3 mb-4">
                    <strong>dari:</strong> {{ selectedPatientForHistory?.company || 'yang berkenaan' }}
                  </p>
                  
                  <p>Beliau didapati tidak sihat dan tidak dapat menjalankan tugas selama</p>
                  <p class="ms-3 mb-4">
                    <strong>{{ calculateMCDays(selectedVisitForMC.mcStartDate, selectedVisitForMC.mcEndDate) }}</strong> hari mulai 
                    <strong>{{ formatDate(selectedVisitForMC.mcStartDate) }}</strong> sehingga 
                    <strong>{{ formatDate(selectedVisitForMC.mcEndDate) }}</strong>
                  </p>
                  
                  <div class="signature-area">
                    <div class="signature-line"></div>
                    <p class="mb-0">Tandatangan</p>
                    <p class="mb-0"><small>{{ selectedVisitForMC.doctor?.name || 'Doctor' }}</small></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeMCModal">Close</button>
            <button type="button" class="btn btn-primary" @click="printMedicalCertificate">Print</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showMCModal"></div>
  </div>
</template>

<script>
import axios from 'axios';
import AuthService from '../../services/AuthService';

export default {
  name: 'PatientList',
  emits: ['patient-added', 'patient-updated', 'patient-deleted'],
  data() {
    return {
      patients: [],
      showAddModal: false,
      searchQuery: '',
      editingPatient: null,
      loading: false,
      saving: false,
      error: null,
      nricError: null,
      form: {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        preInformedIllness: '',
        medicalHistory: ''
      },
      // Visit History Modal
      showVisitHistoryModal: false,
      selectedPatientForHistory: null,
      visitHistory: [],
      loadingVisitHistory: false,
      // Visit Details Modal
      showVisitDetailsModal: false,
      selectedVisit: null,
      // Search functionality
      searchTimeout: null,
      // Receipt and MC viewing
      showReceiptModal: false,
      selectedVisitForReceipt: null,
      showMCModal: false,
      selectedVisitForMC: null,
      // Pagination
      currentPage: 1,
      totalPages: 0,
      totalPatients: 0,
      perPage: 25,
    };
  },
  created() {
    this.loadPatients();
  },
  
  beforeUnmount() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
  },
  methods: {
    async loadPatients() {
      this.loading = true;
      try {
        const response = await axios.get('/api/patients', {
          params: {
            page: this.currentPage,
            limit: this.perPage,
          },
        });
        this.patients = response.data.data;
        this.totalPatients = response.data.total;
        this.totalPages = Math.ceil(response.data.total / this.perPage);
      } catch (error) {
        console.error('Error loading patients:', error);
        this.error = 'Failed to load patients';
      } finally {
        this.loading = false;
      }
    },
    async searchPatients() {
      if (!this.searchQuery) {
        this.loadPatients();
        return;
      }
      this.loading = true;
      try {
        const response = await axios.get('/api/patients/search', {
          params: { 
            query: this.searchQuery,
            page: this.currentPage,
            limit: this.perPage,
          }
        });
        this.patients = response.data.data;
        this.totalPatients = response.data.total;
        this.totalPages = Math.ceil(response.data.total / this.perPage);
      } catch (error) {
        console.error('Failed to search patients:', error);
        this.error = 'Failed to search patients';
      } finally {
        this.loading = false;
      }
    },
    onSearchInput() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page for new search
        if (this.searchQuery) {
          this.searchPatients();
        } else {
          this.loadPatients();
        }
      }, 500);
    },
    clearSearch() {
      this.searchQuery = '';
      this.currentPage = 1; // Reset to first page
      this.loadPatients();
    },
    editPatient(patient) {
      this.editingPatient = { ...patient };
      this.showAddModal = true;
    },
    async deletePatient(patient) {
      if (!confirm('Are you sure you want to delete this patient?')) {
        return;
      }
      
      try {
        await axios.delete(`/api/patients/${patient.id}`);
        this.patients = this.patients.filter(p => p.id !== patient.id);
        this.$emit('patient-deleted');
      } catch (error) {
        console.error('Failed to delete patient:', error);
      }
    },
    async checkNricUniqueness() {
      if (!this.form.nric || this.editingPatient) {
        this.nricError = null;
        return;
      }
      
      try {
        // Check if NRIC already exists
        const response = await axios.get('/api/patients/search', {
          params: { query: this.form.nric }
        });
        
        const existingPatient = response.data.find(patient => 
          patient.nric && patient.nric.toLowerCase() === this.form.nric.toLowerCase()
        );
        
        if (existingPatient) {
          this.nricError = 'This NRIC is already registered to another patient';
        } else {
          this.nricError = null;
        }
      } catch (error) {
        console.error('Error checking NRIC uniqueness:', error);
        // Don't show error to user for this check, just allow them to proceed
        this.nricError = null;
      }
    },
    async savePatient() {
      this.saving = true;
      this.error = null;
      
      try {
        if (this.editingPatient) {
          const response = await axios.put(`/api/patients/${this.editingPatient.id}`, this.form);
          const index = this.patients.findIndex(p => p.id === this.editingPatient.id);
          this.patients[index] = response.data.patient;
          this.$emit('patient-updated');
        } else {
          const response = await axios.post('/api/patients', this.form);
          this.patients.push(response.data.patient);
          this.$emit('patient-added');
        }
        this.closeModal();
      } catch (error) {
        console.error('Failed to save patient:', error);
        this.error = error.response?.data?.message || 'Failed to save patient';
      } finally {
        this.saving = false;
      }
    },
    closeModal() {
      this.showAddModal = false;
      this.editingPatient = null;
      this.error = null;
      this.nricError = null;
      this.form = {
        name: '',
        nric: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        company: '',
        preInformedIllness: '',
        medicalHistory: ''
      };
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

    // Visit History Methods
    async showVisitHistory(patient) {
      this.selectedPatientForHistory = patient;
      this.showVisitHistoryModal = true;
      await this.loadVisitHistory(patient.id);
    },

    async loadVisitHistory(patientId) {
      this.loadingVisitHistory = true;
      this.visitHistory = [];
      
      try {
        const response = await axios.get(`/api/consultations/patient/${patientId}`);
        this.visitHistory = response.data.map(visit => {
          // Parse medications if it's a JSON string
          let medications = [];
          if (visit.medications) {
            try {
              medications = typeof visit.medications === 'string' 
                ? JSON.parse(visit.medications) 
                : visit.medications;
            } catch (e) {
              console.warn('Error parsing medications:', e);
              medications = [];
            }
          }
          
          return {
            ...visit,
            medications: medications
          };
        });
      } catch (error) {
        console.error('Error loading visit history:', error);
        this.visitHistory = [];
      } finally {
        this.loadingVisitHistory = false;
      }
    },

    closeVisitHistoryModal() {
      this.showVisitHistoryModal = false;
      this.selectedPatientForHistory = null;
      this.visitHistory = [];
    },

    viewVisitDetails(visit) {
      this.selectedVisit = visit;
      this.showVisitDetailsModal = true;
    },

    closeVisitDetailsModal() {
      this.showVisitDetailsModal = false;
      this.selectedVisit = null;
    },

    calculateAge(dateOfBirth) {
      if (!dateOfBirth) return 0;
      const today = new Date();
      const birthDate = new Date(dateOfBirth);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-MY', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      } catch (error) {
        return 'Invalid Date';
      }
    },

    formatTime(dateString) {
      if (!dateString) return 'N/A';
      try {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-MY', {
          hour: '2-digit',
          minute: '2-digit',
          hour12: true
        });
      } catch (error) {
        return 'Invalid Time';
      }
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        'completed': 'badge bg-success',
        'in_progress': 'badge bg-warning text-dark',
        'pending': 'badge bg-info',
        'cancelled': 'badge bg-danger',
        'waiting': 'badge bg-secondary'
      };
      return statusClasses[status?.toLowerCase()] || 'badge bg-success';
    },

    // Receipt and MC viewing methods
    viewReceipt(visit) {
      // Create and show receipt modal
      this.selectedVisitForReceipt = visit;
      this.showReceiptModal = true;
    },

    viewMedicalCertificate(visit) {
      // Create and show MC modal
      this.selectedVisitForMC = visit;
      this.showMCModal = true;
    },

    printReceipt() {
      const receiptContent = document.getElementById('receiptPrintContent');
      if (receiptContent) {
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>Receipt - ${this.selectedVisitForReceipt?.receiptNumber}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .receipt-container { max-width: 600px; margin: 0 auto; border: 2px solid #000; padding: 30px; }
              .clinic-header { text-align: center; margin-bottom: 30px; }
              .clinic-name { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
              .receipt-header { display: flex; justify-content: space-between; margin-bottom: 30px; }
              .receipt-title { font-size: 20px; font-weight: bold; }
              .receipt-number { color: #dc3545; font-weight: bold; }
              .patient-info { margin-bottom: 30px; }
              .info-row { margin-bottom: 10px; display: flex; }
              .label { min-width: 140px; }
              .value { flex: 1; padding-left: 10px; border-bottom: 1px dotted #000; font-weight: bold; }
              .amount-section { display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px; }
              .amount-box { border: 2px solid #000; padding: 15px 20px; min-width: 200px; display: flex; justify-content: space-between; }
              .signature-area { text-align: center; }
              .signature-space { height: 60px; border-bottom: 1px solid #ccc; margin-bottom: 10px; }
            </style>
          </head>
          <body onload="window.print(); window.close();">
            ${receiptContent.innerHTML}
          </body>
          </html>
        `);
        printWindow.document.close();
      }
    },

    printMedicalCertificate() {
      const mcContent = document.getElementById('mcPrintContent');
      if (mcContent) {
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>Medical Certificate - ${this.selectedVisitForMC?.mcRunningNumber}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .mc-container { background-color: #e8e5b0; padding: 20px; border: 1px solid #000; max-width: 600px; margin: 0 auto; }
              .clinic-header { text-align: center; margin-bottom: 15px; }
              .clinic-name { font-weight: bold; margin-bottom: 0; }
              .mc-number { text-align: right; margin-bottom: 15px; font-size: 0.9rem; color: #a52a2a; }
              .mc-title { text-align: center; margin-bottom: 20px; }
              .mc-content { margin-bottom: 15px; }
              .signature-area { text-align: right; margin-top: 40px; }
              .signature-line { border-bottom: 1px dotted #000; width: 150px; margin-bottom: 10px; }
            </style>
          </head>
          <body onload="window.print(); window.close();">
            ${mcContent.innerHTML}
          </body>
          </html>
        `);
        printWindow.document.close();
      }
    },

    closeReceiptModal() {
      this.showReceiptModal = false;
      this.selectedVisitForReceipt = null;
    },

    closeMCModal() {
      this.showMCModal = false;
      this.selectedVisitForMC = null;
    },

    formatReceiptDate(date) {
      if (!date) return '';
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('en-MY', {
        timeZone: 'Asia/Kuala_Lumpur',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },

    formatAmount(amount) {
      if (!amount) return '0.00';
      return parseFloat(amount).toFixed(2);
    },

    calculateMCDays(startDate, endDate) {
      if (!startDate || !endDate) return '0';
      const start = new Date(startDate);
      const end = new Date(endDate);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      return diffDays.toString();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadPatients();
      }
    },
  },
  computed: {
    isSuperAdmin() {
      return AuthService.isSuperAdmin();
    },
    pages() {
        const pagesToShow = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(pagesToShow / 2));
        let endPage = Math.min(this.totalPages, startPage + pagesToShow - 1);

        if (endPage - startPage + 1 < pagesToShow) {
            startPage = Math.max(1, endPage - pagesToShow + 1);
        }
        
        const pages = [];
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        return pages;
    }
  }
};
</script>

<style scoped>
.modal {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  z-index: 1040;
}

.modal {
  z-index: 1050;
}

/* Visit History Styling */
.cursor-pointer {
  cursor: pointer;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.1);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  font-weight: 600;
}

/* Action buttons styling */
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.btn-sm i {
  font-size: 0.75rem;
}

/* Modal enhancements */
.modal-xl {
  max-width: 1200px;
}

.modal-header.bg-primary,
.modal-header.bg-info {
  border-bottom: none;
}

.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}

/* Badge styling */
.badge {
  font-size: 0.75em;
}

/* Financial summary styling */
.text-center h5 {
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.bg-light {
  background-color: #f8f9fa !important;
}

/* Receipt and MC specific styling */
.receipt-container {
  border: 2px solid #000;
  padding: 30px;
  background: white;
  font-family: Arial, sans-serif;
}

.mc-container {
  background-color: #e8e5b0;
  padding: 20px;
  border: 1px solid #000;
  font-family: Arial, sans-serif;
}

.clinic-header {
  text-align: center;
  margin-bottom: 30px;
}

.clinic-name {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #000;
}

.receipt-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.receipt-title {
  font-size: 20px;
  font-weight: bold;
  color: #000;
}

.receipt-number {
  color: #dc3545;
  font-weight: bold;
  font-size: 16px;
}

.patient-info {
  margin-bottom: 30px;
}

.info-row {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
}

.label {
  min-width: 140px;
  display: inline-block;
  color: #000;
}

.value {
  flex: 1;
  padding-left: 10px;
  border-bottom: 1px dotted #000;
  min-height: 25px;
  color: #000;
  font-weight: bold;
}

.amount-section {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 30px;
}

.amount-box {
  border: 2px solid #000;
  padding: 15px 20px;
  text-align: left;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: 200px;
  width: 200px;
}

.amount-label {
  font-weight: bold;
  margin-bottom: 0;
  color: #000;
  font-size: 18px;
}

.amount-value {
  font-weight: bold;
  color: #000;
  font-size: 18px;
}

.signature-area {
  flex: 0 0 auto;
  text-align: center;
}

.signature-space {
  height: 60px;
  border-bottom: 1px solid #ccc;
  margin-bottom: 10px;
}

.mc-number {
  text-align: right;
  margin-bottom: 15px;
  font-size: 0.9rem;
  color: #a52a2a;
}

.mc-title {
  text-align: center;
  margin-bottom: 20px;
}

.signature-line {
  border-bottom: 1px dotted #000;
  width: 150px;
  margin-bottom: 10px;
  margin-left: auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .modal-xl {
    max-width: 95%;
  }
  
  .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.8rem;
  }
  
  .table-responsive {
    font-size: 0.9rem;
  }
}
</style>