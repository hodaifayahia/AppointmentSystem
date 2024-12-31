<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import TimeSlotSelector from './TimeSlotSelector.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  doctorId: { type: Number, required: true },
});
const emit = defineEmits(['timeSelected', 'dateSelected']);
const availableSlots = ref([]);
const selectedDate = ref("");

const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return `${selectedDate.value.getFullYear()}-${(selectedDate.value.getMonth() + 1).toString().padStart(2, '0')}-${selectedDate.value.getDate().toString().padStart(2, '0')}`;
});

const fetchSlots = async () => {
  if (!selectedDate.value) {
    availableSlots.value = [];
    emit('dateSelected', null); // Emit null when no date is selected

    return;
  }

  try {
    const response = await axios.get('/api/appointments/checkAvailability', {
      params: { date: formattedDate.value, doctor_id: props.doctorId ,include_slots: false
      }
    });
    
    console.log(response.data.data);
    
    // Assuming that 'next_appointment' is part of the response data
    availableSlots.value = response.data.next_appointment ? { next_appointment: response.data.next_appointment } : [];
    emit('dateSelected', formattedDate.value); // Emit selected date to the parent
    console.log('Available slots:', availableSlots.next_appointment);
    
  } catch (error) {
    console.error('Error fetching slots:', error);
    availableSlots.value = [];
  }
};
const handleTimeSelected = (time) => {
  console.log(`Selected time: ${time}`);
  emit('timeSelected', time); // Emit the selected time to parent

};

watch(selectedDate, fetchSlots, { immediate: true });

// Function to reset the date selection
const resetDateSelection = () => {
  selectedDate.value = null; // Reset to null or to new Date() if you want to default to today
  availableSlots.value = [];
};
</script>

<template>
  <div class="">
    <div class=" ">
      <div class="">
        <div class="mb-3">
          <label for="datepicker" class="form-label">Select Date</label>
          <Datepicker 
                v-model="selectedDate" 
                id="datepicker" 
                :enable-time-picker="false"
                style="display: block; width: 100%;"
/>
        </div>

        <div v-if="availableSlots.next_appointment" class="mt-3">
          <h6 class="mb-2">Next Available Appointment:</h6>
          <p class="mb-0">{{ availableSlots.next_appointment }}</p>
        </div>
      </div>
    </div>

    <div v-if="selectedDate" class="card mb-3 shadow-sm">
      <div class="card-body">
        <TimeSlotSelector
          :date="formattedDate"
          :doctorid="props.doctorId"
          @timeSelected="handleTimeSelected"
          class="mt-3"
        />
        <button @click="resetDateSelection" class="btn btn-outline-secondary btn-sm mt-3">Reset Selection</button>
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