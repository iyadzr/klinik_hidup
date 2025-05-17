<template>
  <div class="finance-dashboard">
    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h6 class="card-title">Today's Income</h6>
            <h3 class="mb-0">${{ formatAmount(todayIncome) }}</h3>
            <small>{{ todayTransactions }} transactions</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <h6 class="card-title">This Month</h6>
            <h3 class="mb-0">${{ formatAmount(monthIncome) }}</h3>
            <small>{{ monthTransactions }} transactions</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <h6 class="card-title">This Year</h6>
            <h3 class="mb-0">${{ formatAmount(yearIncome) }}</h3>
            <small>{{ yearTransactions }} transactions</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <h6 class="card-title">Payment Methods</h6>
            <div class="d-flex justify-content-between">
              <span>Cash: {{ cashPercentage }}%</span>
              <span>Card: {{ cardPercentage }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Date Range</label>
            <select v-model="dateRange" class="form-select" @change="loadTransactions">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="year">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          <div class="col-md-3" v-if="dateRange === 'custom'">
            <label class="form-label">Start Date</label>
            <input type="date" v-model="startDate" class="form-control" @change="loadTransactions">
          </div>
          <div class="col-md-3" v-if="dateRange === 'custom'">
            <label class="form-label">End Date</label>
            <input type="date" v-model="endDate" class="form-control" @change="loadTransactions">
          </div>
          <div class="col-md-3">
            <label class="form-label">Payment Method</label>
            <select v-model="paymentMethod" class="form-select" @change="loadTransactions">
              <option value="">All Methods</option>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transaction History</h5>
        <button class="btn btn-primary" @click="exportToExcel">
          <i class="fas fa-file-excel me-2"></i>
          Export to Excel
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.id">
                <td>{{ formatDate(payment.paymentDate) }}</td>
                <td>{{ payment.consultation.patient.name }}</td>
                <td>{{ payment.consultation.doctor.name }}</td>
                <td>${{ formatAmount(payment.amount) }}</td>
                <td>
                  <span :class="getPaymentMethodBadgeClass(payment.paymentMethod)">
                    {{ payment.paymentMethod }}
                  </span>
                </td>
                <td>{{ payment.reference || '-' }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="viewPayment(payment)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="btn btn-sm btn-secondary" @click="printReceipt(payment)">
                    <i class="fas fa-print"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- View Payment Modal -->
    <div class="modal fade" id="viewPaymentModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Payment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedPayment">
            <div class="mb-3">
              <strong>Date:</strong> {{ formatDate(selectedPayment.paymentDate) }}
            </div>
            <div class="mb-3">
              <strong>Patient:</strong> {{ selectedPayment.consultation.patient.name }}
            </div>
            <div class="mb-3">
              <strong>Doctor:</strong> {{ selectedPayment.consultation.doctor.name }}
            </div>
            <div class="mb-3">
              <strong>Amount:</strong> ${{ formatAmount(selectedPayment.amount) }}
            </div>
            <div class="mb-3">
              <strong>Payment Method:</strong> {{ selectedPayment.paymentMethod }}
            </div>
            <div class="mb-3" v-if="selectedPayment.reference">
              <strong>Reference:</strong> {{ selectedPayment.reference }}
            </div>
            <div class="mb-3" v-if="selectedPayment.notes">
              <strong>Notes:</strong> {{ selectedPayment.notes }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';
import * as XLSX from 'xlsx';

export default {
  name: 'FinanceDashboard',
  data() {
    return {
      payments: [],
      dateRange: 'today',
      startDate: '',
      endDate: '',
      paymentMethod: '',
      selectedPayment: null,
      viewModal: null,
      // Summary statistics
      todayIncome: 0,
      todayTransactions: 0,
      monthIncome: 0,
      monthTransactions: 0,
      yearIncome: 0,
      yearTransactions: 0,
      cashPercentage: 0,
      cardPercentage: 0
    };
  },
  async created() {
    await this.loadTransactions();
    await this.loadSummary();
  },
  mounted() {
    this.viewModal = new Modal(document.getElementById('viewPaymentModal'));
  },
  methods: {
    async loadTransactions() {
      try {
        const params = { dateRange: this.dateRange };
        if (this.dateRange === 'custom') {
          params.startDate = this.startDate;
          params.endDate = this.endDate;
        }
        if (this.paymentMethod) {
          params.paymentMethod = this.paymentMethod;
        }
        
        const response = await axios.get('/api/payments', { params });
        this.payments = response.data;
      } catch (error) {
        console.error('Error loading transactions:', error);
      }
    },
    async loadSummary() {
      try {
        const response = await axios.get('/api/payments/summary');
        const summary = response.data;
        this.todayIncome = summary.today.income;
        this.todayTransactions = summary.today.transactions;
        this.monthIncome = summary.month.income;
        this.monthTransactions = summary.month.transactions;
        this.yearIncome = summary.year.income;
        this.yearTransactions = summary.year.transactions;
        this.cashPercentage = summary.paymentMethods.cash;
        this.cardPercentage = summary.paymentMethods.card;
      } catch (error) {
        console.error('Error loading summary:', error);
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleString();
    },
    formatAmount(amount) {
      return amount.toFixed(2);
    },
    getPaymentMethodBadgeClass(method) {
      return {
        'badge': true,
        'bg-success': method === 'cash',
        'bg-info': method === 'card'
      };
    },
    viewPayment(payment) {
      this.selectedPayment = payment;
      this.viewModal.show();
    },
    async printReceipt(payment) {
      try {
        const response = await axios.get(`/api/payments/${payment.id}/receipt`, {
          responseType: 'blob'
        });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        window.open(url);
      } catch (error) {
        console.error('Error printing receipt:', error);
      }
    },
    exportToExcel() {
      const data = this.payments.map(payment => ({
        Date: this.formatDate(payment.paymentDate),
        Patient: payment.consultation.patient.name,
        Doctor: payment.consultation.doctor.name,
        Amount: payment.amount,
        Method: payment.paymentMethod,
        Reference: payment.reference || ''
      }));

      const ws = XLSX.utils.json_to_sheet(data);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Transactions');
      XLSX.writeFile(wb, `transactions_${this.dateRange}.xlsx`);
    }
  }
};
</script>

<style scoped>
.finance-dashboard {
  padding: 20px;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
  font-size: 0.8rem;
  padding: 0.5em 0.75em;
}

.table th {
  background-color: #f8f9fa;
  white-space: nowrap;
}
</style>
