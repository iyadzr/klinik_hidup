<template>
  <div class="user-profile-menu dropdown" v-if="user">
    <button
      class="btn btn-light d-flex align-items-center dropdown-toggle"
      type="button"
      id="userMenuButton"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <span class="avatar me-2">{{ initials }}</span>
      <span class="user-name">{{ user.name }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
      <li>
        <a class="dropdown-item" href="#" @click.prevent>Profile</a>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li>
        <a class="dropdown-item text-danger" href="#" @click.prevent="logout">Logout</a>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'UserProfileMenu',
  data() {
    return {
      user: null
    };
  },
  computed: {
    initials() {
      if (!this.user || !this.user.name) return '';
      return this.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
    }
  },
  mounted() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        this.user = JSON.parse(userStr);
      } catch (e) {
        this.user = null;
      }
    }
  },
  methods: {
    logout() {
      localStorage.removeItem('user');
      window.location.reload();
    }
  }
};
</script>

<style scoped>
.user-profile-menu {
  position: relative;
  display: flex;
  align-items: center;
}
.avatar {
  width: 32px;
  height: 32px;
  background: #007bff;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.1rem;
}
.user-name {
  font-weight: 500;
  font-size: 1rem;
}
.dropdown-toggle::after {
  margin-left: 0.5em;
}
</style> 