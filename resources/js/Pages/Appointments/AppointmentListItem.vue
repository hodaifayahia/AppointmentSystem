<script setup>
import { defineProps, defineEmits, ref, onMounted, watch, capitalize } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { useRouter } from 'vue-router';
import appointmentWaitlist from '../../Components/appointments/appointmentWaitlist.vue';
import AppointmentModal from '../../Components/appointments/AppointmentModal.vue';
import { useSweetAlert } from '../../Components/useSweetAlert';
import ReasonModel from '../../Components/appointments/ReasonModel.vue';

const swal = useSweetAlert();
const props = defineProps({
    appointments: {
        type: Array,
        required: true
    },
    error: {
        type: String,
        default: null
    },
    totalPages: {
        type: Number,
        required: true, // This makes the prop required
    },
    userRole: {
        type: String,
        required: true
    },
    doctorId: {
        type: String,
        required: false
    },
});

const emit = defineEmits(['getAppointments', "updateAppointment", 'updateStatus']);
const toastr = useToastr();
const router = useRouter();

const statuses = ref([]);
const error = ref(null);
const dropdownStates = ref({});
const searchQuery = ref("");
const isLoading = ref(false);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const localAppointments = ref(props.appointments);
const localPagination = ref(props.pagination);
const selectedDate = ref();
const previousDate = ref(null); // Store the previous date

const showAddModal = ref(false); // Add this line
const ShowReasonModel = ref(false);
const selectedAppointment = ref(null);
const isModalVisible = ref(false);
const appointmentId = ref(null);

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr', {
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

const getPatientFullName = (patient) => {
    // Validate input
    if (!patient || typeof patient !== 'object') {
        return 'N/A';
    }

    // Extract and sanitize properties
    const { Parent = '', patient_last_name = '', patient_first_name = '' } = patient;

    // Construct full name
    const fullName = [Parent, patient_last_name, patient_first_name]
        .filter(Boolean) // Remove empty strings
        .join(' ')       // Join with spaces

    // Capitalize the result (assuming capitalize is defined elsewhere)
    return fullName ? capitalize(fullName) : 'N/A';
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
const OpenReasoneModel = () => {
    ShowReasonModel.value = true;
}
const updateAppointmentStatus = async (appointmentId, newStatus, reason = null) => {
    try {
        const payload = { status: newStatus };

        // Only require reason when status is 2
        if (newStatus === 2) {
            if (!reason) {
                // Open reason modal if no reason provided
                selectedAppointment.value = { id: appointmentId };
                OpenReasoneModel();
                return;
            }
            payload.reason = reason;
        }

        await axios.patch(`/api/appointment/${appointmentId}/status`, payload);
        dropdownStates.value[appointmentId] = false;
        emit('updateStatus');
        emit('update-appointment');
        toastr.success('Appointment status updated successfully');
    } catch (err) {
        console.error('Error updating status:', err);
    }
};

const SubmitReasonValue = (reason) => {
    ShowReasonModel.value = false;
    if (selectedAppointment.value) {
        // Reuse updateAppointmentStatus with status 2 and provided reason
        updateAppointmentStatus(selectedAppointment.value.id, 2, reason);
    }
    selectedAppointment.value = null;
};

watch(() => props.appointments, (newVal) => {
    localAppointments.value = newVal;
}, { deep: true });

watch(() => props.pagination, (newVal) => {
    localPagination.value = newVal;
}, { deep: true });

const getAppointmentsStatus = async () => {
    try {
        const response = await axios.get(`/api/appointmentStatus/${props.doctorId}`);
        statuses.value = response.data;
    } catch (err) {
        error.value = 'Failed to load appointment statuses';
        console.error('Error:', err);
    }
};

const goToEditAppointmentPage = (appointment) => {
    router.push({
        name: 'admin.appointments.edit',
        params: { id: props.doctorId, appointmentId: appointment.id , specialization_id: appointment.specialization_id  }
    });
};
const openModal = (appointment) => {
    isModalVisible.value = true;
    appointmentId.value = appointment.id;
    emit('updateStatus');
    emit('update-appointment');
};

const closeModal = () => {
    isModalVisible.value = false;
    emit('updateStatus');
    emit('update-appointment');
};

const getStatusOption = (statusName) => {
    return statuses.value.find(option => option.name === statusName) ||
        { name: 'UNKNOWN', color: 'secondary', textColor: 'white', bgColor: 'bg-secondary' };
};


const deleteAppointment = async (appointmentid) => {
    try {
        // Show SweetAlert confirmation dialog using the configured swal instance
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        // If user confirms, proceed with deletion
        if (result.isConfirmed) {
            await axios.delete(`/api/appointments/${appointmentid}`);
            emit('updateAppointment');
            // Show success message
            swal.fire(
                'Deleted!',
                'appointment has been deleted.',
                'success'
            );

            // Emit event to notify parent component
            emit('doctorDeleted');
        }
    } catch (error) {
        // Handle error
        if (error.response?.data?.message) {
            swal.fire(
                'Error!',
                error.response.data.message,
                'error'
            );
        } else {
            swal.fire(
                'Error!',
                'Failed to delete Doctor.',
                'error'
            );
        }
    }
};

const openAddModal = () => {
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
    ShowReasonModel.value = false;
    showAddModal.value = false;
    isEditMode.value = false;
    selectedWaitlist.value = null;
};

const handleSave = (newWaitlist) => {
    console.log('New Waitlist:', newWaitlist);
    closeAddModal();
};

const handleUpdate = (updatedWaitlist) => {
    console.log('Updated Waitlist:', updatedWaitlist);
    closeAddModal();
};

const AddToWaitList = async (appointment) => {
    isEditMode.value = appointment.add_to_waitlist;
    selectedWaitlist.value = {
        ...appointment,
        patient_id: appointment.patient_id,
        doctor_id: props.doctorId, // Assuming doctorId is passed as a prop
        specialization_id: appointment.specialization_id, // Assuming this is available in the appointment object
        add_to_waitlist: appointment.add_to_waitlist, // Pass the add_to_waitlist flag
    };
    openAddModal();
};
const applyDateFilter = async () => {
    if (selectedDate.value) {
        isLoading.value = true;
        try {
            // Emit the selected date to the parent component for filtering
            emit('filterByDate', selectedDate.value);
        } catch (err) {
            error.value = 'Failed to filter appointments by date.';
        } finally {
            isLoading.value = false;
        }
    } else {
        // If no date is selected, emit null to clear the filter
        emit('filterByDate', null);
    }
};
const resetToPreviousDate = () => {
    selectedDate.value = null; // Reset to the previous date
    applyDateFilter(); // Reapply the filter with the previous date
};
// Watch for changes in selectedDate
watch(selectedDate, (newDate, oldDate) => {
    if (newDate !== oldDate) {
        previousDate.value = oldDate; // Store the previous date
    }
});
const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                isLoading.value = true;
                
                // If search query is empty or null, fetch appointments with current status
                if (searchQuery.value == null || searchQuery.value.trim() === '') {
                    emit('update-appointment');
                    return;
                }
                
                // Otherwise, perform the search
                const response = await axios.get(`/api/appointments/search`, {
                    params: {
                        query: searchQuery.value,
                        doctor_id: props.doctorId,
                    },
                });
                
                if (response.data) {
                    localAppointments.value = response.data.data;
                    localPagination.value = response.data.meta;
                    emit('getAppointments', response.data);
                }
            } catch (error) {
                console.error('Error searching appointments:', error);
                toastr.error('Failed to search appointments');
                // On error, revert to current status appointments
                emit('getAppointments');
            } finally {
                isLoading.value = false;
            }
        }, 500);
    };
})();

// Add a watch effect that handles empty query explicitly
watch(searchQuery, (newValue) => {
    if (!newValue || newValue.trim() === '') {
        // Clear timeout if exists
        if (timeout) clearTimeout(timeout);
        // Immediately fetch current status appointments
        emit('getAppointments');
    } else {
        debouncedSearch();
    }
});

watch(searchQuery, debouncedSearch);
onMounted(() => {
    getAppointmentsStatus();
});
</script>
<template>
    <div class="shadow-sm">
        <div class="w-100 p-4">
            <!-- Error Alert -->
            <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
            </div>

            <!-- Search and Date Filter -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
                <!-- Date Filter -->
                <div class="input-group w-25 w-md-25">
                    <input type="date" class="form-control rounded-start" v-model="selectedDate"
                        aria-label="Filter by date" />
                    <button class="btn btn-outline-primary" type="button" @click="applyDateFilter"
                        :disabled="isLoading">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click="resetToPreviousDate">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="input-group w-25 w-md-50">
                    <input type="text" class="form-control rounded-start" v-model="searchQuery"
                        placeholder="Search by patient name or date of birth" aria-label="Search" />
                    <button class="btn btn-outline-primary" type="button" :disabled="isLoading">
                        <i class="fas" :class="{ 'fa-search': !isLoading, 'fa-spinner fa-spin': isLoading }"></i>
                    </button>
                </div>
            </div>



            <!-- Appointment Table -->
            <div class="">
                <table class="table table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Parent </th>
                            <th scope="col">Phone</th>
                            <th scope="col">Date Of Birth</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Description</th>
                            <th scope="col">Status</th>
                            <th scope="col">Reason</th>
                            <th v-if="userRole === 'admin'" scope="col">created_by</th>
                            <th v-if="userRole === 'admin'" scope="col">canceled_by</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="appointments.length === 0">
                            <td colspan="10" class="text-center">No appointments found</td>
                        </tr>
                        <tr v-else v-for="(appointment, index) in appointments" :key="appointment.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ getPatientFullName(appointment) }}</td>
                            <td>{{ appointment.Parent }}</td>
                            <td>{{ appointment.phone }}</td>
                            <td>{{ formatDate(appointment.patient_Date_Of_Birth) }}</td>
                            <td>{{ formatDate(appointment.appointment_date) }}</td>
                            <td>{{ formatTime(appointment.appointment_time) }}</td>
                            <td>{{ appointment.description ?? "Null" }}</td>
                            <td>
                                <div class="dropdown" :class="{ 'show': dropdownStates[appointment.id] }">
                                    <button class="btn dropdown-toggle status-button" type="button"
                                        @click="toggleDropdown(appointment.id)">
                                        <span class="status-indicator"
                                            :class="getStatusOption(appointment.status?.name).color"></span>
                                        <span :class="`text-${appointment.status.color}`">
                                            <i :class="[`text-${appointment.status.color}`, appointment.status.icon]"
                                                class="fa-lg ml-1"></i>
                                            {{ getStatusText(appointment.status) }}
                                        </span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end" style="z-index: 100;"
                                        :class="{ 'show': dropdownStates[appointment.id] }">
                                        <li v-for="status in statuses" :key="status.name">
                                            <!-- Only show the status if it's "Done" (status.value === 4) and the user role is "doctor" -->
                                            <template v-if="props.userRole === 'doctor' && status.value === 4">
                                                <a class="dropdown-item d-flex align-items-center" href="#"
                                                    @click.prevent="updateAppointmentStatus(appointment.id, status.value)">
                                                    <span class="status-indicator" :class="status.color"></span>
                                                    <i :class="[`text-${status.color}`, status.icon]"></i>
                                                    <span class="status-text rounded-pill fw-bold"
                                                        :class="[`text-${status.color}`, 'fs-6']">
                                                        {{ status.name }}
                                                    </span>
                                                </a>
                                            </template>
                                            <!-- Show all statuses for other roles -->
                                            <template v-else-if="props.userRole !== 'doctor'">
                                                <a class="dropdown-item d-flex align-items-center" href="#"
                                                    @click.prevent="updateAppointmentStatus(appointment.id, status.value)">
                                                    <span class="status-indicator" :class="status.color"></span>
                                                    <i :class="[`text-${status.color}`, status.icon]"></i>
                                                    <span class="status-text rounded-pill fw-bold"
                                                        :class="[`text-${status.color}`, 'fs-6']">
                                                        {{ status.name }}
                                                    </span>
                                                </a>
                                            </template>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td >{{ appointment.reason ?? "__" }}</td>
                            <td v-if="userRole === 'admin'">{{ appointment.created_by ?? "__" }}</td>
                            <td v-if="userRole === 'admin'">{{ appointment.canceled_by ?? "__" }}</td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button @click="goToEditAppointmentPage(appointment)"
                                        class="btn btn-sm btn-outline-primary mr-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button v-if="props.userRole === 'admin' || props.userRole === 'doctor'"
                                        @click="openModal(appointment)" class="btn btn-sm btn-outline-primary mr-2">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>

                                    <button v-if="props.userRole === 'admin'" @click="deleteAppointment(appointment.id)"
                                        class="btn btn-sm btn-outline-danger mr-2">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <button @click="AddToWaitList(appointment)" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->

            <!-- Add/Edit Waitlist Modal -->
            <ReasonModel :show="ShowReasonModel" @close="closeAddModal" @submit="SubmitReasonValue" />
            <appointmentWaitlist :show="showAddModal" :editMode="isEditMode" :waitlist="selectedWaitlist"
                :add_to_waitlist="selectedWaitlist?.add_to_waitlist ?? false" @close="closeAddModal" @save="handleSave"
                @update="handleUpdate" />
            <AppointmentModal v-if="isModalVisible" :doctorId="props.doctorId" :appointmentId="appointmentId"
                :editMode="true" @close="closeModal" />
        </div>
    </div>
</template>

<style scoped>
/* Responsive Table */


/* Touch-Friendly Buttons */
.btn {
    padding: 0.5rem 1rem;
    font-size: 1rem;
}

/* Dropdown Adjustments */
.dropdown-menu {
    max-height: 200px;
    overflow-y: auto;
}

/* Status Indicator */
.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

/* Mobile-Specific Styles */
@media (max-width: 768px) {
    .input-group {
        width: 100% !important;
    }

    .dropdown-menu {
        position: absolute;
        right: 0;
        left: auto;
    }

    .btn-sm {
        padding: 0.5rem;
        font-size: 0.875rem;
    }

    .table th,
    .table td {
        white-space: nowrap;
    }
}
</style>