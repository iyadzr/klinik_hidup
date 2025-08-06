import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Add request interceptor to ensure auth token is always sent
axios.interceptors.request.use(
  (config) => {
    // Get token from localStorage on every request
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        const user = JSON.parse(userStr);
        if (user && user.token) {
          // Ensure token is properly formatted
          const token = user.token.trim();
          if (token && token.length > 0) {
            config.headers.Authorization = `Bearer ${token}`;
            console.log('🔐 Request interceptor: Token attached for', config.url, 'Token length:', token.length);
          }
        }
      } catch (e) {
        console.error('❌ Request interceptor: Error parsing user data:', e);
        // Clear corrupted localStorage
        localStorage.removeItem('user');
      }
    }
    
    // Fallback: if no Authorization header set and axios defaults exist, use them
    if (!config.headers.Authorization && axios.defaults.headers.common['Authorization']) {
      config.headers.Authorization = axios.defaults.headers.common['Authorization'];
      console.log('🔐 Request interceptor: Using axios default auth header for', config.url);
    }
    
    // Final debug: Show what authorization header we're sending
    if (config.headers.Authorization) {
      console.log('🔐 INTERCEPTOR: Auth header attached for', config.url, ':', config.headers.Authorization.substring(0, 50) + '...');
      // Store for debugging - this will persist even after logout
      window._lastAuthHeader = config.headers.Authorization.substring(0, 50) + '...';
      window._lastAuthUrl = config.url;
    } else {
      console.log('❌ INTERCEPTOR: NO AUTH HEADER for', config.url);
      window._noAuthHeaderUrl = config.url;
      window._noAuthHeaderTime = new Date().toISOString();
    }
    
    // Debug: Force log ALL request details
    console.log('🔍 INTERCEPTOR: Processing request:', {
      url: config.url,
      method: config.method,
      hasAuth: !!config.headers.Authorization,
      headers: Object.keys(config.headers)
    });
    
    return config;
  },
  (error) => {
    console.error('❌ Request interceptor error:', error);
    return Promise.reject(error);
  }
);

// Add response interceptor to handle authentication errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    console.log('Axios error interceptor:', error.response?.status, error.response?.data);
    
    // Handle token expiration and authentication errors
    if (error.response?.status === 401) {
      console.log('🔐 Authentication error detected - token may be expired');
      
      const errorMessage = error.response?.data?.message;
      const isTokenExpired = errorMessage && (
        errorMessage.includes('expired') || 
        errorMessage.includes('invalid') || 
        errorMessage.includes('JWT')
      );
      
      // Check if we're on a page that should trigger session expiration
      const currentPath = window.location.pathname;
      const isAuthPage = ['/login', '/register'].includes(currentPath);
      const isDashboardPage = currentPath === '/dashboard';
      
      // Don't show session expiration for auth pages or if we're already logged out
      if (isAuthPage) {
        console.log('🔐 On auth page, skipping session expiration handling');
        return Promise.reject(error);
      }
      
      // For dashboard API calls, be more lenient and don't immediately logout
      if (isDashboardPage && !isTokenExpired) {
        console.log('🔐 Dashboard API call failed, but not treating as session expiration');
        return Promise.reject(error);
      }
      
      // Add grace period for authentication establishment (first 10 seconds after page load)
      const pageLoadTime = window._pageLoadTime || Date.now();
      const timeSincePageLoad = Date.now() - pageLoadTime;
      const gracePeriod = 10000; // 10 seconds
      
      if (timeSincePageLoad < gracePeriod && !isTokenExpired) {
        console.log('🔐 Within grace period, not treating as session expiration');
        return Promise.reject(error);
      }
      
      // Only show session expiration for actual JWT token issues
      if (isTokenExpired && errorMessage && errorMessage.includes('Invalid JWT Token')) {
        console.log('🕒 JWT Token expired - performing automatic logout');
        
        // Show user-friendly message about session expiration
        if (window.Vue && window.Vue.config && window.Vue.config.globalProperties.$toast) {
          window.Vue.config.globalProperties.$toast.warning(
            'Your session has expired. Please log in again.', 
            { timeout: 4000 }
          );
        } else {
          // Fallback to console and alert if toast is not available
          console.warn('Session expired - redirecting to login');
          if (window.location.pathname !== '/login') {
            alert('Your session has expired. You will be redirected to the login page.');
          }
        }
        
        // Clear ALL authentication data (complete logout)
        localStorage.clear();
        sessionStorage.clear();
        delete axios.defaults.headers.common['Authorization'];
        
        // Clear all cookies for security
        const cookies = document.cookie.split(";");
        for (let cookie of cookies) {
          const eqPos = cookie.indexOf("=");
          const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
          document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
          document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain=${window.location.hostname}`;
        }
        
        // Only redirect if not already on login page
        if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
          console.log('🔄 Redirecting to login page...');
          
          // Use Vue Router if available, otherwise fallback to direct navigation
          if (window.Vue && window.Vue.router) {
            window.Vue.router.push('/login').catch(() => {
              window.location.href = '/login';
            });
          } else {
            // Small delay to allow any toast messages to show
            setTimeout(() => {
              window.location.href = '/login';
            }, 1000);
          }
        }
      } else {
        console.log('🚫 Authentication failed - unauthorized access (not session expiration)');
      }
    }
    
    // Handle other HTTP errors gracefully
    if (error.response?.status >= 500) {
      console.error('🔥 Server error:', error.response.status, error.response.data);
      if (window.Vue && window.Vue.config && window.Vue.config.globalProperties.$toast) {
        window.Vue.config.globalProperties.$toast.error(
          'Server error. Please try again later.',
          { timeout: 5000 }
        );
      }
    }
    
    return Promise.reject(error);
  }
);

// Set page load time for grace period calculation
window._pageLoadTime = Date.now();

// Debug: Override XMLHttpRequest to catch ALL HTTP requests
const originalXHROpen = XMLHttpRequest.prototype.open;
const originalXHRSend = XMLHttpRequest.prototype.send;

XMLHttpRequest.prototype.open = function(method, url, ...args) {
  this._requestUrl = url;
  this._requestMethod = method;
  console.log('🌐 XHR OPEN:', method, url);
  return originalXHROpen.apply(this, [method, url, ...args]);
};

XMLHttpRequest.prototype.send = function(data) {
  console.log('🌐 XHR SEND:', this._requestMethod, this._requestUrl, 'Headers:', Object.fromEntries([...Array(this._requestHeaders?.length || 0)].map((_, i) => [this.getRequestHeader?.(i) || '', ''])));
  
  // Log authorization header specifically
  const authHeader = this.getRequestHeader ? this.getRequestHeader('Authorization') : 'NOT_ACCESSIBLE';
  console.log('🔐 XHR AUTH HEADER:', authHeader);
  
  return originalXHRSend.apply(this, [data]);
};

console.log('🔧 Axios configured with authentication interceptors + XHR debugging'); 