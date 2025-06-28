/**
 * Request Manager Utility
 * Provides robust protection against spam requests, concurrent operations, and system overload
 */

class RequestManager {
  constructor() {
    this.pendingRequests = new Map();
    this.requestQueue = new Map();
    this.throttleMap = new Map();
    this.retryCounters = new Map();
    this.circuitBreakers = new Map();
    
    // Global settings
    this.maxConcurrentRequests = 10;
    this.defaultThrottleMs = 300;
    this.maxRetries = 3;
    this.circuitBreakerThreshold = 5;
    this.circuitBreakerTimeout = 30000;
    this.requestTimeoutMs = 15000;
    
    // Memory management
    this.maxCacheSize = 1000;
    this.cacheCleanupInterval = 300000; // 5 minutes
    
    this.startCleanupTimer();
  }

  /**
   * Make a throttled and protected request
   * @param {string} key - Unique identifier for the request type
   * @param {Function} requestFn - Function that returns a Promise (the actual request)
   * @param {Object} options - Configuration options
   * @returns {Promise} - The request result
   */
  async makeRequest(key, requestFn, options = {}) {
    const config = {
      throttleMs: options.throttleMs || this.defaultThrottleMs,
      retryCount: options.retryCount || 0,
      maxRetries: options.maxRetries || this.maxRetries,
      timeout: options.timeout || this.requestTimeoutMs,
      skipThrottle: options.skipThrottle || false,
      skipCircuitBreaker: options.skipCircuitBreaker || false,
      ...options
    };

    // Check circuit breaker
    if (!config.skipCircuitBreaker && this.isCircuitBreakerOpen(key)) {
      throw new Error(`Circuit breaker is open for ${key}. Too many recent failures.`);
    }

    // Throttle check
    if (!config.skipThrottle && this.isThrottled(key, config.throttleMs)) {
      console.log(`⏩ Request ${key} throttled - too frequent`);
      return this.getLastResult(key);
    }

    // Cancel any existing request with the same key
    await this.cancelExistingRequest(key);

    // Check concurrent request limit
    if (this.pendingRequests.size >= this.maxConcurrentRequests) {
      console.warn(`🚫 Maximum concurrent requests (${this.maxConcurrentRequests}) reached. Queuing request: ${key}`);
      return this.queueRequest(key, requestFn, config);
    }

    return this.executeRequest(key, requestFn, config);
  }

  /**
   * Execute the actual request with full protection
   * @private
   */
  async executeRequest(key, requestFn, config) {
    const controller = new AbortController();
    const requestId = this.generateRequestId();
    
    // Set up timeout
    const timeoutId = setTimeout(() => {
      controller.abort();
      console.warn(`⏰ Request ${key} timed out after ${config.timeout}ms`);
    }, config.timeout);

    // Track pending request
    this.pendingRequests.set(key, {
      controller,
      timeoutId,
      requestId,
      startTime: Date.now()
    });

    try {
      console.log(`🚀 Starting request: ${key} (ID: ${requestId})`);
      
      // Execute the request function with abort signal
      const result = await requestFn(controller.signal);
      
      // Store successful result
      this.storeResult(key, result);
      this.resetCircuitBreaker(key);
      this.updateThrottleTimestamp(key);
      
      console.log(`✅ Request completed: ${key} (ID: ${requestId})`);
      return result;

    } catch (error) {
      console.error(`❌ Request failed: ${key} (ID: ${requestId})`, error);
      
      // Handle different error types
      if (error.name === 'AbortError') {
        console.log(`🛑 Request aborted: ${key}`);
        throw new Error(`Request ${key} was cancelled`);
      }

      // Circuit breaker logic
      this.recordFailure(key);
      
      // Retry logic for non-user-cancelled errors
      if (config.retryCount < config.maxRetries && !controller.signal.aborted) {
        console.log(`🔄 Retrying request ${key} (attempt ${config.retryCount + 1}/${config.maxRetries})`);
        
        // Exponential backoff
        const delay = Math.min(1000 * Math.pow(2, config.retryCount), 10000);
        await this.sleep(delay);
        
        return this.makeRequest(key, requestFn, {
          ...config,
          retryCount: config.retryCount + 1,
          skipThrottle: true
        });
      }

      throw error;

    } finally {
      // Cleanup
      clearTimeout(timeoutId);
      this.pendingRequests.delete(key);
      
      // Process queued requests
      this.processQueue();
    }
  }

  /**
   * Queue a request when concurrent limit is reached
   * @private
   */
  async queueRequest(key, requestFn, config) {
    return new Promise((resolve, reject) => {
      if (!this.requestQueue.has(key)) {
        this.requestQueue.set(key, []);
      }
      
      this.requestQueue.get(key).push({
        requestFn,
        config,
        resolve,
        reject,
        timestamp: Date.now()
      });

      // Prevent queue from growing too large
      const queue = this.requestQueue.get(key);
      if (queue.length > 10) {
        const oldest = queue.shift();
        oldest.reject(new Error(`Request queue overflow for ${key}`));
      }
    });
  }

  /**
   * Process queued requests
   * @private
   */
  processQueue() {
    if (this.pendingRequests.size >= this.maxConcurrentRequests) {
      return;
    }

    for (const [key, queue] of this.requestQueue.entries()) {
      if (queue.length === 0) continue;
      
      const { requestFn, config, resolve, reject } = queue.shift();
      
      this.executeRequest(key, requestFn, config)
        .then(resolve)
        .catch(reject);

      if (this.pendingRequests.size >= this.maxConcurrentRequests) {
        break;
      }
    }
  }

  /**
   * Cancel existing request for a key
   * @private
   */
  async cancelExistingRequest(key) {
    const existing = this.pendingRequests.get(key);
    if (existing) {
      console.log(`🛑 Cancelling existing request: ${key}`);
      existing.controller.abort();
      clearTimeout(existing.timeoutId);
      this.pendingRequests.delete(key);
    }
  }

  /**
   * Check if requests should be throttled
   * @private
   */
  isThrottled(key, throttleMs) {
    const lastCall = this.throttleMap.get(key);
    if (!lastCall) return false;
    
    return (Date.now() - lastCall) < throttleMs;
  }

  /**
   * Update throttle timestamp
   * @private
   */
  updateThrottleTimestamp(key) {
    this.throttleMap.set(key, Date.now());
  }

  /**
   * Circuit breaker functionality
   * @private
   */
  isCircuitBreakerOpen(key) {
    const breaker = this.circuitBreakers.get(key);
    if (!breaker) return false;
    
    if (breaker.state === 'open' && 
        Date.now() - breaker.lastFailure > this.circuitBreakerTimeout) {
      // Move to half-open state
      breaker.state = 'half-open';
      console.log(`🔌 Circuit breaker for ${key} moved to half-open state`);
    }
    
    return breaker.state === 'open';
  }

  /**
   * Record request failure for circuit breaker
   * @private
   */
  recordFailure(key) {
    if (!this.circuitBreakers.has(key)) {
      this.circuitBreakers.set(key, {
        failureCount: 0,
        state: 'closed',
        lastFailure: null
      });
    }
    
    const breaker = this.circuitBreakers.get(key);
    breaker.failureCount++;
    breaker.lastFailure = Date.now();
    
    if (breaker.failureCount >= this.circuitBreakerThreshold) {
      breaker.state = 'open';
      console.warn(`⚡ Circuit breaker opened for ${key} due to ${breaker.failureCount} failures`);
    }
  }

  /**
   * Reset circuit breaker on successful request
   * @private
   */
  resetCircuitBreaker(key) {
    if (this.circuitBreakers.has(key)) {
      this.circuitBreakers.get(key).failureCount = 0;
      this.circuitBreakers.get(key).state = 'closed';
    }
  }

  /**
   * Store request result for potential reuse
   * @private
   */
  storeResult(key, result) {
    // Simple in-memory cache - in production, consider using IndexedDB or localStorage
    if (!this.resultCache) {
      this.resultCache = new Map();
    }
    
    this.resultCache.set(key, {
      data: result,
      timestamp: Date.now()
    });

    // Prevent cache from growing too large
    if (this.resultCache.size > this.maxCacheSize) {
      const oldestKey = this.resultCache.keys().next().value;
      this.resultCache.delete(oldestKey);
    }
  }

  /**
   * Get last successful result if available
   * @private
   */
  getLastResult(key) {
    if (!this.resultCache) return null;
    
    const cached = this.resultCache.get(key);
    if (cached && Date.now() - cached.timestamp < 30000) { // 30 second cache
      console.log(`📋 Using cached result for ${key}`);
      return cached.data;
    }
    
    return null;
  }

  /**
   * Cleanup old entries to prevent memory leaks
   * @private
   */
  startCleanupTimer() {
    setInterval(() => {
      this.cleanup();
    }, this.cacheCleanupInterval);
  }

  /**
   * Cleanup old entries
   * @private
   */
  cleanup() {
    const now = Date.now();
    const maxAge = 300000; // 5 minutes

    // Clean throttle map
    for (const [key, timestamp] of this.throttleMap.entries()) {
      if (now - timestamp > maxAge) {
        this.throttleMap.delete(key);
      }
    }

    // Clean result cache
    if (this.resultCache) {
      for (const [key, result] of this.resultCache.entries()) {
        if (now - result.timestamp > maxAge) {
          this.resultCache.delete(key);
        }
      }
    }

    // Clean old queue entries
    for (const [key, queue] of this.requestQueue.entries()) {
      this.requestQueue.set(key, queue.filter(item => 
        now - item.timestamp < 60000 // 1 minute max queue time
      ));
    }

    console.log(`🧹 RequestManager cleanup completed`);
  }

  /**
   * Utility functions
   * @private
   */
  generateRequestId() {
    return Math.random().toString(36).substr(2, 9);
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Public methods for monitoring
   */
  getStats() {
    return {
      pendingRequests: this.pendingRequests.size,
      queuedRequests: Array.from(this.requestQueue.values()).reduce((sum, queue) => sum + queue.length, 0),
      circuitBreakers: Object.fromEntries(this.circuitBreakers),
      cacheSize: this.resultCache ? this.resultCache.size : 0
    };
  }

  /**
   * Cancel all pending requests
   */
  cancelAllRequests() {
    console.log(`🛑 Cancelling all pending requests (${this.pendingRequests.size})`);
    
    for (const [key, request] of this.pendingRequests.entries()) {
      request.controller.abort();
      clearTimeout(request.timeoutId);
    }
    
    this.pendingRequests.clear();
    this.requestQueue.clear();
  }

  /**
   * Reset all throttles (for testing or forced refresh)
   */
  resetThrottles() {
    this.throttleMap.clear();
    console.log('🔄 All throttles reset');
  }
}

// Create singleton instance
const requestManager = new RequestManager();

export default requestManager;

// Export helper function for easier usage
export const makeProtectedRequest = (key, requestFn, options) => {
  return requestManager.makeRequest(key, requestFn, options);
};

// Export utilities for advanced usage
export const cancelAllRequests = () => requestManager.cancelAllRequests();
export const getRequestStats = () => requestManager.getStats();
export const resetThrottles = () => requestManager.resetThrottles();
