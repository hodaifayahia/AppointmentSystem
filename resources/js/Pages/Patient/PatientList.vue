<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import PatientListItem from './PatientListItem.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

const patients = ref([]);
const loading = ref(false);
const error = ref(null);
const toaster = useToastr();

// Changed from pagination to paginationData for clarity
const paginationData = ref({
});

const selectedPatient = ref({});
const searchQuery = ref('');
const isModalOpen = ref(false);

const getPatients = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/patients?page=${page}`);
    
    // Store the entire response data
    if (response.data.data) {
      patients.value = response.data.data;
      paginationData.value = response.data.meta;  // Store the complete pagination object
    } else {
      patients.value = response.data;
    }
    
    console.log('Pagination Data:', paginationData.value);
  } catch (err) {
    console.error('Error fetching patients:', err);
    error.value = err.response?.data?.message || 'Failed to load patients';
  } finally {
    loading.value = false;
  }
};

const openModal = (patient = null) => {
  selectedPatient.value = patient ? { ...patient } : {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const refreshPatients = async () => {
  await getPatients();
};

const deletePatient = async (id) => {
  if (!confirm('Are you sure you want to delete this Patient?')) return;

  try {
    await axios.delete(`/api/Patients/${id}`);
    toaster.success('Patient deleted successfully');
    refreshPatients();
  } catch (error) {
    handleBackendErrors(error);
  }
};

onMounted(() => {
  getPatients();
});
</script>

<template>
  <div class="appointment-page">
    <!-- Header -->
    
    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <PatientListItem
              :patients="patients"
              :loading="loading"
              :error="error"
              @edit="openModal"
              @delete="deletePatient"
            />
            <!-- Pagination Component -->
              <Bootstrap5Pagination
                :data="paginationData"
                @pagination-change-page="getPatients"
              />
            
          </div>
        </div>
      </div>
    </div>

    <!-- Patient Modal -->
    <PatientModel
      :show-modal="isModalOpen"
      :spec-data="selectedPatient"
      @close="closeModal"
      @specUpdate="refreshPatients"
    />
  </div>
</template>

<style scoped>
.appointment-page {
  padding: 20px;
}

/* Add styling for pagination if needed */
:deep(.pagination) {
  margin-bottom: 0;
}

:deep(.page-link) {
  color: #007bff;
  background-color: #fff;
  border: 1px solid #dee2e6;
}

:deep(.page-item.active .page-link) {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}
</style>