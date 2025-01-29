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
    type: Array,
    default: () => []
  },
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

const emit = defineEmits(['update:modelValue', 'timeSelected', 'availabilityChecked']);

const slots = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedTime = ref(props.modelValue);

const fetchTimeSlots = async () => {
  loading.value = true;
  error.value = null;

  try {
    if (props.deletedslots.length > 0) {
      slots.value = props.deletedslots.map(time => ({
        time,
        available: true,
        isDeleted: true
      }));
      loading.value = false;
      return;
    }

    if (props.forceAppointment) {
      const response = await axios.get('/api/appointments/ForceSlots', {
        params: {
          days: props.days,
          doctor_id: props.doctorid,
          date:props.date
        }
      });
      
      if (response.data.gap_slots || response.data.additional_slots) {
        slots.value = [...(response.data.gap_slots || []), ...(response.data.additional_slots || [])]
          .map(time => ({
            time,
            available: true,
            isDeleted: false
          }));
      } else {
        error.value = 'No slots available for forced appointment.';
      }
      return;
    }

    const response = await axios.get('/api/appointments/checkAvailability', {
      params: {
        date: props.date,
        doctor_id: props.doctorid,
        range: props.range,
        include_slots: true,
      }
    });
    
    // Emit the availability check result to parent
    emit('availabilityChecked', {
      hasSlots: !!response.data.available_slots,
      nextAvailableDate: response.data.next_available_date
    });

    if (response.data.available_slots) {
      const availableSlots = Array.isArray(response.data.available_slots) 
        ? response.data.available_slots 
        : Object.values(response.data.available_slots);

      slots.value = availableSlots.map(time => ({
        time,
        available: true,
        isDeleted: false
      }));
    } else {
      error.value = 'No available slots for this date';
      slots.value = [];
    }
  } catch (err) {
    console.error('Error fetching time slots:', err);
    error.value = err.message || 'An error occurred while fetching time slots.';
    slots.value = [];
  } finally {
    loading.value = false;
  }
};

const selectTimeSlot = (time) => {
  selectedTime.value = time;
  emit('update:modelValue', time);
  emit('timeSelected', time);
};

onMounted(fetchTimeSlots);
watch([() => props.date, () => props.range, () => props.doctorid, () => props.forceAppointment, () => props.days], fetchTimeSlots);
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
      No time slots available for this date.
    </div>
    
    <div v-else class="grid grid-cols-4 gap-2">
      <button
        v-for="slot in slots"
        :key="slot.time"
        type="button"
        class="btn"
        :class="{
          'btn-primary': selectedTime === slot.time,
          'btn-outline-secondary': selectedTime !== slot.time,
          'btn-outline-danger': slot.isDeleted
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

.btn-outline-danger {
  background-color: white;
  color: #dc3545;
  border-color: #dc3545;
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