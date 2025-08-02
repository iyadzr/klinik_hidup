import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../auth/Login.vue';

// Mock AuthService
jest.mock('../../services/AuthService', () => ({
  login: jest.fn(),
  getCurrentUser: jest.fn()
}));

// Create a mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } },
    { path: '/register', component: { template: '<div>Register</div>' } }
  ]
});

describe('Login.vue', () => {
  it('renders login form', () => {
    const wrapper = mount(Login, {
      global: {
        plugins: [router]
      }
    });
    expect(wrapper.text()).toMatch('Please sign in to continue');
    expect(wrapper.find('input[type="text"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
  });

  it('accepts user input', async () => {
    const wrapper = mount(Login, {
      global: {
        plugins: [router]
      }
    });
    const usernameInput = wrapper.find('input[type="text"]');
    const passwordInput = wrapper.find('input[type="password"]');
    await usernameInput.setValue('superadmin');
    await passwordInput.setValue('password123');
    expect(usernameInput.element.value).toBe('superadmin');
    expect(passwordInput.element.value).toBe('password123');
  });
});
