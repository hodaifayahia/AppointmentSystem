<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Field, Form } from 'vee-validate';
import AvailableAppointments from '../../Pages/Appointments/AvailableAppointments.vue';
import { useToastr } from '../../Components/toster';

// Props
const props = defineProps({
    showModal: {
        type: Boolean,
        required: true,
    },
    waitlist: {
        type: Object,
        default: null,
    },
    editMode: { type: Boolean, default: false },
});

// Emits
const emit = defineEmits(['close', 'appointmentUpdated']);

// Router
const router = useRouter();

// Toastr
const toastr = useToastr();

// Refs
const nextAppointmentDate = ref('');
const importanceLevels = ref([]);
const doctors = ref([]);

// Reactive form
const form = reactive({
    patient_id: props.waitlist?.patient_id || null,
    doctor_id: props.waitlist?.doctor_id || null,
    appointment_date: '',
    appointment_time: '',
    description: '',
    addToWaitlist: false,
    appointmentId: props.waitlist?.appointmentId || null,
    importance: null,
    status: {},
});

const fetchDoctors = async () => {
  if (!props.waitlist?.doctor_id) {
    try {
      const response = await axios.get(`/api/doctors/specializations/${props.waitlist.specialization_id}`);
      doctors.value = response.data.data;
    } catch (error) {
      console.error('Failed to fetch doctors:', error);
    }
  }
};

// Handle date selection from calendar
const handleDateSelected = (date) => {
    form.appointment_date = date; // Store selected date in `appointment_date`
    nextAppointmentDate.value = date; // Optionally display it
};

// Handle time selection from TimeSlotSelector
const handleTimeSelected = (time) => {
    form.appointment_time = time; // Store selected time in `appointment_time`
};

// Fetch importance enum values
const fetchImportanceEnum = async () => {
    try {
        const response = await axios.get('/api/importance-enum');
        importanceLevels.value = response.data;
    } catch (error) {
        console.error('Failed to fetch importance levels:', error);
    }
};

// Handle form submission
const handleSubmit = async (values, { setErrors }) => {
    try {
        // If the appointment is created from a waitlist, move the waitlist entry to appointments
        if (props.waitlist?.id) {
            await axios.post(`/api/waitlists/${props.waitlist.id}/add-to-appointments`, {
                doctor_id: form.doctor_id,
                waitlist_id: props.waitlist.id,
                patient_id: form.patient_id,
                appointment_date: form.appointment_date,
                appointment_time: form.appointment_time,
                appointmentId: form.appointmentId,
                notes: form.description,
            });
            console.log('Waitlist entry moved to appointments:', props.waitlist.id);
        } else {
            // Create a new appointment
            const response = await axios.post('/api/appointments', form);
            console.log('Appointment created:', response.data);
        }

        toastr.success(`${props.editMode ? 'Appointment updated' : 'Appointment created'} successfully`);
        emit('close'); // Close the modal after submission
        emit('appointmentUpdated');
    } catch (error) {
        console.error(`${props.editMode ? 'Error updating appointment:' : 'Error creating appointment:'}`, error);
        setErrors({ form: 'An error occurred while processing your request' });
    }
};

// Watch for changes in doctor_id
watch(() => form.doctor_id, (newDoctorId) => {
    if (newDoctorId) {
        // Trigger any necessary actions when the doctor changes
        form.doctor_id = newDoctorId;
    }
});

// Load data when component is mounted
onMounted( () => {
    fetchImportanceEnum();
    fetchDoctors();
});

// Close modal
const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div class="modal fade" :class="{ show: showModal }" tabindex="-1" aria-labelledby="appointmentModalLabel"
        aria-hidden="true" v-if="showModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ editMode ? 'Edit Appointment' : 'Create New Appointment' }}</h5>
                    <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <Form @submit="handleSubmit" v-slot="{ errors }">
                        <!-- Doctor Dropdown -->
                        <div class="mb-3" v-if="!props.waitlist?.doctor_id">
                            {{ doctors.length }} <!-- To check if array has content -->
                            <label for="doctor_id" class="form-label">Select Doctor</label>

                            <Field as="select" id="doctor_id" name="doctor_id" v-model="form.doctor_id"
                                class="form-control" required>
                                <option value="" disabled>Select a doctor</option>
                                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                    {{ doctor.name }}
                                </option>
                            </Field>
                            <span class="text-sm invalid-feedback">{{ errors.doctor_id }}</span>
                        </div>

                        <!-- Available Appointments Component -->
                        <AvailableAppointments :waitlist="true" :doctorId="form.doctor_id" @dateSelected="handleDateSelected"
                            @timeSelected="handleTimeSelected" />

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <Field as="textarea" id="description" name="description" v-model="form.description"
                                class="form-control" rows="4" placeholder="Enter appointment details..." />
                            <span class="text-sm invalid-feedback">{{ errors.description }}</span>
                        </div>

                        <!-- Submit Button -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary">
                                {{ editMode ? 'Update Appointment' : 'Create Appointment' }}
                            </button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal.show {
    display: block;
    background-color: rgba(0, 0, 0, 0.5);
}

.invalid-feedback {
    display: block;
    color: red;
    font-size: 0.875rem;
}
</style>