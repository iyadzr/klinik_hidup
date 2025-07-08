<template>
  <div class="modal fade" :class="{ show: visible }" tabindex="-1" v-if="visible">
    <div class="modal-dialog" :class="modalSizeClass">
      <div class="modal-content">
        <div class="modal-header" :class="headerClass">
          <h5 class="modal-title">
            <i :class="iconClass" class="me-2"></i>
            {{ title }}
          </h5>
          <button type="button" class="btn-close" :class="closeButtonClass" @click="close"></button>
        </div>
        <div class="modal-body">
          <slot></slot>
        </div>
        <div class="modal-footer" v-if="!hideFooter">
          <slot name="footer">
            <button type="button" class="btn btn-secondary" @click="close">Close</button>
          </slot>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-if="visible"></div>
</template>

<script>
export default {
  name: 'SimpleModalWrapper',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Modal'
    },
    size: {
      type: String,
      default: 'lg', // sm, md, lg, xl
      validator: value => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    headerVariant: {
      type: String,
      default: 'primary', // primary, secondary, success, warning, danger, info
      validator: value => ['primary', 'secondary', 'success', 'warning', 'danger', 'info'].includes(value)
    },
    icon: {
      type: String,
      default: 'fas fa-info-circle'
    },
    hideFooter: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close'],
  computed: {
    modalSizeClass() {
      return this.size !== 'md' ? `modal-${this.size}` : '';
    },
    headerClass() {
      return `bg-${this.headerVariant} text-white`;
    },
    closeButtonClass() {
      return this.headerVariant === 'light' ? '' : 'btn-close-white';
    },
    iconClass() {
      return this.icon;
    }
  },
  methods: {
    close() {
      this.$emit('close');
    }
  }
};
</script>

<style scoped>
.modal.show {
  display: block;
  z-index: 1200 !important; /* Higher than sticky patient header (1100) */
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1190 !important; /* Just below modal */
  width: 100vw;
  height: 100vh;
  background-color: #000;
  opacity: 0.5;
}
</style> 