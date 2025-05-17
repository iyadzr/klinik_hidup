<template>
  <div class="clinic-assistant-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Clinic Assistants</h2>
      <button class="btn btn-primary" @click="openAddModal">
        <i class="fas fa-plus me-2"></i>
        Add Assistant
      </button>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Username</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assistant in assistants" :key="assistant.id">
                <td>{{ assistant.name }}</td>
                <td>{{ assistant.email }}</td>
                <td>{{ assistant.phone }}</td>
                <td>{{ assistant.username }}</td>
                <td>
                  <button class="btn btn-sm btn-info me-2" @click="editAssistant(assistant)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" @click="deleteAssistant(assistant)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="assistants.length === 0">
                <td colspan="5" class="text-center py-4">
                  No clinic assistants found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="assistantModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Edit' : 'Add' }} Clinic Assistant</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveAssistant">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" v-model="form.name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" v-model="form.email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" v-model="form.phone" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" v-model="form.username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" v-model="form.password" class="form-control" 
                       :required="!isEditing">
                <small class="text-muted" v-if="isEditing">
                  Leave blank to keep current password
                </small>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditing ? 'Update' : 'Add' }} Assistant
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this clinic assistant?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';

export default {
  name: 'ClinicAssistantList',
  data() {
    return {
      assistants: [],
      form: {
        name: '',
        email: '',
        phone: '',
        username: '',
        password: ''
      },
      isEditing: false,
      selectedAssistant: null,
      assistantModal: null,
      deleteModal: null
    };
  },
  async created() {
    await this.loadAssistants();
  },
  mounted() {
    this.assistantModal = new Modal(document.getElementById('assistantModal'));
    this.deleteModal = new Modal(document.getElementById('deleteModal'));
  },
  methods: {
    async loadAssistants() {
      try {
        const response = await axios.get('/api/clinic-assistants');
        this.assistants = response.data;
      } catch (error) {
        console.error('Error loading clinic assistants:', error);
      }
    },
    openAddModal() {
      this.isEditing = false;
      this.form = {
        name: '',
        email: '',
        phone: '',
        username: '',
        password: ''
      };
      this.assistantModal.show();
    },
    editAssistant(assistant) {
      this.isEditing = true;
      this.selectedAssistant = assistant;
      this.form = {
        name: assistant.name,
        email: assistant.email,
        phone: assistant.phone,
        username: assistant.username,
        password: ''
      };
      this.assistantModal.show();
    },
    async saveAssistant() {
      try {
        if (this.isEditing) {
          await axios.put(`/api/clinic-assistants/${this.selectedAssistant.id}`, this.form);
        } else {
          await axios.post('/api/clinic-assistants', this.form);
        }
        await this.loadAssistants();
        this.assistantModal.hide();
      } catch (error) {
        console.error('Error saving clinic assistant:', error);
        alert('Error saving clinic assistant. Please try again.');
      }
    },
    deleteAssistant(assistant) {
      this.selectedAssistant = assistant;
      this.deleteModal.show();
    },
    async confirmDelete() {
      try {
        await axios.delete(`/api/clinic-assistants/${this.selectedAssistant.id}`);
        await this.loadAssistants();
        this.deleteModal.hide();
      } catch (error) {
        console.error('Error deleting clinic assistant:', error);
        alert('Error deleting clinic assistant. Please try again.');
      }
    }
  }
};
</script>

<style scoped>
.clinic-assistant-list {
  padding: 20px;
}

.table th {
  background-color: #f8f9fa;
}
</style>
