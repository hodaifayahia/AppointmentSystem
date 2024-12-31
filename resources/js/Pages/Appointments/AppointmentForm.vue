<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from './PatientSearch.vue';
import AvailableAppointments from './AvailableAppointments.vue';
import NextAppointmentDate from './NextAppointmentDate.vue';
import AppointmentCalendar from './AppointmentCalendar.vue';

const route = useRoute();
const router = useRouter(); // Import the router for navigation
const nextAppointmentDate = ref('');
const searchQuery = ref('');

// Props passed from the parent component
const props = defineProps({
  editMode: { type: Boolean, default: false },
  doctorId: { type: Number, default: null }
});

// Reactive form data
const form = reactive({
  first_name: '',
  last_name: '',
  days: '',
  description: '',
  phone: '',
  doctor_id: props.doctorId || 0,
  appointment_time: '',
  status: 0,
  selectionMethod: 'days', // Default to 'By Days'
  appointment_date: '', // Store the selected date from the calendar
});

// Handle patient selection
const handlePatientSelect = (patient) => {
  form.first_name = patient.firstname;
  form.last_name = patient.lastname;
  form.phone = patient.phone;
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

// Handle time selection from TimeSlotSelector
const handleTimeSelected = (time) => {
  form.appointment_time = time;  // Store selected time in `appointment_time`
};

// Handle form submission
const handleSubmit = async () => {
  try {
    const response = await axios.post('/api/appointments', form);
    console.log('Appointment created:', response.data);
    
    // Redirect to the appointment list for the doctor
    router.push({ name: 'admin.appointments', params: { doctorId: form.doctor_id } });
    toastr.success('Appointment created successfully');
  } catch (error) {
    console.error('Error creating appointment:', error);
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
  if (props.editMode) {
    const { data } = await axios.get(`/api/appointments/${route.params.id}/edit`);
    Object.assign(form, data);
  }
});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <!-- Patient Search Component -->
    <PatientSearch v-model="searchQuery" @patientSelected="handlePatientSelect" />

    <!-- Available Appointments Component -->
    <AvailableAppointments :doctorId="props.doctorId"   @dateSelected="handleDateSelected"
    @timeSelected="handleTimeSelected" />

    <!-- Appointment Method Selection -->
    <div class="form-group mb-4">
      <label for="selectionMethod" class="form-label">Select Appointment Method</label>
      <select 
        id="selectionMethod" 
        v-model="form.selectionMethod" 
        class="form-control"
      >
        <option value="days">By Days</option>
        <option value="calendar">By Calendar</option>
      </select>
    </div>

    <!-- Show 'By Days' Option -->
    <NextAppointmentDate
      v-if="form.selectionMethod === 'days'"
      :doctorId="props.doctorId"
      :initialDays="form.days"
      @update:days="handleDaysChange"
      @dateSelected="handleDateSelected"
      @timeSelected="handleTimeSelected"
    />

    <!-- Show Calendar if 'By Calendar' Option is Selected -->

    <AppointmentCalendar
      v-if="form.selectionMethod === 'calendar'"
      :doctorId="props.doctorId"
      @timeSelected="handleTimeSelected"
      @dateSelected="handleDateSelected"
    />
    <div class="form-group mb-4">
      <label for="description" class="form-label">Description</label>
      <textarea 
        id="description" 
        v-model="form.description" 
        class="form-control" 
        rows="3"
        placeholder="Enter appointment details..."
      ></textarea>
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
/* Styles for the form and selection method */
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
