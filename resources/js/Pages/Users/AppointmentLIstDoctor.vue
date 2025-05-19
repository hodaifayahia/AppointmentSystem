<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppointmentListItem from '../Appointments/AppointmentListItem.vue';
import headerDoctorAppointment from '@/Components/Doctor/headerDoctorAppointment.vue';
import DoctorWaitlist from '@/Components/Doctor/DoctorWaitlist.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useAppointmentStore } from '../../stores/AppointmentStata';
import { useAuthStoreDoctor } from '../../stores/AuthDoctor';

const pagination = ref({});

// Initialize all refs
const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const currentFilter = ref(0);
const route = useRoute();
const router = useRouter();
const doctorId =ref();
const specializationId =ref();
const file = ref(null);
const countWithDoctor = ref(0);
const countWithoutDoctor = ref(0);
const todaysAppointmentsCount = ref(0);
const NotForYou = ref(false);
const WaitlistDcotro = ref(false);
const isDcotro = ref(false);
const userRole = ref(null);
const appointmentStore = useAppointmentStore();
const doctors = useAuthStoreDoctor(); // Initialize Pinia store

onMounted( () => {
   appointmentStore.getAppointments(doctorId, 1, 0);
  appointments.value = appointmentStore.appointments;
  pagination.value = appointmentStore.pagination;
});


const initializeRole = async () => {
  try {
    const user = await axios.get('/api/role');
      userRole.value = user.data.role;
      doctorId.value = user.data.id;
      specializationId.value = user.data.specialization_id;

  } catch (err) {
    console.error('Error fetching user role:', err);
  }
};

const getAppointments = (() => {
  let timeout;
  return async (page = 1, status = null, filter = null, date = null) => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        loading.value = true;
        
        const params = {
          page,
          status: status ?? currentFilter.value,
          filter,
          date
        };

        const { data } = await axios.get(`/api/appointments/${doctorId.value}`, { params });

        if (data.success) {
          appointments.value = data.data;
          pagination.value = data.meta;

         
        }
      } catch (err) {
        console.error('Error fetching appointments:', err);
      } finally {
        loading.value = false;
      }
    }); // Wait 300ms before making API request
  };
})();
const handleGetAppointments = (data) => {
  appointments.value = data.data; // Update the appointments list
};
// Fetch today's appointments

// Optimized file upload
const uploadFile = async () => {
  if (!file.value) return;

  const formData = new FormData();
  formData.append('file', file.value);

  try {
    loading.value = true;
    await api.post(`import/appointment/${doctorId.value}`, formData);
    await getAppointments(currentFilter.value);
    file.value = null;
  } catch (error) {
    console.error('Error uploading file:', error);
  } finally {
    loading.value = false;
  }
};

const exportAppointments = async () => {
  try {
    const response = await axios.get('/api/import/appointment', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'appointments.xlsx');
    document.body.appendChild(link);
    link.click();
  } catch (error) {
    console.error('Error exporting appointments:', error);
  }
};
// Fetch appointment statuses
const getAppointmentsStatus = async () => {
  try {
    loading.value = true; // Set loading state
    error.value = null; // Clear any previous errors

    const response = await axios.get(`/api/appointmentStatus/${doctorId.value}`);

    statuses.value = [
      { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list' }, // Default "ALL" option
      ...response.data // Spread the response data into the array
    ];
  } catch (err) {
    console.error('Error fetching appointment statuses:', err);
    error.value = 'Failed to load status filters. Please try again later.'; // User-friendly error message
  } finally {
    loading.value = false; // Reset loading state
  }
};

const fetchWaitlists = async (filters = {}) => {
  try {
    const params = { ...filters, is_Daily: 1 };
    console.log(NotForYou.value);
    
   params.doctorId = NotForYou.value ? doctorId : "null" ; // Set doctor_id based on NotForYou
   params.specializationId = specializationId;

    const response = await axios.get('/api/waitlists', { params });
    
    countWithDoctor.value = response.data.count_with_doctor; // Assign count where doctor_id is not null
    countWithoutDoctor.value = response.data.count_without_doctor; // Assign count where doctor_id is null
    console.log();
    
  } catch (error) {
    console.error('Error fetching waitlists:', error);
  }
};

const openWaitlistForYouModal = () => {
  WaitlistDcotro.value = true; // Open the Waitlist modal
  NotForYou.value = false; // Set the NotForYou state to false
};

const openWaitlistNotForYouModal = () => {
  WaitlistDcotro.value = true; // Open the Waitlist modal
  NotForYou.value = true; // Set the NotForYou state to true
};

const closeWaitlistModal = () => {
  WaitlistDcotro.value = false; // Close the Waitlist modal
};



// Update the status filter handler
const handleStatusFilter = (status) => {
  getAppointments(1, status); // Reset to page 1 when changing status
};
const handleFileChange = (event) => {
  file.value = event.target.files[0];
  errorMessage.value = '';
  successMessage.value = '';
};

// Update other methods to use the new getAppointments signature
const handleFilterByDate = (date) => {
  if (date) {
    getAppointments(1, currentFilter.value, null, date);
  } else {
    getAppointments(1, currentFilter.value);
  }
};

const handleSearchResults = (searchData) => {
  appointments.value = searchData.data;
  pagination.value = searchData.meta;
};
const statuses = ref([
  { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list', count: 0 },
  { name: 'SCHEDULED', value: 0, color: 'primary', icon: 'fa fa-calendar-check', count: 0 },
  { name: 'CONFIRMED', value: 1, color: 'success', icon: 'fa fa-check', count: 0 },
  { name: 'CANCELED', value: 2, color: 'danger', icon: 'fa fa-ban', count: 0 },
  { name: 'PENDING', value: 3, color: 'warning', icon: 'fa fa-hourglass-half', count: 0 },
  { name: 'DONE', value: 4, color: 'info', icon: 'fa fa-check-circle', count: 0 },
]);


// Navigate to create appointment page
const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: {id : doctorId.value }
  });
  
};
const getTodaysAppointments = async () => {
  getAppointments(1, 'TODAY', 'today');
};

// Watch for route changes to reload appointments
watch(
  () => route.params.id,
  (newDoctorId) => {
    if (newDoctorId) {
      getAppointments(1, 'TODAY', 'today');
    }
  }
);

onMounted(  ()=>{
  
  initializeRole();
  getAppointmentsStatus();
  fetchWaitlists();
});
</script>
<template>
  <div class="appointment-page">
    <!-- Page header -->
    <div class="p-2">
      <!-- Ensure header-doctor-appointment is rendered only after doctorId is initialized -->
      <header-doctor-appointment  :isDcotro="true" :doctor-id="doctorId" />
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- Search and Import/Export Section -->
          <div class="col-lg-12">
            <!-- Actions toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3">
              <button @click="goToAddAppointmentPage" class="btn btn-primary rounded-pill mb-2 mb-md-0">
                <i class="fas fa-plus me-2"></i>
                Add Appointment
              </button>

              <!-- Status Filters -->
              <div class="btn-group flex-wrap" role="group" aria-label="Appointment status filters">
                <!-- Today's Appointments Tab -->
                <button @click="getTodaysAppointments" :class="[
                  'btn',
                  currentFilter === 'TODAY' ? 'btn-info' : 'btn-outline-info',
                  'btn-sm m-1 rounded'
                ]">
                  <i class="fas fa-calendar-day"></i>
                  Today's Appointments
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === 'TODAY' ? 'bg-light text-dark' : 'bg-info'">
                    {{ todaysAppointmentsCount }}
                  </span>
                </button>

                <!-- Existing Status Filters -->
                <button v-for="status in statuses" :key="status.name" @click="handleStatusFilter(status.value)" :class="[
                  'btn',
                  currentFilter === status.name ? `btn-${status.color}` : `btn-outline-${status.color}`,
                  'btn-sm m-1 rounded'
                ]">
                  <i :class="status.icon"></i>
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === status.name ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ status.count }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Search and Import/Export Section -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
              <!-- File Upload and Export Buttons -->
              <!-- Waitlist Buttons -->
              <div class="d-flex gap-2">
                <!-- Button for "Waitlist for You" Modal -->
                <button class="btn btn-outline-success mr-2 position-relative" type="button" @click="openWaitlistForYouModal">
                  <i class="fas fa-user-clock me-2 "></i> Waitlist for You <span v-if="countWithDoctor > 0" class="custom-time">{{ countWithDoctor }}</span>
                </button>

                <!-- Button for "Waitlist Not for You" Modal -->
                <button class="btn btn-outline-warning position-relative" type="button" @click="openWaitlistNotForYouModal">
                  <i class="fas fa-user-times me-2"></i>  Waitlist Not for You <span v-if="countWithoutDoctor > 0" class="custom-time"> {{ countWithoutDoctor }}</span>
                </button>
              </div>
              <div v-if="userRole == 'admin'" class="d-flex flex-column align-items-center sm:w-100 w-md-auto">
                <!-- File Upload -->
                <div class="custom-file mb-3 w-100">
                  <label for="fileUpload" class="btn btn-primary sm:w-100 premium-file-button">
                    <i class="fas fa-file-upload mr-2"></i> Choose File
                  </label>
                  <input ref="fileInput" type="file" @change="handleFileChange" class="custom-file-input d-none"
                    id="fileUpload">
                </div>

                <!-- Import and Export Buttons -->
                <div class="d-flex flex-column flex-md-row justify-content-between sm:w-100">
                  <button @click="uploadFile" :disabled="loading || !file"
                    class="btn btn-success mb-2 mb-md-0 me-md-2 w-100">
                    Import Appointments
                  </button>
                  <button @click="exportAppointments" class="btn btn-primary w-100">
                    Export File
                  </button>
                </div>
              </div>

            </div>

            <!-- Appointments list -->
            <AppointmentListItem :appointments="appointments" :userRole="userRole" :error="error" :doctor-id="doctorId"
              @update-appointment="getAppointments(currentFilter)" @update-status="getAppointmentsStatus"
              @get-appointments="handleSearchResults" @filterByDate="handleFilterByDate" />
          </div>
          <div class="mt-4">
            <Bootstrap5Pagination :data="pagination" :limit="5"
              @pagination-change-page="(page) => getAppointments(page)" />
          </div>
        </div>
      </div>
    </div>
    <!-- Waitlist Modal -->
    <DoctorWaitlist :WaitlistDcotro="WaitlistDcotro" :NotForYou="NotForYou" :specializationId="specializationId"
      :doctorId="doctorId" @close="closeWaitlistModal" />
  </div>
</template>
<style scoped>
.bg-gradient {
  background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}

/* Ensure buttons and inputs are touch-friendly */
.btn,
.custom-file-label {
  padding: 0.5rem 1rem;
  font-size: 1rem;
}
.custom-time{
  position: absolute;
    top: -8px;
    right: -7px;
    background-color: red;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    color: white;
}

/* Adjust spacing for mobile */
@media (max-width: 768px) {
  .btn-group {
    flex-direction: column;
    width: 100%;
  }

  .btn-group .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.flex-column.flex-md-row {
    flex-direction: column;
  }

  .d-flex.flex-column.flex-md-row .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
}
</style>