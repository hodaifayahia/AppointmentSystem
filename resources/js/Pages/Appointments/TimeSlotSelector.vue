<script setup>
import axios from 'axios';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  doctorid: {
    type: Number,
    required: true,
  },
  date: {
    type: String,
    required: true
  },
  deletedslots: {
    type: Boolean,
    default:false
  } ,
  range: {
    type: Number,
    default: 0
  },
  forceAppointment: {
    type: Boolean,
    default: false
  },
  days: {
    type: Number,
    default: 0
  }
});



const emit = defineEmits(['update:modelValue' , 'timeSelected']);

const slots = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedTime = ref(props.modelValue);
// Fetch time slots from backend
const fetchTimeSlots = async () => {
  loading.value = true;
  error.value = null;

  if (props.deletedslots) {
    // If deletedslots are provided, use them directly
    slots.value = Object.keys(props.deletedslots).map((key) => ({
      time: props.deletedslots[key],
      available: true,
    }));
    loading.value = false;
    return;
  }
  
  try {
    let response;
    if (props.forceAppointment) {

      response = await axios.get('/api/appointments/ForceSlots', {
        params: {
          days: props.days,  // Assuming days is needed for this endpoint, adjust if not
          doctor_id: props.doctorid
        }
      });
      console.log(response.data);
      
      
      // Handle response from /api/appointments/ForceSlots
      if (response.data.gap_slots || response.data.additional_slots) {
        slots.value = [
          ...(response.data.gap_slots || []),
          ...(response.data.additional_slots || [])
        ].map(time => ({
          time: time,
          available: true
        }));

        // If you want to handle 'next_available_date' or other data, do so here
        if (response.data.next_available_date) {
          console.log('Next available date:', response.data.next_available_date);
        }
      } else {
        error.value = 'No slots available for forced appointment.';
      }

    } else {
      // Normal slot fetching
      response = await axios.get('/api/appointments/checkAvailability', {
        params: {
          date: props.date,
          doctor_id: props.doctorid,
          range: props.range,
          include_slots: true,
        }
      });
      
      // Check if available_slots is an object (not an array)
      if (typeof response.data.available_slots === 'object' && response.data.available_slots !== null) {
        const slotsArray = [];
        if (Object.keys(response.data.available_slots).length === 0) {
          error.value = 'No available slots found within the specified range. Try increasing the range or selecting a different date.';
        } else {
          const timeSlots = Object.values(response.data.available_slots); // Get the time values as an array
          for (let i = 0; i < timeSlots.length; i++) {
            slotsArray.push({
              time: timeSlots[i],
              available: true
            });
          }
          slots.value = slotsArray;
        }
      } else {
        error.value = 'Unexpected data format';
      }
    }
    
  } catch (err) {
    if (err.response) {
    } else {
      error.value = 'An error occurred while fetching time slots.';
    }
  } finally {
    loading.value = false;
  }
};


const selectTimeSlot = (time) => {
  selectedTime.value = time;
  emit('update:modelValue', time); // Update v-model binding
  emit('timeSelected', time);     // Emit the custom event to the parent
};


// Fetch slots when component mounts
onMounted(() => {
  fetchTimeSlots();
});

// Watch for changes in props that affect slot fetching
watch([() => (props.date,props.range), () => props.doctorid], () => {
  fetchTimeSlots();
});

// Watch for external modelValue changes
watch(() => props.modelValue, (newValue) => {
  selectedTime.value = newValue;
  console.log('Selected time:', newValue);
  
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
    
    <div v-else-if="(slots.length === 0 )" class="py-4">
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