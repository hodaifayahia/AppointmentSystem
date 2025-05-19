<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import TimeSlotSelector from './TimeSlotSelector.vue';
import { formatDateHelper } from '@/Components/helper.js';

const props = defineProps({
  doctorId: {
    type: Number,
    required: true,
  },
  waitlist: {
    type: Boolean,
    required: false,
    default: false,
  },
  isEmpty:{
    type:Boolean,
    default:false
  }
});

const emit = defineEmits(['dateSelected', 'timeSelected']);

const availableAppointments = ref({
  canceled_appointments: [],
  normal_appointments: null,
});

const selectedAppointment = ref(null);
const selectedValue = ref('');
const availableTimes = ref([]);
const selectedType = ref('');
const timeSlotKey = ref(0);

// Fetch time slots for a specific date and type
const fetchTimeSlots = async (date, type) => {
  try {
    if (type === 'canceled') {
      const appointment = availableAppointments.value.canceled_appointments.find(
        app => formatDateHelper(app.date) === date
      );
      return appointment?.available_times || [];
    } else {
      const response = await axios.get('/api/appointments/checkAvailability', {
        params: {
          date: availableAppointments.value.normal_appointments.date,
          doctor_id: props.doctorId,
          include_slots: true,
        }
      });
      return response.data.available_slots || [];
    }
  } catch (error) {
    console.error('Error fetching time slots:', error);
    return [];
  }
};

// Fetch available appointments for the selected doctor
const fetchAvailableAppointments = async () => {
  try {
    const response = await axios.get('/api/appointments/available', {
      params: { doctor_id: props.doctorId },
    });
    console.log(response.data.canceled_appointments);
    
    availableAppointments.value = {
      canceled_appointments: response.data.canceled_appointments || [],
      normal_appointments: response.data.normal_appointments || null,
    };
  } catch (error) {
    console.error('Error fetching available appointments:', error);
  }
};

// Handle appointment selection
const selectAppointment = async () => {
  const [type, date] = selectedValue.value.split('|');
  selectedType.value = type;
  
  selectedAppointment.value = type === 'canceled' 
    ? availableAppointments.value.canceled_appointments.find(app => formatDateHelper(app.date) === date)
    : availableAppointments.value.normal_appointments;

  if (selectedAppointment.value) {
    const slots = await fetchTimeSlots(date, type);
    availableTimes.value = slots;
    timeSlotKey.value++; // Force TimeSlotSelector to remount
    emit('dateSelected', selectedAppointment.value.date);
  }
};

// Handle time selection
const handleTimeSelected = (time) => {
  emit('timeSelected', time);
};

// Watch for changes in selectedValue
watch(selectedValue, () => {
  if (selectedValue.value) {
    selectAppointment();
  }
});

// Watch for changes in doctorId
watch(() => props.doctorId, (newDoctorId) => {
  if (newDoctorId) {
    // Reset selected values
    selectedAppointment.value = null;
    selectedValue.value = '';
    availableTimes.value = [];
    timeSlotKey.value++; // Force TimeSlotSelector to remount

    // Fetch new appointments
    fetchAvailableAppointments();
  }
});

// Fetch appointments when the component is mounted
onMounted(fetchAvailableAppointments);
</script>
<template>
  <div class="form-group mb-4">
    <label class="text-muted mb-2">Available Appointment Dates</label>
    <div
      v-if="availableAppointments.canceled_appointments.length === 0 && (!availableAppointments.normal_appointments || availableAppointments.normal_appointments.length === 0)"
      class="text-muted p-3 border rounded"
    >
      No available appointments.
    </div>

    <div v-else>
      <select
        v-model="selectedValue"
        class="form-select form-control w-full mb-3"
      >
        <option disabled value="">Select an appointment</option>
        <optgroup v-if="(availableAppointments.canceled_appointments.length > 0 ) || (availableAppointments.canceled_appointments.length > 0 && !isEmpty) " label="Canceled Appointments">
          <option
            v-for="appointment in availableAppointments.canceled_appointments"
            :key="`canceled-${appointment.date}`"
            :value="'canceled|' + formatDateHelper(appointment.date)"
          >
            {{ formatDateHelper(appointment.date) }} (Canceled)
          </option>
        </optgroup>
        <optgroup v-if="availableAppointments.normal_appointments && !waitlist" label="Normal Appointments">
          <option
            :key="`normal-${availableAppointments.normal_appointments.date}`"
            :value="'normal|' + formatDateHelper(availableAppointments.normal_appointments.date)"
          >
            {{ formatDateHelper(availableAppointments.normal_appointments.date) }}
          </option>
        </optgroup>
      </select>
    </div>

    <TimeSlotSelector
      v-if="selectedAppointment"
      :key="timeSlotKey"
      :deletedslots="selectedType === 'canceled' ? availableTimes : []"
      :date="selectedAppointment.date"
      :doctorid="doctorId"
      :slots="selectedType === 'canceled' ? availableTimes : []"
      @timeSelected="handleTimeSelected"
      class="mt-4"
    />
  </div>
</template>