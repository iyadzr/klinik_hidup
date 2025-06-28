import axios from 'axios';

class AuthService {
  constructor() {
    // Set auth header on initialization if user is logged in
    const user = this.getCurrentUser();
    if (user && user.token) {
      this.setAuthHeader(user.token);
    }
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
    localStorage.removeItem('user');
    delete axios.defaults.headers.common['Authorization'];
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