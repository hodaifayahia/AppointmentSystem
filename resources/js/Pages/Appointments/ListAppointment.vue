<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppointmentListItem from './AppointmentListItem.vue';
import headerDoctorAppointment from '@/Components/Doctor/headerDoctorAppointment.vue';
import DoctorWaitlist from '@/Components/Doctor/DoctorWaitlist.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
const pagination = ref({});

// Initialize all refs
const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const statuses = ref([]);
const currentFilter = ref(0);
const route = useRoute();
const router = useRouter();
const doctorId = ref(null);
const specializationId = ref(null);
const file = ref(null);
const countWithDoctor = ref(0);
const countWithoutDoctor = ref(0);
const todaysAppointmentsCount = ref(0);
const NotForYou = ref(false);
const WaitlistDcotro = ref(false);
const isDcotro = ref(false);
const userRole = ref(null);
// Create axios instance with default config
const api = axios.create({
  baseURL: '/api',
  timeout: 10000
});

// Batch multiple API calls together
const fetchInitialData = async (id) => {
  try {
    const [statusesRes, appointmentsRes, waitlistsRes] = await Promise.all([
      api.get(`appointmentStatus/${id}`),
      api.get(`appointments/${id}`),
      api.get('waitlists', {
        params: {
          is_Daily: 1,
          doctor_id: id,
          specialization_id: specializationId.value
        }
      })
    ]);

    // Update all states at once
    statuses.value = [
      { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list' },
      ...statusesRes.data
    ];

    if (appointmentsRes.data.success) {
      appointments.value = appointmentsRes.data.data;
    }

    countWithDoctor.value = waitlistsRes.data.count_with_doctor;
    countWithoutDoctor.value = waitlistsRes.data.count_without_doctor;

  } catch (err) {
    console.error('Error fetching initial data:', err);
    error.value = 'Failed to load initial data';
  } finally {
    loading.value = false;
  }
};
const initializeDoctorId = async () => {
  try {
    const { data: { data: user } } = await api.get('/loginuser');
    
    if (user && user.role === 'doctor') {
      userRole.value = user.role;
      doctorId.value = user.id;
      specializationId.value = user.specialization_id;
      isDcotro.value = true;
    } 

  } catch (err) {
    doctorId.value = route.params.id;
    specializationId.value = route.params.specializationId;
  }

  // Fetch all initial data at once
  await fetchInitialData(doctorId.value);
};
const getAppointments = (() => {
  let timeout;
  return async (status = null, filter = null, date = null, page = null) => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        loading.value = true;
        currentFilter.value = status || 'ALL';

        const { data } = await api.get(`appointments/${doctorId.value}`, {
          params: {
            status: status === 'ALL' ? null : status,
            filter,
            date,
            page // Include page in the params object
          }
        });

        if (data.success) {
          appointments.value = data.data;
          pagination.value = data.meta; // Store meta data for pagination
        }
      } catch (err) {
        console.error('Error fetching appointments:', err);
        error.value = 'Failed to load appointments';
        appointments.value = [];
      } finally {
        loading.value = false;
      }
    }, 300);
  };
})();

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

const handleFilterByDate = (date) => {
  if (date) {
    // Fetch appointments with the selected date
    getAppointments(currentFilter.value, null, date);
  } else {
    // If no date is selected, reset the filter
    getAppointments(currentFilter.value);
  }
};

const handleGetAppointments = (data) => {
  appointments.value = data.data; // Update the appointments list
};

const getTodaysAppointments = async () => {
  try {
    loading.value = true; // Set loading state
    error.value = null; // Clear any previous errors

    const response = await axios.get(`/api/appointments/${doctorId.value}`, {
      params: { filter: 'today' } // Pass filter=today to fetch today's appointments
    });

    if (response.data.success) {
      appointments.value = response.data.data;
      todaysAppointmentsCount.value = response.data.data.length; // Update todaysAppointmentsCount
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching today\'s appointments:', err);
    error.value = 'Failed to load today\'s appointments. Please try again later.'; // User-friendly error message
  } finally {
    loading.value = false; // Reset loading state
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

const handleSearchResults = (searchData) => {
  appointments.value = searchData.data;
};

// Navigate to create appointment page
const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: { id: doctorId.value }
  });
};

const fetchWaitlists = async (filters = {}) => {
  try {
    const params = { ...filters, is_Daily: 1 };
    params.doctor_id = NotForYou.value ? "null" : doctorId.value; // Set doctor_id based on NotForYou
    params.specialization_id = specializationId.value;

    const response = await axios.get('/api/waitlists', { params });
    
    countWithDoctor.value = response.data.count_with_doctor; // Assign count where doctor_id is not null
    countWithoutDoctor.value = response.data.count_without_doctor; // Assign count where doctor_id is null
    console.log(countWithoutDoctor.value);
    
  } catch (error) {
    console.error('Error fetching waitlists:', error);
    swal.fire('Error!', 'Failed to fetch waitlists. Please try again.', 'error');
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

// Optimize watchers
watch(doctorId, (newValue) => {
  if (newValue) getAppointments(currentFilter.value);
}, { immediate: false });

watch(() => route.params.id, (newDoctorId) => {
  if (newDoctorId) getAppointments(currentFilter.value);
}, { immediate: false });

onMounted(() => {
  initializeDoctorId();
  fetchWaitlists();
});
</script>

<template>
  <div class="appointment-page">
    <!-- Page header -->
    <div class="p-2">
      <!-- Ensure header-doctor-appointment is rendered only after doctorId is initialized -->
      <header-doctor-appointment v-if="doctorId" :isDcotro="isDcotro" :doctor-id="doctorId" />
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
                <button v-for="status in statuses" :key="status.name" @click="getAppointments(status.value)" :class="[
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
                <button class="btn btn-outline-success mr-2" type="button" @click="openWaitlistForYouModal">
                  <i class="fas fa-user-clock me-2"></i> Waitlist for You ({{ countWithDoctor }})
                </button>

                <!-- Button for "Waitlist Not for You" Modal -->
                <button class="btn btn-outline-warning" type="button" @click="openWaitlistNotForYouModal">
                  <i class="fas fa-user-times me-2"></i> Waitlist Not for You ({{ countWithoutDoctor }})
                </button>
              </div>
              <div class="d-flex flex-column align-items-center sm:w-100 w-md-auto">
                <!-- File Upload -->
                <div class="custom-file mb-3 w-100">
                  <label for="fileUpload" class="btn btn-primary sm:w-100 premium-file-button">
                    <i class="fas fa-file-upload mr-2"></i> Choose File
                  </label>
                  <input ref="fileInput" type="file"  @change="handleFileChange"
                    class="custom-file-input d-none" id="fileUpload">
                </div>

                <!-- Import and Export Buttons -->
                <div class="d-flex flex-column flex-md-row justify-content-between sm:w-100">
                  <button @click="uploadFile" :disabled="loading || !file" class="btn btn-success mb-2 mb-md-0 me-md-2 w-100">
                    Import Appointments
                  </button>
                  <button @click="exportAppointments" class="btn btn-primary w-100">
                    Export File
                  </button>
                </div>
              </div>

            </div>

            <!-- Appointments list -->
            <AppointmentListItem :appointments="appointments" :userRole="userRole"  :error="error"  :doctor-id="doctorId"
              @update-appointment="getAppointments(currentFilter)" @update-status="getAppointmentsStatus"
              @get-appointments="handleSearchResults" @filterByDate="handleFilterByDate" />
          </div>
          <div class="mt-4">
            <Bootstrap5Pagination
              :data="pagination"
              :limit="5"
              @pagination-change-page="(page) => getAppointments(page, currentFilter)"
            />
          </div>
        </div>
      </div>
    </div>
    <!-- Waitlist Modal -->
    <DoctorWaitlist 
      :WaitlistDcotro="WaitlistDcotro" 
      :NotForYou="NotForYou" 
      :specializationId="specializationId" 
      :doctorId="doctorId" 
      @close="closeWaitlistModal" 
    />
  </div>
</template>

<style scoped>
.bg-gradient {
  background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}

/* Ensure buttons and inputs are touch-friendly */
.btn, .custom-file-label {
  padding: 0.5rem 1rem;
  font-size: 1rem;
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