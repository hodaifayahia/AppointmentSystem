<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppointmentListItem from './AppointmentListItem.vue';

const appointments = ref([]);
const loading = ref(true);
const totalPages = ref(1);
const error = ref(null);
const statuses = ref([]);
const currentFilter = ref('ALL');
const route = useRoute();
const router = useRouter();
const doctorId = route.params.id;

const getAppointments = async (status, page = 1, doctorId = null) => {
  try {
    const params = { page };
    if (status && status !== 'ALL') {
      params.status = status;
    }
    if (doctorId) {
      params.doctor_id = doctorId;
    }
    loading.value = true;

    const response = await axios.get('/api/appointments', { params });
    appointments.value = response.data.data;
  } catch (error) {
    console.error('Error fetching appointments:', error);
    error.value = error.response?.data?.message || 'Failed to load appointments';
  } finally {
    loading.value = false;
  }
};

const getStatusCount = (status) => {
  if (status === 'ALL') return appointments.value.length;
  return appointments.value.filter(a => a.status?.name === status).length;
};

const getAppointmentsStatus = async () => {
  try {
    const response = await axios.get(`/api/appointmentStatus`)
    statuses.value = response.data;
  } catch (error) {
    console.error('Error fetching appointments:', error)
    error.value = 'Failed to load appointments'
  }
};

const goToNextPage = () => {
  router.push({ name: 'admin.appointments.create', params: { doctorId } });
};

onMounted(() => {
  getAppointments();
  getAppointmentsStatus();
});
</script>

<template>
  <div class="appointment-page">
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Appointments</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Appointments</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <button @click="goToNextPage" class="btn btn-primary rounded-pill">
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
                  <i :class="status.icon"></i> <!-- Dynamic icon class -->
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === status.name ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ getStatusCount(status.name) }}
                  </span>
                </button>
              </div>
            </div>


            <AppointmentListItem :appointments="appointments" :loading="loading" :error="error"
              :total-pages="totalPages" @get-appointments="getAppointments" @updateAppointment="getAppointments" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.appointment-page {
  background-color: #f8f9fa;
}

.btn-rounded {
  border-radius: 50px;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.badge {
  padding: 0.5em 1em;
}
</style>