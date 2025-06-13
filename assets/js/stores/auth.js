import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    userRoles: [],
    token: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (role) => state.userRoles.includes(role),
    isSuperAdmin: (state) => state.userRoles.includes('ROLE_SUPER_ADMIN')
  },

  actions: {
    setUser(user) {
      this.user = user;
      this.userRoles = user?.roles || [];
    },

    setToken(token) {
      this.token = token;
    },

    clearAuth() {
      this.user = null;
      this.userRoles = [];
      this.token = null;
    }
  }
}); 