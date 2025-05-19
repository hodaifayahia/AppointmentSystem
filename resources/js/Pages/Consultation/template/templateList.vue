<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import TemplateModel from '../template/templateModel.vue';
import templateListitem from '../template/templateListitem.vue';
// import router 
import { useRouter } from 'vue-router';
const router = useRouter();

const swal = useSweetAlert();
const toaster = useToastr();

// State
const templates = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedTemplate = ref({});
const isEditmode = ref(false);
const searchQuery = ref('');

// Computed
const filteredTemplates = computed(() => {
  if (!searchQuery.value.trim()) return templates.value;
  const query = searchQuery.value.toLowerCase();
  return templates.value.filter(t =>
    t.name.toLowerCase().includes(query) ||
    (t.description && t.description.toLowerCase().includes(query))
  );
});

const hasTemplates = computed(() => templates.value.length > 0);

// API
const getTemplates = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/templates');
    templates.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load templates';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const deleteTemplate = async (id, name) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: `Delete template "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/templates/${id}`);
      await getTemplates();
      toaster.success('Template deleted successfully');
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete template');
    }
  }
};

// UI Events
const openModal = (template = null, edit = false) => {
  selectedTemplate.value = template ? { ...template } : {};
  isModalOpen.value = true;
  isEditmode.value = edit;
};


// Navigate to create Template page
const goToAddTemplatePage = () => {
  router.push({
    name: 'admin.consultation.template.add',
  });
};
const closeModal = () => {
  isModalOpen.value = false;
  selectedTemplate.value = {};
  isEditmode.value = false;
};

// Lifecycle
onMounted(() => {
  getTemplates();
});
</script>

<template>
  <div class="template-page">
    <div class="card">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="mb-0">Templates</h3>
          <div class="d-flex gap-3 align-items-center">
            <div class="position-relative">
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control" 
                placeholder="Search templates..." 
              />
              <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
            </div>
            <button 
              class="btn btn-primary d-flex align-items-center gap-2" 
              @click="goToAddTemplatePage()"
            >
              <i class="fas fa-plus"></i>
              <span>Add Template</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <p class="mb-0">Loading templates...</p>
        </div>

        <div v-if="error" class="alert alert-danger m-3">{{ error }}</div>

        <div v-if="!loading && !hasTemplates" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-file-alt fa-3x text-muted"></i>
          </div>
          <p class="mb-3">No templates found. Create your first template now!</p>
          <button class="btn btn-primary" @click="openModal()">
            <i class="fas fa-plus me-2"></i>Add Template
          </button>
        </div>

        <div v-if="!loading && filteredTemplates.length === 0 && searchQuery.trim()" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-search fa-3x text-muted"></i>
          </div>
          <p class="mb-0">No templates match your search.</p>
        </div>

        <div v-if="filteredTemplates.length" class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">#</th>
                <th>Name</th>
                <th>Description</th>
                <th>Doctor</th>
                <th>MIME Type</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <templateListitem
                v-for="(template, index) in filteredTemplates"
                :key="template.id"
                :template="template"
                :index="index"
                @edit="(t) => openModal(t, true)"
                @delete="deleteTemplate"
              />
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- <template-model
      :show-modal="isModalOpen"
      :template-data="selectedTemplate"
      :is-edit="isEditmode"
      @close="closeModal"
      @refresh="getTemplates"
    /> -->
  </div>
</template>

<style scoped>
.template-page {
  padding: 1rem;
}

.card {
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  overflow: hidden;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

/* Add responsive styling */
@media (max-width: 768px) {
  .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
  
  .d-flex.gap-3 {
    width: 100%;
  }
  
  input.form-control {
    width: 100%;
  }
}
</style>