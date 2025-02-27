<script setup>
import { reactive, defineEmits, watch, ref } from 'vue';
import * as yup from 'yup';
import { useForm } from 'vee-validate';

const emit = defineEmits(['schedulesUpdated', 'update:modelValue']);

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  patients_based_on_time: {
    type: Boolean,
    default: false,
  },
  time_slot: {
    type: Number,
    required: false,
  },
  existingSchedules: {
    type: Array,
    default: () => []
  }
});

// Initialize schedules with reactive
const schedules = reactive(
  daysOfWeek.reduce((acc, day) => ({
    ...acc,
    [day]: {
      morning: { 
        isActive: false, 
        startTime: '08:00', 
        endTime: '12:00',
        patients_per_day: 0 
      },
      afternoon: { 
        isActive: false, 
        startTime: '13:00', 
        endTime: '17:00',
        patients_per_day: 0 
      },
    },
  }), {})
);

// Improved reset function
const resetSchedules = () => {
  Object.keys(schedules).forEach(day => {
    schedules[day] = {
      morning: { 
        isActive: false, 
        startTime: '08:00', 
        endTime: '12:00',
        patients_per_day: 0 
      },
      afternoon: { 
        isActive: false, 
        startTime: '13:00', 
        endTime: '17:00',
        patients_per_day: 0 
      }
    };
  });
};

// Improved populate function
const populateSchedules = (existingSchedules) => {
  if (!Array.isArray(existingSchedules) || existingSchedules.length === 0) return;

  resetSchedules();

  existingSchedules.forEach(schedule => {
    const day = schedule.day_of_week.charAt(0).toUpperCase() + schedule.day_of_week.slice(1);
    
    if (schedules[day] && schedule.is_active) {
      const shift = schedule.shift_period.toLowerCase();
      if (schedules[day][shift]) {
        schedules[day][shift] = {
          isActive: true,
          startTime: schedule.start_time.slice(0, 5),
          endTime: schedule.end_time.slice(0, 5),
          patients_per_day: schedule.number_of_patients_per_day || 0
        };
      }
    }
  });
};

// Watch for existingSchedules changes
watch(
  () => props.existingSchedules,
  (newSchedules) => {
    if (newSchedules && Array.isArray(newSchedules)) {
      populateSchedules(newSchedules);
    }
  },
  { immediate: true, deep: true }
);

// Form validation schema
const { values, errors } = useForm({
  initialValues: schedules,
  validationSchema: yup.object().shape({
    schedules: yup.array().min(1, "At least one schedule is required"),
    patients_based_on_time: yup.boolean().required(),
    time_slot: yup.number().when('patients_based_on_time', {
      is: true,
      then: yup.number().required('Time slot is required when scheduling by time'),
    }),
  }),
});
// Fetch excluded dates from the backend
const fetchExcludedDates = async (doctorId) => {    
    
    try {
      const response = await axios.get(`/api/excluded-dates/${doctorId}`);
      excludedDates.value = response.data.data;
      console.log(excludedDates.value);
      
    } catch (error) {
      toast.error('Failed to fetch excluded dates');
      console.error('Error fetching excluded dates:', error);
    }
  };

// Calculate patients per shift
const calculatePatientsPerDay = (startTime, endTime, slot) => {
  if (!startTime || !endTime || !slot) return 0;
  const start = new Date(`1970-01-01T${startTime}`);
  const end = new Date(`1970-01-01T${endTime}`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

// Function to update schedules data
const updateSchedulesData = () => {
  const schedulesData = Object.entries(schedules)
    .flatMap(([day, shifts]) => {
      const records = [];
      if (shifts.morning.isActive) {
        records.push({
          day_of_week: day.toLowerCase(),
          shift_period: 'morning',
          start_time: shifts.morning.startTime.slice(0, 5),
          end_time: shifts.morning.endTime.slice(0, 5),
          is_active: true,
          number_of_patients_per_day: props.patients_based_on_time
            ? calculatePatientsPerDay(shifts.morning.startTime, shifts.morning.endTime, props.time_slot)
            : shifts.morning.patients_per_day,
        });
      }
      if (shifts.afternoon.isActive) {
        records.push({
          day_of_week: day.toLowerCase(),
          shift_period: 'afternoon',
          start_time: shifts.afternoon.startTime.slice(0, 5),
          end_time: shifts.afternoon.endTime.slice(0, 5),
          is_active: true,
          number_of_patients_per_day: props.patients_based_on_time
            ? calculatePatientsPerDay(shifts.afternoon.startTime, shifts.afternoon.endTime, props.time_slot)
            : shifts.afternoon.patients_per_day,
        });
      }
      return records;
    })
    .filter(record => record !== null);

  emit('schedulesUpdated', schedulesData);
  emit('update:modelValue', { schedules: schedulesData });
};

// Watch for changes in schedules
watch(schedules, () => {
  updateSchedulesData();
}, { deep: true });

// Watch for changes in props that affect patient numbers
watch(
  [
    () => props.patients_based_on_time,
    () => props.time_slot
  ],
  () => {
    updateSchedulesData();
  }
);

// Handle time input changes
const handleTimeChange = (day, shift, type, event) => {
  const time = event.target.value;
  schedules[day][shift][type] = time;
  updateSchedulesData();
};

// Handle patients per day changes
const handlePatientsChange = (day, shift, event) => {
  const value = parseInt(event.target.value) || 0;
  schedules[day][shift].patients_per_day = value;
  updateSchedulesData();
};
</script>

<template>
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <i class="fas fa-clock me-2"></i>
        Weekly Schedule
      </h2>
    </div>
    
    <div class="card-body">
      <div v-if="errors.schedules" class="alert alert-danger">
        {{ errors.schedules }}
      </div>
      
      <div v-for="day in daysOfWeek" :key="day" class="mb-4 p-3 border rounded">
        <h3 class="mb-3">{{ day }}</h3>
        
        <!-- Morning Shift -->
        <div class="mb-4">
          <div class="d-flex align-items-center mb-2">
            <div class="form-check">
              <input
                type="checkbox"
                class="form-check-input"
                :id="'morning-' + day"
                v-model="schedules[day].morning.isActive"
              />
              <label class="form-check-label" :for="'morning-' + day">Morning Shift</label>
            </div>
          </div>
          
          <div v-if="schedules[day].morning.isActive" class="row ms-4">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time</label>
              <input
                type="time"
                class="form-control"
                :value="schedules[day].morning.startTime"
                @input="(e) => handleTimeChange(day, 'morning', 'startTime', e)"
              />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input
                type="time"
                class="form-control"
                :value="schedules[day].morning.endTime"
                @input="(e) => handleTimeChange(day, 'morning', 'endTime', e)"
              />
            </div>
            <div class="col-md-4 mb-3" v-if="!patients_based_on_time">
              <label class="form-label">Patients Per Day Morning</label>
              <input
                type="number"
                class="form-control"
                :value="schedules[day].morning.patients_per_day"
                @input="(e) => handlePatientsChange(day, 'morning', e)"
                min="0"
                :disabled="props.patients_based_on_time"
              />
            </div>
          </div>
        </div>
        
        <!-- Afternoon Shift -->
        <div>
          <div class="d-flex align-items-center mb-2">
            <div class="form-check">
              <input
                type="checkbox"
                class="form-check-input"
                :id="'afternoon-' + day"
                v-model="schedules[day].afternoon.isActive"
              />
              <label class="form-check-label" :for="'afternoon-' + day">Afternoon Shift</label>
            </div>
          </div>
          
          <div v-if="schedules[day].afternoon.isActive"   class="row ms-4">
            <div class="col-md-6 mb-3" >
              <label class="form-label">Start Time</label>
              <input
                type="time"
                class="form-control"
                :value="schedules[day].afternoon.startTime"
                @input="(e) => handleTimeChange(day, 'afternoon', 'startTime', e)"
              />
            </div>
            <div class="col-md-6 mb-3" >
              <label class="form-label">End Time</label>
              <input
                type="time"
                class="form-control"
                :value="schedules[day].afternoon.endTime"
                @input="(e) => handleTimeChange(day, 'afternoon', 'endTime', e)"
              />
            </div>
            <div class="col-md-4 mb-3" v-if="!patients_based_on_time">
              <label class="form-label">Patients Per Day Afternoon</label>
              <input
                type="number"
                class="form-control"
                :value="schedules[day].afternoon.patients_per_day"
                @input="(e) => handlePatientsChange(day, 'afternoon', e)"
                min="0"
                :disabled="props.patients_based_on_time"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.input-group {
  display: flex;
  align-items: center;
}

.invalid-feedback {
  display: block;
  color: red;
  font-size: 0.875rem;
}

.modal-dialog {
  max-width: 800px;
}
</style>