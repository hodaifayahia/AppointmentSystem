<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import {  useRoute } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import flatpickr from "flatpickr";
import PatientSearch from './PatientSearch.vue';
import TimeSlotSelector from './TimeSlotSelector.vue';

const route = useRoute();

const props = defineProps({
  editMode: { type: Boolean, default: false },
  doctorId: { type: Number, default: null }
});


const form = reactive({
  first_name: '',
  last_name: '',
  days: '',
  description: '',
  phone: '',
  doctor_id: 0,
  appointment_time: '',
  status: 0
});

const doctors = ref([]);
const availableSlots = ref([]);
const nextAppointmentDate = ref('');
const searchQuery = ref('');
const period = ref('');
const selectedDoctor = ref(null);

const checkAvailability = async () => {
  try {
    const response = await axios.get('/api/appointments/checkAvailability', {
      params: { days: form.days }
    });    
    
    nextAppointmentDate.value = response.data.next_appointment;
     period.value = response.data.period || [];
    if (selectedDoctor.value && nextAppointmentDate.value) {
      availableSlots.value = calculateSlots(
        selectedDoctor.value, 
        nextAppointmentDate.value,
        bookedSlots
      );
    }
  } catch (error) {
  }
};

const calculateSlots = (doctor, date, bookedSlots = []) => {
  if (!doctor || !date) return [];
  
  const slots = [];
  const startTime = new Date(`${date}T${doctor.start_time}`);
  const endTime = new Date(`${date}T${doctor.end_time}`);
  
  if (doctor.time_slots) {
    const slotDuration = parseInt(doctor.time_slots);
    let currentTime = startTime;
    
    while (currentTime < endTime) {
      const timeString = currentTime.toTimeString().slice(0, 5);
      slots.push({
        time: timeString,
        available: !bookedSlots.includes(timeString)
      });
      currentTime = new Date(currentTime.getTime() + slotDuration * 60000);
    }
  }
  
  return slots;
};

const handlePatientSelect = (patient) => {
  form.first_name = patient.firstname;
  form.last_name = patient.lastname;
  form.phone = patient.phone;
};

watch(() => form.days, checkAvailability);

watch(() => form.doctor_id, (newDoctorId) => {
  selectedDoctor.value = doctors.value.find(d => d.id === newDoctorId);
  if (selectedDoctor.value && nextAppointmentDate.value) {
    checkAvailability();
  }
});

onMounted(async () => {
  if (props.editMode) {
    const { data } = await axios.get(`/api/appointments/${route.params.id}/edit`);
    Object.assign(form, data);
    searchQuery.value = `${data.first_name} ${data.last_name}`;
  }

  
  flatpickr(".flatpickr", {
    dateFormat: "Y-m-d",
  });
});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <PatientSearch
      v-model="searchQuery"
      @patientSelected="handlePatientSelect"
    />

    <div class="form-group mb-4">
      <label for="days" class="text-muted">Days</label>
      <input
        type="number"
        v-model="form.days"
        class="form-control"
        id="days"
        placeholder="Enter number of days"
      />
      {{  }}
      
      <div v-if="nextAppointmentDate" class="mt-2 text-info">
        Next appointment will be on: {{ nextAppointmentDate }} 
       <p>{{ period }}</p> 
      </div>
    </div>

    <TimeSlotSelector

    :doctorid="doctorId"
  />

    <div class="form-group">
      <button type="submit" class="btn btn-primary rounded-pill">
        {{ props.editMode ? 'Update Appointment' : 'Create Appointment' }}
      </button>
    </div>
  </Form>
</template>