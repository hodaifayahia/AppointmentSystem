<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from './PatientSearch.vue';
import AvailableAppointments from './AvailableAppointments.vue';
import NextAppointmentDate from './NextAppointmentDate.vue';
import AppointmentCalendar from './AppointmentCalendar.vue';
import { useToastr } from '../../Components/toster';

const route = useRoute();
const router = useRouter();
const nextAppointmentDate = ref('');
const searchQuery = ref('');
const toastr = useToastr();
const isEmpty = ref(false);
const importanceLevels = ref([]);

const props = defineProps({
  editMode: { type: Boolean, default: false },
  doctorId: { type: Number, default: null },
  appointmentId: { type: Number, default: null }
});

const form = reactive({
  id: null,
  first_name: '',
  patient_id: null,
  last_name: '',
  patient_Date_Of_Birth: '',
  phone: '',
  doctor_id: props.doctorId,
  appointment_date: '',
  appointment_time: '',
  description: '',
  description: '',
  addToWaitlist: false, // Default to false
  importance: 1, // Default importance level
  status: {},
});
const fetchAppointmentData = async () => {
  if (props.editMode && props.appointmentId) {
    try {
      const response = await axios.get(`/api/appointments/${props.doctorId}/${props.appointmentId}`);
      if (response.data.success) {
        const appointment = response.data.data;
        // Populate form with appointment data
        form.id = appointment.id;
        form.first_name = appointment.first_name;
        form.patient_id = appointment.patient_id;
        form.last_name = appointment.last_name;
        form.patient_Date_Of_Birth = appointment.patient_Date_Of_Birth;
        form.phone = appointment.phone;
        form.doctor_id = props.doctorId;
        form.appointment_date = appointment.appointment_date;
        form.appointment_time = appointment.appointment_time;
        form.description = appointment.description;
        form.addToWaitlist = appointment.addToWaitlist; // Populate waitlist field
        form.status = appointment.status;

        // Set the search query to potentially match the patient's name
        searchQuery.value = `${appointment.first_name} ${appointment.last_name} ${appointment.patient_Date_Of_Birth} ${appointment.phone}`;
      }
    } catch (error) {
      console.error('Failed to fetch appointment data:', error);
    }
  }
};
// Fetch importance enum values
const fetchImportanceEnum = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceLevels.value = response.data;
};
const isWaitListEmpty = async () => {
  const response = await axios.get('/api/waitlist/empty');
  isEmpty.value = response.data.data;
  
};
// Handle patient selection
const handlePatientSelect = (patient) => {

  form.first_name = patient.first_name;
  form.last_name = patient.last_name;
  form.patient_Date_Of_Birth = patient.dateOfBirth;
  form.phone = patient.phone;
  form.patient_id = patient.id;
};


// Handle days change from "By Days"
const handleDaysChange = (days) => {
  form.days = days;
};

// Handle date selection from calendar
const handleDateSelected = (date) => {

  form.appointment_date = date; // Store selected date in `appointment_date`
  nextAppointmentDate.value = date; // Optionally display it
};
if (props.editMode && props.appointmentId) {
  // Assuming you've fetched appointment data and form.patient_id is set
  searchQuery.value = `${form.first_name} ${form.last_name} ${form.patient_Date_Of_Birth} ${form.phone}`;
}

// Handle time selection from TimeSlotSelector
const handleTimeSelected = (time) => {
  form.appointment_time = time;  // Store selected time in `appointment_time`
};

const handleSubmit = async (values, { setErrors }) => {
  try {
    const method = props.editMode ? 'put' : 'post';
    const url = props.editMode
      ? `/api/appointments/${props.appointmentId}`
      : '/api/appointments';

    const response = await axios[method](url, form);
    console.log(`${props.editMode ? 'Appointment updated:' : 'Appointment created:'}`, response.data);

    // Redirect to the appointment list for the doctor
    router.push({ name: 'admin.appointments', params: { doctorId: form.doctor_id } });
    toastr.success(`${props.editMode ? 'Appointment updated' : 'Appointment created'} successfully`);
  } catch (error) {
    console.error(`${props.editMode ? 'Error updating appointment:' : 'Error creating appointment:'}`, error);
    setErrors({ form: 'An error occurred while processing your request' });
  }
};


// Reset selection when method changes or after submission
const resetSelection = () => {
  if (form.selectionMethod === 'days') {
    form.appointment_date = '';
    nextAppointmentDate.value = '';
  } else {
    form.days = '';
  }
};

// Watch for changes in selectionMethod to reset appropriate fields
watch(() => form.selectionMethod, resetSelection);

// Load existing appointment data in edit mode
onMounted(async () => {
  fetchImportanceEnum();
  fetchAppointmentData();
  isWaitListEmpty();

});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <!-- Patient Search Component -->
    <PatientSearch v-model="searchQuery" :patientId="form.patient_id" @patientSelected="handlePatientSelect" />

    <!-- Available Appointments Component -->
    <AvailableAppointments :waitlist="false" :isEmpty="isEmpty" :doctorId="props.doctorId" @dateSelected="handleDateSelected"
      @timeSelected="handleTimeSelected" />

    <!-- Appointment Method Selection -->
    <div class="form-group mb-4">
      <label for="selectionMethod" class="form-label">Select Appointment Method</label>
      <select id="selectionMethod" v-model="form.selectionMethod" class="form-control">
        <option value="days">By Days</option>
        <option value="calendar">By Calendar</option>
      </select>
    </div>

    <!-- Show 'By Days' Option -->
    <NextAppointmentDate v-if="form.selectionMethod === 'days'" :doctorId="props.doctorId" :initialDays="form.days"
      @update:days="handleDaysChange" @dateSelected="handleDateSelected" @timeSelected="handleTimeSelected" />

    <!-- Show Calendar if 'By Calendar' Option is Selected -->

    <AppointmentCalendar v-if="form.selectionMethod === 'calendar'" :doctorId="props.doctorId"
      @timeSelected="handleTimeSelected" @dateSelected="handleDateSelected" />
    <!-- Waitlist Checkbox -->
    <div class="form-group mb-4">
      <label for="addToWaitlist" class="form-label ">Add to Waitlist</label>
      <input type="checkbox" id="addToWaitlist" v-model="form.addToWaitlist" class="form-check-input" />
    </div>
    <div class="form-group mb-4">
      <label for="description" class="form-label">Description</label>
      <textarea id="description" v-model="form.description" class="form-control" rows="3"
        placeholder="Enter appointment details..."></textarea>
    </div>
    <!-- Submit Button -->
    <div class="form-group">
      <button type="submit" class="btn btn-primary rounded-pill">
        {{ props.editMode ? 'Update Appointment' : 'Create Appointment' }}
      </button>
    </div>
  </Form>
</template>
<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border-radius: 4px;
}

.form-check-input {
  margin-left: 10px
}

.btn {
  padding: 0.8rem 1.5rem;
  font-size: 16px;
}

.text-muted {
  color: #6c757d;
}

.rounded-pill {
  border-radius: 50px;
}

.no-slots {
  text-align: center;
  margin: 2rem 0;
}

.no-slots button {
  width: 200px;
}
</style>
