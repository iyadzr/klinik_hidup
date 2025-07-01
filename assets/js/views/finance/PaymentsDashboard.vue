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
      <div class="d-flex gap-2">
        <button class="btn btn-outline-success" @click="exportPayments">
          <i class="fas fa-download me-1"></i>
          Export
        </button>
        <button class="btn btn-primary" @click="refreshData">
          <i class="fas fa-sync-alt me-1"></i>
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
                <h6 class="card-title">Today's Revenue</h6>
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
        <div class="card bg-info text-white">
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
        <div class="card bg-warning text-white">
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
            <button class="btn btn-outline-secondary w-100" @click="clearFilters">
              <i class="fas fa-times me-1"></i>
              Clear Filters
            </button>
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
                  <small class="text-muted">{{ formatDate(payment.payment_date) }}</small>
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
                  <span :class="getPaymentMethodBadge(payment.payment_method)">
                    <i :class="getPaymentMethodIcon(payment.payment_method)" class="me-1"></i>
                    {{ payment.payment_method.toUpperCase() }}
                  </span>
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
                    <div class="fw-medium">{{ payment.processed_by?.name || 'System' }}</div>
                    <small class="text-muted">{{ payment.processed_by?.email || '' }}</small>
                  </div>
                </td>
                <td>
                  <code class="small">{{ payment.reference }}</code>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click="viewPaymentDetails(payment)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" @click="printReceipt(payment)" title="Print Receipt">
                      <i class="fas fa-print"></i>
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
        startDate: new Date().toISOString().split('T')[0], // Today by default
        endDate: new Date().toISOString().split('T')[0],
        paymentMethod: ''
      },
      todaysStats: {
        revenue: 0,
        count: 0,
        cashCount: 0,
        cardCount: 0
      }
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
    getPaymentMethodBadge(method) {
      return {
        'badge': true,
        'bg-success': method === 'cash',
        'bg-primary': method === 'card'
      };
    },
    getPaymentMethodIcon(method) {
      return method === 'cash' ? 'fas fa-coins' : 'fas fa-credit-card';
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

        const response = await axios.get('/api/financial/payments', { params });
        
        this.payments = response.data.data;
        this.totalPayments = response.data.total;
        this.totalPages = response.data.total_pages;
        
        this.calculateTodaysStats();
        
      } catch (error) {
        console.error('Error loading payments:', error);
        this.$toast?.error('Failed to load payment data');
      } finally {
        this.loading = false;
      }
    },
    calculateTodaysStats() {
      const today = new Date().toISOString().split('T')[0];
      const todaysPayments = this.payments.filter(p => 
        p.payment_date.startsWith(today)
      );
      
      this.todaysStats = {
        revenue: todaysPayments.reduce((sum, p) => sum + parseFloat(p.amount), 0).toFixed(2),
        count: todaysPayments.length,
        cashCount: todaysPayments.filter(p => p.payment_method === 'cash').length,
        cardCount: todaysPayments.filter(p => p.payment_method === 'card').length
      };
    },
    applyFilters() {
      this.currentPage = 1;
      this.loadPayments();
    },
    clearFilters() {
      this.filters = {
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
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
    viewPaymentDetails(payment) {
      this.selectedPayment = payment;
      this.detailsModal.show();
    },
    printReceipt(payment) {
      // Implement receipt printing logic
      console.log('Print receipt for payment:', payment.id);
      this.$toast?.info('Receipt printing not implemented yet');
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
    }
  },
  mounted() {
    this.detailsModal = new Modal(document.getElementById('paymentDetailsModal'));
    this.loadPayments();
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

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
  }
}
</style> 