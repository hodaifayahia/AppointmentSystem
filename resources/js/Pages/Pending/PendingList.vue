<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import PendingListItem from './PendingListItem.vue';
import { useToastr } from '../../Components/toster';
const toastr = useToastr();
const router = useRouter();

const pagination = ref({});

// Initialize all refs
const appointments = ref([]);
const loading = ref(true);
const currentFilter = ref(0);
const route = useRoute();
const doctors = ref([]); // Ref to store doctors list
const selectedDoctorId = ref(null); // Ref to store selected doctor id
const searchQuerys = ref(''); // Ref to store search query for doctors
const searchQuery = ref(''); // Ref to store search query for doctors
const selectedDate = ref();
const isLoading = ref();


const getDoctors = async (page = 1) => {
  try {
    const response = await axios.get(`/api/doctors?page=${page}`);
    doctors.value = response.data.data; // Update the doctors list
    pagination.value = response.data.meta; // Store meta data for pagination
    console.log(doctors.value);

  } catch (error) {
    toaster.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};
const getAppointments = async (page = 1) => {
  try {
    loading.value = true;
    const params = {
      doctorId: selectedDoctorId.value ?? null,
      date: selectedDate.value,
      query: searchQuery.value,
    };

    console.log('API Request Parameters:', params); // Debugging log

    const { data } = await axios.get(`/api/appointment/pending`, { params });

    console.log('API Response Data:', data); // Debugging log

    if (data.success) {
      appointments.value = data.data;
      pagination.value = data.meta;
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    toaster.error('Failed to fetch appointments: ' + err.message); // Display error to user
  } finally {
    loading.value = false;
  }
};
const applyDateFilter = async () => {
  if (selectedDate.value) {
    isLoading.value = true;
    try {
      await getAppointments(); // Call the function first
      selectedDate.value = selectedDate.value; // Ensure the date is updated (not necessary but ensures reactivity)
    } catch (err) {
      error.value = 'Failed to filter appointments by date.';
    } finally {
      isLoading.value = false;
    }
  } else {
    emit('filterByDate', null);
  }
};
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;

        if (!searchQuery.value) {
          await getAppointments(); // If search query is empty, fetch all appointments
          return;
        }

        const response = await axios.get(`/api/appointments/search`, {
          params: {
            query: searchQuery.value,
            doctor_id: selectedDoctorId.value, // Include doctorId in search params
          },
        });
        appointments.value = response.data.data;
        pagination.value = response.data.meta;
      } catch (error) {
        toastr.error('Failed to search appointments');
        console.error('Error searching appointments:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

const goToEditAppointmentPage = (appointment) => {
    router.push({
        name: 'admin.appointments.edit',
        params: { id: props.doctorId, appointmentId: appointment.id }
    });
};
const selectedDoctor = ref(null);
const showDropdown = ref(false);

const filteredDoctors = computed(() => {
  if (!searchQuerys.value) return doctors.value;
  return doctors.value.filter(doctor =>
    doctor.name.toLowerCase().includes(searchQuerys.value.toLowerCase())
  );
});

const selectDoctor = (doctor) => {
  selectedDoctor.value = doctor;
  searchQuerys.value = doctor.name;
  selectedDoctorId.value = doctor.id;
  showDropdown.value = false;
  getAppointments(); // Fetch appointments for the selected doctor
};
// Close dropdown when clicking outside
const clickOutside = (event) => {
  if (!event.target.closest(".doctor-search-wrapper")) {
    showDropdown.value = false;
  }
};
watch(searchQuery, debouncedSearch);
watch(selectedDoctorId, () => getAppointments()); // Watch for doctor selection change

onMounted(async () => {
  await getDoctors();
  if (selectedDoctorId.value) {
    const doctor = doctors.value.find(d => d.id === selectedDoctorId.value);
    if (doctor) {
      selectedDoctor.value = doctor;
      searchQuerys.value = doctor.name;
    }
  }
  getAppointments();
  document.addEventListener('click', clickOutside);
});
onUnmounted(() => {
  document.removeEventListener('click', clickOutside); // Remove click outside listener on unmount
});
</script>
<template>
  <div class="appointment-page">
    <div class="content-header bg-light border-bottom mb-4">
      <div class="container-fluid">
        <div class="row align-items-center py-3">
          <div class="col-sm-6">
            <h1 class="h3 mb-0">Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end mb-0">
              <li class="breadcrumb-item">
                <a href="#" class="text-decoration-none">Home</a>
              </li>
              <li class="breadcrumb-item active">Appointment</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row g-3 mb-4">
          <div class="col-md-4">
    <div class="position-relative doctor-search-wrapper">
      <input 
        type="text" 
        v-model="searchQuerys" 
        placeholder="Search & Select Doctor..." 
        class="form-control"
        @focus="showDropdown = true" 
        @click="showDropdown = true" 
        @input="showDropdown = true" 
        @blur="!searchQuerys && (showDropdown = false)"
      />
      <div 
        v-if="showDropdown" 
        class="position-absolute w-100 mt-1 dropdown-menu show" 
        style="max-height: 280px; overflow-y: auto;"
      >
        <div v-if="filteredDoctors.length > 0">
          <div 
            v-for="doctor in filteredDoctors" 
            :key="doctor.id"
            class="dropdown-item d-flex align-items-center gap-2 py-2 cursor-pointer"
            @mousedown="selectDoctor(doctor)" 
          >
            <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary"
              style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
              {{ doctor.name.charAt(0) }}
            </div>
            <span>{{ doctor.name }}</span>
          </div>
        </div>
        <div v-else class="dropdown-item text-muted text-center py-3">
          No doctors found
        </div>
      </div>
    </div>
  </div>

          <div class="col-md-4">
            <div class="input-group">
              <input type="date" class="form-control" v-model="selectedDate" />
              <button class="btn btn-outline-primary" type="button" @click="applyDateFilter" :disabled="loading">
                <i class="fas fa-filter"></i>
              </button>
            </div>
          </div>

          <div class="col-md-4">
            <div class="input-group">
              <input type="text" class="form-control" v-model="searchQuery"
                placeholder="Search by patient name or date of birth" />
              <button class="btn btn-outline-primary" type="button" @click="debouncedSearch" :disabled="loading">
                <i class="fas" :class="{ 'fa-search': !loading, 'fa-spinner fa-spin': loading }"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="">
                      <input type="checkbox" class="" v-model="selectAll" @change="selectAllUsers">
                    </th>
                    <th class="border-bottom-0">#</th>
                    <th class="border-bottom-0">Doctor</th>
                    <th class="border-bottom-0">Patient</th>
                    <th class="border-bottom-0">Phone</th>
                    <th class="border-bottom-0">Date Of Birth</th>
                    <th class="border-bottom-0">Date</th>
                    <th class="border-bottom-0">Time</th>
                    <th class="border-bottom-0">Description</th>
                    <th class="border-bottom-0">Reason</th>
                    <th class="border-bottom-0">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="appointments.length > 0">
                    <PendingListItem v-for="(appointment, index) in appointments" :key="index"
                      :appointment="appointment" :index="index" @appointment-updated="getAppointments()" />
                  </template>
                  <template v-else>
                    <tr>
                      <td colspan="11" class="text-center py-4 text-muted">
                        No Results Found...
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
          <Bootstrap5Pagination :data="pagination" :limit="5"
            @pagination-change-page="(page) => getAppointments(page)" />
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.selected {
  background-color: #f8f9fa;
  border-color: var(--bs-primary);
}
.dropdown-menu {
  z-index: 1000;
  border: 1px solid rgba(0, 0, 0, 0.1);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-item:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
}

/* Custom scrollbar */
.dropdown-menu::-webkit-scrollbar {
  width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.dropdown-menu::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .float-sm-end {
    float: none !important;
  }

  .breadcrumb {
    margin-top: 1rem;
    justify-content: flex-start;
  }
}
</style>