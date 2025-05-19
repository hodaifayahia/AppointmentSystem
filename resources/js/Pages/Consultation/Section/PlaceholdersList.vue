<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import PlaceholderModel from "../Section/placeholderModel.vue";
import PlaceholderItem from "../Section/PlaceholderItem.vue";
import { useSweetAlert } from '../../../Components/useSweetAlert';

const swal = useSweetAlert();
const toaster = useToastr();

// State management
const placeholders = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedPlaceholder = ref({});
const excelFiles = ref([]);
const isUploading = ref(false);
const isEditmode = ref(false);
const searchQuery = ref('');

// Computed properties
const filteredPlaceholders = computed(() => {
  if (!searchQuery.value.trim()) return placeholders.value;

  const query = searchQuery.value.toLowerCase();
  return placeholders.value.filter(placeholder =>
    placeholder.name.toLowerCase().includes(query) ||
    (placeholder.description && placeholder.description.toLowerCase().includes(query))
  );
});

const hasPlaceholders = computed(() => placeholders.value.length > 0);
const canUpload = computed(() => excelFiles.value.length > 0 && !isUploading.value);

// API methods
const getPlaceholders = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get('/api/placeholders');
    placeholders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load placeholders';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const deletePlaceholder = async (id, name) => {
  try {
    const result = await swal.fire({
      title: 'Are you sure?',
      text: `Delete placeholder "${name}"? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    });

    if (result.isConfirmed) {
      loading.value = true;
      await axios.delete(`/api/placeholders/${id}`);
      await getPlaceholders();
      toaster.success('Placeholder deleted successfully');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to delete placeholder');
  } finally {
    loading.value = false;
  }
};

// UI handlers
const openModal = (placeholder = null, isEdit) => {

  selectedPlaceholder.value = placeholder ? { ...placeholder } : {};
  isModalOpen.value = true;
  isEditmode.value = isEdit;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedPlaceholder.value = {};
  isEditmode.value = false;
};

const handleFileUpload = (event) => {
  excelFiles.value = Array.from(event.target.files);
};

const clearFileSelection = () => {
  excelFiles.value = [];
  // Reset the file input element
  const fileInput = document.getElementById('file-upload');
  if (fileInput) fileInput.value = '';
};

const uploadFiles = async () => {
  if (!excelFiles.value.length) return;

  const formData = new FormData();
  excelFiles.value.forEach(file => {
    formData.append('files[]', file);
  });

  try {
    isUploading.value = true;
    const response = await axios.post('/api/import/placeholders', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    toaster.success(response.data.message || 'Files uploaded successfully');
    await getPlaceholders();
    clearFileSelection();
  } catch (error) {
    toaster.error(error.response?.data?.message || 'File upload failed');
  } finally {
    isUploading.value = false;
  }
};

// Lifecycle hooks
onMounted(() => {
  getPlaceholders();
});
</script>

<template>
  <div class="placeholder-page">
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Sections</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Sections</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <!-- Action Buttons -->
            <div class="card shadow-sm mb-4">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <div class="d-flex align-items-center">
                      <button class="btn btn-primary me-2" @click="openModal">
                        <i class="fas fa-plus-circle me-1"></i> New Section
                      </button>

                      <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search placeholders..."
                          v-model="searchQuery">
                        <button class="btn btn-outline-secondary" type="button" @click="searchQuery = ''"
                          v-if="searchQuery">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="d-flex align-items-center">
                      <input id="file-upload" type="file" multiple @change="handleFileUpload" class="form-control me-2"
                        accept=".xlsx,.xls,.csv" />
                      <button class="btn btn-success" @click="uploadFiles" :disabled="!canUpload">
                        <i class="fas fa-upload me-1"></i>
                        <span v-if="isUploading">Uploading...</span>
                        <span v-else>Upload</span>
                      </button>

                      <button class="btn btn-outline-secondary ms-2" @click="clearFileSelection"
                        v-if="excelFiles.length">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>

                    <div class="mt-2 small text-muted" v-if="excelFiles.length">
                      {{ excelFiles.length }} file(s) selected
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Placeholders Table -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger">
                  <i class="fas fa-exclamation-circle me-2"></i> {{ error }}
                  <button class="btn-close float-end" @click="error = null"></button>
                </div>

                <div v-if="loading" class="text-center py-5">
                  <div class="spinner-border text-primary"></div>
                  <p class="mt-2">Loading placeholders...</p>
                </div>

                <div v-else-if="!hasPlaceholders" class="text-center py-5">
                  <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                  <p class="lead">No placeholders found</p>
                  <button class="btn btn-primary mt-2" @click="openModal">
                    <i class="fas fa-plus-circle me-1"></i> Add Your First Placeholder
                  </button>
                </div>

                <div v-else>
                  <div v-if="filteredPlaceholders.length === 0" class="text-center py-4">
                    <p>No placeholders match your search.</p>
                    <button class="btn btn-sm btn-outline-secondary" @click="searchQuery = ''">
                      Clear Search
                    </button>
                  </div>


                  <table v-else class="table table-hover table-striped">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 30%">Name</th>
                        <th style="width: 45%">Description</th>
                        <th style="width: 45%">Specializations</th>
                        <th style="width: 45%">Doctor</th>
                        <th style="width: 20%">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <placeholder-item v-for="(placeholder, index) in filteredPlaceholders" :key="placeholder.id"
                        :placeholder="placeholder" :index="index" @edit="(item) => openModal(item, true)"
                        @delete="deletePlaceholder" />
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Placeholder Modal -->
    <PlaceholderModel :show-modal="isModalOpen" :placeholder-data="selectedPlaceholder" @close="closeModal"
      @placeholderUpdate="getPlaceholders" />
  </div>
</template>

<style scoped>
.placeholder-page {
  padding-bottom: 2rem;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.card {
  border-radius: 0.5rem;
  overflow: hidden;
}

table {
  margin-bottom: 0;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}
</style>