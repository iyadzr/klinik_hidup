/**
 * Search Debouncer Utility
 * Specialized utility for handling user search inputs with intelligent debouncing
 */

class SearchDebouncer {
  constructor() {
    this.activeSearches = new Map();
    this.searchHistory = new Map();
    this.searchCache = new Map();
    
    // Configuration
    this.defaultDebounceMs = 300;
    this.minSearchLength = 2;
    this.maxCacheSize = 500;
    this.cacheExpiryMs = 300000; // 5 minutes
  }

  /**
   * Debounced search with intelligent caching and protection
   * @param {string} searchKey - Unique identifier for this search context
   * @param {string} query - Search query
   * @param {Function} searchFn - Function that performs the actual search
   * @param {Object} options - Configuration options
   * @returns {Promise} Search results
   */
  async search(searchKey, query, searchFn, options = {}) {
    const config = {
      debounceMs: options.debounceMs || this.defaultDebounceMs,
      minLength: options.minLength || this.minSearchLength,
      cacheResults: options.cacheResults !== false,
      onLoading: options.onLoading || (() => {}),
      onError: options.onError || ((error) => console.error('Search error:', error)),
      onEmpty: options.onEmpty || (() => {}),
      ...options
    };

    // Validate query length
    if (query.length < config.minLength) {
      config.onEmpty();
      return [];
    }

    // Check cache first
    if (config.cacheResults) {
      const cachedResult = this.getFromCache(searchKey, query);
      if (cachedResult) {
        console.log(`📋 Using cached search result for "${query}"`);
        return cachedResult;
      }
    }

    // Cancel any existing search for this key
    this.cancelSearch(searchKey);

    // Create debounced search promise
    return new Promise((resolve, reject) => {
      const searchInfo = {
        query,
        timeout: setTimeout(async () => {
          try {
            config.onLoading(true);
            // Only log searches that take longer than expected
            const logSearch = query.length > 3 || searchKey !== 'medication';
            if (logSearch) {
              console.log(`🔍 Executing search: "${query}" (${searchKey})`);
            }
            
            const results = await searchFn(query);
            
            // Cache results
            if (config.cacheResults) {
              this.saveToCache(searchKey, query, results);
            }
            
            // Update search history
            this.updateSearchHistory(searchKey, query, results.length);
            
            config.onLoading(false);
            resolve(results);
            
          } catch (error) {
            config.onLoading(false);
            config.onError(error);
            reject(error);
          } finally {
            this.activeSearches.delete(searchKey);
          }
        }, config.debounceMs),
        startTime: Date.now()
      };

      this.activeSearches.set(searchKey, searchInfo);
    });
  }

  /**
   * Cancel active search for a specific key
   * @param {string} searchKey 
   */
  cancelSearch(searchKey) {
    const existing = this.activeSearches.get(searchKey);
    if (existing) {
      // Only log if the search was running for a reasonable amount of time
      const runTime = Date.now() - existing.startTime;
      if (runTime > 100) { // Only log if search was active for more than 100ms
        console.log(`🛑 Cancelling search: ${searchKey} (ran for ${runTime}ms)`);
      }
      clearTimeout(existing.timeout);
      this.activeSearches.delete(searchKey);
    }
  }

  /**
   * Cancel all active searches
   */
  cancelAllSearches() {
    console.log(`🛑 Cancelling all searches (${this.activeSearches.size})`);
    for (const [key, searchInfo] of this.activeSearches.entries()) {
      clearTimeout(searchInfo.timeout);
    }
    this.activeSearches.clear();
  }

  /**
   * Get results from cache
   * @private
   */
  getFromCache(searchKey, query) {
    const cacheKey = `${searchKey}:${query.toLowerCase()}`;
    const cached = this.searchCache.get(cacheKey);
    
    if (cached && Date.now() - cached.timestamp < this.cacheExpiryMs) {
      return cached.results;
    }
    
    return null;
  }

  /**
   * Save results to cache
   * @private
   */
  saveToCache(searchKey, query, results) {
    const cacheKey = `${searchKey}:${query.toLowerCase()}`;
    
    this.searchCache.set(cacheKey, {
      results,
      timestamp: Date.now()
    });

    // Prevent cache from growing too large
    if (this.searchCache.size > this.maxCacheSize) {
      const oldestKey = this.searchCache.keys().next().value;
      this.searchCache.delete(oldestKey);
    }
  }

  /**
   * Update search history for analytics
   * @private
   */
  updateSearchHistory(searchKey, query, resultCount) {
    if (!this.searchHistory.has(searchKey)) {
      this.searchHistory.set(searchKey, []);
    }
    
    const history = this.searchHistory.get(searchKey);
    history.push({
      query,
      resultCount,
      timestamp: Date.now()
    });

    // Keep only last 100 searches per key
    if (history.length > 100) {
      history.shift();
    }
  }

  /**
   * Get search statistics
   */
  getSearchStats(searchKey = null) {
    if (searchKey) {
      return {
        activeSearches: this.activeSearches.has(searchKey) ? 1 : 0,
        history: this.searchHistory.get(searchKey) || [],
        cacheEntries: Array.from(this.searchCache.keys())
          .filter(key => key.startsWith(`${searchKey}:`)).length
      };
    }

    return {
      totalActiveSearches: this.activeSearches.size,
      totalCacheEntries: this.searchCache.size,
      totalHistoryEntries: Array.from(this.searchHistory.values())
        .reduce((sum, history) => sum + history.length, 0)
    };
  }

  /**
   * Clear cache for a specific search key or all
   */
  clearCache(searchKey = null) {
    if (searchKey) {
      const keysToDelete = Array.from(this.searchCache.keys())
        .filter(key => key.startsWith(`${searchKey}:`));
      
      keysToDelete.forEach(key => this.searchCache.delete(key));
      console.log(`🗑️ Cleared ${keysToDelete.length} cache entries for ${searchKey}`);
    } else {
      this.searchCache.clear();
      console.log('🗑️ Cleared all search cache');
    }
  }

  /**
   * Cleanup old cache entries
   */
  cleanup() {
    const now = Date.now();
    let deletedCount = 0;

    for (const [key, cached] of this.searchCache.entries()) {
      if (now - cached.timestamp > this.cacheExpiryMs) {
        this.searchCache.delete(key);
        deletedCount++;
      }
    }

    if (deletedCount > 0) {
      console.log(`🧹 SearchDebouncer cleaned up ${deletedCount} expired cache entries`);
    }
  }

  /**
   * Start automatic cleanup
   */
  startAutoCleanup() {
    setInterval(() => {
      this.cleanup();
    }, 60000); // Clean every minute
  }
}

// Create singleton instance
const searchDebouncer = new SearchDebouncer();

// Start automatic cleanup
searchDebouncer.startAutoCleanup();

export default searchDebouncer;

// Export helper functions
export const debouncedSearch = (searchKey, query, searchFn, options) => {
  return searchDebouncer.search(searchKey, query, searchFn, options);
};

export const cancelSearch = (searchKey) => searchDebouncer.cancelSearch(searchKey);
export const cancelAllSearches = () => searchDebouncer.cancelAllSearches();
export const getSearchStats = (searchKey) => searchDebouncer.getSearchStats(searchKey);
export const clearSearchCache = (searchKey) => searchDebouncer.clearCache(searchKey);
