<template>
  <div class="row mb-3">
    <div class="col-md-6">
      <div class="input-group">
        <input 
          type="text" 
          class="form-control" 
          placeholder="Search by name, NRIC, or phone (min 2 characters)" 
          v-model="searchQuery" 
          @keyup.enter="search"
          :disabled="loading"
        >
        <button class="btn btn-outline-primary" @click="search" :disabled="loading || (searchQuery && searchQuery.trim().length < 2)">
          <i v-if="loading" class="fas fa-spinner fa-spin me-1"></i>
          <i v-else class="fas fa-search me-1"></i>
          Search
        </button>
        <button class="btn btn-outline-secondary" @click="clear" v-if="searchQuery" :disabled="loading">
          <i class="fas fa-times me-1"></i>
          Clear
        </button>
      </div>
      <small v-if="searchQuery && !loading" class="text-muted mt-1 d-block">
        <i class="fas fa-filter me-1"></i>
        Showing results for "{{ searchQuery }}" ({{ resultCount }} found)
      </small>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PatientSearchBar',
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    resultCount: {
      type: Number,
      default: 0
    },
    modelValue: {
      type: String,
      default: ''
    }
  },
  emits: ['update:modelValue', 'search', 'clear'],
  computed: {
    searchQuery: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value);
      }
    }
  },
  methods: {
    search() {
      this.$emit('search', this.searchQuery);
    },
    clear() {
      this.searchQuery = '';
      this.$emit('clear');
    }
  }
};
</script>

<style scoped>
.input-group .btn {
  border-left: 0;
}

.input-group .btn:first-of-type {
  border-left: 1px solid #ced4da;
}
</style> 