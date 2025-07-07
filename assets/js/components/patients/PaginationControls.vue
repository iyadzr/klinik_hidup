<template>
  <div class="d-flex justify-content-between align-items-center mt-4" v-if="totalPages > 1 || totalRecords > 0">
    <div class="d-flex align-items-center">
      <span class="text-muted me-3">
        <i class="fas fa-users me-1"></i>
        Showing {{ startRecord }} to {{ endRecord }} of {{ totalRecords }} {{ itemName }}
      </span>
      <div class="d-flex align-items-center">
        <label class="form-label me-2 mb-0 text-muted">Rows per page:</label>
        <select v-model="selectedPerPage" @change="onPerPageChange" class="form-select form-select-sm" style="width: 80px;">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
    <nav v-if="totalPages > 1">
      <ul class="pagination mb-0">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">
            <i class="fas fa-chevron-left me-1"></i>Previous
          </a>
        </li>
        <li class="page-item" v-for="page in pages" :key="page" :class="{ active: currentPage === page }">
          <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
        </li>
        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">
            Next<i class="fas fa-chevron-right ms-1"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'PaginationControls',
  props: {
    currentPage: {
      type: Number,
      default: 1
    },
    perPage: {
      type: Number,
      default: 25
    },
    totalRecords: {
      type: Number,
      default: 0
    },
    totalPages: {
      type: Number,
      default: 1
    },
    itemName: {
      type: String,
      default: 'items'
    }
  },
  emits: ['page-change', 'per-page-change'],
  data() {
    return {
      selectedPerPage: this.perPage
    };
  },
  computed: {
    startRecord() {
      return (this.currentPage - 1) * this.perPage + 1;
    },
    endRecord() {
      return Math.min(this.currentPage * this.perPage, this.totalRecords);
    },
    pages() {
      const pages = [];
      const maxPagesToShow = 5;
      let startPage = Math.max(1, this.currentPage - Math.floor(maxPagesToShow / 2));
      let endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);
      
      // Adjust start page if we're near the end
      if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
      }
      
      for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  watch: {
    perPage(newVal) {
      this.selectedPerPage = newVal;
    }
  },
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.$emit('page-change', page);
      }
    },
    onPerPageChange() {
      this.$emit('per-page-change', parseInt(this.selectedPerPage));
    }
  }
};
</script>

<style scoped>
.pagination .page-link {
  color: #6c757d;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.pagination .page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  cursor: default;
}

.form-select-sm {
  font-size: 0.875rem;
}
</style> 