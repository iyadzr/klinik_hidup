<template>
  <div class="financial-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="dashboard-title">Financial Dashboard</h1>
          <p class="dashboard-subtitle mb-0">Real-time financial insights and analytics</p>
        </div>
        <div class="dashboard-controls d-flex gap-3">
          <div class="period-selector">
            <select v-model="selectedPeriod" @change="loadDashboardData" class="form-select" :disabled="isLoading">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="quarter">This Quarter</option>
              <option value="year">This Year</option>
              <option value="all">All Time</option>
            </select>
          </div>
          <button @click="exportData" class="btn btn-success" :disabled="isLoading">
            <i class="fas fa-file-excel me-2"></i>Export
          </button>
          <button @click="loadDashboardData" class="btn btn-primary" :disabled="isLoading">
            <i class="fas fa-sync-alt me-2" :class="{ 'fa-spin': isLoading }"></i>
            {{ isLoading ? 'Loading...' : 'Refresh' }}
          </button>
        </div>
      </div>

      <!-- Last Updated Info -->
      <div class="last-updated mb-4" v-if="lastUpdated">
        <small class="text-muted">
          <i class="fas fa-clock me-1"></i>
          Last updated: {{ formatDateTime(lastUpdated) }}
        </small>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong>Error:</strong> {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading && !hasInitialData" class="loading-overlay">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">Loading financial data...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Key Performance Indicators -->
      <div class="kpi-section mb-5">
        <div class="row g-4">
          <!-- Total Revenue -->
          <div class="col-lg-3 col-md-6">
            <div class="kpi-card revenue-card" :class="{ 'loading': isLoading }">
              <div class="kpi-icon">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">RM {{ formatCurrency(summary.total_revenue) }}</div>
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-change" v-if="trends.revenue_change !== undefined">
                  <span :class="getTrendClass(trends.revenue_change)">
                    <i :class="getTrendIcon(trends.revenue_change)"></i>
                    {{ Math.abs(trends.revenue_change) }}%
                  </span>
                  <small class="text-muted">vs previous period</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Consultations -->
          <div class="col-lg-3 col-md-6">
            <div class="kpi-card consultations-card" :class="{ 'loading': isLoading }">
              <div class="kpi-icon">
                <i class="fas fa-user-md"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ formatNumber(summary.total_consultations) }}</div>
                <div class="kpi-label">Consultations</div>
                <div class="kpi-change" v-if="trends.consultations_change !== undefined">
                  <span :class="getTrendClass(trends.consultations_change)">
                    <i :class="getTrendIcon(trends.consultations_change)"></i>
                    {{ Math.abs(trends.consultations_change) }}%
                  </span>
                  <small class="text-muted">vs previous period</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Average Revenue per Consultation -->
          <div class="col-lg-3 col-md-6">
            <div class="kpi-card average-card" :class="{ 'loading': isLoading }">
              <div class="kpi-icon">
                <i class="fas fa-calculator"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">RM {{ formatCurrency(summary.average_per_consultation) }}</div>
                <div class="kpi-label">Avg per Consultation</div>
                <div class="kpi-change" v-if="trends.average_change !== undefined">
                  <span :class="getTrendClass(trends.average_change)">
                    <i :class="getTrendIcon(trends.average_change)"></i>
                    {{ Math.abs(trends.average_change) }}%
                  </span>
                  <small class="text-muted">vs previous period</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Monthly Growth -->
          <div class="col-lg-3 col-md-6">
            <div class="kpi-card growth-card" :class="{ 'loading': isLoading }">
              <div class="kpi-icon">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">
                  {{ monthlyStats.growth_percentage >= 0 ? '+' : '' }}{{ formatNumber(monthlyStats.growth_percentage) }}%
                </div>
                <div class="kpi-label">Monthly Growth</div>
                <div class="kpi-trend">
                  <span :class="getTrendClass(monthlyStats.growth_percentage)">
                    {{ monthlyStats.growth_percentage >= 0 ? 'Increasing' : 'Decreasing' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Analytics -->
      <div class="analytics-section mb-5">
        <div class="row g-4">
          <!-- Revenue Chart -->
          <div class="col-lg-8">
            <div class="analytics-card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="card-title mb-0">
                    <i class="fas fa-chart-area me-2"></i>Revenue Trend
                  </h5>
                  <div class="chart-controls">
                    <div class="btn-group btn-group-sm" role="group">
                      <input type="radio" class="btn-check" id="chart-7days" v-model="chartPeriod" value="7days" @change="loadRevenueChart">
                      <label class="btn btn-outline-primary" for="chart-7days">7D</label>
                      
                      <input type="radio" class="btn-check" id="chart-30days" v-model="chartPeriod" value="30days" @change="loadRevenueChart">
                      <label class="btn btn-outline-primary" for="chart-30days">30D</label>
                      
                      <input type="radio" class="btn-check" id="chart-90days" v-model="chartPeriod" value="90days" @change="loadRevenueChart">
                      <label class="btn btn-outline-primary" for="chart-90days">90D</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas ref="revenueChart" id="revenueChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Methods Breakdown -->
          <div class="col-lg-4">
            <div class="analytics-card">
              <div class="card-header">
                <h5 class="card-title mb-0">
                  <i class="fas fa-credit-card me-2"></i>Payment Methods
                </h5>
              </div>
              <div class="card-body">
                <div class="payment-methods">
                  <div v-for="method in paymentMethodStats" :key="method.paymentMethod" class="payment-method-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="payment-method-info">
                        <i :class="getPaymentMethodIcon(method.paymentMethod)" class="me-2"></i>
                        <span class="method-name">{{ formatPaymentMethod(method.paymentMethod) }}</span>
                      </div>
                      <div class="method-stats">
                        <span class="amount">RM {{ formatCurrency(method.total) }}</span>
                        <small class="count text-muted">({{ method.count }} txns)</small>
                      </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                      <div 
                        class="progress-bar" 
                        :class="getPaymentMethodColor(method.paymentMethod)"
                        :style="{ width: getPaymentMethodPercentage(method.total) + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Tables -->
      <div class="tables-section">
        <div class="row g-4">
          <!-- Recent Transactions -->
          <div class="col-lg-7">
            <div class="data-card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Recent Transactions
                  </h5>
                  <router-link to="/payments" class="btn btn-sm btn-outline-primary">
                    View All
                  </router-link>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="transaction in recentTransactions" :key="transaction.id">
                        <td>
                          <small class="text-muted">{{ formatDate(transaction.payment_date) }}</small>
                        </td>
                        <td>
                          <div class="patient-info">
                            <span class="patient-name">{{ transaction.patient_name }}</span>
                          </div>
                        </td>
                        <td>
                          <span class="amount-cell">RM {{ formatCurrency(transaction.amount) }}</span>
                        </td>
                        <td>
                          <span :class="getPaymentMethodBadge(transaction.payment_method)">
                            {{ formatPaymentMethod(transaction.payment_method) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-success">Paid</span>
                        </td>
                      </tr>
                      <tr v-if="recentTransactions.length === 0">
                        <td colspan="5" class="text-center text-muted py-4">
                          <i class="fas fa-info-circle me-2"></i>
                          No transactions found for the selected period
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Medications -->
          <div class="col-lg-5">
            <div class="data-card">
              <div class="card-header">
                <h5 class="card-title mb-0">
                  <i class="fas fa-pills me-2"></i>Top Prescribed Medications
                </h5>
              </div>
              <div class="card-body">
                <div class="medication-stats">
                  <div v-for="(med, index) in medicationStats.slice(0, 6)" :key="index" class="medication-item">
                    <div class="medication-info">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h6 class="medication-name mb-1">{{ med.medicationName }}</h6>
                          <small class="text-muted">{{ med.usageCount }} prescriptions</small>
                        </div>
                        <div class="text-end">
                          <span class="badge bg-primary">{{ med.totalQuantity }} units</span>
                        </div>
                      </div>
                      <div class="progress" style="height: 4px;">
                        <div 
                          class="progress-bar bg-gradient" 
                          :style="{ width: (med.usageCount / (medicationStats[0]?.usageCount || 1) * 100) + '%' }"
                        ></div>
                      </div>
                    </div>
                  </div>
                  <div v-if="medicationStats.length === 0" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    No medication data available for the selected period
                  </div>
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
import Chart from 'chart.js/auto';

export default {
  name: 'FinancialDashboard',
  data() {
    return {
      selectedPeriod: 'today',
      chartPeriod: '30days',
      dashboardData: {},
      summary: {
        total_revenue: 0,
        total_consultations: 0,
        average_per_consultation: 0
      },
      monthlyStats: {
        growth_percentage: 0
      },
      trends: {},
      medicationStats: [],
      paymentMethodStats: [],
      recentTransactions: [],
      revenueChartData: [],
      isLoading: false,
      hasInitialData: false,
      error: null,
      lastUpdated: null,
      revenueChart: null
    };
  },
  async created() {
    await this.loadDashboardData();
    await this.loadRevenueChart();
  },
  mounted() {
    // Set up auto-refresh every 5 minutes
    this.autoRefreshInterval = setInterval(() => {
      this.loadDashboardData(true); // Silent refresh
    }, 5 * 60 * 1000);
  },
  beforeUnmount() {
    if (this.autoRefreshInterval) {
      clearInterval(this.autoRefreshInterval);
    }
    if (this.revenueChart) {
      this.revenueChart.destroy();
    }
  },
  methods: {
    async loadDashboardData(silent = false) {
      if (!silent) {
        this.isLoading = true;
      }
      this.error = null;
      
      try {
        const response = await axios.get(`/api/financial/dashboard?period=${this.selectedPeriod}`);
        
        if (response.data) {
          this.dashboardData = response.data;
          this.summary = response.data.summary || {};
          this.monthlyStats = response.data.monthly_stats || {};
          this.trends = response.data.trends || {};
          this.medicationStats = response.data.medication_stats || [];
          this.paymentMethodStats = response.data.payment_method_stats || [];
          this.recentTransactions = response.data.recent_transactions || [];
          this.lastUpdated = new Date();
          this.hasInitialData = true;
        }
      } catch (error) {
        console.error('Error loading dashboard data:', error);
        this.error = error.response?.data?.message || 'Failed to load financial data. Please try again.';
        
        // Show more specific error messages
        if (error.response?.status === 404) {
          this.error = 'Financial data endpoint not found. Please check if the API is properly configured.';
        } else if (error.response?.status >= 500) {
          this.error = 'Server error occurred while loading financial data. Please contact support.';
        }
      } finally {
        this.isLoading = false;
      }
    },

    async loadRevenueChart() {
      try {
        const response = await axios.get(`/api/financial/revenue-chart?period=${this.chartPeriod}`);
        this.revenueChartData = response.data;
        this.renderRevenueChart();
      } catch (error) {
        console.error('Error loading revenue chart:', error);
      }
    },

    renderRevenueChart() {
      const ctx = this.$refs.revenueChart;
      if (!ctx || !this.revenueChartData.length) return;

      if (this.revenueChart) {
        this.revenueChart.destroy();
      }

      const labels = this.revenueChartData.map(item => {
        const date = new Date(item.payment_date);
        return date.toLocaleDateString('en-MY', { month: 'short', day: 'numeric' });
      });

      const data = this.revenueChartData.map(item => parseFloat(item.daily_total || 0));

      this.revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Daily Revenue (RM)',
            data: data,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: 'rgb(75, 192, 192)',
            pointBorderWidth: 2,
            pointRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: '#fff',
              bodyColor: '#fff',
              cornerRadius: 8,
              displayColors: false,
              callbacks: {
                label: function(context) {
                  return `Revenue: RM ${context.parsed.y.toFixed(2)}`;
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0,0,0,0.05)'
              },
              ticks: {
                font: {
                  size: 12
                },
                callback: function(value) {
                  return 'RM ' + value.toFixed(0);
                }
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        }
      });
    },

    async exportData() {
      try {
        this.isLoading = true;
        const response = await axios.get(`/api/financial/export?period=${this.selectedPeriod}`, {
          responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `financial-report-${this.selectedPeriod}-${new Date().toISOString().split('T')[0]}.xlsx`;
        link.click();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error exporting data:', error);
        this.error = 'Failed to export data. Please try again.';
      } finally {
        this.isLoading = false;
      }
    },

    formatCurrency(amount) {
      const num = parseFloat(amount || 0);
      return num.toFixed(2);
    },

    formatNumber(number) {
      return parseInt(number || 0).toLocaleString('en-MY');
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      try {
        const dateObj = new Date(dateString);
        return dateObj.toLocaleDateString('en-GB', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        });
      } catch (error) {
        return 'Invalid Date';
      }
    },

    formatDateTime(dateTime) {
      if (!dateTime) return 'N/A';
      try {
        return dateTime.toLocaleString('en-GB', {
          timeZone: 'Asia/Kuala_Lumpur',
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          hour12: false
        });
      } catch (error) {
        return 'Invalid Date';
      }
    },

    formatPaymentMethod(method) {
      const methods = {
        'cash': 'Cash',
        'card': 'Card',
        'bank_transfer': 'Bank Transfer',
        'ewallet': 'E-Wallet',
        'online': 'Online Payment'
      };
      return methods[method] || method.charAt(0).toUpperCase() + method.slice(1);
    },

    getPaymentMethodIcon(method) {
      const icons = {
        'cash': 'fas fa-money-bill-alt text-success',
        'card': 'fas fa-credit-card text-primary',
        'bank_transfer': 'fas fa-university text-info',
        'ewallet': 'fas fa-mobile-alt text-warning',
        'online': 'fas fa-globe text-secondary'
      };
      return icons[method] || 'fas fa-dollar-sign';
    },

    getPaymentMethodColor(method) {
      const colors = {
        'cash': 'bg-success',
        'card': 'bg-primary',
        'bank_transfer': 'bg-info',
        'ewallet': 'bg-warning',
        'online': 'bg-secondary'
      };
      return colors[method] || 'bg-primary';
    },

    getPaymentMethodBadge(method) {
      const badges = {
        'cash': 'badge bg-success-subtle text-success',
        'card': 'badge bg-primary-subtle text-primary',
        'bank_transfer': 'badge bg-info-subtle text-info',
        'ewallet': 'badge bg-warning-subtle text-warning',
        'online': 'badge bg-secondary-subtle text-secondary'
      };
      return badges[method] || 'badge bg-light text-dark';
    },

    getPaymentMethodPercentage(amount) {
      const total = this.paymentMethodStats.reduce((sum, method) => sum + parseFloat(method.total), 0);
      return total > 0 ? (parseFloat(amount) / total * 100) : 0;
    },

    getTrendClass(change) {
      if (change > 0) return 'text-success';
      if (change < 0) return 'text-danger';
      return 'text-muted';
    },

    getTrendIcon(change) {
      if (change > 0) return 'fas fa-arrow-up';
      if (change < 0) return 'fas fa-arrow-down';
      return 'fas fa-minus';
    }
  }
};
</script>

<style scoped>
.financial-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 2rem;
}

.dashboard-header {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.dashboard-subtitle {
  color: #718096;
  font-size: 1.1rem;
}

.dashboard-controls .form-select {
  min-width: 150px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 500;
}

.dashboard-controls .btn {
  border-radius: 12px;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.dashboard-controls .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.last-updated {
  padding: 0.5rem 1rem;
  background: rgba(102, 126, 234, 0.1);
  border-left: 4px solid #667eea;
  border-radius: 0 8px 8px 0;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

/* KPI Cards */
.kpi-section {
  margin-bottom: 3rem;
}

.kpi-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
  border: none;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  height: 140px;
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--gradient);
}

.kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px rgba(0,0,0,0.12);
}

.kpi-card.loading {
  opacity: 0.7;
  pointer-events: none;
}

.kpi-icon {
  width: 70px;
  height: 70px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  flex-shrink: 0;
  background: var(--gradient);
}

.kpi-content {
  flex: 1;
}

.kpi-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 0.25rem;
  line-height: 1;
}

.kpi-label {
  color: #718096;
  font-weight: 500;
  font-size: 0.95rem;
  margin-bottom: 0.5rem;
}

.kpi-change {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Card Gradients */
.revenue-card {
  --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.consultations-card {
  --gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.average-card {
  --gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.growth-card {
  --gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

/* Analytics Cards */
.analytics-card,
.data-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
  border: none;
  overflow: hidden;
  transition: all 0.3s ease;
}

.analytics-card:hover,
.data-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.12);
}

.analytics-card .card-header,
.data-card .card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-bottom: none;
  padding: 1.5rem;
  color: white;
}

.analytics-card .card-title,
.data-card .card-title {
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
  margin: 0;
}

.analytics-card .card-body,
.data-card .card-body {
  padding: 1.5rem;
}

/* Chart Styles */
.chart-container {
  position: relative;
  height: 300px;
}

.chart-controls .btn-group .btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 6px;
}

/* Payment Methods */
.payment-methods {
  space-y: 1rem;
}

.payment-method-item {
  padding: 1rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.payment-method-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.payment-method-info {
  display: flex;
  align-items: center;
}

.method-name {
  font-weight: 500;
  color: #2d3748;
}

.method-stats {
  text-align: right;
}

.amount {
  font-weight: 600;
  color: #2d3748;
  display: block;
}

.count {
  font-size: 0.8rem;
}

/* Table Styles */
.table {
  border-radius: 0;
  margin: 0;
}

.table th {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-top: none;
  border-bottom: 2px solid #e2e8f0;
  font-weight: 600;
  color: #4a5568;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.25rem;
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

.patient-name {
  font-weight: 500;
  color: #2d3748;
}

.amount-cell {
  font-weight: 600;
  color: #2d3748;
}

/* Medication Stats */
.medication-item {
  padding: 1rem 0;
  border-bottom: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.medication-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.medication-item:hover {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  margin: 0 -1.5rem;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  border-bottom: 1px solid transparent;
}

.medication-name {
  color: #2d3748;
  font-weight: 600;
  font-size: 0.95rem;
}

/* Progress Bars */
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

.progress-bar.bg-gradient {
  background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
}

/* Badges */
.badge {
  font-weight: 500;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
}

/* Alert Styles */
.alert {
  border-radius: 12px;
  border: none;
  box-shadow: 0 4px 16px rgba(220, 53, 69, 0.15);
}

/* Responsive Design */
@media (max-width: 768px) {
  .financial-dashboard {
    padding: 1rem;
  }
  
  .dashboard-header {
    padding: 1.5rem;
  }
  
  .dashboard-title {
    font-size: 2rem;
  }
  
  .dashboard-controls {
    flex-direction: column;
    gap: 0.75rem !important;
  }
  
  .kpi-card {
    padding: 1.5rem;
    gap: 1rem;
    height: auto;
    min-height: 120px;
  }
  
  .kpi-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }
  
  .kpi-value {
    font-size: 1.5rem;
  }
  
  .analytics-card .card-body,
  .data-card .card-body {
    padding: 1rem;
  }
  
  .table th,
  .table td {
    padding: 0.75rem;
    font-size: 0.875rem;
  }
  
  .chart-container {
    height: 250px;
  }
}

/* Animation for cards */
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

.kpi-card {
  animation: slideInUp 0.6s ease forwards;
}

.kpi-card:nth-child(1) { animation-delay: 0.1s; }
.kpi-card:nth-child(2) { animation-delay: 0.2s; }
.kpi-card:nth-child(3) { animation-delay: 0.3s; }
.kpi-card:nth-child(4) { animation-delay: 0.4s; }

.analytics-card,
.data-card {
  animation: slideInUp 0.6s ease forwards;
  animation-delay: 0.5s;
}

/* Skeleton Loading Effect */
.kpi-card.loading::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  height: 100%;
  width: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}
</style> 