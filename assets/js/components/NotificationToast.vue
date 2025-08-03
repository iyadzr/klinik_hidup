<template>
  <div v-if="isVisible" 
       class="notification-toast"
       :class="[`toast-${type}`, { 'toast-show': isVisible }]">
    <div class="toast-content">
      <div class="toast-icon">
        <i :class="iconClass"></i>
      </div>
      <div class="toast-message">
        <div class="toast-title">{{ title }}</div>
        <div class="toast-description">{{ message }}</div>
      </div>
      <div class="toast-close" @click="close">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <div class="toast-progress" :style="{ width: progressWidth + '%' }"></div>
  </div>
</template>

<script>
export default {
  name: 'NotificationToast',
  props: {
    type: {
      type: String,
      default: 'info',
      validator: value => ['success', 'error', 'warning', 'info'].includes(value)
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    duration: {
      type: Number,
      default: 3000
    },
    autoClose: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      isVisible: false,
      progressWidth: 100,
      progressInterval: null,
      closeTimeout: null
    };
  },
  computed: {
    iconClass() {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      };
      return icons[this.type] || icons.info;
    }
  },
  mounted() {
    this.show();
  },
  beforeUnmount() {
    this.cleanup();
  },
  methods: {
    show() {
      this.isVisible = true;
      
      if (this.autoClose) {
        this.startProgress();
        this.closeTimeout = setTimeout(() => {
          this.close();
        }, this.duration);
      }
    },
    
    close() {
      this.isVisible = false;
      this.cleanup();
      this.$emit('close');
    },
    
    startProgress() {
      const startTime = Date.now();
      const endTime = startTime + this.duration;
      
      this.progressInterval = setInterval(() => {
        const now = Date.now();
        const elapsed = now - startTime;
        const remaining = this.duration - elapsed;
        
        if (remaining <= 0) {
          this.progressWidth = 0;
          clearInterval(this.progressInterval);
        } else {
          this.progressWidth = (remaining / this.duration) * 100;
        }
      }, 50);
    },
    
    cleanup() {
      if (this.progressInterval) {
        clearInterval(this.progressInterval);
        this.progressInterval = null;
      }
      if (this.closeTimeout) {
        clearTimeout(this.closeTimeout);
        this.closeTimeout = null;
      }
    }
  }
};
</script>

<style scoped>
.notification-toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 350px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  z-index: 10000;
  transform: translateX(400px);
  transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  overflow: hidden;
  border: 1px solid #e9ecef;
}

.notification-toast.toast-show {
  transform: translateX(0);
}

.toast-content {
  display: flex;
  align-items: flex-start;
  padding: 16px;
  gap: 12px;
}

.toast-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 14px;
}

.toast-success .toast-icon {
  background: #d4edda;
  color: #155724;
}

.toast-error .toast-icon {
  background: #f8d7da;
  color: #721c24;
}

.toast-warning .toast-icon {
  background: #fff3cd;
  color: #856404;
}

.toast-info .toast-icon {
  background: #d1ecf1;
  color: #0c5460;
}

.toast-message {
  flex-grow: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 14px;
  color: #2c3e50;
  margin-bottom: 4px;
  line-height: 1.2;
}

.toast-description {
  font-size: 13px;
  color: #6c757d;
  line-height: 1.4;
}

.toast-close {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6c757d;
  border-radius: 50%;
  transition: all 0.2s ease;
  font-size: 12px;
}

.toast-close:hover {
  background: #f8f9fa;
  color: #495057;
}

.toast-progress {
  height: 3px;
  background: linear-gradient(90deg, #007bff, #0056b3);
  transition: width 0.05s linear;
}

.toast-success .toast-progress {
  background: linear-gradient(90deg, #28a745, #1e7e34);
}

.toast-error .toast-progress {
  background: linear-gradient(90deg, #dc3545, #c82333);
}

.toast-warning .toast-progress {
  background: linear-gradient(90deg, #ffc107, #e0a800);
}

.toast-info .toast-progress {
  background: linear-gradient(90deg, #17a2b8, #138496);
}

/* Animation for new toasts */
@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(400px);
    opacity: 0;
  }
}

.notification-toast.toast-show {
  animation: slideIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.notification-toast:not(.toast-show) {
  animation: slideOut 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
</style> 