<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const schedules = ref([]);
const loading = ref(true);
const error = ref(null);
const route = useRoute();
const doctorName = ref("");
const doctorId = route.params.id;
const fetchSchedules = async () => {
  try {
    const response = await axios.get(`/api/schedules/${doctorId}`, {
      params: {
        doctor_id: doctorId,
      },
    });
    
    // Store the fetched schedules in the reactive variable
    schedules.value = response.data.schedules;
    doctorName.value = response.data.doctor_name;
    
    
    // Process the schedules into the desired format
    let formattedSchedules = [];
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    // Loop through each day and format morning and afternoon schedules
    days.forEach(day => {
      const morning = schedules.value.find(s => s.day_of_week === day && s.shift_period === 'morning');
      const afternoon = schedules.value.find(s => s.day_of_week === day && s.shift_period === 'afternoon');

      formattedSchedules.push({
        day_of_week: day.charAt(0).toUpperCase() + day.slice(1),
        morning_start_time: morning?.start_time.slice(0, -3) || '-',
        morning_end_time: morning?.end_time.slice(0, -3) || '-',
        afternoon_start_time: afternoon?.start_time.slice(0, -3) || '-',
        afternoon_end_time: afternoon?.end_time.slice(0, -3) || '-',
        number_of_patients_per_day: morning?.number_of_patients_per_day ?? afternoon?.number_of_patients_per_day ?? 0
      });
    });

    // Update the schedules with the formatted data
    schedules.value = formattedSchedules;
    loading.value = false;
  } catch (e) {
   
    loading.value = false;
  }
};


onMounted(fetchSchedules);
</script>
<template>
  <div class="container mt-4 premium-ui">
    <!-- Loading Spinner -->
    <div v-if="loading" class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden"></span>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <!-- Doctor Name Header and Schedule Table -->
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
              <td>{{ schedule.day_of_week }}</td>
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
    </div>
  </div>
</template>

<style scoped>
.premium-ui {
  font-family: 'Roboto', 'Helvetica Neue', sans-serif;
  color: #333;
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
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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