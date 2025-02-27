<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import PatientListItem from './PatientListItem.vue';

const patients = ref([]);
const loading = ref(false);
const error = ref(null);
const role= ref(null);
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
const initializeRole = async () => {
  try {
    const user = await axios.get('/api/role');
    role.value = user.data.role;


    if (user.data.role === 'admin') {
      role.value = user.data.role;
      // console.log('User role:', userRole.value);

    }
  } catch (err) {
    console.error('Error fetching user role:', err);
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

onMounted(() => {
  getPatients();
  initializeRole();
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
              :role="role"
              @edit="openModal"
             
            />
            <!-- Pagination Component -->
           
             
            
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