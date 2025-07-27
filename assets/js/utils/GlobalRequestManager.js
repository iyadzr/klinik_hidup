/**
 * Global Request Manager - Prevents deadlocks by managing all HTTP requests
 * Automatically cancels requests on route changes and prevents overlapping calls
 */
import axios from 'axios';

class GlobalRequestManager {
    constructor() {
        this.activeRequests = new Map();
        this.routeRequests = new Map();
        this.eventSources = new Map();
        this.intervals = new Map();
        this.timeouts = new Map();
        this.setupInterceptors();
        this.setupEventSourceTracking();
        this.setupIntervalTracking();
    }

    setupInterceptors() {
        // Request interceptor - track all outgoing requests
        axios.interceptors.request.use((config) => {
            // Create abort controller for this request
            const abortController = new AbortController();
            config.signal = abortController.signal;
            
            // Generate unique request key
            const requestKey = this.generateRequestKey(config);
            
            // Cancel duplicate requests
            if (this.activeRequests.has(requestKey)) {
                console.log(`🔄 Cancelling duplicate request: ${requestKey}`);
                this.activeRequests.get(requestKey).abort();
            }
            
            // Track this request
            this.activeRequests.set(requestKey, abortController);
            
            // Associate with current route if available
            const currentRoute = window.location.pathname;
            if (!this.routeRequests.has(currentRoute)) {
                this.routeRequests.set(currentRoute, new Set());
            }
            this.routeRequests.get(currentRoute).add(requestKey);
            
            console.log(`🚀 Starting request: ${requestKey}`);
            return config;
        }, (error) => {
            return Promise.reject(error);
        });

        // Response interceptor - cleanup completed requests
        axios.interceptors.response.use((response) => {
            const requestKey = this.generateRequestKey(response.config);
            this.cleanupRequest(requestKey);
            console.log(`✅ Completed request: ${requestKey}`);
            return response;
        }, (error) => {
            if (error.config) {
                const requestKey = this.generateRequestKey(error.config);
                this.cleanupRequest(requestKey);
                
                if (error.name === 'AbortError') {
                    console.log(`⏹️ Cancelled request: ${requestKey}`);
                } else {
                    console.log(`❌ Failed request: ${requestKey}`, error.message);
                }
            }
            return Promise.reject(error);
        });
    }

    setupEventSourceTracking() {
        // Override EventSource constructor to track connections
        if (typeof window !== 'undefined' && window.EventSource) {
            const originalEventSource = window.EventSource;
            const self = this;
            
            window.EventSource = function(url, eventSourceInitDict) {
                const eventSource = new originalEventSource(url, eventSourceInitDict);
                const route = window.location.pathname;
                const sourceKey = `${route}:${url}`;
                
                // Track this EventSource
                self.eventSources.set(sourceKey, eventSource);
                console.log(`📡 Tracking EventSource: ${sourceKey}`);
                
                // Override close method to clean up tracking
                const originalClose = eventSource.close.bind(eventSource);
                eventSource.close = function() {
                    self.eventSources.delete(sourceKey);
                    console.log(`🔌 EventSource closed: ${sourceKey}`);
                    return originalClose();
                };
                
                return eventSource;
            };
        }
    }

    setupIntervalTracking() {
        // Override setInterval to track intervals
        if (typeof window !== 'undefined') {
            const originalSetInterval = window.setInterval;
            const originalClearInterval = window.clearInterval;
            const self = this;
            
            window.setInterval = function(callback, delay, ...args) {
                const intervalId = originalSetInterval(callback, delay, ...args);
                const route = window.location.pathname;
                const intervalKey = `${route}:${intervalId}`;
                
                self.intervals.set(intervalKey, intervalId);
                console.log(`⏰ Tracking interval: ${intervalKey}`);
                
                return intervalId;
            };
            
            window.clearInterval = function(intervalId) {
                // Remove from tracking when cleared
                for (let [key, id] of self.intervals) {
                    if (id === intervalId) {
                        self.intervals.delete(key);
                        console.log(`⏹️ Interval cleared: ${key}`);
                        break;
                    }
                }
                return originalClearInterval(intervalId);
            };
        }
    }

    generateRequestKey(config) {
        // Create unique key based on method, URL, and critical params
        const method = config.method?.toUpperCase() || 'GET';
        const url = config.url || '';
        const params = JSON.stringify(config.params || {});
        return `${method}:${url}:${params}`;
    }

    cleanupRequest(requestKey) {
        this.activeRequests.delete(requestKey);
        
        // Remove from route tracking
        for (let [route, requests] of this.routeRequests) {
            requests.delete(requestKey);
            if (requests.size === 0) {
                this.routeRequests.delete(route);
            }
        }
    }

    /**
     * Cancel all requests for a specific route
     */
    cancelRouteRequests(route) {
        const requests = this.routeRequests.get(route);
        if (requests) {
            console.log(`🧹 Cancelling ${requests.size} requests for route: ${route}`);
            
            for (let requestKey of requests) {
                if (this.activeRequests.has(requestKey)) {
                    this.activeRequests.get(requestKey).abort();
                    this.activeRequests.delete(requestKey);
                }
            }
            
            this.routeRequests.delete(route);
        }
        
        // Also cancel EventSources for this route
        this.cancelRouteEventSources(route);
        
        // Also cancel intervals for this route
        this.cancelRouteIntervals(route);
    }

    /**
     * Cancel EventSources for a specific route
     */
    cancelRouteEventSources(route) {
        const routeEventSources = [];
        for (let [key, eventSource] of this.eventSources) {
            if (key.startsWith(route + ':')) {
                routeEventSources.push(key);
            }
        }
        
        if (routeEventSources.length > 0) {
            console.log(`🔌 Closing ${routeEventSources.length} EventSources for route: ${route}`);
            
            for (let key of routeEventSources) {
                const eventSource = this.eventSources.get(key);
                if (eventSource) {
                    eventSource.close();
                    this.eventSources.delete(key);
                }
            }
        }
    }

    /**
     * Cancel intervals for a specific route
     */
    cancelRouteIntervals(route) {
        const routeIntervals = [];
        for (let [key, intervalId] of this.intervals) {
            if (key.startsWith(route + ':')) {
                routeIntervals.push([key, intervalId]);
            }
        }
        
        if (routeIntervals.length > 0) {
            console.log(`⏹️ Clearing ${routeIntervals.length} intervals for route: ${route}`);
            
            for (let [key, intervalId] of routeIntervals) {
                clearInterval(intervalId);
                this.intervals.delete(key);
            }
        }
    }

    /**
     * Cancel all active requests
     */
    cancelAllRequests() {
        console.log(`🧹 Cancelling ${this.activeRequests.size} requests, ${this.eventSources.size} EventSources, ${this.intervals.size} intervals`);
        
        // Cancel HTTP requests
        for (let [requestKey, controller] of this.activeRequests) {
            controller.abort();
        }
        
        // Close all EventSources
        for (let [key, eventSource] of this.eventSources) {
            eventSource.close();
        }
        
        // Clear all intervals
        for (let [key, intervalId] of this.intervals) {
            clearInterval(intervalId);
        }
        
        this.activeRequests.clear();
        this.routeRequests.clear();
        this.eventSources.clear();
        this.intervals.clear();
    }

    /**
     * Get status information
     */
    getStatus() {
        return {
            activeRequests: this.activeRequests.size,
            routeRequests: this.routeRequests.size,
            eventSources: this.eventSources.size,
            intervals: this.intervals.size,
            details: {
                requests: Array.from(this.activeRequests.keys()),
                eventSources: Array.from(this.eventSources.keys()),
                intervals: Array.from(this.intervals.keys())
            }
        };
    }
}

// Create global instance
const globalRequestManager = new GlobalRequestManager();

// Setup route change detection to cancel requests
if (typeof window !== 'undefined') {
    let currentRoute = window.location.pathname;
    
    // Listen for route changes (works with Vue Router)
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;
    
    history.pushState = function(...args) {
        const newRoute = args[2] || window.location.pathname;
        if (newRoute !== currentRoute) {
            globalRequestManager.cancelRouteRequests(currentRoute);
            currentRoute = newRoute;
        }
        return originalPushState.apply(history, args);
    };
    
    history.replaceState = function(...args) {
        const newRoute = args[2] || window.location.pathname;
        if (newRoute !== currentRoute) {
            globalRequestManager.cancelRouteRequests(currentRoute);
            currentRoute = newRoute;
        }
        return originalReplaceState.apply(history, args);
    };
    
    // Listen for back/forward navigation
    window.addEventListener('popstate', () => {
        const newRoute = window.location.pathname;
        if (newRoute !== currentRoute) {
            globalRequestManager.cancelRouteRequests(currentRoute);
            currentRoute = newRoute;
        }
    });
    
    // Cancel all requests when page unloads
    window.addEventListener('beforeunload', () => {
        globalRequestManager.cancelAllRequests();
    });
}

export default globalRequestManager;