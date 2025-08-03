/**
 * Notification Service for managing toast notifications
 * Handles multiple notifications with auto-close functionality
 */
class NotificationService {
  constructor() {
    this.notifications = [];
    this.nextId = 1;
    this.container = null;
    this.init();
  }

  init() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
      this.container = document.createElement('div');
      this.container.id = 'notification-container';
      this.container.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
        max-width: 400px;
        width: 100%;
      `;
      document.body.appendChild(this.container);
    } else {
      this.container = document.getElementById('notification-container');
    }
  }

  /**
   * Show a notification toast
   */
  show(options) {
    const id = this.nextId++;
    const notification = {
      id,
      type: options.type || 'info',
      title: options.title || '',
      message: options.message || '',
      duration: options.duration || 3000,
      autoClose: options.autoClose !== false,
      position: options.position || 'bottom-right' // Add position support
    };

    this.notifications.push(notification);
    this.renderNotification(notification);
    
    return id;
  }

  /**
   * Show notification at bottom-left position
   */
  showBottomLeft(options) {
    return this.show({
      ...options,
      position: 'bottom-left'
    });
  }

  /**
   * Show success notification
   */
  success(title, message, duration = 3000) {
    return this.show({
      type: 'success',
      title,
      message,
      duration
    });
  }

  /**
   * Show error notification
   */
  error(title, message, duration = 4000) {
    return this.show({
      type: 'error',
      title,
      message,
      duration
    });
  }

  /**
   * Show warning notification
   */
  warning(title, message, duration = 3500) {
    return this.show({
      type: 'warning',
      title,
      message,
      duration
    });
  }

  /**
   * Show info notification
   */
  info(title, message, duration = 3000) {
    return this.show({
      type: 'info',
      title,
      message,
      duration
    });
  }

  /**
   * Close a specific notification
   */
  close(id) {
    const index = this.notifications.findIndex(n => n.id === id);
    if (index > -1) {
      this.notifications.splice(index, 1);
      this.removeNotificationElement(id);
    }
  }

  /**
   * Close all notifications
   */
  closeAll() {
    this.notifications = [];
    this.container.innerHTML = '';
  }

  /**
   * Render notification element
   */
  renderNotification(notification) {
    const element = document.createElement('div');
    element.id = `notification-${notification.id}`;
    element.className = 'notification-toast';
    
    // Determine position and animation direction
    const isBottomLeft = notification.position === 'bottom-left';
    const transformDirection = isBottomLeft ? 'translateX(-400px)' : 'translateX(400px)';
    
    element.style.cssText = `
      pointer-events: auto;
      transform: ${transformDirection};
      transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      width: 350px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      border: 1px solid #e9ecef;
    `;

    const iconClass = this.getIconClass(notification.type);
    const progressColor = this.getProgressColor(notification.type);

    element.innerHTML = `
      <div class="toast-content">
        <div class="toast-icon" style="
          flex-shrink: 0;
          width: 24px;
          height: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          font-size: 14px;
          background: ${this.getIconBackground(notification.type)};
          color: ${this.getIconColor(notification.type)};
        ">
          <i class="${iconClass}"></i>
        </div>
        <div class="toast-message" style="flex-grow: 1; min-width: 0;">
          <div class="toast-title" style="
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.2;
          ">${notification.title}</div>
          <div class="toast-description" style="
            font-size: 13px;
            color: #6c757d;
            line-height: 1.4;
          ">${notification.message}</div>
        </div>
        <div class="toast-close" onclick="window.notificationService.close(${notification.id})" style="
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
          hover: background-color: #f8f9fa;
        ">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <div class="toast-progress" style="
        height: 3px;
        background: ${progressColor};
        width: 100%;
        transition: width 0.05s linear;
      "></div>
    `;

    // Use appropriate container based on position
    const targetContainer = isBottomLeft ? this.getBottomLeftContainer() : this.container;
    targetContainer.appendChild(element);

    // Trigger animation
    setTimeout(() => {
      element.style.transform = 'translateX(0)';
    }, 10);

    // Auto-close functionality
    if (notification.autoClose) {
      this.startProgress(element, notification.duration);
      setTimeout(() => {
        this.close(notification.id);
      }, notification.duration);
    }
  }

  /**
   * Remove notification element
   */
  removeNotificationElement(id) {
    const element = document.getElementById(`notification-${id}`);
    if (element) {
      // Determine animation direction based on container
      const isBottomLeft = element.parentNode && element.parentNode.id === 'notification-container-bottom-left';
      const transformDirection = isBottomLeft ? 'translateX(-400px)' : 'translateX(400px)';
      
      element.style.transform = transformDirection;
      setTimeout(() => {
        if (element.parentNode) {
          element.parentNode.removeChild(element);
        }
      }, 300);
    }
  }

  /**
   * Start progress bar animation
   */
  startProgress(element, duration) {
    const progressBar = element.querySelector('.toast-progress');
    if (!progressBar) return;

    const startTime = Date.now();
    const interval = setInterval(() => {
      const elapsed = Date.now() - startTime;
      const remaining = duration - elapsed;
      
      if (remaining <= 0) {
        progressBar.style.width = '0%';
        clearInterval(interval);
      } else {
        const percentage = (remaining / duration) * 100;
        progressBar.style.width = percentage + '%';
      }
    }, 50);
  }

  /**
   * Get icon class for notification type
   */
  getIconClass(type) {
    const icons = {
      success: 'fas fa-check-circle',
      error: 'fas fa-exclamation-circle',
      warning: 'fas fa-exclamation-triangle',
      info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
  }

  /**
   * Get icon background color
   */
  getIconBackground(type) {
    const backgrounds = {
      success: '#d4edda',
      error: '#f8d7da',
      warning: '#fff3cd',
      info: '#d1ecf1'
    };
    return backgrounds[type] || backgrounds.info;
  }

  /**
   * Get icon color
   */
  getIconColor(type) {
    const colors = {
      success: '#155724',
      error: '#721c24',
      warning: '#856404',
      info: '#0c5460'
    };
    return colors[type] || colors.info;
  }

  /**
   * Get progress bar color
   */
  getProgressColor(type) {
    const colors = {
      success: 'linear-gradient(90deg, #28a745, #1e7e34)',
      error: 'linear-gradient(90deg, #dc3545, #c82333)',
      warning: 'linear-gradient(90deg, #ffc107, #e0a800)',
      info: 'linear-gradient(90deg, #17a2b8, #138496)'
    };
    return colors[type] || colors.info;
  }

  /**
   * Get or create bottom-left container
   */
  getBottomLeftContainer() {
    if (!document.getElementById('notification-container-bottom-left')) {
      const container = document.createElement('div');
      container.id = 'notification-container-bottom-left';
      container.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
        max-width: 400px;
        width: 100%;
      `;
      document.body.appendChild(container);
    }
    return document.getElementById('notification-container-bottom-left');
  }
}

// Create global instance
const notificationService = new NotificationService();

// Make it available globally
window.notificationService = notificationService;

export default notificationService; 