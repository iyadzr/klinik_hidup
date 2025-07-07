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
    this.requestDeduplication = new Map(); // New: Request deduplication
    this.performanceMetrics = new Map(); // New: Performance tracking
    
    // Global settings - optimized for better navigation performance
    this.maxConcurrentRequests = 12; // Reduced from 15 for better stability
    this.defaultThrottleMs = 150; // Slightly increased for stability
    this.maxRetries = 1; // Reduced to 1 for faster failure handling
    this.circuitBreakerThreshold = 2; // Reduced for faster circuit breaking
    this.circuitBreakerTimeout = 10000; // Reduced to 10s for faster recovery
    this.requestTimeoutMs = 8000; // Reduced to 8s for faster timeouts
    this.deduplicationWindow = 500; // 500ms window for request deduplication
    
    // Memory management
    this.maxCacheSize = 500; // Reduced cache size
    this.cacheCleanupInterval = 180000; // 3 minutes
    
    this.startCleanupTimer();
    this.startPerformanceMonitoring();
  }

  /**
   * Make a throttled and protected request with enhanced deduplication
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
      skipDeduplication: options.skipDeduplication || false,
      priority: options.priority || 'normal',
      ...options
    };

    // Request deduplication - prevent identical requests within time window
    if (!config.skipDeduplication && this.isDuplicateRequest(key)) {
      console.log(`🔄 Duplicate request detected: ${key}, returning existing promise`);
      return this.getExistingRequest(key);
    }

    // Check circuit breaker
    if (!config.skipCircuitBreaker && this.isCircuitBreakerOpen(key)) {
      const error = new Error(`Circuit breaker is open for ${key}. Too many recent failures.`);
      error.circuitBreakerOpen = true;
      throw error;
    }

    // Enhanced throttle check with performance awareness
    if (!config.skipThrottle && this.isThrottled(key, config.throttleMs)) {
      console.log(`⏩ Request ${key} throttled - too frequent`);
      const lastResult = this.getLastResult(key);
      if (lastResult !== null && this.isResultFresh(key, 5000)) { // 5 second freshness
        return lastResult;
      }
      // If no cached result or stale, allow the request with warning
      console.log(`⏩ No fresh cached result for ${key}, allowing request despite throttling`);
    }

    // Cancel any existing request with the same key (unless it's high priority)
    if (config.priority !== 'high') {
      await this.cancelExistingRequest(key);
    }

    // Enhanced concurrent request management with priority queuing
    if (this.pendingRequests.size >= this.maxConcurrentRequests) {
      if (config.priority === 'high') {
        // For high priority requests, cancel a low priority request if possible
        this.cancelLowestPriorityRequest();
      } else {
        console.warn(`🚫 Maximum concurrent requests (${this.maxConcurrentRequests}) reached. Queuing request: ${key}`);
        return this.queueRequest(key, requestFn, config);
      }
    }

    return this.executeRequest(key, requestFn, config);
  }

  /**
   * Execute the actual request with full protection and monitoring
   * @private
   */
  async executeRequest(key, requestFn, config) {
    const controller = new AbortController();
    const requestId = this.generateRequestId();
    const startTime = performance.now();
    
    // Set up timeout with grace period
    const timeoutId = setTimeout(() => {
      console.warn(`⏰ Request ${key} timed out after ${config.timeout}ms`);
      controller.abort();
    }, config.timeout);

    // Track pending request with enhanced metadata
    const requestMetadata = {
      controller,
      timeoutId,
      requestId,
      startTime,
      priority: config.priority,
      key
    };
    
    this.pendingRequests.set(key, requestMetadata);
    
    // Store request promise for deduplication
    const requestPromise = this.executeRequestInternal(key, requestFn, controller, config, requestId, startTime);
    this.requestDeduplication.set(key, {
      promise: requestPromise,
      timestamp: Date.now()
    });

    try {
      const result = await requestPromise;
      return result;
    } finally {
      // Cleanup
      clearTimeout(timeoutId);
      this.pendingRequests.delete(key);
      
      // Clean up deduplication after a delay
      setTimeout(() => {
        this.requestDeduplication.delete(key);
      }, this.deduplicationWindow);
      
      // Process queued requests
      this.processQueue();
    }
  }

  /**
   * Internal request execution with detailed monitoring
   * @private
   */
  async executeRequestInternal(key, requestFn, controller, config, requestId, startTime) {
    try {
      console.log(`🚀 Starting request: ${key} (ID: ${requestId}, Priority: ${config.priority})`);
      
      // Execute the request function with abort signal
      const result = await requestFn(controller.signal);
      
      const executionTime = performance.now() - startTime;
      
      // Store successful result with timestamp
      this.storeResult(key, result, executionTime);
      this.resetCircuitBreaker(key);
      this.updateThrottleTimestamp(key);
      this.recordPerformanceMetric(key, executionTime, 'success');
      
      console.log(`✅ Request completed: ${key} (ID: ${requestId}) in ${executionTime.toFixed(2)}ms`);
      return result;

    } catch (error) {
      const executionTime = performance.now() - startTime;
      
      // Handle different error types with less noise for expected cancellations
      if (error.name === 'AbortError' || error.name === 'CanceledError' || error.message?.includes('canceled')) {
        console.log(`🛑 Request cancelled: ${key} (ID: ${requestId})`);
        this.recordPerformanceMetric(key, executionTime, 'cancelled');
        throw new Error(`Request ${key} was cancelled`);
      }
      
      // Enhanced error logging with context
      console.error(`❌ Request failed: ${key} (ID: ${requestId}) after ${executionTime.toFixed(2)}ms`, {
        error: error.message,
        status: error.response?.status,
        retryCount: config.retryCount
      });

      // Circuit breaker logic with enhanced failure tracking
      this.recordFailure(key, error);
      this.recordPerformanceMetric(key, executionTime, 'error');
      
      // Enhanced retry logic for non-user-cancelled errors
      if (config.retryCount < config.maxRetries && !controller.signal.aborted) {
        // Only retry for specific error types
        if (this.shouldRetry(error)) {
          console.log(`🔄 Retrying request ${key} (attempt ${config.retryCount + 1}/${config.maxRetries})`);
          
          // Exponential backoff with jitter
          const baseDelay = 1000 * Math.pow(2, config.retryCount);
          const jitter = Math.random() * 500; // Add randomness to prevent thundering herd
          const delay = Math.min(baseDelay + jitter, 5000);
          
          await this.sleep(delay);
          
          return this.makeRequest(key, requestFn, {
            ...config,
            retryCount: config.retryCount + 1,
            skipThrottle: true,
            skipDeduplication: true
          });
        }
      }

      throw error;
    }
  }

  /**
   * Enhanced request queuing with priority support
   * @private
   */
  async queueRequest(key, requestFn, config) {
    return new Promise((resolve, reject) => {
      if (!this.requestQueue.has(config.priority)) {
        this.requestQueue.set(config.priority, new Map());
      }
      
      const priorityQueue = this.requestQueue.get(config.priority);
      if (!priorityQueue.has(key)) {
        priorityQueue.set(key, []);
      }
      
      priorityQueue.get(key).push({
        requestFn,
        config,
        resolve,
        reject,
        timestamp: Date.now()
      });

      // Prevent queue from growing too large
      const queue = priorityQueue.get(key);
      if (queue.length > 5) { // Reduced from 10
        const oldest = queue.shift();
        oldest.reject(new Error(`Request queue overflow for ${key}`));
      }
    });
  }

  /**
   * Enhanced queue processing with priority support
   * @private
   */
  processQueue() {
    if (this.pendingRequests.size >= this.maxConcurrentRequests) {
      return;
    }

    // Process high priority first, then normal
    const priorities = ['high', 'normal'];
    
    for (const priority of priorities) {
      const priorityQueue = this.requestQueue.get(priority);
      if (!priorityQueue) continue;
      
      for (const [key, queue] of priorityQueue.entries()) {
        if (queue.length === 0) continue;
        
        const { requestFn, config, resolve, reject } = queue.shift();
        
        this.executeRequest(key, requestFn, config)
          .then(resolve)
          .catch(reject);

        if (this.pendingRequests.size >= this.maxConcurrentRequests) {
          return;
        }
      }
    }
  }

  /**
   * Check if this is a duplicate request within the deduplication window
   * @private
   */
  isDuplicateRequest(key) {
    const existing = this.requestDeduplication.get(key);
    if (!existing) return false;
    
    const age = Date.now() - existing.timestamp;
    return age < this.deduplicationWindow;
  }

  /**
   * Get existing request promise for deduplication
   * @private
   */
  getExistingRequest(key) {
    const existing = this.requestDeduplication.get(key);
    return existing ? existing.promise : null;
  }

  /**
   * Cancel the lowest priority request to make room for high priority ones
   * @private
   */
  cancelLowestPriorityRequest() {
    let lowestPriorityRequest = null;
    let lowestPriority = 'high';
    
    for (const [key, request] of this.pendingRequests.entries()) {
      if (request.priority === 'normal' && lowestPriority !== 'normal') {
        lowestPriorityRequest = { key, request };
        lowestPriority = 'normal';
      }
    }
    
    if (lowestPriorityRequest) {
      console.log(`🔄 Cancelling low priority request to make room: ${lowestPriorityRequest.key}`);
      lowestPriorityRequest.request.controller.abort();
    }
  }

  /**
   * Determine if an error should trigger a retry
   * @private
   */
  shouldRetry(error) {
    // Retry on network errors, timeouts, and 5xx server errors
    if (error.name === 'NetworkError') return true;
    if (error.name === 'TimeoutError') return true;
    if (error.response?.status >= 500) return true;
    if (error.response?.status === 429) return true; // Rate limited
    
    return false;
  }

  /**
   * Start performance monitoring
   * @private
   */
  startPerformanceMonitoring() {
    setInterval(() => {
      this.analyzePerformance();
    }, 30000); // Every 30 seconds
  }

  /**
   * Analyze performance metrics and adjust settings
   * @private
   */
  analyzePerformance() {
    const metrics = Array.from(this.performanceMetrics.values());
    if (metrics.length === 0) return;
    
    const avgResponseTime = metrics.reduce((sum, m) => sum + m.avgTime, 0) / metrics.length;
    const errorRate = metrics.reduce((sum, m) => sum + m.errorRate, 0) / metrics.length;
    
    console.log(`📊 Performance Analysis: Avg Response Time: ${avgResponseTime.toFixed(2)}ms, Error Rate: ${(errorRate * 100).toFixed(2)}%`);
    
    // Auto-adjust settings based on performance
    if (avgResponseTime > 3000) {
      this.requestTimeoutMs = Math.min(this.requestTimeoutMs + 1000, 15000);
      console.log(`⚙️ Increased timeout to ${this.requestTimeoutMs}ms due to slow responses`);
    } else if (avgResponseTime < 1000 && this.requestTimeoutMs > 5000) {
      this.requestTimeoutMs = Math.max(this.requestTimeoutMs - 1000, 5000);
      console.log(`⚙️ Decreased timeout to ${this.requestTimeoutMs}ms due to fast responses`);
    }
    
    if (errorRate > 0.2) { // More than 20% error rate
      this.maxConcurrentRequests = Math.max(this.maxConcurrentRequests - 1, 5);
      console.log(`⚙️ Reduced concurrent requests to ${this.maxConcurrentRequests} due to high error rate`);
    } else if (errorRate < 0.05 && this.maxConcurrentRequests < 15) {
      this.maxConcurrentRequests = Math.min(this.maxConcurrentRequests + 1, 15);
      console.log(`⚙️ Increased concurrent requests to ${this.maxConcurrentRequests} due to low error rate`);
    }
  }

  /**
   * Record performance metric
   * @private
   */
  recordPerformanceMetric(key, executionTime, status) {
    if (!this.performanceMetrics.has(key)) {
      this.performanceMetrics.set(key, {
        totalRequests: 0,
        totalTime: 0,
        errors: 0,
        avgTime: 0,
        errorRate: 0
      });
    }
    
    const metric = this.performanceMetrics.get(key);
    metric.totalRequests++;
    metric.totalTime += executionTime;
    
    if (status === 'error') {
      metric.errors++;
    }
    
    metric.avgTime = metric.totalTime / metric.totalRequests;
    metric.errorRate = metric.errors / metric.totalRequests;
  }

  /**
   * Check if cached result is still fresh
   * @private
   */
  isResultFresh(key, maxAge) {
    const result = this.resultCache?.get(key);
    if (!result) return false;
    
    return (Date.now() - result.timestamp) < maxAge;
  }

  /**
   * Cancel existing request for a key
   * @private
   */
  async cancelExistingRequest(key) {
    const existing = this.pendingRequests.get(key);
    if (existing) {
      console.log(`🔄 Cancelling existing request: ${key} (replacing with new request)`);
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
  recordFailure(key, error) {
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
  storeResult(key, result, executionTime) {
    // Simple in-memory cache - in production, consider using IndexedDB or localStorage
    if (!this.resultCache) {
      this.resultCache = new Map();
    }
    
    this.resultCache.set(key, {
      data: result,
      timestamp: Date.now(),
      executionTime: executionTime
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
    if (cached && Date.now() - cached.timestamp < 15000) { // 15 second cache for fresher data
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

  /**
   * Handle navigation requests with high priority
   * These requests skip throttling and have higher concurrent limits
   */
  async makeNavigationRequest(key, requestFn, options = {}) {
    const navigationConfig = {
      ...options,
      skipThrottle: true,
      timeout: options.timeout || 8000, // Faster timeout for navigation
      maxRetries: options.maxRetries || 1, // Fewer retries for navigation
      priority: 'high'
    };

    // For navigation requests, we allow higher concurrency
    const originalLimit = this.maxConcurrentRequests;
    this.maxConcurrentRequests = Math.max(20, originalLimit);

    try {
      return await this.makeRequest(key, requestFn, navigationConfig);
    } finally {
      // Restore original limit
      this.maxConcurrentRequests = originalLimit;
    }
  }
}

// Create singleton instance
const requestManager = new RequestManager();

export default requestManager;

// Export helper function for easier usage
export const makeProtectedRequest = (key, requestFn, options) => {
  return requestManager.makeRequest(key, requestFn, options);
};

// Export navigation-optimized request function
export const makeNavigationRequest = (key, requestFn, options) => {
  return requestManager.makeNavigationRequest(key, requestFn, options);
};

// Export utilities for advanced usage
export const cancelAllRequests = () => requestManager.cancelAllRequests();
export const getRequestStats = () => requestManager.getStats();
export const resetThrottles = () => requestManager.resetThrottles();
