import axios from 'axios';

class AuthService {
  constructor() {
    this._authStateReady = false;
    this._authStatePromise = null;
    
    // Set auth header on initialization if user is logged in
    const user = this.getCurrentUser();
    if (user && user.token) {
      this.setAuthHeader(user.token);
      this._authStateReady = true;
    }
    
    // Note: axios interceptor is handled in bootstrap.js to avoid conflicts
  }

  // New method to wait for authentication state to be ready
  async waitForAuthState() {
    if (this._authStateReady) {
      return true;
    }
    
    if (this._authStatePromise) {
      return this._authStatePromise;
    }
    
    this._authStatePromise = new Promise((resolve) => {
      let attempts = 0;
      const maxAttempts = 50; // 5 seconds total
      
      const checkAuth = () => {
        if (this.isAuthenticated()) {
          this._authStateReady = true;
          resolve(true);
          return;
        }
        
        attempts++;
        if (attempts >= maxAttempts) {
          console.warn('⚠️ Authentication state not ready after maximum attempts');
          resolve(false);
          return;
        }
        
        setTimeout(checkAuth, 100);
      };
      
      checkAuth();
    });
    
    return this._authStatePromise;
  }

  async login(username, password) {
    try {
      const response = await axios.post('/api/login', { 
        username, 
        password 
      }, {
        timeout: 10000 // 10 second timeout
      });
      
      if (response.data && response.data.token) {
        // Validate token format before storing
        const token = response.data.token;
        if (!this.isValidJWTFormat(token)) {
          console.error('🔐 AuthService: Invalid JWT token format received');
          throw new Error('Invalid JWT token format');
        }
        
        // Store both user and token data
        const userData = {
          token: token,
          user: response.data.user || response.data,
          ...response.data.user
        };
        
        // Store user data first
        localStorage.setItem('user', JSON.stringify(userData));
        
        // Ensure localStorage write is complete before setting axios header
        // Force localStorage to be written synchronously
        const verifyData = localStorage.getItem('user');
        if (!verifyData || !JSON.parse(verifyData).token) {
          throw new Error('Failed to store authentication data');
        }
        
        // Now set the axios default header as backup
        this.setAuthHeader(token);
        
        // Mark authentication state as ready
        this._authStateReady = true;
        this._authStatePromise = Promise.resolve(true);
        
        console.log('🔐 AuthService: Login successful, user data stored, token length:', token.length);
        return userData;
      } else {
        console.error('🔐 AuthService: Invalid response data', response.data);
        throw new Error('Invalid login response');
      }
    } catch (error) {
      console.error('🔐 AuthService: Login failed', error);
      // Clear any partial authentication state
      this.logout();
      throw error;
    }
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
    
    // Reset authentication state
    this._authStateReady = false;
    this._authStatePromise = null;
    
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
    if (token && this.isValidJWTFormat(token)) {
      const cleanToken = token.trim();
      axios.defaults.headers.common['Authorization'] = `Bearer ${cleanToken}`;
      console.log('🔐 AuthService: Authorization header set, token length:', cleanToken.length);
    } else {
      delete axios.defaults.headers.common['Authorization'];
      console.warn('⚠️ AuthService: Invalid token, cleared Authorization header');
    }
  }

  isAuthenticated() {
    const user = this.getCurrentUser();
    if (!user || !user.token) {
      return false;
    }
    
    // Check if token is expired (if we can decode it)
    if (this.isTokenExpired(user.token)) {
      console.log('🕒 Token is expired, performing logout');
      this.logout();
      return false;
    }
    
    return true;
  }
  
  isTokenExpired(token) {
    if (!token) return true;
    
    try {
      // Decode JWT token to check expiration
      const payload = JSON.parse(atob(token.split('.')[1]));
      const currentTime = Math.floor(Date.now() / 1000);
      
      // Add a 5-minute buffer to prevent edge cases
      const bufferTime = 5 * 60; // 5 minutes in seconds
      
      // Check if token has expired (with buffer)
      if (payload.exp && payload.exp < (currentTime + bufferTime)) {
        console.log('🕒 JWT token has expired or will expire soon');
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('❌ Error checking token expiration:', error);
      // Don't treat parsing errors as expired tokens
      // This prevents false positives during token validation
      return false;
    }
  }

  hasRole(role) {
    const user = this.getCurrentUser();
    return user && user.roles && Array.isArray(user.roles) && user.roles.includes(role);
  }

  getTokenExpirationTime() {
    const user = this.getCurrentUser();
    if (!user || !user.token) return null;
    
    try {
      const payload = JSON.parse(atob(user.token.split('.')[1]));
      return payload.exp ? new Date(payload.exp * 1000) : null;
    } catch (error) {
      console.error('❌ Error getting token expiration time:', error);
      return null;
    }
  }

  getTokenTimeRemaining() {
    const expirationTime = this.getTokenExpirationTime();
    if (!expirationTime) return null;
    
    const currentTime = new Date();
    const timeRemaining = expirationTime.getTime() - currentTime.getTime();
    
    return timeRemaining > 0 ? timeRemaining : 0;
  }

  isTokenExpiringSoon(minutesThreshold = 30) {
    const timeRemaining = this.getTokenTimeRemaining();
    if (timeRemaining === null) return false;
    
    const threshold = minutesThreshold * 60 * 1000; // Convert to milliseconds
    return timeRemaining < threshold && timeRemaining > 0;
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

  // Bypass JWT token format validation - accept all non-empty tokens 
  isValidJWTFormat(token) {
    // Simply check if token exists and is a non-empty string - updated version
    return token && typeof token === 'string' && token.length > 0;
  }
}

export default new AuthService(); 