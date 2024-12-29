<script setup>
import { reactive, defineEmits, watch } from 'vue';
import * as yup from 'yup';
import { useForm } from 'vee-validate';

const emit = defineEmits(['schedulesUpdated']);

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const props = defineProps({
  doctorId: {
    type: Number,
    required: false,
  },
  value: {
    type: Object,
    default: () => ({}),
  },
  patients_based_on_time: {
    type: Boolean,
    required: true,
  },
  time_slot: {
    type: Number,
    required: true,
  },
  number_of_patients_per_day: {
    type: Number,
    default: 0,
  },
});

// Reactive schedules state
const schedules = reactive(
  daysOfWeek.reduce((acc, day) => ({
    ...acc,
    [day]: {
      morning: { isActive: false, startTime: '08:00', endTime: '12:00' },
      afternoon: { isActive: false, startTime: '13:00', endTime: '17:00' },
    },
  }), {})
);

// Form validation setup
const { values, errors } = useForm({
  initialValues: schedules,
  validationSchema: yup.object(
    daysOfWeek.reduce((acc, day) => {
      acc[day] = yup.object({
        morning: yup.object({
          isActive: yup.boolean(),
          startTime: yup.string().when('isActive', {
            is: true,
            then: yup.string().required('Start time is required'),
          }),
          endTime: yup.string().when('isActive', {
            is: true,
            then: yup.string().required('End time is required'),
          }),
        }),
        afternoon: yup.object({
          isActive: yup.boolean(),
          startTime: yup.string().when('isActive', {
            is: true,
            then: yup.string().required('Start time is required'),
          }),
          endTime: yup.string().when('isActive', {
            is: true,
            then: yup.string().required('End time is required'),
          }),
        }),
      });
      return acc;
    }, {})
  ),
});

// Helper function to calculate patients per shift
const calculatePatientsPerDay = (startTime, endTime, slot) => {
  const start = new Date(`1970-01-01T${startTime}:00`);
  const end = new Date(`1970-01-01T${endTime}:00`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

// Watch schedules and emit changes to parent
watch(
  schedules,
  (newSchedules) => {
    const schedulesData = Object.entries(newSchedules).flatMap(([day, shifts]) => {
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
    });

    emit('schedulesUpdated', schedulesData);
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