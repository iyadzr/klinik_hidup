<template>
  <div class="payments-dashboard">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">
          <i class="fas fa-credit-card me-2"></i>
          Payments Dashboard
        </h2>
        <p class="text-muted mb-0">Daily transaction records and payment tracking</p>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <!-- Real-time status indicator -->
        <div class="real-time-status me-2">
          <span v-if="sseConnected" class="badge bg-success">
            <i class="fas fa-wifi me-1"></i>Live
          </span>
          <span v-else class="badge bg-warning text-dark">
            <i class="fas fa-clock me-1"></i>Polling
          </span>
        </div>
        
        <button class="btn btn-outline-success" @click="exportPayments">
          <i class="fas fa-download me-1"></i>
          Export
        </button>
        <button class="btn btn-primary" @click="refreshData" :disabled="loading">
          <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
          Refresh
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="card-title">{{ getDateRangeLabel() }} Revenue</h6>
                <h4 class="mb-0">RM {{ todaysStats.revenue }}</h4>
              </div>
              <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="card-title">Transactions</h6>
                <h4 class="mb-0">{{ todaysStats.count }}</h4>
              </div>
              <i class="fas fa-receipt fa-2x opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white clickable-card" @click="filterByCash" style="cursor: pointer;" title="Click to filter cash payments">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="card-title">Cash Payments</h6>
                <h4 class="mb-0">{{ todaysStats.cashCount }}</h4>
              </div>
              <i class="fas fa-coins fa-2x opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white clickable-card" @click="filterByCard" style="cursor: pointer;" title="Click to filter card payments">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="card-title">Card Payments</h6>
                <h4 class="mb-0">{{ todaysStats.cardCount }}</h4>
              </div>
              <i class="fas fa-credit-card fa-2x opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" v-model="filters.startDate" @change="applyFilters">
          </div>
          <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" v-model="filters.endDate" @change="applyFilters">
          </div>
          <div class="col-md-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select" v-model="filters.paymentMethod" @change="applyFilters">
              <option value="">All Methods</option>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
            </select>
          </div>
          <div class="col-md-3">
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary flex-fill" @click="setToday">
                <i class="fas fa-calendar-day me-1"></i>
                Today
              </button>
              <button class="btn btn-outline-secondary flex-fill" @click="clearFilters">
                <i class="fas fa-times me-1"></i>
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payment Transactions</h5>
        <span class="badge bg-secondary">{{ totalPayments }} records</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-dark">
              <tr>
                <th>Time</th>
                <th>Queue #</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Medicines</th>
                <th>Staff</th>
                <th>Reference</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.id" class="payment-row">
                <td>
                  <div class="fw-bold">{{ formatTime(payment.payment_time) }}</div>
                  <small class="text-muted">{{ formatDate(payment.payment_date || payment.consultation_date) }}</small>
                </td>
                <td>
                  <span class="badge bg-primary">{{ payment.queue_number || 'N/A' }}</span>
                </td>
                <td>
                  <div class="patient-info">
                    <div class="fw-bold">{{ payment.patient?.name || 'N/A' }}</div>
                    <small class="text-muted">{{ payment.patient?.nric || '' }}</small>
                  </div>
                </td>
                <td>
                  <span class="fw-medium">{{ payment.doctor?.name || 'N/A' }}</span>
                </td>
                <td>
                  <span class="fw-bold text-success">RM {{ payment.amount }}</span>
                </td>
                <td>
                  <!-- Payment Method Column: Shows both method and status -->
                  <div class="payment-method-display">
                    <!-- For completed payments: Show the actual payment method -->
                    <div v-if="payment.type === 'completed'">
                      <span :class="getPaymentMethodBadge(payment.payment_method)">
                        <i :class="getPaymentMethodIcon(payment.payment_method)" class="me-1"></i>
                        {{ formatPaymentMethod(payment.payment_method) }}
                      </span>
                      <!-- Payment Status Indicator: Confirms payment was completed -->
                      <div class="payment-status-indicator mt-1">
                        <small class="text-success fw-bold">
                          <i class="fas fa-check-circle me-1"></i>PAID
                        </small>
                      </div>
                    </div>
                    <!-- For pending payments: Show PENDING status -->
                    <div v-else-if="payment.type === 'pending'">
                      <span class="badge bg-warning text-dark">
                        <i class="fas fa-clock me-1"></i>PENDING
                      </span>
                      <div class="payment-status-indicator mt-1">
                        <small class="text-warning fw-bold">
                          <i class="fas fa-exclamation-triangle me-1"></i>AWAITING PAYMENT
                        </small>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="medicines-info">
                    <span class="badge bg-info">{{ payment.medicines_count }} meds</span>
                    <div class="small text-muted mt-1" style="max-width: 200px;">
                      {{ payment.medicines_summary }}
                    </div>
                  </div>
                </td>
                <td>
                  <div class="staff-info">
                    <div v-if="payment.type === 'completed'" class="fw-medium">{{ payment.processed_by?.name || 'System' }}</div>
                    <div v-else class="text-muted">-</div>
                    <small v-if="payment.type === 'completed'" class="text-muted">{{ payment.processed_by?.email || '' }}</small>
                  </div>
                </td>
                <td>
                  <code v-if="payment.type === 'completed'" class="small">{{ payment.reference }}</code>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click="viewPaymentDetails(payment)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button v-if="payment.type === 'completed'" class="btn btn-outline-success" @click="printReceipt(payment)" title="Print Receipt">
                      <i class="fas fa-print"></i>
                    </button>
                    <button v-else-if="payment.type === 'pending'" class="btn btn-outline-warning" @click="processPayment(payment)" title="Process Payment">
                      <i class="fas fa-credit-card"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="payments.length === 0">
                <td colspan="10" class="text-center py-4">
                  <div class="text-muted">
                    <i class="fas fa-receipt fa-3x mb-3 d-block"></i>
                    No payment records found for the selected criteria
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalPages > 1">
          <div class="text-muted">
            Showing {{ ((currentPage - 1) * pageLimit) + 1 }} to {{ Math.min(currentPage * pageLimit, totalPayments) }} of {{ totalPayments }} entries
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <a class="page-link" @click="changePage(currentPage - 1)">Previous</a>
              </li>
              <li v-for="page in visiblePages" :key="page" 
                  class="page-item" :class="{ active: page === currentPage }">
                <a class="page-link" @click="changePage(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <a class="page-link" @click="changePage(currentPage + 1)">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Payment Details Modal -->
    <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Payment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedPayment">
            <div class="row">
              <div class="col-md-6">
                <h6>Payment Information</h6>
                <table class="table table-borderless table-sm">
                  <tbody>
                    <tr>
                      <td class="fw-bold">Reference:</td>
                      <td><code>{{ selectedPayment.reference }}</code></td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Amount:</td>
                      <td class="text-success fw-bold">RM {{ selectedPayment.amount }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Method:</td>
                      <td>{{ selectedPayment.payment_method.toUpperCase() }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Date & Time:</td>
                      <td>{{ formatDateTime(selectedPayment.payment_date) }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Processed By:</td>
                      <td>{{ selectedPayment.processed_by?.name || 'System' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <h6>Patient Information</h6>
                <table class="table table-borderless table-sm" v-if="selectedPayment.patient">
                  <tbody>
                    <tr>
                      <td class="fw-bold">Name:</td>
                      <td>{{ selectedPayment.patient.name }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">NRIC:</td>
                      <td>{{ selectedPayment.patient.nric }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Phone:</td>
                      <td>{{ selectedPayment.patient.phone }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Doctor:</td>
                      <td>{{ selectedPayment.doctor?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Queue #:</td>
                      <td><span class="badge bg-primary">{{ selectedPayment.queue_number }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <div class="mt-3" v-if="selectedPayment.medicines?.length > 0">
              <h6>Prescribed Medicines</h6>
              <div class="table-responsive">
                <table class="table table-sm table-striped">
                  <thead>
                    <tr>
                      <th>Medicine</th>
                      <th>Dosage</th>
                      <th>Frequency</th>
                      <th>Duration</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="medicine in selectedPayment.medicines" :key="medicine.name">
                      <td class="fw-medium">{{ medicine.name }}</td>
                      <td>{{ medicine.dosage }}</td>
                      <td>{{ medicine.frequency }}</td>
                      <td>{{ medicine.duration }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mt-3" v-if="selectedPayment.notes">
              <h6>Notes</h6>
              <div class="alert alert-info">{{ selectedPayment.notes }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-success" @click="printReceipt(selectedPayment)">
              <i class="fas fa-print me-1"></i>
              Print Receipt
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';
import { formatDate } from '../../utils/dateUtils';

export default {
  name: 'PaymentsDashboard',
  data() {
    return {
      payments: [],
      totalPayments: 0,
      currentPage: 1,
      totalPages: 1,
      pageLimit: 50,
      loading: false,
      selectedPayment: null,
      detailsModal: null,
      filters: {
        startDate: new Date().toLocaleDateString('en-CA', { 
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        }), // Today by default in Malaysian timezone
        endDate: new Date().toLocaleDateString('en-CA', { 
          timeZone: 'Asia/Kuala_Lumpur',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        }),
        paymentMethod: ''
      },
      todaysStats: {
        revenue: 0,
        count: 0,
        cashCount: 0,
        cardCount: 0
      },
      autoRefreshInterval: null,
      // SSE connection for real-time updates
      eventSource: null,
      sseConnected: false,
      reconnectAttempts: 0,
      maxReconnectAttempts: 5
    };
  },
  computed: {
    visiblePages() {
      const pages = [];
      const start = Math.max(1, this.currentPage - 2);
      const end = Math.min(this.totalPages, this.currentPage + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  methods: {
    formatDate,
    formatTime(time) {
      return time || '';
    },
    formatDateTime(dateTime) {
      if (!dateTime) return '';
      const date = new Date(dateTime);
      return date.toLocaleString();
    },
    formatPaymentMethod(method) {
      const methods = {
        'cash': 'Cash',
        'card': 'Credit Card',
        'Card': 'Credit Card',
        'Cash': 'Cash',
        'debit': 'Debit Card',
        'credit': 'Credit Card',
        'bank_transfer': 'Bank Transfer',
        'ewallet': 'E-Wallet'
      };
      return methods[method] || method.charAt(0).toUpperCase() + method.slice(1);
    },
    getPaymentMethodBadge(method) {
      const normalizedMethod = method.toLowerCase();
      return {
        'badge': true,
        'badge-payment-method': true,
        'bg-success': normalizedMethod === 'cash',
        'bg-primary': normalizedMethod === 'card' || normalizedMethod === 'credit',
        'bg-info': normalizedMethod === 'debit',
        'bg-warning': normalizedMethod === 'bank_transfer',
        'bg-secondary': normalizedMethod === 'ewallet'
      };
    },
    getPaymentMethodIcon(method) {
      const normalizedMethod = method.toLowerCase();
      const icons = {
        'cash': 'fas fa-money-bill-wave',
        'card': 'fas fa-credit-card',
        'credit': 'fas fa-credit-card',
        'debit': 'fas fa-credit-card',
        'bank_transfer': 'fas fa-university',
        'ewallet': 'fas fa-mobile-alt'
      };
      return icons[normalizedMethod] || 'fas fa-credit-card';
    },
    getDateRangeLabel() {
      if (this.filters.startDate === this.filters.endDate) {
        // Same date - check if it's today
        const today = new Date().toISOString().split('T')[0];
        if (this.filters.startDate === today) {
          return "Today's";
        } else {
          return "Selected Day's";
        }
      } else {
        // Date range
        return "Period";
      }
    },
    async loadPayments() {
      this.loading = true;
      try {
        const params = {
          page: this.currentPage,
          limit: this.pageLimit,
          start_date: this.filters.startDate,
          end_date: this.filters.endDate,
          payment_method: this.filters.paymentMethod
        };

        // Use the new API endpoint that includes both completed and pending payments
        const response = await axios.get('/api/financial/payments/all', { params });
        
        this.payments = response.data.data;
        this.totalPayments = response.data.total;
        this.totalPages = response.data.total_pages;
        
        this.calculateTodaysStats();
        
        console.log('✅ Payments loaded successfully:', {
          count: this.payments.length,
          total: this.totalPayments,
          types: this.payments.reduce((acc, p) => {
            acc[p.type] = (acc[p.type] || 0) + 1;
            return acc;
          }, {})
        });
        
      } catch (error) {
        console.error('Error loading payments:', error);
        this.$toast?.error('Failed to load payment data');
      } finally {
        this.loading = false;
      }
    },
    calculateTodaysStats() {
      // No need to filter by date - API already returns filtered data based on date range
      // Just separate completed payments from pending payments
      const completedPayments = this.payments.filter(p => p.type === 'completed');
      
      this.todaysStats = {
        revenue: completedPayments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0).toFixed(2),
        count: completedPayments.length,
        cashCount: completedPayments.filter(p => p.payment_method?.toLowerCase() === 'cash').length,
        cardCount: completedPayments.filter(p => p.payment_method?.toLowerCase() === 'card').length
      };
    },
    applyFilters() {
      this.currentPage = 1;
      this.loadPayments();
    },
    setToday() {
      // Use Malaysia timezone to get the correct current date
      const malaysianDate = new Date().toLocaleDateString('en-CA', { 
        timeZone: 'Asia/Kuala_Lumpur',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
      console.log('🕐 Setting Today button to Malaysian date:', malaysianDate);
      
      this.filters = {
        startDate: malaysianDate,
        endDate: malaysianDate,
        paymentMethod: this.filters.paymentMethod // Keep the payment method filter
      };
      this.applyFilters();
    },
    clearFilters() {
      // Use Malaysia timezone to get the correct current date
      const malaysianDate = new Date().toLocaleDateString('en-CA', { 
        timeZone: 'Asia/Kuala_Lumpur',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
      
      this.filters = {
        startDate: malaysianDate,
        endDate: malaysianDate,
        paymentMethod: ''
      };
      this.applyFilters();
    },
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadPayments();
      }
    },
    refreshData() {
      this.loadPayments();
    },
    filterByCash() {
      this.filters.paymentMethod = 'cash';
      this.applyFilters();
      this.$toast?.info('Filtered to show only cash payments');
    },
    filterByCard() {
      this.filters.paymentMethod = 'card';
      this.applyFilters();
      this.$toast?.info('Filtered to show only card payments');
    },
    viewPaymentDetails(payment) {
      this.selectedPayment = payment;
      this.detailsModal.show();
    },
    printReceipt(payment) {
      // Implement receipt printing logic
      console.log('Print receipt for payment:', payment.id);
      this.$toast?.info('Receipt printing not implemented yet');
    },
    processPayment(payment) {
      // For pending payments, redirect to queue management to process payment
      if (payment.type === 'pending' && payment.queue_number) {
        this.$router.push(`/queue?payment=${payment.queue_number}`);
      } else {
        this.$toast?.warning('Unable to process payment - queue information not available');
      }
    },
    async exportPayments() {
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate,
          payment_method: this.filters.paymentMethod
        };
        
        const response = await axios.get('/api/financial/export', { params });
        
        // Create CSV content
        const csvContent = this.generateCSV(response.data.data);
        
        // Download file
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `payments_${this.filters.startDate}_to_${this.filters.endDate}.csv`;
        link.click();
        window.URL.revokeObjectURL(url);
        
        this.$toast?.success('Payments exported successfully');
      } catch (error) {
        console.error('Error exporting payments:', error);
        this.$toast?.error('Failed to export payment data');
      }
    },
    generateCSV(data) {
      const headers = ['Date', 'Time', 'Queue#', 'Patient', 'Doctor', 'Amount', 'Method', 'Staff', 'Reference', 'Medicines'];
      const rows = data.map(payment => [
        payment.payment_date.split(' ')[0],
        payment.payment_time || '',
        payment.queue_number || '',
        payment.patient?.name || '',
        payment.doctor?.name || '',
        payment.amount,
        payment.payment_method,
        payment.processed_by?.name || 'System',
        payment.reference,
        payment.medicines_summary || 'No medicines'
      ]);
      
      return [headers, ...rows].map(row => 
        row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',')
      ).join('\n');
    },
    handlePaymentEvent(event) {
      // Handle payment event from other components
      console.log('Payment processed event:', event);
      this.loadPayments();
    },
    handleStorageEvent(event) {
      // Handle storage event (cross-tab communication)
      console.log('Storage event:', event);
      if (event.key === 'paymentsUpdated') {
        this.loadPayments();
      }
    },
    
    // SSE Methods for real-time updates
    initializeSSE() {
      if (!window.EventSource) {
        console.warn('EventSource not supported, using fallback polling');
        return;
      }
      
      // Close existing connection
      if (this.eventSource) {
        this.eventSource.close();
      }
      
      try {
        console.log('🔌 Initializing SSE connection for payment updates...');
        this.eventSource = new EventSource('/api/sse/queue-updates');
        
        this.eventSource.onopen = () => {
          console.log('✅ Payment Dashboard SSE connection established');
          this.sseConnected = true;
          this.reconnectAttempts = 0;
        };
        
        this.eventSource.onmessage = (event) => {
          try {
            const update = JSON.parse(event.data);
            console.log('📡 SSE update received:', update);
            
            if (update.type === 'queue_status_update') {
              this.handlePaymentUpdate(update.data);
            } else if (update.type === 'payment_processed') {
              this.handleDirectPaymentUpdate(update.data);
            }
          } catch (error) {
            console.error('❌ Error parsing SSE data:', error);
          }
        };
        
        this.eventSource.addEventListener('heartbeat', () => {
          // Keep connection alive
          console.log('💓 Payment Dashboard SSE heartbeat received');
        });
        
        this.eventSource.onerror = (error) => {
          console.warn('❌ Payment Dashboard SSE connection error:', error);
          this.sseConnected = false;
          
          if (this.eventSource.readyState === EventSource.CLOSED) {
            this.handleSSEReconnect();
          }
        };
        
      } catch (error) {
        console.error('❌ Failed to initialize SSE:', error);
      }
    },
    
    handleSSEReconnect() {
      if (this.reconnectAttempts >= this.maxReconnectAttempts) {
        console.warn('⚠️ Max SSE reconnection attempts reached for Payment Dashboard');
        return;
      }
      
      this.reconnectAttempts++;
      const delay = 1000 * Math.pow(2, this.reconnectAttempts - 1); // Exponential backoff
      
      console.log(`🔄 Attempting Payment Dashboard SSE reconnection ${this.reconnectAttempts}/${this.maxReconnectAttempts} in ${delay}ms`);
      
      setTimeout(() => {
        this.initializeSSE();
      }, delay);
    },
    
    handlePaymentUpdate(updateData) {
      // Check if this update is related to a payment
      if (updateData.status === 'completed' || updateData.isPaid) {
        console.log('💰 Payment status update detected, refreshing payment data');
        
        // Refresh payment data to get the latest information
        this.loadPayments();
        
        // Show notification for new payment
        if (updateData.isPaid) {
          this.$toast?.success(`New payment received for Queue #${updateData.queueNumber}`);
        }
      }
    },
    
    handleDirectPaymentUpdate(paymentData) {
      console.log('💰 Direct payment update received:', paymentData);
      
      // Add the new payment to the current list if it matches our filters
      const today = new Date().toISOString().split('T')[0];
      const paymentDate = paymentData.payment_date.split(' ')[0];
      
      // Check if the payment matches current filters
      const matchesDateFilter = (!this.filters.startDate || paymentDate >= this.filters.startDate) &&
                               (!this.filters.endDate || paymentDate <= this.filters.endDate);
      const matchesMethodFilter = !this.filters.paymentMethod || 
                                 paymentData.payment_method.toLowerCase() === this.filters.paymentMethod.toLowerCase();
      
      if (matchesDateFilter && matchesMethodFilter) {
        // Refresh the entire list to get proper formatting and avoid duplicates
        this.loadPayments();
        
        // Show notification with payment details
        this.$toast?.success(
          `Payment received: RM ${paymentData.amount} via ${this.formatPaymentMethod(paymentData.payment_method)} from ${paymentData.patient.name}`,
          { timeout: 5000 }
        );
      }
    },
    
    closeSSE() {
      if (this.eventSource) {
        console.log('🔌 Closing Payment Dashboard SSE connection');
        this.eventSource.close();
        this.eventSource = null;
        this.sseConnected = false;
      }
    }
  },
  mounted() {
    this.detailsModal = new Modal(document.getElementById('paymentDetailsModal'));
    this.loadPayments();
    
    // Initialize SSE for real-time updates
    this.initializeSSE();
    
    // Set up auto-refresh every 30 seconds as fallback
    this.autoRefreshInterval = setInterval(() => {
      if (!this.loading && !this.sseConnected) {
        // Only use polling if SSE is not connected
        console.log('📡 SSE not connected, using fallback polling');
        this.loadPayments();
      }
    }, 30000);
    
    // Listen for payment events from other components
    window.addEventListener('paymentProcessed', this.handlePaymentEvent);
    
    // Listen for storage events (cross-tab communication)
    window.addEventListener('storage', this.handleStorageEvent);
  },
  beforeUnmount() {
    // Cleanup intervals and event listeners
    if (this.autoRefreshInterval) {
      clearInterval(this.autoRefreshInterval);
    }
    
    // Close SSE connection
    this.closeSSE();
    
    window.removeEventListener('paymentProcessed', this.handlePaymentEvent);
    window.removeEventListener('storage', this.handleStorageEvent);
  }
};
</script>

<style scoped>
.payments-dashboard {
  padding: 20px;
}

.payment-row:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

.patient-info, .staff-info {
  min-width: 120px;
}

.medicines-info {
  max-width: 200px;
}

.table th {
  font-weight: 600;
  border-top: none;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
  font-size: 0.75em;
}

code {
  font-size: 0.75em;
  background-color: #f8f9fa;
  padding: 2px 4px;
  border-radius: 3px;
}

.page-link {
  cursor: pointer;
}

/* Real-time status indicator */
.real-time-status .badge {
  font-size: 0.75rem;
  padding: 0.4rem 0.7rem;
  border-radius: 12px;
  font-weight: 600;
  letter-spacing: 0.3px;
  animation: pulse 2s ease-in-out infinite;
}

.real-time-status .bg-success {
  background: linear-gradient(45deg, #28a745, #20c997) !important;
  box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.real-time-status .bg-warning {
  background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
  box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Payment method display styling */
.payment-method-display {
  min-width: 120px;
}

.badge-payment-method {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.payment-status-indicator {
  opacity: 0.8;
}

.payment-status-indicator small {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* Clickable card hover effects */
.clickable-card {
  transition: all 0.3s ease;
}

.clickable-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2) !important;
}

.clickable-card:active {
  transform: translateY(0);
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
  }
  
  .payment-method-display {
    min-width: 100px;
  }
  
  .badge-payment-method {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
  }
}
</style> 