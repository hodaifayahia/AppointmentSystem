<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import TimeSlotSelector from './TimeSlotSelector.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  doctorId: { type: Number, required: true },
  days: { type: Number, default: null },
  date: { type: Number, default: null }
});

const emit = defineEmits(['timeSelected', 'dateSelected']);
const availableSlots = ref({});
const selectedDate = ref(null);

// Format the selected date as yyyy-MM-dd for API
const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  const date = selectedDate.value;
  return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
});

// Custom date formatter for display
const formatDate = (date) => {
  if (!date) return '';
  return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
};

const fetchSlots = async () => {
  try {
    const response = await axios.get('/api/appointments/ForceSlots', {
      params: {
        date: props.date ? props.date : null,
        days: props.date ? null : props.days,
        doctor_id: props.doctorId
      }
    });

    availableSlots.value = {
      gap_slots: response.data.gap_slots || [],
      additional_slots: response.data.additional_slots || [],
      next_available_date: response.data.next_available_date ? new Date(response.data.next_available_date) : null
    };

    // Set the selected date to the next available date if it exists
    selectedDate.value = availableSlots.value.next_available_date;
    emit('dateSelected', formattedDate.value); // Emit the formatted date to the parent

  } catch (error) {
    console.error('Error fetching slots:', error);
    availableSlots.value = {};
    selectedDate.value = null;
  }
};

const handleTimeSelected = (time) => {
  emit('timeSelected', time); // Emit the selected time to parent
};

// Combine gap and additional slots for TimeSlotSelector
const allSlots = computed(() => {
  return [...(availableSlots.value.gap_slots || []), ...(availableSlots.value.additional_slots || [])];
});

// Fetch slots on component mount or when props change
watch(() => [props.days, props.doctorId], fetchSlots, { immediate: true });

// Function to reset the date selection
const resetDateSelection = () => {
  selectedDate.value = null;
  availableSlots.value = {};
};
</script>

<template>
  <div class="appointment-selection">
    <div class="row">
      <div class="col">
        <div class="mb-3">
          <label for="datepicker" class="form-label">Select Date</label>
          <Datepicker 
            v-model="selectedDate" 
            id="datepicker" 
            :enable-time-picker="false"
            :format-locale="{ code: 'en-GB', monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] }"
            :auto-apply="true"
            :format="(date) => formatDate(date)"
            style="display: block; width: 100%;"
          />
        </div>
      </div>
    </div>

    <div v-if="selectedDate" class="card mb-3 shadow-sm">
      <div class="card-body">
        <TimeSlotSelector
          :date="formattedDate" 
          :forceAppointment="true"
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