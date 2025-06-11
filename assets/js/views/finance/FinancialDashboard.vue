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
      return new Date(dateString).toLocaleDateString();
    }
  }
};
</script>

<style scoped>
.financial-dashboard {
  padding: 2rem 0;
}

.stats-card {
  padding: 2rem;
  border-radius: 16px;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border: none;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: transform 0.2s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
}

.stats-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.revenue-card .stats-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.consultations-card .stats-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.average-card .stats-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.growth-card .stats-icon {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stats-content h3 {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #2d3748;
}

.stats-content p {
  margin: 0;
  color: #718096;
  font-weight: 500;
}

.card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem;
  border-radius: 16px 16px 0 0;
}

.card-header h5 {
  color: #2d3748;
  font-weight: 600;
}

.medication-item {
  padding: 1rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.medication-item:last-child {
  border-bottom: none;
}

.medication-item h6 {
  color: #2d3748;
  font-weight: 600;
}

.progress {
  height: 4px;
  background-color: #e2e8f0;
}

.progress-bar {
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #4a5568;
  font-size: 0.875rem;
}

.badge {
  font-weight: 500;
  padding: 0.375rem 0.75rem;
}
</style> 