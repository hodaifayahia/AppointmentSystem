<script setup>
import { reactive, ref, watch, computed , onMounted} from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '@/Components/toster';
import { Form } from 'vee-validate';
import flatpickr from "flatpickr";
import PatientSearch from './PatientSearch.vue';
import TimeSlotSelector from './TimeSlotSelector.vue';

const props = defineProps({
  editMode: {
    type: Boolean,
    default: false
  }
});

const router = useRouter();
const route = useRoute();
const toastr = useToastr();

const form = reactive({
  first_name: '',
  last_name: '',
  description: '',
  phone: '',
  doctor_id: 0,
  start_time: '',
  start_date: '',
  appointment_time: '',
  status: 0
});

const doctors = ref([]);
const selectedDoctor = ref(null);
const availableSlots = ref([]);
const searchQuery = ref('');

const getDoctors = async () => {
  try {
    const response = await axios.get('/api/doctors');
    doctors.value = response.data.data;
  } catch (error) {
    toastr.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};

const calculateSlots = (doctor, date) => {
  if (!doctor || !doctor.start_time || !doctor.end_time || !date) return [];

  const slots = [];
  const startTime = new Date(`${date}T${doctor.start_time}`);
  const endTime = new Date(`${date}T${doctor.end_time}`);

  if (doctor.time_slots) {
    const slotDuration = parseInt(doctor.time_slots);
    let currentTime = startTime;
    while (currentTime < endTime) {
      slots.push({
        time: currentTime.toTimeString().slice(0, 5),
        available: true
      });
      currentTime = new Date(currentTime.getTime() + slotDuration * 60000);
    }
  } else if (doctor.number_of_patients_per_day) {
    const totalMinutes = (endTime - startTime) / 60000;
    const slotDuration = Math.floor(totalMinutes / doctor.number_of_patients_per_day);

    let currentTime = startTime;
    for (let i = 0; i < doctor.number_of_patients_per_day; i++) {
      slots.push({
        time: currentTime.toTimeString().slice(0, 5),
        available: true
      });
      currentTime = new Date(currentTime.getTime() + slotDuration * 60000);
    }
  }

  return slots;
};

const handleSubmit = async (values, actions) => {
  try {
    if (props.editMode) {
      await axios.put(`/api/appointments/${route.params.id}/edit`, form);
      toastr.success('Appointment updated successfully!');
    } else {
      await axios.post('/api/appointment', form);
      toastr.success('Appointment created successfully!');
    }
    router.push('/admin/appointments');
  } catch (error) {
    if (error.response?.data?.errors) {
      actions.setErrors(error.response.data.errors);
    }
  }
};

const handlePatientSelect = (patient) => {
  form.first_name = patient.firstname;
  form.last_name = patient.lastname;
  form.phone = patient.phone;
};

watch([() => form.doctor_id, () => form.start_date], ([newDoctorId, newDate]) => {
  const doctor = doctors.value.find(d => d.id === newDoctorId);
  if (doctor && newDate) {
    selectedDoctor.value = doctor;
    availableSlots.value = calculateSlots(doctor, newDate);
  } else {
    selectedDoctor.value = null;
    availableSlots.value = [];
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

  await getDoctors();
});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <PatientSearch
      v-model="searchQuery"
      @patientSelected="handlePatientSelect"
    />

    <div class="row">
      <div class="col-md-6">
        <div class="form-group mb-4">
          <label for="doctor" class="text-muted">Doctor</label>
          <select 
            v-model="form.doctor_id" 
            id="doctor"
            class="form-control form-select-sm rounded-pill"
            :class="{ 'is-invalid': errors.doctor_id }"
          >
            <option value="">Select Doctor</option>
            <option v-for="doctor in doctors" 
              :key="doctor.id"
              :value="doctor.id"
            >
              {{ doctor.full_name }}
            </option>
          </select>
          <span class="invalid-feedback">{{ errors.doctor_id }}</span>
        </div>
      </div>
    </div>

    <div class="form-group mb-4">
      <label for="start_date" class="text-muted">Start Date</label>
      <input 
        v-model="form.start_date" 
        id="start_date" 
        type="text"
        class="form-control form-control-sm rounded-pill flatpickr"
        placeholder="Select Date"
      >
    </div>

    <TimeSlotSelector
      v-model="form.appointment_time"
      :slots="availableSlots"
    />

    <div class="form-group">
      <button type="submit" class="btn btn-primary rounded-pill">
        {{ props.editMode ? 'Update Appointment' : 'Create Appointment' }}
      </button>
    </div>
  </Form>
</template>
<style scoped>
.vue-select {
    border-radius: 50px;
}

.appointment-page {
    background-color: #f8f9fa;
}

.content-header {
    border-radius: 0 0 1rem 1rem;
}

.breadcrumb-dark {
    background: transparent;
}

.card {
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0;
}

.form-group label {
    font-weight: 500;
}

.slot-container {
    max-height: 200px;
    overflow-y: auto;
}

.slot-btn {
    width: 100px;
    margin-bottom: 5px;
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
}

@media (max-width: 768px) {
    .slot-btn {
        width: 100%;
    }
}

.search-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.search-wrapper input {
    padding: 1rem 1.5rem;
    font-size: 1rem;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.search-wrapper input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.patient-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 350px;
    overflow-y: auto;
    z-index: 1050;
    border: 1px solid #e2e8f0;
    animation: dropdownFade 0.2s ease-out;
}

.loading-state {
    padding: 1.5rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.95rem;
}

.dropdown-header {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
}

.patient-list {
    padding: 0.5rem 0;
}

.patient-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.patient-item:hover {
    background-color: #f1f5f9;
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.patient-phone {
    color: #64748b;
    font-size: 0.875rem;
}

.select-icon {
    color: #94a3b8;
    margin-left: 1rem;
}

.no-results {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.no-results-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.no-results-text {
    font-size: 0.95rem;
}

/* Custom Scrollbar */
.patient-dropdown::-webkit-scrollbar {
    width: 8px;
}

.patient-dropdown::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 0 12px 12px 0;
}

.patient-dropdown::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.patient-dropdown::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>
