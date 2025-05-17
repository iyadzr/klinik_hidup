/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/app.scss';

import { createApp } from 'vue';
import App from './js/App.vue';
import router from './js/router';
import axios from 'axios';

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

// Configure axios defaults
axios.defaults.baseURL = window.location.origin;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
app.mount('#app');
