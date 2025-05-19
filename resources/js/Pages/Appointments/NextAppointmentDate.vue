<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { formatDateHelper } from '@/Components/helper.js';
import TimeSlotSelector from './TimeSlotSelector.vue';  // Assuming you have this component
import SimpleCalendar from './SimpleCalendar.vue';
import { useAuthStore } from '../../stores/auth';

// Props passed from parent component
const props = defineProps({
  doctorId: { type: Number, required: true },
  initialDays: { type: Number, default: 0 }
});

// Emit event to parent component
const emit = defineEmits(['update:days', 'update:range', 'dateSelected', 'timeSelected']);

const days = ref(props.initialDays); // Days input
const nextAppointmentDate = ref(''); // Next available appointment date
const period = ref(''); // Period information for the appointment
const selectedAppointment = ref(null); // To store the selected appointment
const range = ref(0);

const isForcingAppointment = ref(false);

const forceAppointment = () => {
  isForcingAppointment.value = true;
};


const authStore = useAuthStore();
const is_able_tO_force = ref(false);
onMounted(async () => {
  IsAbleToForce();

});


const IsAbleToForce = async () => {

  try {
    const response = await axios.get('/api/doctor-user-permissions/ability', {
      params: {
        doctor_id: props.doctorId,
        user_id: authStore.user.id,
      }
    });

    is_able_tO_force.value = response.data.data.is_able_to_force;

  } catch (error) {
    console.error('somthing happened:', error);

  }
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

    const response = await axios.get('/api/appointments/checkAvailability', {
      params: {
        days: days.value,
        doctor_id: props.doctorId,
        range: range.value, // Pass the range to the API
        include_slots: true

      }
    });
    console.log(response.data.data);

    nextAppointmentDate.value = response.data.next_available_date;
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
const handleDateSelected = (date) => {
  console.log(`Selected date: ${date}`);

  emit('dateSelected', date);; // Emit the selected time to parent
};
const handleTimeSelected = (time) => {
  console.log(`Selected time: ${time}`);
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
    <label for="days" class="text-muted">Days</label>
    <input type="number" v-model="days" class="form-control" id="days" placeholder="Enter number of days" />


    <div class="form-group mb-4">
      <label for="range" class="text-muted">Range (Optionall)</label>
      <input class="form-control" type="number" id="range" placeholder="Enter the range" v-model="range"
        @input="checkAvailability" />
    </div>

    <div v-if="nextAppointmentDate" class="mt-2 text-info">
      Next appointment will be on: {{ formatDateHelper(nextAppointmentDate) }}
      <p>{{ period }}</p>
    </div>


    <div v-else-if="!nextAppointmentDate && days > 0" class="mt-2 text-center">

      <div v-if="(is_able_tO_force || authStore.user.role === 'admin' || authStore.user.role === 'doctor' || authStore.user.role === 'SuperAdmin' ) ">
        <button @click="forceAppointment" class="btn btn-outline-secondary mt-2 mb-2">Force Appointment</button>
        <div v-if="isForcingAppointment">
          <SimpleCalendar :days="days" :doctorId="props.doctorId" @timeSelected="handleTimeSelected"
            @dateSelected="handleDateSelected" />
        </div>
      </div>
      <div v-else class="alert alert-info text-center w-50 m-auto"
        style="background-color: #5bc0de; border-radius: 5px; color: white; padding: 5px; ">
        <p class="text-white" style="font-weight: bold; font-size: 1.1em;">No slots available at the moment.</p>
      </div>
    </div>
  </div>

  <!-- If next appointment date is available, show the TimeSlotSelector component -->
  <TimeSlotSelector v-if="nextAppointmentDate" :date="nextAppointmentDate" :range="range"
    @timeSelected="handleTimeSelected" :doctorid="props.doctorId" class="mt-4" />
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
