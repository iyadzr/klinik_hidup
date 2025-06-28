import { mount } from '@vue/test-utils';
import UserProfile from '../UserProfile.vue';

describe('UserProfile.vue', () => {
  it('renders profile form', () => {
    const wrapper = mount(UserProfile, {
      global: {
        mocks: {
          $router: { push: jest.fn() }
        }
      },
      data() {
        return {
          user: { name: 'Test User', email: 'test@clinic.com', username: 'testuser' },
          form: { name: 'Test User', email: 'test@clinic.com', username: 'testuser' }
        };
      }
    });
    expect(wrapper.text()).toMatch('User Profile');
    expect(wrapper.find('input[type="text"]').element.value).toBe('Test User');
    expect(wrapper.find('input[type="email"]').element.value).toBe('test@clinic.com');
  });
});
