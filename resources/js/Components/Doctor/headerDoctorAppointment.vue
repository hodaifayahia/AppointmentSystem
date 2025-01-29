<script setup>
import { useToastr } from '../../Components/toster';
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const doctor = ref({});
const toaster = useToastr();

// Change the prop type to [String, Number] to handle both string and number IDs
const props = defineProps({
  doctorId: {
    type: [String, Number],
    required: true
  },
  isDcotro: {
    type: Boolean,
    default: true
  },
});

const availableAppointments = ref({
  canceled_appointments: null,
  normal_appointments: null
});

const getDoctorsInfo = async (page = 1) => {
  try {
    // Ensure doctorId is being passed correctly in the URL
    if (!props.doctorId) {
      console.error('Doctor ID is missing');
      return;
    }

    const response = await axios.get(`/api/doctors/${props.doctorId}?page=${page}`);
    doctor.value = response.data.data;
  } catch (error) {
    console.error('Error fetching doctor info:', error);
    toaster.error('Failed to fetch doctor information');
  }
};

const fetchAvailableAppointments = async () => {
  try {
    // Ensure doctorId is being passed correctly in the params
    if (!props.doctorId) {
      console.error('Doctor ID is missing');
      return;
    }

    const response = await axios.get('/api/appointments/available', {
      params: { doctor_id: props.doctorId }
    });

    availableAppointments.value = {
      canceled_appointments: response.data.canceled_appointments,
      normal_appointments: response.data.normal_appointments
    };
  } catch (error) {
    console.error('Error fetching available appointments:', error);
    toaster.error('Failed to fetch available appointments');
  }
};

const formatClosestCanceledAppointment = (appointments) => {
  if (!appointments || appointments.length === 0) return 'No upcoming canceled appointments';

  const sortedAppointments = appointments.sort((a, b) => {
    const dateA = new Date(a.date + 'T' + a.available_times[0] + ':00');
    const dateB = new Date(b.date + 'T' + b.available_times[0] + ':00');
    return dateA - dateB;
  });

  const closest = sortedAppointments[0];
  return `${closest.date} at ${closest.available_times[0]}`;
};

onMounted(() => {
  // Ensure both functions are called with the current doctorId

    getDoctorsInfo();
    fetchAvailableAppointments();
  
});
</script>


<template>
  <!-- Doctor Details Section -->
  <div class="header p-4 rounded-lg d-flex flex-column position-relative bg-primary">
    <!-- Back Button at the top of the card -->
    <button v-if="!isDcotro" class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute" style="top: 20px; left: 20px;" @click="router.go(-1)">
      <i class="fas fa-arrow-left"></i> Back
    </button>

    <!-- Main Content -->
    <div class="d-flex align-items-center justify-content-between mt-5">
      <!-- Left Section: Photo and Doctor Details -->
      <div class="d-flex align-items-center">
        <!-- Doctor Photo -->
        <div class="rounded-circle overflow-hidden shadow-lg border border-4 border-white" style="width: 150px; height: 150px;">
          <img src="/doctor.png" alt="Doctor image" class="w-100 h-100 object-fit-cover" />
        </div>
        <!-- Doctor Info -->
        <div class="ml-4">
          <h2 class="h4 font-weight-bold text-white mb-2">{{ doctor.name }}</h2>
          <p class="mb-1 text-white font-weight-bold">{{ doctor.specialization }} <span class="font-weight-bold">{{ doctor.credentials }}</span></p>
          <p class="mb-0 text-white-50">
            <span class="font-weight-bold"><i class="fas fa-phone"></i> {{ doctor.phone }}</span>
          </p>
        </div>
      </div>

      <!-- Right Section: Appointments -->
      <div class="text-right">
        <!-- Next Appointment -->
        <div class="mb-4">
          <p class="mb-1 small text-white-50">Next Appointment:</p>
          <p class="h5 font-weight-bold text-white mb-2">
            {{ availableAppointments.normal_appointments ? availableAppointments.normal_appointments.date + ' at ' + availableAppointments.normal_appointments.time : 'No upcoming appointments' }}
          </p>
        
        </div>
        <!-- Last Appointment -->
        <div>
          <p class="mb-1 small text-white-50">Closest Appointments:</p>
          <p class="h5 font-weight-bold text-white mb-2">
            {{ formatClosestCanceledAppointment(availableAppointments.canceled_appointments) }}
          </p>
         
        </div>
      </div>
    </div>
  </div>
</template>