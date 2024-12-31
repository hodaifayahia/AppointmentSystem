<script setup>
import { ref, onMounted } from 'vue';
import TimeSlotSelector from './TimeSlotSelector.vue';
import { formatDateHelper } from '@/Components/helper.js';

const props = defineProps({
  doctorId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['dateSelected', 'timeSelected']);

const availableAppointments = ref({
  canceled_appointments: [],
  normal_appointments: null
});

const selectedAppointment = ref(null);

const fetchAvailableAppointments = async () => {
  try {
    const response = await axios.get('/api/appointments/available', {
      params: { doctor_id: props.doctorId }
    });
    availableAppointments.value = {
      canceled_appointments: response.data.canceled_appointments,
      normal_appointments: response.data.normal_appointments
    };
  } catch (error) {
    console.error('Error fetching available appointments:', error);
  }
};

const selectAppointment = (event) => {
  // Find the selected appointment from the available appointments
  const selectedValue = event.target.value;
  let found = availableAppointments.value.canceled_appointments.find(
    app => formatDateHelper(app.date) === selectedValue
  );
  
  if (!found && availableAppointments.value.normal_appointments) {
    if (formatDateHelper(availableAppointments.value.normal_appointments.date) === selectedValue) {
      found = availableAppointments.value.normal_appointments;
    }
  }

  if (found) {
    selectedAppointment.value = found;
    
    emit('dateSelected', selectedAppointment.value.date);
  }
};

const handleTimeSelected = (time) => {
  emit('timeSelected', time);
};

onMounted(() => {
  fetchAvailableAppointments();
});
</script>

<template>
  <div class="form-group mb-4">
    <label class="text-muted mb-2">Available Appointment Dates</label>

    <!-- If no appointments available, show a message -->
    <div 
      v-if="availableAppointments.canceled_appointments.length === 0 && !availableAppointments.normal_appointments" 
      class="text-muted p-3 border rounded"
    >
      No available appointments.
    </div>

    <!-- Dropdown for selecting an appointment -->
    <div v-else>
      <div class="relative">
        <select
          v-model="selectedAppointment"
          class="form-select form-control w-full mb-3"
          @change="selectAppointment"
        >
          <option disabled value="">Select an appointment</option>
          <template v-if="availableAppointments.canceled_appointments.length > 0">
            <optgroup label="Canceled Appointments">
              <option
                v-for="appointment in availableAppointments.canceled_appointments"
                :key="appointment.date"
                :value="formatDateHelper(appointment.date)"
              >
                {{ formatDateHelper(appointment.date) }}
              </option>
            </optgroup>
          </template>
          <template v-if="availableAppointments.normal_appointments">
            <optgroup label="Normal Appointments">
              <option
                :value="formatDateHelper(availableAppointments.normal_appointments.date)"
              >
                {{ formatDateHelper(availableAppointments.normal_appointments.date) }}
              </option>
            </optgroup>
          </template>
        </select>
      </div>
    </div>

    <!-- TimeSlotSelector component -->
    <TimeSlotSelector
      v-if="selectedAppointment"
      :date="selectedAppointment.date"
      :doctorid="doctorId"
      @timeSelected="handleTimeSelected"
      class="mt-4"
    />
  </div>
</template>

<style scoped>
.form-select {
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  border: 1px solid #ddd;
}

.text-muted {
  color: #6c757d;
}

.text-success {
  color: #28a745;
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