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
const doctorId = ref(null);
const pagination = ref({});

const initializeDoctorId = async () => {
  try {
    const response = await axios.get('/api/user');
    const user = response.data.data;

    if (user.role === 'doctor') {
      doctorId.value = user.id;
    } else {
      doctorId.value = route.params.id;
    }
  } catch (err) {
    console.error('Error fetching user:', err);
    doctorId.value = route.params.id;
  }
};

const getAppointments = async (status = null) => {
  try {
    loading.value = true;
    error.value = null;

    currentFilter.value = status || 'ALL';

    const params = {
      status: status === 'ALL' ? null : status,
    };

    const response = await axios.get(`/api/appointments/${doctorId.value}`, { params });
    pagination.value = response.data.meta;

    if (response.data.success) {
      appointments.value = response.data.data;
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = 'Failed to load appointments. Please try again later.';
    appointments.value = [];
  } finally {
    loading.value = false;
  }
};

const getAppointmentsStatus = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axios.get(`/api/appointmentStatus/${doctorId.value}`);

    statuses.value = [
      { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list' },
      ...response.data,
    ];
  } catch (err) {
    console.error('Error fetching appointment statuses:', err);
    error.value = 'Failed to load status filters. Please try again later.';
  } finally {
    loading.value = false;
  }
};

const handleSearchResults = (searchData) => {
  appointments.value = searchData.data;
  pagination.value = searchData.meta;
};

const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: { doctorId: doctorId.value },
  });
};

watch(
  () => route.params.id,
  (newDoctorId) => {
    if (newDoctorId) {
      getAppointments(currentFilter.value);
    }
  }
);

onMounted(async () => {
  await initializeDoctorId();
  getAppointmentsStatus();
  getAppointments();
});
</script>

<template>
  <div class="appointment-page">
    <!-- Page header -->
    <div class="p-2">
      <headerDoctorAppointment :doctorId="doctorId" />
    </div>
    <!--     page-header -->
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

            <!-- Appointments list -->
            <AppointmentListItem :appointments="appointments" :loading="loading" :error="error" :doctor-id="doctorId"
              @update-appointment="getAppointments(currentFilter)" @update-status="getAppointmentsStatus"
              @get-appointments="handleSearchResults" />
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