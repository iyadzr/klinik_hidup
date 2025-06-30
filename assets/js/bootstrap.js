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
          config.headers.Authorization = `Bearer ${user.token}`;
        }
      } catch (e) {
        console.error('Error parsing user data:', e);
      }
    }
    return config;
  },
  (error) => {
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
      
      if (isTokenExpired) {
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
      } else {
        console.log('🚫 Authentication failed - unauthorized access');
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

console.log('🔧 Axios configured with authentication interceptors'); 