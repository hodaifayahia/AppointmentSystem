<script setup>
import { reactive, defineEmits, watch } from 'vue';
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
  number_of_patients_per_day: {
    type: Number,
    default: 0,
  },
  existingSchedules: {
    type: Array,
    default: null
  }
});

const schedules = reactive(
  daysOfWeek.reduce((acc, day) => ({
    ...acc,
    [day]: {
      morning: { isActive: false, startTime: '08:00', endTime: '12:00' },
      afternoon: { isActive: false, startTime: '13:00', endTime: '17:00' },
    },
  }), {})
);

// Helper function to reset schedules
const resetSchedules = () => {
  daysOfWeek.forEach(day => {
    schedules[day] = {
      morning: { isActive: false, startTime: '08:00', endTime: '12:00' },
      afternoon: { isActive: false, startTime: '13:00', endTime: '17:00' }
    };
  });
};

// Helper function to populate schedules from existing data
const populateSchedules = (newSchedules) => {
  newSchedules.forEach(schedule => {
    const day = schedule.day_of_week.charAt(0).toUpperCase() + schedule.day_of_week.slice(1);
    if (schedules[day]) {
      schedules[day][schedule.shift_period] = {
        isActive: schedule.is_active,
        startTime: schedule.start_time,
        endTime: schedule.end_time
      };
    }
  });
};

// Watch for changes in props.existingSchedules
watch(() => props.existingSchedules, (newSchedules) => {
  if (newSchedules?.length) {
    resetSchedules();
    populateSchedules(newSchedules);
  }
}, { immediate: true, deep: true });

// Form validation setup
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

// Helper function to calculate patients per shift
const calculatePatientsPerDay = (startTime, endTime, slot) => {
  if (!startTime || !endTime || !slot) return 0;
  const start = new Date(`1970-01-01T${startTime}`);
  const end = new Date(`1970-01-01T${endTime}`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

// Ensure this calculation is triggered when any schedule changes
watch(
  schedules,
  (newSchedules) => {
    const schedulesData = Object.entries(newSchedules)
      .flatMap(([day, shifts]) => {
        const records = [];
        if (shifts.morning.isActive) {
          records.push({
            day_of_week: day.toLowerCase(),
            shift_period: 'morning',
            start_time: shifts.morning.startTime,
            end_time: shifts.morning.endTime,
            is_active: true,
            number_of_patients_per_day: props.patients_based_on_time
              ? calculatePatientsPerDay(shifts.morning.startTime, shifts.morning.endTime, props.time_slot)
              : props.number_of_patients_per_day,
          });
        }
        if (shifts.afternoon.isActive) {
          records.push({
            day_of_week: day.toLowerCase(),
            shift_period: 'afternoon',
            start_time: shifts.afternoon.startTime,
            end_time: shifts.afternoon.endTime,
            is_active: true,
            number_of_patients_per_day: props.patients_based_on_time
              ? calculatePatientsPerDay(shifts.afternoon.startTime, shifts.afternoon.endTime, props.time_slot)
              : props.number_of_patients_per_day,
          });
        }
        return records;
      })
      .filter(record => record !== null);

    emit('schedulesUpdated', schedulesData);
    emit('update:modelValue', { schedules: schedulesData });
  },
  { deep: true }
);
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
                v-model="schedules[day].morning.startTime"
              />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input
                type="time"
                class="form-control"
                v-model="schedules[day].morning.endTime"
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
          
          <div v-if="schedules[day].afternoon.isActive" class="row ms-4">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time</label>
              <input
                type="time"
                class="form-control"
                v-model="schedules[day].afternoon.startTime"
              />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input
                type="time"
                class="form-control"
                v-model="schedules[day].afternoon.endTime"
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