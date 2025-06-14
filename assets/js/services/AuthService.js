import axios from 'axios';

class AuthService {
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
}

export default new AuthService(); 