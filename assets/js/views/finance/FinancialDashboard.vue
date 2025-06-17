<template>
  <div class="financial-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Financial Dashboard</h2>
      <div class="d-flex gap-2">
        <select v-model="selectedPeriod" @change="loadDashboardData" class="form-select">
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="quarter">This Quarter</option>
          <option value="year">This Year</option>
        </select>
        <button @click="loadDashboardData" class="btn btn-outline-primary">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="stats-card revenue-card">
          <div class="stats-icon">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="stats-content">
            <h3>RM {{ formatCurrency(summary.total_revenue) }}</h3>
            <p>Total Revenue</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card consultations-card">
          <div class="stats-icon">
            <i class="fas fa-stethoscope"></i>
          </div>
          <div class="stats-content">
            <h3>{{ summary.total_consultations }}</h3>
            <p>Consultations</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card average-card">
          <div class="stats-icon">
            <i class="fas fa-calculator"></i>
          </div>
          <div class="stats-content">
            <h3>RM {{ formatCurrency(summary.average_per_consultation) }}</h3>
            <p>Avg per Consultation</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card growth-card">
          <div class="stats-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="stats-content">
            <h3>{{ monthlyStats.growth_percentage >= 0 ? '+' : '' }}{{ monthlyStats.growth_percentage }}%</h3>
            <p>Monthly Growth</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Recent Transactions -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Recent Transactions</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Amount</th>
                    <th>Method</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="transaction in recentTransactions" :key="transaction.id">
                    <td>{{ formatDate(transaction.payment_date) }}</td>
                    <td>{{ transaction.patient_name }}</td>
                    <td>RM {{ formatCurrency(transaction.amount) }}</td>
                    <td>
                      <span class="badge bg-light text-dark">{{ transaction.payment_method }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Medications -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Top Prescribed Medications</h5>
          </div>
          <div class="card-body">
            <div class="medication-stats">
              <div 
                v-for="(med, index) in medicationStats.slice(0, 5)" 
                :key="index"
                class="medication-item"
              >
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1">{{ med.medicationName }}</h6>
                    <small class="text-muted">{{ med.usageCount }} prescriptions</small>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-primary">{{ med.totalQuantity }} units</span>
                  </div>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                  <div 
                    class="progress-bar" 
                    :style="{ width: (med.usageCount / medicationStats[0]?.usageCount * 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'FinancialDashboard',
  data() {
    return {
      selectedPeriod: 'today',
      dashboardData: {},
      summary: {
        total_revenue: 0,
        total_consultations: 0,
        average_per_consultation: 0
      },
      monthlyStats: {
        growth_percentage: 0
      },
      medicationStats: [],
      paymentMethodStats: [],
      recentTransactions: [],
      isLoading: false
    };
  },
  async created() {
    await this.loadDashboardData();
  },
  methods: {
    async loadDashboardData() {
      this.isLoading = true;
      try {
        const response = await axios.get(`/api/financial/dashboard?period=${this.selectedPeriod}`);
        this.dashboardData = response.data;
        this.summary = response.data.summary;
        this.monthlyStats = response.data.monthly_stats;
        this.medicationStats = response.data.medication_stats;
        this.paymentMethodStats = response.data.payment_method_stats;
        this.recentTransactions = response.data.recent_transactions;
      } catch (error) {
        console.error('Error loading dashboard data:', error);
      }
      this.isLoading = false;
    },

    formatCurrency(amount) {
      return parseFloat(amount || 0).toFixed(2);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        const dateObj = new Date(dateString);
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
    }
  }
};
</script>

<style scoped>
.financial-dashboard {
  padding: 2rem 0;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

.stats-card {
  padding: 2rem;
  border-radius: 20px;
  background: white;
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
  border: none;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stats-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--gradient);
}

.stats-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px rgba(0,0,0,0.12);
}

.stats-icon {
  width: 70px;
  height: 70px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  flex-shrink: 0;
}

.revenue-card {
  --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.revenue-card .stats-icon {
  background: var(--gradient);
}

.consultations-card {
  --gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.consultations-card .stats-icon {
  background: var(--gradient);
}

.average-card {
  --gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.average-card .stats-icon {
  background: var(--gradient);
}

.growth-card {
  --gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.growth-card .stats-icon {
  background: var(--gradient);
}

.stats-content h3 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #1a202c;
  background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stats-content p {
  margin: 0;
  color: #718096;
  font-weight: 500;
  font-size: 0.95rem;
}

.card {
  border: none;
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  background: white;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.12);
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-bottom: none;
  padding: 1.75rem;
  border-radius: 20px 20px 0 0;
  color: white;
}

.card-header h5 {
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
  margin: 0;
}

.card-body {
  padding: 1.75rem;
}

.medication-item {
  padding: 1.25rem 0;
  border-bottom: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.medication-item:hover {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  margin: 0 -1.75rem;
  padding: 1.25rem 1.75rem;
  border-radius: 12px;
}

.medication-item:last-child {
  border-bottom: none;
}

.medication-item h6 {
  color: #2d3748;
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.progress {
  height: 6px;
  background-color: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-bar {
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 3px;
  transition: width 0.6s ease;
}

.table {
  border-radius: 0 0 20px 20px;
  overflow: hidden;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #4a5568;
  font-size: 0.875rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 1rem 1.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table td {
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table tbody tr {
  transition: background-color 0.2s ease;
}

.table tbody tr:hover {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.badge {
  font-weight: 500;
  padding: 0.5rem 0.875rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.badge.bg-light {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
  color: #4a5568 !important;
  border: 1px solid #e2e8f0;
}

.form-select {
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  padding: 0.75rem 1rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn {
  border-radius: 12px;
  font-weight: 500;
  padding: 0.75rem 1.5rem;
  transition: all 0.2s ease;
  border: none;
}

.btn-outline-primary {
  border: 2px solid #667eea;
  color: #667eea;
  background: white;
}

.btn-outline-primary:hover {
  background: #667eea;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Loading states */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
  z-index: 10;
}

.spinner-border {
  color: #667eea;
}

/* Responsive design */
@media (max-width: 768px) {
  .financial-dashboard {
    padding: 1rem 0;
  }
  
  .stats-card {
    padding: 1.5rem;
    gap: 1rem;
  }
  
  .stats-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }
  
  .stats-content h3 {
    font-size: 1.5rem;
  }
  
  .card-header,
  .card-body {
    padding: 1.25rem;
  }
  
  .table th,
  .table td {
    padding: 0.75rem;
    font-size: 0.875rem;
  }
}

/* Animation for stats cards */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stats-card {
  animation: slideInUp 0.6s ease forwards;
}

.stats-card:nth-child(1) { animation-delay: 0.1s; }
.stats-card:nth-child(2) { animation-delay: 0.2s; }
.stats-card:nth-child(3) { animation-delay: 0.3s; }
.stats-card:nth-child(4) { animation-delay: 0.4s; }
</style> 