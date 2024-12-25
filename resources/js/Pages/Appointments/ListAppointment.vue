<script setup>
import { ref, computed, onMounted } from 'vue';
import {useRoute,useRouter } from 'vue-router';
import axios from 'axios'

const appointments = ref([]);
const loading = ref(true);
const currentPage = ref(1);
const totalPages = ref(1);
const error = ref(null);
const statuses = ref([]);
const route = useRoute();
const doctorId = route.params.id; // Access specialization ID

const router = useRouter();
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

    const response = await axios.get('/api/appointment', { params });

    appointments.value = response.data.data;
    currentPage.value = page;

  } catch (error) {
    console.error('Error fetching appointments:', error);
    error.value = error.response?.data?.message || 'Failed to load appointments';
  } finally {
    loading.value = false;
  }
};


const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
};

const formatTime = (time) => {
  // Extract the time part from the datetime string
  const [, timePart] = time.split('T');
  if (timePart.length === 5) {
    return timePart;
  }
  // Handle other formats:
  const [hours, minutes] = timePart.split(':');
  return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
};
const currentFilter = ref('ALL');


const filterAppointments = (status) => {
  currentFilter.value = status;
};

const getStatusCount = (status) => {
  if (status === 'ALL') return appointments.value.length;
  return appointments.value.filter(a => a.status?.name === status).length;
};

const getPatientFullName = (patient) => {
  if (!patient) return 'N/A'
  return `${patient.patient_first_name} ${patient.patient_last_name}`
}

const getAppointmentsStatus = async () => {
  try {
    const response = await axios.get(`/api/appointmentStatus`)
    statuses.value = response.data;
    console.log(statuses.value);
  } catch (error) {
    console.error('Error fetching appointments:', error)
    error.value = 'Failed to load appointments'
  }
}



const getBadgeClass = (status) => {
  const colorMap = {
    'SCHEDULED': 'bg-primary',
    'DONE': 'bg-success',
    'CANCELED': 'bg-danger',
    'PENDING': 'bg-warning'
  }
  return colorMap[status?.name] || 'bg-secondary'
}

const getStatusText = (status) => {
  return status?.name || 'Unknown'
}

const openAddAppointmentModal = () => {
  console.log('Open modal to add appointment')
}

const editAppointment = (appointment) => {
  console.log('Edit appointment:', appointment)
}

const deleteAppointment = async (id) => {
  if (!confirm('Are you sure you want to delete this appointment?')) return

  try {
    await axios.delete(`/api/appointment/${id}`)
    await getAppointments(currentPage.value)
  } catch (error) {
    console.error('Error deleting appointment:', error)
  }
}

const goToNextPage = () => {
  router.push({ name: 'admin.appointments.create', params: { doctorId } }); // Replace `NextPage` with your route name
};

onMounted(() => {
  getAppointments();
  getAppointmentsStatus();

})
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
              <button @click="goToNextPage">Go to Next Page</button>

              <!-- Status Filters -->
              <div class="btn-group" role="group" aria-label="Appointment status filters">
                <button v-for="status in statuses" :key="status.name" @click="getAppointments(status.value)" :class="[
                  'btn',
                  currentFilter === status.name ? `btn-${status.color}` : `btn-outline-${status.color}`,
                  'btn-sm'
                ]">
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1"
                    :class="currentFilter === status.name ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ getStatusCount(status.name) }}
                  </span>
                </button>
              </div>
            </div>



            <!-- Appointment List -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"></span>
                  </div>
                </div>

                <table v-else class="table table-hover">
                  <thead>
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">Patient Name</th>
                      <th scope="col">Phone</th>
                      <th scope="col">Doctor Name</th>
                      <th scope="col">Date</th>
                      <th scope="col">Time</th>
                      <th scope="col">Status</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="appointments.length === 0">
                      <td colspan="8" class="text-center">No appointments found</td>
                    </tr>
                    <tr v-else v-for="(appointment, index) in appointments" :key="appointment.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ getPatientFullName(appointment) }}</td>
                      <td>{{ appointment.phone }}</td>
                      <td>{{ appointment.doctor_name }}</td>
                      <td>{{ formatDate(appointment.appointment_date) }}</td>
                      <td>{{ formatTime(appointment.appointment_time) }}</td>
                      <td>
                        <span :class="`badge ${getBadgeClass(appointment.status)}`">
                          {{ getStatusText(appointment.status) }}
                        </span>
                      </td>
                      <td>
                        <button @click="editAppointment(appointment)" class="btn btn-sm btn-outline-primary me-2">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="deleteAppointment(appointment.id)" class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="!loading && totalPages > 1" class="d-flex justify-content-center mt-4">
                  <nav aria-label="Appointments pagination">
                    <ul class="pagination">
                      <li v-for="page in totalPages" :key="page"
                        :class="['page-item', { active: page === currentPage }]">
                        <a class="page-link" href="#" @click.prevent="getAppointments(page)">
                          {{ page }}
                        </a>
                      </li>
                    </ul>
                  </nav>
                </div>
              </div>
            </div>
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

.card {
  border-radius: 10px;
  overflow: hidden;
}

.card-body {
  padding: 1.5rem;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.075);
}

.table td,
.table th {
  vertical-align: middle;
}

.pagination {
  margin-bottom: 0;
}

.me-2 {
  margin-right: 0.5rem;
}
</style>