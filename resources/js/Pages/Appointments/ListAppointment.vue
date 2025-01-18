<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppointmentListItem from './AppointmentListItem.vue';
import headerDoctorAppointment from '@/Components/Doctor/headerDoctorAppointment.vue';

const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const statuses = ref([]);
const currentFilter = ref('ALL');
const route = useRoute();
const router = useRouter();
const doctorId = route.params.id;
const pagination = ref({});
const todaysAppointmentsCount = ref(0); // New ref for today's appointments count

// Fetch appointments with filters
const getAppointments = async (status = null, filter = null, date = null) => {
  try {
    loading.value = true;
    error.value = null;

    currentFilter.value = status || 'ALL';

    const params = {
      status: status === 'ALL' ? null : status,
      filter: filter, // Pass the filter parameter
      date: date, // Pass the date parameter
    };

    const response = await axios.get(`/api/appointments/${doctorId}`, { params });
    pagination.value = response.data.meta; // Store meta data for pagination
    console.log(response.data.data);
    
    if (response.data.success) {
      appointments.value = response.data.data;
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = err.message || 'Failed to load appointments';
    appointments.value = [];
  } finally {
    loading.value = false;
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
// Fetch today's appointments
const getTodaysAppointments = async () => {
  try {
    loading.value = true; // Set loading state
    error.value = null; // Clear any previous errors

    const response = await axios.get(`/api/appointments/${doctorId}`, {
      params: { filter: 'today' } // Pass filter=today to fetch today's appointments
    });

    if (response.data.success) {
      appointments.value = response.data.data;
      todaysAppointmentsCount.value = response.data.data.length; // Store today's appointments count
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

    const response = await axios.get(`/api/appointmentStatus/${doctorId}`);

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
  pagination.value = searchData.meta;
};

// Navigate to create appointment page
const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: { doctorId }
  });
};

// Watch for route changes to reload appointments
watch(
  () => route.params.id,
  (newDoctorId) => {
    if (newDoctorId) {
      getAppointments(currentFilter.value);
    }
  }
);

onMounted(() => {
  getAppointmentsStatus();
  getAppointments();
  getTodaysAppointments(); // Fetch today's appointments on mount
});
</script>

<template>
  <div class="appointment-page">
    <!-- Page header -->
    <div class="p-2">
      <headerDoctorAppointment :doctorId="doctorId" />
    </div>
    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <!-- Actions toolbar -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <button @click="goToAddAppointmentPage" class="btn btn-primary rounded-pill">
                <i class="fas fa-plus me-2"></i>
                Add Appointment
              </button>

              <!-- Status Filters -->
              <div class="btn-group" role="group" aria-label="Appointment status filters">
                <!-- Today's Appointments Tab -->
                <button 
                  @click="getTodaysAppointments" 
                  :class="[
                    'btn',
                    currentFilter === 'TODAY' ? 'btn-info' : 'btn-outline-info',
                    'btn-sm m-1 rounded'
                  ]"
                >
                  <i class="fas fa-calendar-day"></i>
                  Today's Appointments
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === 'TODAY' ? 'bg-light text-dark' : 'bg-info'">
                    {{ todaysAppointmentsCount }}
                  </span>
                </button>

                <!-- Existing Status Filters -->
                <button 
                  v-for="status in statuses" 
                  :key="status.name" 
                  @click="getAppointments(status.value)" 
                  :class="[
                    'btn',
                    currentFilter === status.name ? `btn-${status.color}` : `btn-outline-${status.color}`,
                    'btn-sm m-1 rounded'
                  ]"
                >
                  <i :class="status.icon"></i>
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === status.name ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ status.count }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Appointments list -->
            <AppointmentListItem :appointments="appointments" :loading="loading" :error="error" :doctor-id="doctorId"
              @update-appointment="getAppointments(currentFilter)" @update-status="getAppointmentsStatus"
              @get-appointments="handleSearchResults"  @filterByDate="handleFilterByDate" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
.bg-gradient {
  background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}
</style>