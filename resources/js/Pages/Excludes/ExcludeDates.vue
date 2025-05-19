<script setup>
import { ref, computed, onMounted } from 'vue';
import DoctorsListExcluded from '../../Components/exclude/DoctorsListExcluded.vue';
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

// Toast notifications
const toast = useToastr();
// const sortedExcludedDates = computed(() => {
//   return excludedDates.value
//     .slice()
//     .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
// });

const editData = ref(null);
// const openEditModal = (date) => {
//     // Reset editData first to ensure a clean state
//     editData.value = null;

//     // Set the correct modal mode based on whether there's an end date
//     modalMode.value = date.end_date ? 'range' : 'single';

//     // Now set the edit data
//     editData.value = date;

//     // Open the modal
//     showModal.value = true;
// };


// Modal handlers
const openSingleDateModal = () => {
  modalMode.value = 'single';
  showModal.value = true;
};

const openDateRangeModal = () => {
  modalMode.value = 'range';
  showModal.value = true;
};

// Fetch doctors
const getDoctors = async () => {
  try {
    const response = await axios.get('/api/doctors');
    doctors.value = response.data.data; // Immediately update the list

  } catch (error) {
    toast.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};

// Fetch excluded dates from the backend
// const fetchExcludedDates = async () => {
//   try {
//     const response = await axios.get('/api/excluded-dates');
//     excludedDates.value = response.data;    
//   } catch (error) {
//     toast.error('Failed to fetch excluded dates');
//     console.error('Error fetching excluded dates:', error);
//   }
// };



// Close modal
const closeModal = () => {
  showModal.value = false;
  getDoctors();
};
// Close modal
const addExcludedDate = () => {
  showModal.value = false;
  getDoctors();
};

// Fetch data on mount
onMounted(() => {
  getDoctors();
});
</script>
<template>
  <div class="container mt-4">
    <h2 class="text-center mb-4">
      <i class="bi bi-calendar-x me-2 "></i>Manage Excluded Dates
    </h2>

    <!-- Buttons for adding single date or date range -->
    <!-- <div class="d-flex gap-3 mb-4">
      <button class="btn btn-primary mr-2" @click="openSingleDateModal">
        <i class="bi bi-calendar-plus me-2"></i>Add Single Date
      </button>
      <button class="btn btn-primary" @click="openDateRangeModal">
        <i class="bi bi-calendar-range me-2"></i>Add Date Range
      </button>
    </div> -->
    <!-- DataTable component to display excluded dates -->
    <DoctorsListExcluded :doctors="doctors" />


    <!-- DateModal component for adding dates -->

    <DateModal :show="showModal" :doctors="doctors" :mode="modalMode" v-model:new-date="newDate" :editData="editData"
      v-model:date-range="dateRange" @updateDATA="fetchExcludedDates" @close="closeModal" />
  </div>
</template>
<style scoped>
/* Previous styles remain the same */
</style>