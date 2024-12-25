<script setup>
import axios from 'axios';
import { ref, watch, onMounted } from 'vue';

// Remove the static doctor data definition
// const doctor = {
//   start_time: '09:00', // Example start time
//   end_time: '17:00',   // Example end time
//   time_slots: 20       // Slot duration in minutes
// };

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  doctorid: {
    type: Object,
    required: true,
  }
});

const doctor = ref({}); // Now using ref for reactive data

// get doctor data
const getDoctorData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(`/api/doctors/${props.doctorid}`);
    const doctorData = response.data.data;
    doctor.value  = response.data.data;
    console.log('Fetched doctor data:', doctor.value);
    
    // Map the response to the structure expected by calculateTimeSlots
    doctor.value = {
      start_time: doctorData.start_time,
      end_time: doctorData.end_time,
      number_of_patients_per_day: doctorData.number_of_patients_per_day
    };
    console.log('Fetched doctor data:', doctor.value);

    // Since time_slots is null, you might want to set a default or handle this case:
    if (!doctor.value.time_slots) {
      doctor.value.time_slots = 30; // Default slot duration, adjust as needed
    }

    await fetchTimeSlots();
  } catch (err) {
    console.error('Error fetching doctor data:', err);
    error.value = 'Failed to load doctor data';
  } finally {
    loading.value = false;
  }
};
const emit = defineEmits(['update:modelValue']);
const slots = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedTime = ref(props.modelValue);
const calculateTimeSlots = (doctor) => {
  const calculatedSlots = [];
  
  // Parse the doctor's start and end time strings
  const [startHour, startMinute] = doctor.start_time.split(':').map(Number);
  const [endHour, endMinute] = doctor.end_time.split(':').map(Number);
  
  // Convert start and end time to total minutes
  const startMinutes = startHour * 60 + startMinute;
  const endMinutes = endHour * 60 + endMinute;
  const totalMinutes = endMinutes - startMinutes;
  
  if (doctor.number_of_patients_per_day) {
    // Calculate the exact duration for each slot based on total time and number of patients
    const slotDuration = Math.floor(totalMinutes / doctor.number_of_patients_per_day);
    let remainderTime = totalMinutes % doctor.number_of_patients_per_day;

    for (let i = 0; i < doctor.number_of_patients_per_day; i++) {
      let currentDuration = slotDuration;
      
      // Add any extra minutes to the slots to distribute the remainder evenly
      if (remainderTime > 0) {
        currentDuration += 1;
        remainderTime--;
      }

      const currentMinutes = startMinutes + (i * currentDuration);

      // Ensure we don't exceed end time, but we need all slots, so we'll cap the last one if necessary
      let hours = Math.floor(currentMinutes / 60).toString().padStart(2, '0');
      let minutes = (currentMinutes % 60).toString().padStart(2, '0');
      
      // If this is the last slot and adding the full duration would exceed end time, adjust it
      if (i === doctor.number_of_patients_per_day - 1 && currentMinutes + currentDuration > endMinutes) {
        minutes = endMinute.toString().padStart(2, '0');
        hours = endHour.toString().padStart(2, '0');
      }

      const timeString = `${hours}:${minutes}`;
      calculatedSlots.push({
        time: timeString,
        available: true
      });
    }
  } else {
    console.warn('Cannot generate time slots: number_of_patients_per_day is not defined');
  }

  return calculatedSlots;
};
// Manually set static slots
const fetchTimeSlots = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Use the dynamic `doctor` data to calculate slots
    slots.value = calculateTimeSlots(doctor.value);
  } catch (err) {
    console.error('Error generating time slots:', err);
    error.value = 'Failed to generate time slots';
    slots.value = [];
  } finally {
    loading.value = false;
  }
};

const selectTimeSlot = (time) => {
  selectedTime.value = time;
  emit('update:modelValue', time);
};

// Call `getDoctorData` immediately since we need dynamic data before fetching slots
onMounted(() => {
  getDoctorData();
});

// Watch for external modelValue changes
watch(() => props.modelValue, (newValue) => {
  selectedTime.value = newValue;
});
</script>

<template>
  <div class="time-slots-container">
    <label class="text-muted mb-2 block">Available Time Slots</label>
    
    <div v-if="loading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm me-2"></span>
      Loading available slots...
    </div>
    
    <div v-else-if="error" class="text-danger py-4">
      {{ error }}
    </div>
    
    <div v-else-if="slots.length === 0" class="py-4">
      No time slots available for this day.
    </div>
    
    <div v-else class="grid grid-cols-4 gap-2">
      <button
        v-for="slot in slots"
        :key="slot.time"
        type="button"
        class="btn"
        :class="{
          'btn-primary': selectedTime === slot.time,
          'btn-outline-secondary': selectedTime !== slot.time
        }"
        @click="selectTimeSlot(slot.time)"
      >
        {{ slot.time }}
      </button>
    </div>
    
    <div class="mt-4">
      <p v-if="selectedTime" class="text-success">
        Selected time: {{ selectedTime }}
      </p>
      <p v-else class="text-muted">
        Please select an available time slot
      </p>
    </div>
  </div>
</template>

<style scoped>
@keyframes spinner-border {
  to { transform: rotate(360deg); }
}

.time-slots-container {
  width: 100%;
}

.btn {
  padding: 10px 20px;
  border-radius: 5px;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-outline-secondary {
  background-color: white;
  color: #6c757d;
  border-color: #6c757d;
}

.grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.spinner-border {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid #ccc;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border 1s infinite linear;
}
</style>
