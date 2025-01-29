<!-- DateSelector.vue -->
<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import TimeSlotSelector from './TimeSlotSelector.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import SimpleCalendar from './SimpleCalendar.vue'; // Import the SimpleCalendar component

const props = defineProps({
  doctorId: {
    type: Number,
    required: true
  },
});

const emit = defineEmits(['timeSelected', 'dateSelected']);
const selectedDate = ref(null);
const availableSlots = ref([]);
const nextAvailableDate = ref(null);
const showTimeSlots = ref(false);
const isForcingAppointment = ref(false); // New state variable

const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return `${selectedDate.value.getFullYear()}-${(selectedDate.value.getMonth() + 1).toString().padStart(2, '0')}-${selectedDate.value.getDate().toString().padStart(2, '0')}`;
});

const checkDateAvailability = async () => {
  if (!selectedDate.value) {
    availableSlots.value = [];
    emit('dateSelected', null);
    return;
  }

  try {
    const response = await axios.get('/api/appointments/checkAvailability', {
      params: {
        date: formattedDate.value,
        doctor_id: props.doctorId,
        include_slots: false
      }
    });

    nextAvailableDate.value = response.data.next_available_date;
    showTimeSlots.value = formattedDate.value === response.data.next_available_date;
    emit('dateSelected', formattedDate.value);

  } catch (error) {
    console.error('Error checking availability:', error);
    showTimeSlots.value = false;
    nextAvailableDate.value = null;
  }
};

const handleTimeSelected = (time) => {
  emit('timeSelected', time);
};

const handleAvailabilityChecked = ({ hasSlots, nextAvailableDate: nextDate }) => {
  if (!hasSlots && nextDate) {
    nextAvailableDate.value = nextDate;
  }
};
const handleDateSelected = (date) => {
  console.log(`Selected date: ${date}`);
  
  emit('dateSelected', date);; // Emit the selected time to parent
};

const resetDateSelection = () => {
  selectedDate.value = null;
  availableSlots.value = [];
  showTimeSlots.value = false;
  nextAvailableDate.value = null;
  isForcingAppointment.value = false; // Reset forcing state
};

const forceAppointment = () => {
  isForcingAppointment.value = true; // Set forcing state to true
};

const isDateDisabled = (date) => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return date < today;
};

watch(selectedDate, checkDateAvailability);
</script>

<template>
  <div class="">
    <div class="mb-3">
      <label for="datepicker" class="form-label">Select Date</label>
      <Datepicker
        v-model="selectedDate"
        id="datepicker"
        :enable-time-picker="false"
        :disabled-dates="isDateDisabled"
        :min-date="new Date()"
        style="display: block; width: 100%;"
      />
    </div>

    

    <div v-if="selectedDate && showTimeSlots" class="card mb-3 shadow-sm">
      <div class="card-body">
        <TimeSlotSelector
          :date="formattedDate"
          :doctorid="props.doctorId"
          @timeSelected="handleTimeSelected"
          @availabilityChecked="handleAvailabilityChecked"
          class="mt-3"
        />
        <button @click="resetDateSelection" class="btn btn-outline-secondary btn-sm mt-3">
          Reset Selection
        </button>
      </div>
    </div>

    <!-- Show this when there are no available dates -->
    <div v-else-if="!nextAvailableDate" class="mt-2 text-center">
      <p class="text-danger">There are no slots available.</p>
      <button @click="forceAppointment" class="btn btn-outline-secondary mt-2 mb-2">Force Appointment</button>
      <div v-if="isForcingAppointment">
        <SimpleCalendar 
          :date="formattedDate" 
          :doctorId="props.doctorId"
          @timeSelected="handleTimeSelected"
          @dateSelected="handleDateSelected" 
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.appointment-selection {
  max-width: 600px;
  margin: 0 auto;
}

.card {
  border: none;
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-control {
  border-radius: 8px;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.2rem;
}

h6 {
  color: #333;
  font-weight: 600;
}
</style>