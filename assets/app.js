/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/app.scss';
import './styles/global-improvements.css';

import { createApp } from 'vue';
import App from './js/App.vue';
import router from './js/router';
import axios from 'axios';
import AuthService from './js/services/AuthService';

// Font Awesome
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
  faTachometerAlt, 
  faUserInjured, 
  faUserMd, 
  faCalendarAlt, 
  faSignOutAlt 
} from '@fortawesome/free-solid-svg-icons';

// Set axios baseURL to current host (dynamic)
// In containerized setup, API calls go through nginx proxy to /api/
axios.defaults.baseURL = window.location.protocol + '//' + window.location.host;

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Initialize AuthService to set up axios headers
console.log('🔐 Initializing AuthService...');
const currentUser = AuthService.getCurrentUser();
if (currentUser && currentUser.token) {
  console.log('✅ User found, setting auth header');
  AuthService.setAuthHeader(currentUser.token);
} else {
  console.log('❌ No authenticated user found');
}

// Add Font Awesome icons
library.add(
  faTachometerAlt, 
  faUserInjured, 
  faUserMd, 
  faCalendarAlt, 
  faSignOutAlt
);

const app = createApp(App);
app.use(router);
app.component('font-awesome-icon', FontAwesomeIcon);

// Add global currency formatting methods
app.config.globalProperties.$formatCurrency = function(amount) {
  const numericAmount = parseFloat(amount || 0);
  return numericAmount.toFixed(2);
};

app.config.globalProperties.$formatRM = function(amount) {
  const formatted = this.$formatCurrency(amount);
  return `RM ${formatted}`;
};

app.mount('#app');

// Expose router for debugging
window.$router = router;
