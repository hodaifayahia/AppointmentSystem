<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const months = ref([]);
const schedules = ref([]);
const loading = ref(true);
const error = ref(null);
const route = useRoute();
const doctorName = ref("");
const patients_based_on_time = ref("");
const doctorId = route.params.id;
const excludedDates = ref([]); // Array to store excluded dates

const monthNames = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"
];

const monthwork = async () => {
  try {
    const response = await axios.get(`/api/monthwork/${doctorId}`);
    months.value = response.data.map(month => ({
      ...month,
      month_name: monthNames[month.month - 1] // Convert month integer to name
    }));
  } catch (e) {
    console.error("Error fetching schedules:", e);
    loading.value = false; // Still set loading to false on error
    error.value = "Failed to fetch month data."; // Set an error message
  }
};

const fetchSchedules = async () => {
  try {
    const response = await axios.get(`/api/schedules/${doctorId}`, {
      params: {
        doctor_id: doctorId,
      },
    });

    schedules.value = response.data.schedules;
    doctorName.value = response.data.doctor_name;
    patients_based_on_time.value = response.data.patients_based_on_time;

    let formattedSchedules = [];
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    days.forEach(day => {
      const schedulesOfDay = schedules.value.filter(s => s.day_of_week === day);
      const morning = schedulesOfDay.find(s => s.shift_period === 'morning');
      const afternoon = schedulesOfDay.find(s => s.shift_period === 'afternoon');
      const date = schedulesOfDay.find(s => s.date)?.date || null;

      formattedSchedules.push({
        date: date,
        day_of_week: day.charAt(0).toUpperCase() + day.slice(1),
        morning_start_time: morning?.start_time.slice(0, -3) || '-',
        morning_end_time: morning?.end_time.slice(0, -3) || '-',
        afternoon_start_time: afternoon?.start_time.slice(0, -3) || '-',
        afternoon_end_time: afternoon?.end_time.slice(0, -3) || '-',
        number_of_patients_per_day:
          (morning?.number_of_patients_per_day || 0) +
          (patients_based_on_time.value ? (afternoon?.number_of_patients_per_day || 0) : 0)
      });
    });

    schedules.value = formattedSchedules;

  } catch (e) {
    console.error("Error fetching schedules:", e);
    error.value = "Failed to fetch schedules."; // Set user-friendly error
  } finally {
    loading.value = false;
  }
};

// Fetch excluded dates from the backend
const fetchExcludedDates = async () => {
  try {
    const response = await axios.get(`/api/excluded-dates/${doctorId}`);
    console.log(response.data);
    excludedDates.value = response.data.data.map(item => ({
      start_date: item.start_date,
      end_date: item.end_date || null // Handle cases where end_date is missing
    }));
  } catch (error) {
    error.value = "Failed to fetch Excluded Dates."; // Set user-friendly error
    console.error('Error fetching excluded dates:', error);
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  fetchSchedules();
  monthwork();
  fetchExcludedDates(); // Fetch excluded dates on mount
});
</script>
<template>
  <div class="container mt-4 premium-ui">
    <!-- Back Button -->
    <button class="float-left btn btn-light bg-primary rounded-pill mb-3" @click="router.go(-1)">
      <i class="fas fa-arrow-left"></i> Back
    </button>
    <div class="clearfix"></div>  <!-- Clear the float -->

    <!-- Loading Spinner -->
    <div v-if="loading" class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <!-- Doctor Schedule and Data -->
    <div v-else>
      <div class="doctor-header mb-4 text-center fw-bold">
        <h3>Schedule for Dr. {{ doctorName }}</h3>
      </div>

      <!-- Schedule Table -->
      <div class="table-responsive">
        <table v-if="schedules.length > 0" class="table table-custom">
          <thead class="thead-custom">
            <tr>
              <th scope="col">Day of Week</th>
              <th scope="col">Morning Start</th>
              <th scope="col">Morning End</th>
              <th scope="col">Afternoon Start</th>
              <th scope="col">Afternoon End</th>
              <th scope="col">Patients</th>
            </tr>
          </thead>
          <transition-group name="list" tag="tbody">
            <tr v-for="schedule in schedules" :key="schedule.day_of_week" class="schedule-row">
              <td>
                {{ schedule.day_of_week }} <br>
                {{ schedule.date ? formatDate(schedule.date) : "" }}
              </td>
              <td>{{ schedule.morning_start_time || '-' }}</td>
              <td>{{ schedule.morning_end_time || '-' }}</td>
              <td>{{ schedule.afternoon_start_time || '-' }}</td>
              <td>{{ schedule.afternoon_end_time || '-' }}</td>
              <td>{{ schedule.number_of_patients_per_day }}</td>
            </tr>
          </transition-group>
        </table>
        <div v-else class="alert alert-info" role="alert">
          No schedules found for this doctor.
        </div>
      </div>

      <!-- Available Months -->
      <div class="available-months mb-4">
        <h4 class="mb-3 d-flex align-items-center justify-content-center">Available Months</h4>
        <div class="row">
          <div v-for="month in months" :key="month.month" class="col-md-3 mb-3">
            <div class="card card-aviable h-100 shadow-sm  d-flex align-items-center justify-content-center">
              <div class="card-body">
                <h5 class="card-title ">{{ month.month_name }} {{ month.year }}</h5>
             
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Excluded Dates -->
      <div class="excluded-dates mb-4 ">
        <h4 class="mb-3 d-flex align-items-center justify-content-center">Days Off</h4>
        <div v-if="excludedDates.length > 0" class="row">
          <div v-for="(date, index) in excludedDates" :key="index" class="col-md-3 mb-3">
            <div class="card card-dayoff h-100 shadow-sm hover-effect">
              <div class="card-body d-flex align-items-center justify-content-center">
                <p class="card-text mb-0 ">
                  {{ formatDate(date.start_date) }}
                  <span v-if="date.end_date"> - {{ formatDate(date.end_date) }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
        <p v-else>No dates are currently excluded.</p>
      </div>
    </div>
  </div>
</template>
<style scoped>
.premium-ui {
  font-family: 'Roboto', 'Helvetica Neue', sans-serif;
  color: #333;
}

.card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card-aviable {
  background-color: rgb(204, 240, 204)
}
.card-dayoff {
  background-color: rgb(243, 196, 196)
}


.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.premium-title {
  color: #2c3e50;
  font-size: 2rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 2px solid #3498db;
  padding-bottom: 10px;
}

.table-custom {
  border-collapse: separate;
  border-spacing: 0 10px;
  width: 100%;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.thead-custom {
  background: #67b7ec;
  color: white;
  border-radius: 10px 10px 0 0;
}

.thead-custom th {
  padding: 15px;
  border: none;
  font-weight: 600;
  text-align: left;
}

.schedule-row {
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.schedule-row:hover {
  background: #e9ecef;
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.schedule-row td {
  padding: 15px;
  border: none;
  text-align: left;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {

  .table-custom th,
  .table-custom td {
    font-size: 14px;
    padding: 8px;
  }
}
</style>