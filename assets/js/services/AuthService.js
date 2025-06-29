import axios from 'axios';

class AuthService {
  constructor() {
    // Set auth header on initialization if user is logged in
    const user = this.getCurrentUser();
    if (user && user.token) {
      this.setAuthHeader(user.token);
    }
    
    // Add axios interceptor to ensure token is always sent
    axios.interceptors.request.use(
      (config) => {
        const currentUser = this.getCurrentUser();
        if (currentUser && currentUser.token) {
          config.headers.Authorization = `Bearer ${currentUser.token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );
  }

  async login(email, password) {
    const response = await axios.post('/api/login', { email, password });
    
    if (response.data) {
      // Store both user and token data
      const userData = {
        token: response.data.token,
        user: response.data.user || response.data,
        ...response.data.user
      };
      
      localStorage.setItem('user', JSON.stringify(userData));
      this.setAuthHeader(response.data.token);
      
      return userData;
    }
    return response.data;
  }

  logout() {
    // Clear ALL localStorage data
    localStorage.clear();
    
    // Clear ALL sessionStorage data
    sessionStorage.clear();
    
    // Clear axios authorization header
    delete axios.defaults.headers.common['Authorization'];
    
    // Clear all cookies
    this.clearAllCookies();
    
    console.log('Logout: All storage and cookies cleared');
  }

  clearAllCookies() {
    // Get all cookies and clear them
    const cookies = document.cookie.split(";");
    
    for (let cookie of cookies) {
      const eqPos = cookie.indexOf("=");
      const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
      
      // Clear cookie for current domain
      document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
      
      // Clear cookie for current domain with dot prefix
      document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain=${window.location.hostname}`;
      
      // Clear cookie for parent domain (if subdomain)
      const domain = window.location.hostname;
      const parts = domain.split('.');
      if (parts.length > 1) {
        const parentDomain = '.' + parts.slice(-2).join('.');
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain=${parentDomain}`;
      }
    }
  }

  getCurrentUser() {
    try {
      const userStr = localStorage.getItem('user');
      return userStr ? JSON.parse(userStr) : null;
    } catch (e) {
      console.error('Error parsing user data:', e);
      this.logout(); // Clear corrupted data
      return null;
    }
  }

  setAuthHeader(token) {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    } else {
      delete axios.defaults.headers.common['Authorization'];
    }
  }

  isAuthenticated() {
    const user = this.getCurrentUser();
    return !!user && !!user.token;
  }

  hasRole(role) {
    const user = this.getCurrentUser();
    return user && user.roles && Array.isArray(user.roles) && user.roles.includes(role);
  }

  isSuperAdmin() {
    return this.hasRole('ROLE_SUPER_ADMIN');
  }

  async getDoctorId() {
    const user = this.getCurrentUser();
    if (!user || !this.hasRole('ROLE_DOCTOR')) {
      return null;
    }

    try {
      // Cache the doctor ID to avoid repeated API calls
      if (user.doctorId) {
        return user.doctorId;
      }

      const response = await axios.get('/api/doctors');
      const doctors = response.data;
      const doctorRecord = doctors.find(doctor => doctor.userId === user.id);
      
      if (doctorRecord) {
        // Cache the doctor ID in the user object
        user.doctorId = doctorRecord.id;
        localStorage.setItem('user', JSON.stringify(user));
        return doctorRecord.id;
      }
    } catch (error) {
      console.error('Error fetching doctor ID:', error);
    }
    
    return null;
  }
}

export default new AuthService(); 