<script setup>
import { defineProps, defineEmits, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { useRouter } from 'vue-router';

const props = defineProps({
    appointments: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: null
    },
    totalPages: {
        type: Number,
        required: true
    },
    doctorId: {
        type: String,
        required: false
    },
    pagination:{
        
    }
   
});


const emit = defineEmits(['getAppointments' ,"updateAppointment" ,'updateStatus']);
const toastr = useToastr();
const router = useRouter();

const statuses = ref([]);
const error = ref(null);
const dropdownStates = ref({});
const searchQuery = ref("");
const isLoading = ref(false);
const localAppointments = ref(props.appointments);
const localPagination = ref(props.pagination);


const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};



const formatTime = (time) => {
    const [, timePart] = time.split('T');
    if (timePart.length === 5) return timePart;
    const [hours, minutes] = timePart.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
};

const getPatientFullName = (patient) => {
    if (!patient) return 'N/A';
    return `${patient.patient_first_name} ${patient.patient_last_name}`;
};

const toggleDropdown = (appointmentId) => {
    dropdownStates.value = {
        ...dropdownStates.value,
        [appointmentId]: !dropdownStates.value[appointmentId]
    };
};

const getStatusText = (status) => {
    return status?.name || 'Unknown';
};

const updateAppointmentStatus = async (appointmentId, newStatus) => {
    try {
        await axios.patch(`/api/appointment/${appointmentId}/status`, { status: newStatus });
        dropdownStates.value[appointmentId] = false;
        emit('updateAppointment');
        emit('updateStatus');
        toastr.success('Appointment status updated successfully');
    } catch (err) {
        console.error('Error updating status:', err);
    }
};
    
 
// Watch for props changes
watch(() => props.appointments, (newVal) => {
    localAppointments.value = newVal;
}, { deep: true });

watch(() => props.pagination, (newVal) => {
    localPagination.value = newVal;
}, { deep: true });

const getAppointmentsStatus = async () => {
    try {
        const response = await axios.get(`/api/appointmentStatus`);
        statuses.value = response.data;
    } catch (err) {
        error.value = 'Failed to load appointment statuses';
        console.error('Error:', err);
    }
};

const goToEditAppointmentPage = (appointment) => {
  router.push({
    name: 'admin.appointments.edit',
    params: { doctorId: props.doctorId, appointmentId: appointment.id }
  });
};
const getStatusOption = (statusName) => {
    return statuses.value.find(option => option.name === statusName) ||
        { name: 'UNKNOWN', color: 'secondary', textColor: 'white', bgColor: 'bg-secondary' };
};

const deleteAppointment = async (id) => {
    
    if (!confirm('Are you sure you want to delete this appointment?')) return;
    try {
        await axios.delete(`/api/appointments/${id}`);
        emit('updateAppointment');
    } catch (err) {
        console.error('Error deleting appointment:', err);
    }
};
const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                isLoading.value = true;
                const response = await axios.get('/api/appointments/search', {
                    params: {
                        query: searchQuery.value,
                        doctorId: props.doctorId,
                    },
                });
                localAppointments.value = response.data.data;
                localPagination.value = response.data.meta;
                emit('getAppointments', response.data); // Emit the updated data
            } catch (error) {
                toastr.error('Failed to search appointments');
                console.error('Error searching appointments:', error);
            } finally {
                isLoading.value = false;
            }
        }, 300);
    };
})();


// Watch for search query changes
watch(searchQuery, debouncedSearch);
onMounted(() => {
    getAppointmentsStatus();
});
</script>

<template>

    <div class="shadow-sm">
        <div class="card-body">
            <!-- Error Alert -->
            <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
            </div>

            <!-- Search Bar -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"></h5>
                <div class="input-group w-50">
                    <input 
                        type="text" 
                        class="form-control rounded-start" 
                        v-model="searchQuery"
                        placeholder="Search by patient name or date of birth" 
                        aria-label="Search" 
                    />
                    <button 
                        class="btn btn-outline-primary" 
                        type="button" 
                        :disabled="isLoading"
                    >
                        <i class="fas" :class="{ 'fa-search': !isLoading, 'fa-spinner fa-spin': isLoading }"></i>
                    </button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"></span>
                </div>
            </div>

            <!-- Appointment Table -->
            <table v-else class="table table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Patient Name</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Date Of Birth</th>
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
                        <td>{{ appointment.patient_Date_Of_Birth }}</td>
                        <td>{{ formatDate(appointment.appointment_date) }}</td>
                        <td>{{ formatTime(appointment.appointment_time) }}</td>
                        <td>
                            <!-- Dropdown for Status -->
                            <div class="dropdown" :class="{ 'show': dropdownStates[appointment.id] }">
                                <button class="btn dropdown-toggle status-button" type="button"
                                    @click="toggleDropdown(appointment.id)">
                                    <span class="status-indicator"
                                        :class="getStatusOption(appointment.status?.name).color"></span>
                                    <span :class="`text-${appointment.status.color}`">
                                        <i :class="[`text-${appointment.status.color}`, appointment.status.icon ]"
                                            class="fa-lg ml-1"></i>
                                        {{ getStatusText(appointment.status) }}
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="z-index: 100;"
                                    :class="{ 'show': dropdownStates[appointment.id] }">
                                    <li v-for="status in statuses" :key="status.name">
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            @click.prevent="updateAppointmentStatus(appointment.id, status.value)">
                                            <span class="status-indicator" :class="status.color"></span>
                                            <i :class="[`text-${status.color}`, status.icon]"></i>
                                            <span class="status-text rounded-pill fw-bold"
                                                :class="[`text-${status.color}`, 'fs-6']">
                                                {{ status.name }}
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button @click="goToEditAppointmentPage(appointment)" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deleteAppointment(appointment.id)"
                                    class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <Bootstrap5Pagination :data="pagination" @pagination-change-page="appointments" />
        </div>
    </div>

</template>


<style scoped>
.dropdown-item:hover {
    background-color: #0080f0; /* Light blue background */
    color: #000; /* Text color */
    transition: background-color 0.2s ease; /* Smooth transition */
}
.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
    /* Space between indicator and icon */
}

/* Ensure icons are styled appropriately */
.dropdown-item i {
    margin-right: 2px;
    /* Space between icon and text */
    font-size: 1.2em;
    /* Increase icon size if needed */
}

/* Adjustments for bold text and size */
.status-text {
    white-space: nowrap;
    /* Prevent text wrapping */
}

.status-button {
    min-width: 120px;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: var(--bs-light);
}

.dropdown-toggle::after {
    margin-left: 0.5rem;
}

/* Color overrides for status buttons */
.bg-primary {
    background-color: #0d6efd !important;
}

.bg-success {
    background-color: #198754 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}

.gap-2 {
    gap: 0.5rem !important;
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

.status-btn {
    min-width: 100px;
    text-align: left;
    position: relative;
    padding-right: 24px;
}

.status-btn::after {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
}

.status-btn.btn-scheduled {
    background-color: var(--bs-primary);
    color: white;
}

.status-btn.btn-done {
    background-color: var(--bs-success);
    color: white;
}

.status-btn.btn-canceled {
    background-color: var(--bs-danger);
    color: white;
}

.status-btn.btn-pending {
    background-color: var(--bs-warning);
    color: white;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.gap-2 {
    gap: 0.5rem;
}

.dropdown-item.active {
    background-color: var(--bs-light);
    color: var(--bs-dark);
}

.dropdown-item:hover {
    background-color: var(--bs-light);
}

.status-button {
    min-width: 120px;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    /* More rounded corners for a softer look */
}

.status-indicator {
    width: 12px;
    /* Increased size for better visibility */
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
    /* Adds space between indicator and text */
}

.status-text {
    font-size: 0.85em;
    color: white;
    white-space: nowrap;
    /* Prevents text wrapping */
}

.dropdown-menu {
    min-width: 150px;
    /* Larger dropdown for better readability */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    /* Adds a subtle shadow for depth */
}

.dropdown-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.dropdown-item:hover {
    background-color: var(--bs-light);
}

/* Enhancements for selected status */
.dropdown-item .status-text {
    transition: opacity 0.2s ease;
}

.dropdown-item:hover .status-text,
.dropdown-item:focus .status-text {
    opacity: 0.8;
    /* Subtle opacity change on hover/focus for interaction feedback */
}

/* Color overrides for status buttons */
.bg-primary {
    background-color: #0d6efd !important;
}

.bg-success {
    background-color: #198754 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}

.text-white {
    color: #ffffff !important;
}

.text-dark {
    color: #343a40 !important;
}

/* Adjust padding for better touch interaction */
.gap-2 {
    gap: 0.5rem !important;
}

.status-button {
    min-width: 120px;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 4px;
    /* Reduced margin to decrease space */
}

.status-text {
    font-size: 0.85em;
    color: white;
    white-space: nowrap;
}

.dropdown-menu {
    min-width: 150px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
    padding: 0.5rem 0.75rem;
    /* Adjusted padding to control vertical spacing */
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.dropdown-item:hover,
.dropdown-item:focus {
    background-color: var(--bs-light);
}

/* Remove gap between indicator and text */
.no-gap {
    gap: 0;
    /* This removes all gap between flex items */
}

/* Style for selected status */
.dropdown-item .status-text {
    transition: opacity 0.2s ease;
}

.dropdown-item:hover .status-text,
.dropdown-item:focus .status-text {
    opacity: 0.8;
}

/* Color overrides for status buttons */
.bg-primary {
    background-color: #0d6efd !important;
}

.bg-success {
    background-color: #198754 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}

.text-white {
    color: #ffffff !important;
}

.text-dark {
    color: #343a40 !important;
}
</style>