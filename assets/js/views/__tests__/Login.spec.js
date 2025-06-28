import { mount } from '@vue/test-utils';
import Login from '../auth/Login.vue';

describe('Login.vue', () => {
  it('renders login form', () => {
    const wrapper = mount(Login);
    expect(wrapper.text()).toMatch('Please sign in to continue');
    expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
  });

  it('accepts user input', async () => {
    const wrapper = mount(Login);
    const emailInput = wrapper.find('input[type="email"]');
    const passwordInput = wrapper.find('input[type="password"]');
    await emailInput.setValue('test@clinic.com');
    await passwordInput.setValue('password123');
    expect(emailInput.element.value).toBe('test@clinic.com');
    expect(passwordInput.element.value).toBe('password123');
  });
});
