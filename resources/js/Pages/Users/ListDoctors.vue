<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import DoctorListItem from './DoctorListItem.vue';
import DoctorModel from '../../Components/DoctorModel.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

const doctors = ref([]);
const selectedDoctor = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  specialization: 0,
  number_of_patients_per_day: '',
  patients_based_on_time: '',
  time_slot: '',
  frequency: '',
  start_time: '',
  end_time: '',
  days: [],
  number_patients: false,
});
const isModalOpen = ref(false);
const toaster = useToastr();
const searchQuery = ref('');
const isLoading = ref(false);
const selectedDoctorBox = ref([]);
const selectAll = ref(false);
const pagination = ref({});
const userRole = ref('');


const getDoctors = async (page = 1) => {
  try {
    const response = await axios.get(`/api/doctors?page=${page}`);
    doctors.value = response.data.data; // Immediately update the list
    pagination.value = response.data.meta; // Store meta data for pagination
    console.log(doctors.value);
    
  } catch (error) {
    toaster.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};

// Open modal for adding a new doctor
const openModal = () => {
  selectedDoctor.value = {
    name: '',
    email: '',
    phone: '',
    password: '',
    specialization: 0,
    number_of_patients_per_day: '',
    patients_based_on_time: '',
    appointmentBookingWindow: '',
    time_slot: '',
    frequency: '',
    start_time: '',
    end_time: '',
    days: [],
    number_patients: false,
  };
  isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
  isModalOpen.value = false;
};


const refreshUsers = () => {
  getDoctors();
  closeModal(); // Close modal after successful update
};
// Fetch user role and initialize doctor ID
const initializeDoctorId = async () => {
  try {
    const user = await axios.get('/api/role');
   

    if (user.data.role === 'admin') {
      userRole.value = user.data.role;
      // console.log('User role:', userRole.value);
      
    } 
  } catch (err) {
    console.error('Error fetching user role:', err);
  }
};


// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/doctors/search', {
          params: { query: searchQuery.value },
        });
        doctors.value = response.data.data;
      
      } catch (error) {
        toaster.error('Failed to search doctors');
        console.error('Error searching doctors:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

const toggleSelection = (doctor) => {
  const index = selectedDoctorBox.value.indexOf(doctor.id);
  if (index === -1) {
    selectedDoctorBox.value.push(doctor.id);
  } else {
    selectedDoctorBox.value.splice(index, 1);
  }
};


const bulkDelete = async () => {
  try {
    const response = await axios.delete('/api/doctors', {
      params: { ids: selectedDoctorBox.value },
    });
    toaster.success('Users deleted successfully!');
    selectedDoctorBox.value =[];
    selectAll.value =false;
    getDoctors();
  } catch (error) {
    toaster.error('Failed to delete users');
    console.error('Error deleting users:', error);
  }
};


const selectAllDoctors = () => {
  if (selectAll.value) {
    selectedDoctorBox.value = doctors.value.map(doctor => doctor.id);

    
  } else {
    selectedDoctorBox.value = [];
  }
};

onMounted(() => {
  getDoctors();
  initializeDoctorId();
});

</script>

<template>
  <div>
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Doctors</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Doctors</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container">
        <h2 class="text-center mb-4">Doctor Management</h2>
        <div class="text-right mb-4">
          <div class="d-flex justify-content-between align-items mb-3">
            <!-- Add Doctor Button -->
            <div class="row mb-3">
              <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                
                <button 
                  class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 py-2" 
                  title="Add Doctor"
                  @click="openModal"
                >
                  <i class="fas fa-plus-circle"></i>
                  <span>Add Doctor</span>
                </button>

                <!-- Delete Doctors Button -->
                <div v-if="selectedDoctorBox.length > 0" class="row ml-2">
                  <button
                    @click="bulkDelete"
                    class="btn btn-danger btn-sm d-flex align-items-center gap-1 px-3 py-2 ml-1"
                    title="Delete Doctors"
                  >
                    <i class="fas fa-trash-alt mr-1"></i>
                    <span>Delete Doctors</span>
                  </button>
                  <span class="ml-2 mt-1">selected ({{ selectedDoctorBox.length }}) doctors</span>
                </div>
              </div>
            </div>

            <!-- Search Bar -->
            <div class="mb-1">
              <input 
                type="text" 
                class="form-control" 
                v-model="searchQuery" 
                placeholder="Search doctors" 
              />
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-primary">
              <tr>
                <th><input type="checkbox" v-model="selectAll" @change="selectAllDoctors"></th>
                <th>#</th>
                <th>Photo</th>
                <th>Name</th>
                <th>Username</th>
                <th>Phone</th>
                <th>Specialization</th>
                <th>Frequency</th>
                <th>Time Slots</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody  v-if="doctors.length > 0">
              <DoctorListItem
                v-for="(doctor, index) in doctors"
                :key="doctor.id"
                :doctor="doctor"
                :index="index"
                @doctorUpdated="refreshUsers"
                @toggle-selection="toggleSelection"
                :selectAll="selectAll"
              />
            </tbody>
            <tbody v-else >
              <tr>
                <td colspan="11" class="text-center">No Results Found...</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Bootstrap5Pagination :data="pagination" @pagination-change-page="getDoctors" />
      </div>
    </div>

    <!-- Doctor Modal -->
    <DoctorModel
      :show-modal="isModalOpen"
      :doctor-data="selectedDoctor"
      @close="closeModal"
      @doctorUpdated="refreshUsers"
    />
  </div>
</template>

<style scoped>
.table {
  min-width: 800px;
}

.table th,
.table td {
  vertical-align: middle;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
}
</style>