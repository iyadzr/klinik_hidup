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

// Add response interceptor to handle 401 errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    console.log('Axios error interceptor:', error.response?.status, error.response?.data);
    
    if (error.response?.status === 401) {
      console.log('401 Unauthorized - clearing auth and redirecting to login');
      
      // Clear ALL storage and cookies (complete logout)
      localStorage.clear();
      sessionStorage.clear();
      delete axios.defaults.headers.common['Authorization'];
      
      // Clear all cookies
      const cookies = document.cookie.split(";");
      for (let cookie of cookies) {
        const eqPos = cookie.indexOf("=");
        const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
      }
      
      // Only redirect if not already on login page
      if (window.location.pathname !== '/login') {
        console.log('Redirecting to login page');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

console.log('🔧 Axios configured with authentication interceptors'); 