
<script setup>
import { useToastr } from '../../Components/toster';
import { ref ,onMounted } from 'vue';
 const doctor = ref({
 
});
const toaster = useToastr();

const props = defineProps({
  doctorId: {
    type: Object,
    required: true
  }
});
const getDoctorsInFo = async (page = 1) => {
  try {
    const response = await axios.get(`/api/doctors/${props.doctorId}?page=${page}`);
    doctor.value = response.data.data;     
  } catch (error) {
    toaster.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
  }
};

const availableAppointments = ref({
  canceled_appointments: null,
  normal_appointments: null
});

console.log(props.doctorId);

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
const formatClosestCanceledAppointment = (appointments) => {
  if (!appointments || appointments.length === 0) return 'No upcoming canceled appointments';

  // Sort appointments by date, then by time for the closest one
  const sortedAppointments = appointments.sort((a, b) => {
    const dateA = new Date(a.date + 'T' + a.available_times[0] + ':00');
    const dateB = new Date(b.date + 'T' + b.available_times[0] + ':00');
    return dateA - dateB;
  });

  const closest = sortedAppointments[0];
  return `${closest.date} at ${closest.available_times[0]}`;
};

onMounted(() => {
    getDoctorsInFo();
    fetchAvailableAppointments();
    });
</script>


<template>
     <!-- Header section remains the same -->
     <div class="header bg-gradient p-4  rounded-lg d-flex align-items-center justify-content-between">
    <!-- Left Section: Photo and Doctor Details -->
    <div class="d-flex align-items-center">
      <!-- Doctor Photo -->
      <div class="rounded-circle overflow-hidden shadow-lg border border-4 border-white" style="width: 150px; height: 150px;">
        <img src="/doctor.png" alt="Doctor image" class="w-100 h-100 object-fit-cover" />
      </div>
      <!-- Doctor Info -->
      <div class="ml-3">
        <h2 class="h5 font-weight-bold text-white mb-0">{{ doctor.name }}</h2>
        <p class="mb-1 text-white font-weight-bold">{{ doctor.specialization }} <span class=" font-weight-bold">{{ doctor.credentials }}</span></p>
        <p class="mb-0 text-white-50 ">
          <span class="font-weight-bold"><i class="fas fa-phone"></i> {{ doctor.phone }}</span>
        </p>
      </div>
    </div>

    <!-- Right Section: Appointments -->
    <div class="text-right">
      <!-- Next Appointment -->
      <div class="mb-3">
        <p class="mb-1 small text-white-50">Next Appointment:</p>
        <p class="h6 font-weight-bold text-white mb-2">
          {{ availableAppointments.normal_appointments ? availableAppointments.normal_appointments.date + ' at ' + availableAppointments.normal_appointments.time : 'No upcoming appointments' }}
        </p>
        <button v-if="nextAppointment" class="btn btn-sm btn-light rounded-pill shadow-sm">
          <i class="fas fa-calendar-check"></i> Confirm
        </button>
      </div>
      <!-- Last Appointment -->
      <div>
        <p class="mb-1 small text-white-50">Closest Appointments:</p>
        <p class="h6 font-weight-bold text-white mb-2">
          {{ formatClosestCanceledAppointment(availableAppointments.canceled_appointments) }}

        </p>
        <button v-if="lastAppointment" class="btn btn-sm btn-light rounded-pill shadow-sm">
          <i class="fas fa-file-medical-alt"></i> View Report
        </button>
      </div>
    </div>
  </div>
</template>