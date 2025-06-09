import { createApp } from 'vue';
import App from './App.vue';
import enterSubmitDirective from './enterSubmitDirective';

const app = createApp(App);

// Register the v-enter-submit directive globally
app.directive('enter-submit', enterSubmitDirective);

app.mount('#app');
