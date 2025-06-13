import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './bootstrap';
import enterSubmitDirective from './enterSubmitDirective';

const app = createApp(App);
const pinia = createPinia();

// Register the v-enter-submit directive globally
app.directive('enter-submit', enterSubmitDirective);

app.use(pinia);
app.use(router);
app.mount('#app');
