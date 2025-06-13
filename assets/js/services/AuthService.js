import axios from 'axios';

class AuthService {
  async login(email, password) {
    const response = await axios.post('/api/login', { email, password });
    if (response.data.token) {
      localStorage.setItem('user', JSON.stringify(response.data));
      this.setAuthHeader(response.data.token);
    }
    return response.data;
  }

  logout() {
    localStorage.removeItem('user');
    delete axios.defaults.headers.common['Authorization'];
  }

  getCurrentUser() {
    return JSON.parse(localStorage.getItem('user'));
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
    return user && user.roles && user.roles.includes(role);
  }

  isSuperAdmin() {
    return this.hasRole('ROLE_SUPER_ADMIN');
  }
}

export default new AuthService(); 