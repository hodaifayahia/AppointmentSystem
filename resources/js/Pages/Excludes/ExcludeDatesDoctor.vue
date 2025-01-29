<script setup>
import { ref, computed, onMounted } from 'vue';
import DataTable from '../../Components/exclude/DataTable.vue';
import DateModal from '../../Components/exclude/DataModal.vue';
import { useToastr } from '../../Components/toster';
import axios from 'axios';

// Reactive state
const excludedDates = ref([]);
const newDate = ref('');
const dateRange = ref({ from: '', to: '' });
const showModal = ref(false);
const modalMode = ref('single');
const doctors = ref([]);
const doctorId = ref(null);
const specializationId = ref(null);

// Initialize doctorId based on role
const initializeDoctorId = async () => {
  try {
    const user = await axios.get('/api/loginuser');
    
    if (user.data.data.role === 'doctor') {
        doctorId.value = user.data.data.id;
        specializationId.value = user.data.data.specialization_id;
        
    }     

    await fetchExcludedDates(doctorId.value);
  } catch (err) {
    console.error('Error initializing:', err);
    toast.error('Failed to initialize doctor data');
  }
};

// Toast notifications
const toast = useToastr();

// Computed property
const sortedExcludedDates = computed(() => {
  return excludedDates.value.slice().sort((a, b) => new Date(a.date) - new Date(b.date));
});
const editData = ref(null);

const openEditModal = (date) => {
    editData.value = date;
    showModal.value = true;
};

// Modal handlers
const openSingleDateModal = () => {
  modalMode.value = 'single';
  showModal.value = true;
};

const openDateRangeModal = () => {
  modalMode.value = 'range';
  showModal.value = true;
};



// Fetch excluded dates from the backend
const fetchExcludedDates = async (doctorId) => {    
    
  try {
    const response = await axios.get(`/api/excluded-dates/${doctorId}`);
    excludedDates.value = response.data.data;
    console.log(excludedDates.value);
    
  } catch (error) {
    toast.error('Failed to fetch excluded dates');
    console.error('Error fetching excluded dates:', error);
  }
};

// Remove an excluded date
const removeExcludedDate = async (id) => {
  try {
    await axios.delete(`/api/excluded-dates/${id}`, {
      params: { doctorId: doctorId.value }
    });
    excludedDates.value = excludedDates.value.filter((d) => d.id !== id);
    toast.info('Date removed from excluded list.');
  } catch (error) {
    toast.error('Failed to remove excluded date');
    console.error('Error removing excluded date:', error);
  }
};

// Close modal
const closeModal = () => {
  showModal.value = false;
  fetchExcludedDates(doctorId.value);
};

// Fetch data on mount
onMounted(async () => {
  await initializeDoctorId();
});
</script>

<template>
  <div class="container mt-4">
    <h2 class="text-center mb-4">
      <i class="bi bi-calendar-x me-2 "></i>Manage Excluded Dates
    </h2>

    <!-- Buttons for adding single date or date range -->
    <div class="d-flex gap-3 mb-4">
      <button class="btn btn-primary mr-2" @click="openSingleDateModal">
        <i class="bi bi-calendar-plus me-2"></i>Add Single Date
      </button>
      <button class="btn btn-primary" @click="openDateRangeModal">
        <i class="bi bi-calendar-range me-2"></i>Add Date Range
      </button>
    </div>

    <!-- DataTable component to display excluded dates -->
    <DataTable
        
      :dates="sortedExcludedDates"
      @remove="removeExcludedDate"
      @edit="openEditModal"
    />
    
    <!-- DateModal component for adding dates -->
    <DateModal
      :show="showModal"
      :doctors="doctors"
      :mode="modalMode"
      v-model:new-date="newDate"
      :editData="editData"
      :doctorId="doctorId"
      v-model:date-range="dateRange"
      @updateDATA="fetchExcludedDates"
      @close="closeModal"
    />
  </div>
</template>

<style scoped>
/* Previous styles remain the same */
</style>