/**
 * Request Debouncer - Prevents overlapping API calls and deadlocks
 * This is the most effective way to prevent request conflicts
 */
class RequestDebouncer {
    constructor() {
        this.activeRequests = new Map();
        this.debouncedRequests = new Map();
        this.requestQueue = new Map();
    }
    
    /**
     * Debounce API requests to prevent overlapping calls
     * @param {string} key - Unique key for the request type
     * @param {Function} requestFn - The API request function
     * @param {number} delay - Debounce delay in milliseconds
     * @returns {Promise}
     */
    debounce(key, requestFn, delay = 300) {
        return new Promise((resolve, reject) => {
            // Cancel existing debounced request
            if (this.debouncedRequests.has(key)) {
                clearTimeout(this.debouncedRequests.get(key).timeout);
            }
            
            // If there's an active request for this key, queue this one
            if (this.activeRequests.has(key)) {
                console.log(`🔄 Queueing request for key: ${key}`);
                this.requestQueue.set(key, { requestFn, resolve, reject });
                return;
            }
            
            const timeout = setTimeout(async () => {
                this.debouncedRequests.delete(key);
                
                try {
                    // Mark request as active
                    const abortController = new AbortController();
                    this.activeRequests.set(key, abortController);
                    
                    console.log(`🚀 Executing debounced request: ${key}`);
                    const result = await requestFn(abortController.signal);
                    
                    // Clean up and resolve
                    this.activeRequests.delete(key);
                    resolve(result);
                    
                    // Process queued request if any
                    this.processQueue(key);
                    
                } catch (error) {
                    this.activeRequests.delete(key);
                    
                    // Don't reject if request was cancelled
                    if (error.name === 'AbortError') {
                        console.log(`⏹️ Request cancelled: ${key}`);
                        return;
                    }
                    
                    // Handle timeout errors gracefully
                    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
                        console.warn(`⏰ Request timeout for ${key}, will retry`);
                        // Don't immediately reject, let the system try again
                        setTimeout(() => {
                            this.processQueue(key);
                        }, 2000);
                        return;
                    }
                    
                    reject(error);
                    this.processQueue(key);
                }
            }, delay);
            
            this.debouncedRequests.set(key, { timeout, resolve, reject });
        });
    }
    
    /**
     * Process queued requests
     */
    processQueue(key) {
        if (this.requestQueue.has(key)) {
            const { requestFn, resolve, reject } = this.requestQueue.get(key);
            this.requestQueue.delete(key);
            
            console.log(`📤 Processing queued request: ${key}`);
            this.debounce(key, requestFn, 100).then(resolve).catch(reject);
        }
    }
    
    /**
     * Cancel all pending requests for a key
     */
    cancel(key) {
        // Cancel debounced request
        if (this.debouncedRequests.has(key)) {
            clearTimeout(this.debouncedRequests.get(key).timeout);
            this.debouncedRequests.delete(key);
        }
        
        // Cancel active request
        if (this.activeRequests.has(key)) {
            this.activeRequests.get(key).abort();
            this.activeRequests.delete(key);
        }
        
        // Clear queue
        this.requestQueue.delete(key);
        
        console.log(`🛑 Cancelled all requests for key: ${key}`);
    }
    
    /**
     * Cancel all requests
     */
    cancelAll() {
        // Cancel all debounced requests
        for (let [key, { timeout }] of this.debouncedRequests) {
            clearTimeout(timeout);
        }
        this.debouncedRequests.clear();
        
        // Cancel all active requests
        for (let [key, controller] of this.activeRequests) {
            controller.abort();
        }
        this.activeRequests.clear();
        
        // Clear all queues
        this.requestQueue.clear();
        
        console.log('🧹 Cancelled all requests');
    }
}

// Create global instance
const requestDebouncer = new RequestDebouncer();

export default requestDebouncer;