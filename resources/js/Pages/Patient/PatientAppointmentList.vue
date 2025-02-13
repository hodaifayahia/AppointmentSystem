<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const statuses = ref([]);
const currentFilter = ref('ALL');
const route = useRoute();
const pagination = ref({});

const Patientid = route.params.id;

const getAppointments = async (status = null) => {
    try {
        loading.value = true;
        error.value = null;

        // Update current filter
        currentFilter.value = status || 'ALL';

        const params = {
            status: status === 'ALL' ? null : status
        };
        

        const response = await axios.get(`/api/appointments/patient/${Patientid}`, { params });
        
        if (response.data.success === false) {
            // Handle the case where no appointments are found
            appointments.value = [];
            error.value = response.data.message;
        } else {
            pagination.value = response.data.meta;
            appointments.value = response.data.data;            
        }
    } catch (err) {
        console.error('Error fetching appointments:', err);
        error.value = err.message || 'Failed to load appointments';
        appointments.value = [];
    } finally {
        loading.value = false;
    }
};

// Fetch appointment statuses
const getAppointmentsStatus = async () => {
    try {
        const response = await axios.get(`/api/appointmentStatus/patient/${Patientid}`);
        statuses.value = [
            { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list' },
            ...response.data
        ];
    } catch (err) {
        console.error('Error fetching appointment statuses:', err);
        error.value = 'Failed to load status filters';
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
    if (!time) return "00:00";
    try {
        if (time.includes('T')) {
            const [, timePart] = time.split('T');
            if (timePart.length === 6) return timePart;
            const [hours, minutes] = timePart.split(':');
            return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
        }
        const [hours, minutes] = time.split(':');
        return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
    } catch (error) {
        console.error("Error formatting time:", error);
        return "00:00";
    }
};

// Watch for route changes to reload appointments
watch(
    () => route.params.id,
    (newPatient) => {
        if (newPatient) {
            getAppointments(currentFilter.value);
        }
    }
);

onMounted(() => {
    getAppointmentsStatus();
    getAppointments();
});
</script>

<template>
    <div class="appointment-page">
        <!--     page-header -->
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <!-- Actions toolbar -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class=" rounded-pill hidden">
                            </h2>

                            <!-- Status Filters -->
                            <div class="btn-group" role="group" aria-label="Appointment status filters">
                                <button v-for="status in statuses" :key="status.name"
                                    @click="getAppointments(status.value)" :class="[
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

                        <div class="card shadow-sm">
                            <div class="card-body">


                                <div class="table-responsive">
                                    <table class="table table-hover  table-bordered">
                                        <thead class="table-primary">
                                            <tr>
                                                <th scope="col" class="text-center">#</th>
                                                <th scope="col">Patient Full Name</th>
                                                <th scope="col">Date of Birth</th>
                                                <th scope="col">Phone</th>
                                                <th scope="col">Doctor</th>
                                                <th scope="col">Date</th>
                                                <th scope="col">Time</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="appointments.length === 0">
                                                <td colspan="8" class="text-center text-muted py-4">No appointments
                                                    found</td>
                                            </tr>
                                            

                                            <tr v-else v-for="(appointment, index) in appointments"

                                                :key="appointment.id" :class="appointment.status.color">
                                                <td class="text-center align-middle">{{ index + 1 }}</td>
                                                <td class="align-middle">{{ appointment.patient_first_name }} {{
                                                    appointment.patient_last_name }}</td>
                                                <td class="align-middle">{{ appointment.patient_Date_Of_Birth }}</td>
                                                <td class="align-middle">{{ appointment.phone }}</td>
                                                <td class="align-middle">{{ appointment.doctor_name }}</td>
                                                <td>{{ formatDate(appointment.appointment_date) }}</td>
                                                <td>{{ formatTime(appointment.appointment_time) }}</td>
                                                <td class="align-middle">
                                                    <h7 class=" d-flex align-items-center" href="#">
                                                        <span class="status-indicator"
                                                            :class="appointment.status.color"></span>
                                                        <i
                                                            :class="[`text-${appointment.status.color}`, appointment.status.icon]"></i>
                                                        <span class="status-text rounded-pill fw-bold"
                                                            :class="[`text-${appointment.status.color}`, 'fs-6', 'ml-2']">
                                                            {{ appointment.status.name }}
                                                        </span>
                                                    </h7>
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
    </div>
</template>
<style scoped>
.bg-gradient {
    background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}
</style>