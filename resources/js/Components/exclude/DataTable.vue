<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToastr } from '../../Components/toster';
import axios from 'axios';
import DateModal from '../../Components/exclude/DataModal.vue';
import { useRoute } from 'vue-router';
// Reactive state
const excludedDates = ref([]);
const doctorId = ref(null);
const showModal = ref(false);
const modalMode = ref('single');
const newDate = ref('');
const dateRange = ref({ from: '', to: '' });
const editData = ref(null);
const router = useRoute();
// Toast notifications
doctorId.value = router.params.id;

const toast = useToastr();
const initializeDoctorId = async () => {
  try {
    const response = await axios.get('/api/loginuser');
    const userData = response.data.data;

    if (userData.role === 'doctor') {
      doctorId.value = userData.id;
    } else {
      doctorId.value = router.params.id;
    }

    if (doctorId.value) {
      await fetchExcludedDates();
    }
  } catch (err) {
    console.error('Error initializing:', err);
  }
};

// Fetch excluded dates for the doctor
const fetchExcludedDates = async () => {
  if (!doctorId.value) return;
  
  try {
    const response = await axios.get(`/api/excluded-dates/${doctorId.value}`);
    excludedDates.value = response.data.data;
  } catch (error) {
    toast.error('Failed to fetch excluded dates');
    console.error('Error fetching excluded dates:', error);
  }
};

// Computed property for grouped excluded dates
const groupedExcludedDates = computed(() => {
  const grouped = {};

  excludedDates.value.forEach((date) => {
    const key = date.start_date;

    if (!grouped[key]) {
      grouped[key] = {
        ...date,
        shifts: {
          morning: null,
          afternoon: null
        },
      };
    }

    // Check if `date.shifts` is defined and is an array
    if (Array.isArray(date.shifts)) {
      date.shifts.forEach((shift) => {
        if (shift.shift_period === 'morning') {
          grouped[key].shifts.morning = {
            start_time: shift.start_time,
            end_time: shift.end_time,
            number_of_patients_per_day: shift.number_of_patients_per_day,
          };
        } else if (shift.shift_period === 'afternoon') {
          grouped[key].shifts.afternoon = {
            start_time: shift.start_time,
            end_time: shift.end_time,
            number_of_patients_per_day: shift.number_of_patients_per_day,
          };
        }
      });
    }
  });

  return Object.values(grouped).sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
});

// Modal handlers
const openSingleDateModal = () => {
  modalMode.value = 'single';
  editData.value = null;
  showModal.value = true;
};

const openDateRangeModal = () => {
  modalMode.value = 'range';
  editData.value = null;
  showModal.value = true;
};

const openEditModal = (date) => {
  console.log(date);
  
  modalMode.value = date.end_date ? 'range' : 'single';
  editData.value = date;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editData.value = null;
  fetchExcludedDates();
};

// Remove an excluded date
const removeExcludedDate = async (dateInfo) => {
  // Extract just the date part from the timestamp
  const formattedDate = dateInfo.start_date.split(' ')[0];
    
  try {
    await axios.delete('/api/excluded-dates/delete-by-date', {
      data: {
        date: formattedDate,
      }
    });

    await fetchExcludedDates();
    toast.info('Date removed from excluded list.');
  } catch (error) {
    toast.error('Failed to remove excluded date: ' + (error.response?.data?.message || error.message));
    console.error('Error removing excluded date:', error);
  }
};

// Helper function to format dates
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Computed property to format end date
const formattedEndDate = (endDate) => {
  return endDate === null ? 'N/A' : formatDate(endDate);
};

// Fetch data on component mount
onMounted(() => {
  initializeDoctorId();
  fetchExcludedDates();
});
</script>

<template>
  <div class="container mt-4">
    <h2 class="text-center mb-4">
      <i class="bi bi-calendar-x me-2"></i>Manage Your Excluded Dates
    </h2>
    <div class="d-flex gap-3 mb-4">
      <button class="btn btn-primary mr-2" @click="openSingleDateModal">
        <i class="bi bi-calendar-plus me-2"></i>Add Single Date
      </button>
      <button class="btn btn-primary" @click="openDateRangeModal">
        <i class="bi bi-calendar-range me-2"></i>Add Date Range
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="bg-light">
          <tr>
            <th>#</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Reason</th>
            <th>Applies to All Years</th>
            <th>Shift Period</th>
            <th>Type</th>
            <th>Morning</th>
            <th>Afternoon</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(date, index) in groupedExcludedDates" :key="date.id || index">
            <td>{{ index + 1 }}</td>
            <td>{{ formatDate(date.start_date) }}</td>
            <td>{{ formattedEndDate(date.end_date) }}</td>
            <td>{{ date.reason || 'No reason provided' }}</td>
            <td>{{ date.apply_for_all_years ? 'Yes' : 'No' }}</td>
            <td>
              <span>Morning</span><br>
              <span>Afternoon</span>
            </td>
            <td>{{ date.exclusionType }}</td>
            <td>
              {{ date.shifts.morning ? `${date.shifts.morning.start_time} - ${date.shifts.morning.end_time} (np:
              ${date.shifts.morning.number_of_patients_per_day || 'N/A'})` : 'N/A' }}
            </td>
            <td>
              {{ date.shifts.afternoon ? `${date.shifts.afternoon.start_time} - ${date.shifts.afternoon.end_time} (np:
              ${date.shifts.afternoon.number_of_patients_per_day || 'N/A'})` : 'N/A' }}
            </td>
            <td class="d-flex">
              <button class="btn btn-sm btn-outline-primary ml-1 mb-1" @click="openEditModal(date)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger ml-1" @click="removeExcludedDate(date)">
                <i class="fas fa-trash-alt"></i>
              </button>
            </td>
          </tr>
          <tr v-if="groupedExcludedDates.length === 0">
            <td colspan="10" class="text-center">No excluded dates found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- DateModal component for adding/editing dates -->
    <DateModal 
      :show="showModal" 
      :doctorId="doctorId" 
      :mode="modalMode" 
      v-model:new-date="newDate" 
      :editData="editData"
      v-model:date-range="dateRange" 
      @updateDATA="fetchExcludedDates" 
      @close="closeModal" 
    />
  </div>
</template>