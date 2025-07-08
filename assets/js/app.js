import './bootstrap.js';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import enterSubmitDirective from './enterSubmitDirective.js';

const app = createApp(App);

// Use Pinia for state management
app.use(createPinia());
app.use(router);

// Add custom directive for enter to submit
app.directive('enter-submit', enterSubmitDirective);

// Global error handler
app.config.errorHandler = (err, vm, info) => {
    console.error('Vue error:', err, info);
};

app.mount('#app');

// Simple global click protection to prevent system overwhelm
let lastClickTime = 0;
const CLICK_THROTTLE_MS = 300; // 300ms between clicks

document.addEventListener('click', function(e) {
    const now = Date.now();
    
    // Only protect buttons and form elements that could trigger API calls
    if (e.target.matches('button, .btn, input[type="submit"], a[href*="/"], .clickable')) {
        if (now - lastClickTime < CLICK_THROTTLE_MS) {
            console.log('Click throttled - too fast');
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        lastClickTime = now;
    }
}, true); // Use capture phase to catch early

// Simple debugging helpers
window.clinicDebug = {
    clearConsole: () => console.clear(),
    reloadPage: () => window.location.reload(),
    getCurrentRoute: () => router.currentRoute.value,
    
    // Emergency reset function
    emergencyReset: () => {
        console.log('🚨 Emergency reset initiated');
        
        // Clear all timeouts and intervals
        let id = window.setTimeout(() => {}, 0);
        while (id--) {
            window.clearTimeout(id);
            window.clearInterval(id);
        }
        
        // Reset click protection
        lastClickTime = 0;
        
        console.log('✅ Emergency reset completed');
    }
};

console.log('Clinic Management System initialized with simple, safe architecture'); 