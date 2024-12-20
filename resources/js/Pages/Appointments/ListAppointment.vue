<script setup>
import { ref, computed } from 'vue'

const appointments = ref([
  { id: 1, clientName: 'Mr. Martin Glover MD', date: '2023-01-27', time: '05:40 PM', status: 'closed' },
  { id: 1, clientName: 'Mr. Martin Glover MD', date: '2023-01-27', time: '05:40 PM', status: 'closed' },
  { id: 1, clientName: 'Mr. Martin Glover MD', date: '2023-01-27', time: '05:40 PM', status: 'closed' },
  { id: 1, clientName: 'Mr. Martin Glover MD', date: '2023-01-27', time: '05:40 PM', status: 'closed' },
  // Add more appointments here
])

const Appointments = ref([]);
const getAppointment = async (page = 1) => {
  try {
    const response = await axios.get(`/api/appointment?page=${page}`);
    Appointments.value = response.data.data; // Immediately update the list
  } catch (error) {
    toaster.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};

const statuses = ['All', 'Scheduled', 'Closed']
const currentFilter = ref('All')

const filteredAppointments = computed(() => {
  if (currentFilter.value === 'All') return appointments.value
  return appointments.value.filter(a => a.status.toLowerCase() === currentFilter.value.toLowerCase())
})

const getStatusCount = (status) => {
  if (status === 'All') return appointments.value.length
  return appointments.value.filter(a => a.status.toLowerCase() === status.toLowerCase()).length
}

const getStatusColor = (status) => {
  switch (status.toLowerCase()) {
    case 'scheduled': return 'primary'
    case 'closed': return 'success'
    default: return 'secondary'
  }
}

const filterAppointments = (status) => {
  currentFilter.value = status
}

const openAddAppointmentModal = () => {
  // Implement modal logic here
  console.log('Open modal to add appointment')
}

const editAppointment = (appointment) => {
  // Implement edit logic here
  console.log('Edit appointment:', appointment)
}

const deleteAppointment = (id) => {
  // Implement delete logic here
  console.log('Delete appointment:', id)
}
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
                <!-- Add New Appointment Button -->
                <button @click="openAddAppointmentModal" class="btn btn-primary btn-rounded">
                  <i class="feather icon-plus-circle"></i> Add New Appointment
                </button>
  
                <!-- Status Filters -->
                <div class="btn-group" role="group" aria-label="Appointment status filters">
                  <button 
                    v-for="status in statuses" 
                    :key="status" 
                    @click="filterAppointments(status)"
                    :class="{'btn-primary': currentFilter === status, 'btn-outline-primary': currentFilter !== status}"
                    class="btn btn-sm"
                  >
                    {{ status }}
                    <span class="badge badge-pill ml-1" :class="{'badge-secondary': currentFilter === status, 'badge-light': currentFilter !== status}">
                      {{ getStatusCount(status) }}
                    </span>
                  </button>
                </div>
              </div>
  
              <!-- Appointment List -->
              <div class="card shadow-sm">
                <div class="card-body">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">Client Name</th>
                        <th scope="col">Date</th>
                        <th scope="col">Time</th>
                        <th scope="col">Status</th>
                        <th scope="col">Options</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(appointment, index) in filteredAppointments" :key="appointment.id">
                        <td>{{ index + 1 }}</td>
                        <td>{{ appointment.clientName }}</td>
                        <td>{{ appointment.date }}</td>
                        <td>{{ appointment.time }}</td>
                        <td>
                          <span :class="`badge badge-${getStatusColor(appointment.status)}`">{{ appointment.status }}</span>
                        </td>
                        <td>
                          <button @click="editAppointment(appointment)" class="btn btn-sm btn-outline-primary mr-2">
                            <i class=" fas fa-edit"></i>
                          </button>
                          <button @click="deleteAppointment(appointment.id)" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
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
  
  .badge-pill {
    font-size: 85%;
    font-weight: 600;
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
  
  .table td, .table th {
    vertical-align: middle;
  }
  </style>