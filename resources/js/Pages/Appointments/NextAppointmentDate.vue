<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { formatDateHelper } from '@/Components/helper.js';
import TimeSlotSelector from './TimeSlotSelector.vue';  // Assuming you have this component
import SimpleCalendar from './SimpleCalendar.vue';

// Props passed from parent component
const props = defineProps({
  doctorId: { type: Number, required: true },
  initialDays: { type: Number, default: 0 }
});

// Emit event to parent component
const emit = defineEmits(['update:days','update:range', 'dateSelected' , 'timeSelected']);

const days = ref(props.initialDays); // Days input
const nextAppointmentDate = ref(''); // Next available appointment date
const period = ref(''); // Period information for the appointment
const selectedAppointment = ref(null); // To store the selected appointment
const range = ref(0);

const isForcingAppointment = ref(false);

const forceAppointment = () => {
  isForcingAppointment.value = true;
};

const handleDateTimeSelected = (dateTime) => {
  emit('dateSelected', dateTime.date); // Emit date
  emit('timeSelected', dateTime.time); // Emit time
};
// Function to check availability based on the `days` value
const checkAvailability = async () => {
  if (!days.value) {
    nextAppointmentDate.value = '';
    period.value = '';
    selectedAppointment.value = null; // Reset selected appointment
    emit('dateSelected', null); // Emit null if no appointment is selected
    return;
  }

  try {
   
    // Fetch availability from the API
    const response = await axios.get('/api/appointments/checkAvailability', {
      params: { 
        days: days.value, 
        doctor_id: props.doctorId, 
        range: range.value , // Pass the range to the API
        include_slots: true

      }
    });
    
    nextAppointmentDate.value = response.data.next_available_date;
    console.log(nextAppointmentDate.value);
    period.value = response.data.period || [];
    emit('dateSelected', nextAppointmentDate.value); // Emit the selected date to parent
  } catch (err) {
    if (err.response?.status === 404) {
      err.value = 'No available slots found within the specified range. Try increasing the range or selecting a different date.';
    } else {
      err.value = 'An error occurred while fetching time slots.';
    }
  }
};


// Handle the time slot selection
const handleTimeSelected = (time) => {
  emit('timeSelected', time); // Emit the selected time to parent
};

// Watch for changes in the `days` value and trigger the check
watch(() => days.value, (newValue) => {
  emit('update:days', newValue); // Emit the updated days to parent
  checkAvailability(); // Recheck availability when days change
});

// Watch for changes in the `range` value and trigger the check
watch(() => range.value, (newValue) => {
  emit('update:range', newValue); // Emit the updated range to parent
  checkAvailability(); // Recheck availability when range change
});

// On mount, check availability if `days` is provided
onMounted(() => {
  if (days.value > 0) {
    checkAvailability(); // Check availability when component mounts
  }
});
</script>

<template>
  <div class="w-100" style="width: 200px;">
    <label for="range" class="text-muted">Range (Optionall)</label>
    <input 
      class="form-control" 
      type="number" 
      id="range" 
      placeholder="Enter the range" 
      v-model="range" 
      @input="checkAvailability"  
    />
  </div>

  <div class="form-group mb-4">
    <label for="days" class="text-muted">Days</label>
    <input
      type="number"
      v-model="days"
      class="form-control"
      id="days"
      placeholder="Enter number of days"
    />
    
    <div v-if="nextAppointmentDate" class="mt-2 text-info">
      Next appointment will be on: {{ formatDateHelper(nextAppointmentDate) }}
      <p>{{ period }}</p>
    </div>
    
    <div v-else-if="!nextAppointmentDate && days > 0" class="mt-2 text-center">
  <p class="text-danger">There are no slots available.</p>
  <button @click="forceAppointment" class="btn btn-outline-secondary mt-2 mb-2">Force Appointment</button>
  <div v-if="isForcingAppointment">
    <SimpleCalendar @dateTimeSelected="handleDateTimeSelected" />
  </div>
</div>
  </div>

  <!-- If next appointment date is available, show the TimeSlotSelector component -->
  <TimeSlotSelector
    v-if="nextAppointmentDate"
    :date="nextAppointmentDate"
    :range="range"
    @timeSelected="handleTimeSelected"
    :doctorid="props.doctorId"
    class="mt-4"
  />
</template>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.text-info {
  color: #17a2b8;
}

input[type="number"] {
  width: 100%;
}
</style>
