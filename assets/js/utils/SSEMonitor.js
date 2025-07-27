/**
 * SSE Connection Monitor - Prevents stale EventSource connections
 */

class SSEMonitor {
    constructor() {
        this.connections = new Map();
        this.maxConnectionAge = 5 * 60 * 1000; // 5 minutes
        this.cleanupInterval = null;
        this.startMonitoring();
    }

    /**
     * Register an EventSource connection for monitoring
     */
    register(id, eventSource, component = 'unknown') {
        console.log(`📊 SSEMonitor: Registering connection ${id} from ${component}`);
        
        // Close existing connection with same ID
        if (this.connections.has(id)) {
            console.log(`📊 SSEMonitor: Closing existing connection ${id}`);
            const existing = this.connections.get(id);
            try {
                existing.eventSource.close();
            } catch (error) {
                console.warn('Error closing existing connection:', error);
            }
        }

        this.connections.set(id, {
            eventSource,
            component,
            createdAt: Date.now(),
            lastActivity: Date.now()
        });

        // Override onmessage to track activity
        const originalOnMessage = eventSource.onmessage;
        eventSource.onmessage = (event) => {
            this.updateActivity(id);
            if (originalOnMessage) {
                originalOnMessage.call(eventSource, event);
            }
        };

        return eventSource;
    }

    /**
     * Unregister an EventSource connection
     */
    unregister(id) {
        if (this.connections.has(id)) {
            console.log(`📊 SSEMonitor: Unregistering connection ${id}`);
            const connection = this.connections.get(id);
            try {
                connection.eventSource.close();
            } catch (error) {
                console.warn('Error closing connection during unregister:', error);
            }
            this.connections.delete(id);
        }
    }

    /**
     * Update last activity timestamp for a connection
     */
    updateActivity(id) {
        if (this.connections.has(id)) {
            this.connections.get(id).lastActivity = Date.now();
        }
    }

    /**
     * Get all registered connections
     */
    getConnections() {
        const result = [];
        for (const [id, connection] of this.connections) {
            result.push({
                id,
                component: connection.component,
                url: connection.eventSource.url,
                readyState: connection.eventSource.readyState,
                age: Date.now() - connection.createdAt,
                timeSinceActivity: Date.now() - connection.lastActivity,
                isStale: this.isConnectionStale(connection)
            });
        }
        return result;
    }

    /**
     * Check if a connection is stale
     */
    isConnectionStale(connection) {
        const age = Date.now() - connection.createdAt;
        const timeSinceActivity = Date.now() - connection.lastActivity;
        
        return (
            age > this.maxConnectionAge ||
            timeSinceActivity > this.maxConnectionAge ||
            connection.eventSource.readyState === EventSource.CLOSED
        );
    }

    /**
     * Clean up stale connections
     */
    cleanupStaleConnections() {
        const staleConnections = [];
        
        for (const [id, connection] of this.connections) {
            if (this.isConnectionStale(connection)) {
                staleConnections.push(id);
            }
        }

        if (staleConnections.length > 0) {
            console.log(`📊 SSEMonitor: Cleaning up ${staleConnections.length} stale connections`);
            
            for (const id of staleConnections) {
                this.unregister(id);
            }
        }
    }

    /**
     * Kill all connections immediately
     */
    killAllConnections() {
        console.log(`📊 SSEMonitor: Killing all ${this.connections.size} connections`);
        
        for (const [id, connection] of this.connections) {
            try {
                connection.eventSource.close();
            } catch (error) {
                console.warn(`Error closing connection ${id}:`, error);
            }
        }
        
        this.connections.clear();
    }

    /**
     * Start automatic monitoring and cleanup
     */
    startMonitoring() {
        if (this.cleanupInterval) {
            clearInterval(this.cleanupInterval);
        }

        // Clean up stale connections every 30 seconds
        this.cleanupInterval = setInterval(() => {
            this.cleanupStaleConnections();
        }, 30000);

        console.log('📊 SSEMonitor: Started monitoring');
    }

    /**
     * Stop monitoring
     */
    stopMonitoring() {
        if (this.cleanupInterval) {
            clearInterval(this.cleanupInterval);
            this.cleanupInterval = null;
        }

        this.killAllConnections();
        console.log('📊 SSEMonitor: Stopped monitoring');
    }

    /**
     * Get monitoring statistics
     */
    getStats() {
        const connections = this.getConnections();
        const staleCount = connections.filter(c => c.isStale).length;
        
        return {
            totalConnections: connections.length,
            staleConnections: staleCount,
            activeConnections: connections.length - staleCount,
            connections: connections
        };
    }
}

// Create global instance
const sseMonitor = new SSEMonitor();

// Make it globally accessible for debugging
window.sseMonitor = sseMonitor;

export default sseMonitor;