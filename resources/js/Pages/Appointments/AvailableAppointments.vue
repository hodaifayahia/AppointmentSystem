<script setup>
import { ref, watch, onMounted } from 'vue';
import TimeSlotSelector from './TimeSlotSelector.vue'; // Assuming you have the TimeSlotSelector component already
import { formatDateHelper } from '@/Components/helper.js';

const props = defineProps({
 
  doctorId: {
    type: Number,
    required: true,
  },
});
const availableAppointments = ref([]);

const selectedAppointment = ref(null);
const fetchAvilableAppointments = async () => {

const response = await axios.get('/api/appointments/available', { params: { doctor_id: props.doctorId } });
availableAppointments.value = response.data;
console.log(availableAppointments.value);


};


const selectAppointment = (appointment) => {
  selectedAppointment.value = appointment;
};
onMounted(() => {
  fetchAvilableAppointments();
});

</script>

<template>
  <div class="form-group mb-4">
    <label class="text-muted mb-2">Available Appointment Dates</label>

    <!-- If no appointments available, show a message -->
    <div v-if="availableAppointments.length === 0" class="text-muted p-3 border rounded">
      No available appointments.
    </div>

    <!-- Dropdown for selecting an appointment -->
    <div v-else>
      <div class="relative">
        <select
          v-model="selectedAppointment"
          class="form-select form-control w-full mb-3"
        >
          <option disabled value="">Select an appointment</option>
          <option
            v-for="appointment in availableAppointments"
            :key="appointment.id"
            :value="appointment"
          >
            {{ formatDateHelper(appointment.date) }}
          </option>
        </select>
      </div>
    </div>

    <!-- If an appointment is selected, show the TimeSlotSelector component -->
    <TimeSlotSelector
      v-if="selectedAppointment"
      :date="selectedAppointment.a"
      :doctorid="doctorId"
      :deletedslots="selectedAppointment.available_times"
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
