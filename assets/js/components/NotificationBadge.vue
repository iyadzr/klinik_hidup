<template>
  <span 
    v-if="count > 0" 
    class="notification-badge"
    :class="{ 'notification-badge-large': count > 99 }"
    :title="`${count} pending ${count === 1 ? 'action' : 'actions'}`"
  >
    {{ displayCount }}
  </span>
</template>

<script>
export default {
  name: 'NotificationBadge',
  props: {
    count: {
      type: Number,
      default: 0
    }
  },
  computed: {
    displayCount() {
      if (this.count > 99) {
        return '99+';
      }
      return this.count.toString();
    }
  }
};
</script>

<style scoped>
.notification-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  min-width: 20px;
  height: 20px;
  font-size: 11px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  z-index: 10;
  animation: pulse 2s infinite;
}

.notification-badge-large {
  min-width: 24px;
  height: 24px;
  font-size: 10px;
  top: -10px;
  right: -10px;
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

/* Reduce animation for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
  .notification-badge {
    animation: none;
  }
}
</style> 