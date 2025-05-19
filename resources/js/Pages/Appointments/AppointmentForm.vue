<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from './PatientSearch.vue';
import AvailableAppointments from './AvailableAppointments.vue';
import NextAppointmentDate from './NextAppointmentDate.vue';
import AppointmentCalendar from './AppointmentCalendar.vue';
import { useToastr } from '../../Components/toster';

const route = useRoute();
const router = useRouter();
const nextAppointmentDate = ref('');
const searchQuery = ref('');
const toastr = useToastr();
const isEmpty = ref(false);
const importanceLevels = ref([]);
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const doctors = ref([]);

const props = defineProps({
  editMode: { type: Boolean, default: false },
  NextAppointment: { type: Boolean, default: false },
  doctorId: { type: Number, default: null },
  specialization_id: { type: Number, default: null },
  appointmentId: { type: Number, default: null }
});
const emit = defineEmits(['close']);

const fetchDoctors = async () => {
  if (props.editMode ) {
    try {
      const response = await axios.get(`/api/doctors/specializations/${props.specialization_id}`);
      doctors.value = response.data.data;
      
    } catch (error) {
      console.error('Failed to fetch doctors:', error);
    }
  }
};

const form = reactive({
  id: null,
  first_name: '',
  patient_id: null,
  last_name: '',
  patient_Date_Of_Birth: '',
  phone: '',
  doctor_id: null, // Initialize as null
  appointment_date: '',
  appointment_time: '',
  description: '',
  addToWaitlist: false,
  importance: 1,
  status: {},
  selectionMethod: '',
  days: ''
});

const fetchAppointmentData = async () => {
  if (props.editMode && props.appointmentId) {
    try {
      const response = await axios.get(`/api/appointments/${props.doctorId}/${props.appointmentId}`);
      if (response.data.success) {
        const appointment = response.data.data;
        // Populate form with appointment data
        Object.assign(form, {
          id: appointment.id,
          first_name: appointment.first_name,
          patient_id: appointment.patient_id,
          last_name: appointment.last_name,
          patient_Date_Of_Birth: appointment.patient_Date_Of_Birth,
          phone: appointment.phone,
          doctor_id: appointment.doctor_id || props.doctorId, // Use appointment's doctor_id if available, otherwise use props
          appointment_date: appointment.appointment_date,
          appointment_time: appointment.appointment_time,
          description: appointment.description,
          addToWaitlist: appointment.addToWaitlist,
          status: appointment.status
        });

        searchQuery.value = `${appointment.first_name} ${appointment.last_name} ${appointment.patient_Date_Of_Birth} ${appointment.phone}`;
      }
    } catch (error) {
      console.error('Failed to fetch appointment data:', error);
    }
  } else if (!props.editMode && props.doctorId) {
    // If not in edit mode but doctorId is provided, set it as the initial doctor
    form.doctor_id = props.doctorId;
  }
};

// Rest of the existing functions remain the same
const fetchImportanceEnum = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceLevels.value = response.data;
};

const isWaitListEmpty = async () => {
  const response = await axios.get('/api/waitlist/empty');
  isEmpty.value = response.data.data;
};

const handlePatientSelect = (patient) => {
  form.first_name = patient.first_name;
  form.last_name = patient.last_name;
  form.patient_Date_Of_Birth = patient.dateOfBirth;
  form.phone = patient.phone;
  form.patient_id = patient.id;
};

const getPatientFullName = (patient) => {
    // Validate input
    if (!patient || typeof patient !== 'object') {
        return 'N/A';
    }

    // Extract and sanitize properties
    const {  patient_last_name = '', patient_first_name = '' } = patient;

    // Construct full name
    const fullName = [ patient_first_name , patient_last_name]
        .filter(Boolean) // Remove empty strings
        .join(' ')       // Join with spaces

    // Capitalize the result (assuming capitalize is defined elsewhere)
    return fullName ? capitalize(fullName) : 'N/A';
};

const handleDaysChange = (days) => {
  form.days = days;
};

const handleDateSelected = (date) => {
  form.appointment_date = date;
  nextAppointmentDate.value = date;
};

const handleTimeSelected = (time) => {
  form.appointment_time = time;
};

// Add this ref at the top with other refs
const autoPrint = ref(false);

// Update the handleSubmit function
const handleSubmit = async (values, { setErrors }) => {
  try {
    let url = '/api/appointments';
    let method = props.editMode ? 'put' : 'post';

    if (props.editMode) {
      if (props.NextAppointment) {
        url = `/api/appointment/nextappointment/${props.appointmentId}`;
        method = 'post';
      } else {
        url = `/api/appointments/${props.appointmentId}`;
      }
    }

    const response = await axios[method](url, form);
    toastr.success(`${props.editMode ? 'Appointment updated' : 'Appointment created'} successfully`);

    // Print ticket if autoPrint is checked
    if (autoPrint.value && response.data.data) {
      await PrintTicket(response.data.data);
    }

    if (props.NextAppointment) {
      emit('close');
    } else {
      router.push({ name: 'admin.appointments', params: { doctorId: form.doctor_id } });
    }
  } catch (error) {
    console.error(`${props.editMode ? 'Error updating appointment:' : 'Error creating appointment:'}`, error);
    setErrors({ form: 'An error occurred while processing your request' });
  }
};

// Update the PrintTicket function
const PrintTicket = async () => {
    try {
        // Prepare the ticket data
        const ticketData = {
            patient_name: `${form.first_name} ${form.last_name}`,
            patient_first_name: form.first_name,
            patient_last_name: form.last_name,
            doctor_id: form.doctor_id || 'N/A',
            appointment_date: form.appointment_date,
            appointment_time: form.appointment_time,
            description: form.description || 'N/A'
        };

        // Send POST request with the ticket data
        const response = await axios.post('/api/appointments/print-ticket', ticketData, {
            responseType: 'blob'
        });
        
        // Create PDF URL
        const pdfUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        
        // Open in new tab
        const printWindow = window.open(pdfUrl);
        
        // Automatically trigger print dialog
        printWindow.onload = function() {
            printWindow.print();
        };
    } catch (error) {
        console.error('Error printing ticket:', error);
        toastr.error('Failed to print ticket');
    }
}
const resetSelection = () => {
  if (form.selectionMethod === 'days') {
    form.appointment_date = '';
    nextAppointmentDate.value = '';
  } else {
    form.days = '';
  }
};

watch(() => form.selectionMethod, resetSelection);

onMounted(async () => {
  await Promise.all([
    fetchImportanceEnum(),
    fetchDoctors(),
    isWaitListEmpty()
  ]);
  await fetchAppointmentData();
});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <!-- Patient Search Component -->
    <PatientSearch v-model="searchQuery" :patientId="form.patient_id" @patientSelected="handlePatientSelect" />

    <!-- Doctor Selection - Show only if user is not a doctor -->
    <div class="mb-3" v-if="props.editMode && authStore.user.role !== 'doctor'">
      <label for="doctor_id" class="form-label">Select Doctor</label>
      <select id="doctor_id" v-model="form.doctor_id" class="form-control" required>
        <option value="" disabled>Select a doctor</option>
        <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
          {{ doctor.name }}
        </option>
      </select>
      <span class="text-sm invalid-feedback">{{ errors.doctor_id }}</span>
    </div>

    <!-- Available Appointments -->
    <AvailableAppointments 
      v-if="!form.selectionMethod" 
      :waitlist="false" 
      :isEmpty="isEmpty" 
      :doctorId="form.doctor_id || props.doctorId"
      @dateSelected="handleDateSelected" 
      @timeSelected="handleTimeSelected" 
    />

    <!-- Appointment Method Selection -->
    <div class="form-group mb-4">
      <label for="selectionMethod" class="form-label">Select Appointment Method</label>
      <select id="selectionMethod" v-model="form.selectionMethod" class="form-control">
        <option value="">Select Available Appointments</option>
        <option value="days">By Days</option>
        <option value="calendar">By Calendar</option>
      </select>
    </div>

    <!-- By Days Option -->
    <NextAppointmentDate 
      v-if="form.selectionMethod === 'days'"
      :doctorId="form.doctor_id || props.doctorId"
      :initialDays="form.days"
      @update:days="handleDaysChange"
      @dateSelected="handleDateSelected"
      @timeSelected="handleTimeSelected"
    />

    <!-- Calendar Option -->
    <AppointmentCalendar 
      v-if="form.selectionMethod === 'calendar'"
      :doctorId="form.doctor_id || props.doctorId"
      @timeSelected="handleTimeSelected"
      @dateSelected="handleDateSelected"
    />

    <!-- Waitlist Checkbox -->
    <div class="form-group mb-4">
      <label for="addToWaitlist" class="form-label">Add to Waitlist</label>
      <input type="checkbox" id="addToWaitlist" v-model="form.addToWaitlist" class="form-check-input" />
    </div>

    <!-- Description -->
    <div class="form-group mb-4">
      <label for="description" class="form-label">Description</label>
      <textarea 
        id="description" 
        v-model="form.description" 
        class="form-control" 
        rows="3"
        placeholder="Enter appointment details..."
      ></textarea>
    </div>

    <!-- Auto Print Checkbox -->
    <div class="form-group mb-4">
      <label for="autoPrint" class="form-label">
        <input type="checkbox" id="autoPrint" v-model="autoPrint"  />
        Print ticket automatically after creating appointment
      </label>
    </div>

    <!-- Submit Button -->
    <div class="form-group">
      <button type="submit" class="btn btn-primary rounded-pill">
        {{ props.NextAppointment ? 'Create Appointment' : props.editMode ? 'Update Appointment' : 'Create Appointment' }}
      </button>
    </div>
  </Form>
</template>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border-radius: 4px;
}

.form-check-input {
  margin-left: 10px;
}

.btn {
  padding: 0.8rem 1.5rem;
  font-size: 16px;
}

.text-muted {
  color: #6c757d;
}

.rounded-pill {
  border-radius: 50px;
}

.no-slots {
  text-align: center;
  margin: 2rem 0;
}

.no-slots button {
  width: 200px;
}
</style>