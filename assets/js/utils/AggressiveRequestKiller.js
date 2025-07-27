/**
 * Aggressive Request Killer - Nuclear option for preventing deadlocks
 * Immediately cancels ALL pending network activity on navigation
 */

class AggressiveRequestKiller {
    constructor() {
        this.allControllers = new Set();
        this.allEventSources = new Set();
        this.originalXHROpen = XMLHttpRequest.prototype.open;
        this.originalFetch = window.fetch;
        this.originalEventSource = window.EventSource;
        this.setupXHRInterception();
        this.setupFetchInterception();
        this.setupEventSourceInterception();
    }

    setupXHRInterception() {
        const self = this;
        
        XMLHttpRequest.prototype.open = function(...args) {
            const xhr = this;
            
            // Create abort controller for this XHR
            const controller = new AbortController();
            self.allControllers.add(controller);
            
            // Override abort to clean up tracking
            const originalAbort = xhr.abort.bind(xhr);
            xhr.abort = function() {
                controller.abort();
                self.allControllers.delete(controller);
                return originalAbort();
            };
            
            // Clean up on completion
            xhr.addEventListener('loadend', () => {
                self.allControllers.delete(controller);
            });
            
            return self.originalXHROpen.apply(this, args);
        };
    }

    setupFetchInterception() {
        const self = this;
        
        window.fetch = function(input, init = {}) {
            const controller = new AbortController();
            self.allControllers.add(controller);
            
            // Add abort signal
            init.signal = controller.signal;
            
            const fetchPromise = self.originalFetch(input, init);
            
            // Clean up on completion
            fetchPromise.finally(() => {
                self.allControllers.delete(controller);
            });
            
            return fetchPromise;
        };
    }

    setupEventSourceInterception() {
        const self = this;
        
        window.EventSource = function(url, eventSourceInitDict) {
            const eventSource = new self.originalEventSource(url, eventSourceInitDict);
            
            // Track this EventSource
            self.allEventSources.add(eventSource);
            
            // Override close to remove from tracking
            const originalClose = eventSource.close.bind(eventSource);
            eventSource.close = function() {
                self.allEventSources.delete(eventSource);
                return originalClose();
            };
            
            // Auto-cleanup on error
            eventSource.addEventListener('error', () => {
                setTimeout(() => {
                    if (eventSource.readyState === EventSource.CLOSED) {
                        self.allEventSources.delete(eventSource);
                    }
                }, 1000);
            });
            
            return eventSource;
        };
        
        // Copy static properties
        window.EventSource.CONNECTING = self.originalEventSource.CONNECTING;
        window.EventSource.OPEN = self.originalEventSource.OPEN;
        window.EventSource.CLOSED = self.originalEventSource.CLOSED;
    }

    /**
     * NUCLEAR OPTION: Cancel ALL pending requests immediately
     */
    killAllRequests() {
        console.log(`💥 KILLING ${this.allControllers.size} pending requests and ${this.allEventSources.size} EventSources`);
        
        // Abort all tracked controllers
        for (const controller of this.allControllers) {
            try {
                controller.abort();
            } catch (error) {
                // Ignore errors - controller might already be aborted
            }
        }
        
        // Close all tracked EventSources
        for (const eventSource of this.allEventSources) {
            try {
                console.log(`💥 KILLING EventSource: ${eventSource.url}`);
                eventSource.close();
            } catch (error) {
                // Ignore errors - EventSource might already be closed
            }
        }
        
        this.allControllers.clear();
        this.allEventSources.clear();
        
        // Also close any global EventSource references
        const globalEventSources = [
            'window._queueDisplayEventSource',
            'window._queueManagementEventSource',
            'window._appEventSource'
        ];
        
        for (const prop of globalEventSources) {
            const parts = prop.split('.');
            let obj = window;
            for (let i = 1; i < parts.length - 1; i++) {
                obj = obj[parts[i]];
                if (!obj) break;
            }
            if (obj && obj[parts[parts.length - 1]]) {
                console.log(`💥 KILLING global EventSource: ${prop}`);
                obj[parts[parts.length - 1]].close();
                delete obj[parts[parts.length - 1]];
            }
        }
        
        console.log('💥 ALL REQUESTS AND EVENTSOURCES KILLED');
    }

    /**
     * Kill only EventSource connections
     */
    killAllEventSources() {
        console.log(`💥 KILLING ${this.allEventSources.size} EventSources`);
        
        for (const eventSource of this.allEventSources) {
            try {
                eventSource.close();
            } catch (error) {
                // Ignore errors
            }
        }
        
        this.allEventSources.clear();
    }

    getStats() {
        return {
            pendingRequests: this.allControllers.size,
            activeEventSources: this.allEventSources.size,
            controllers: Array.from(this.allControllers),
            eventSources: Array.from(this.allEventSources).map(es => ({
                url: es.url,
                readyState: es.readyState
            }))
        };
    }
}

// Create global killer instance
const requestKiller = new AggressiveRequestKiller();

// Export for global access
window.requestKiller = requestKiller;

// Add global debugging methods
window.debugSSE = () => {
    console.log('=== SSE Debug Info ===');
    console.log('RequestKiller stats:', requestKiller.getStats());
    
    if (window.sseMonitor) {
        console.log('SSE Monitor stats:', window.sseMonitor.getStats());
    }
    
    // Check global EventSource references
    const globals = ['_queueDisplayEventSource', '_queueManagementEventSource', '_appEventSource'];
    globals.forEach(prop => {
        if (window[prop]) {
            console.log(`Global ${prop}:`, {
                url: window[prop].url,
                readyState: window[prop].readyState
            });
        }
    });
};

window.killAllSSE = () => {
    console.log('💥 EMERGENCY: Killing ALL SSE connections');
    requestKiller.killAllRequests();
    if (window.sseMonitor) {
        window.sseMonitor.killAllConnections();
    }
};

export default requestKiller;