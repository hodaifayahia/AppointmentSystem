<script setup>
import { ref, defineProps, defineEmits, onMounted, watch } from 'vue';
import { Field } from 'vee-validate';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
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
  existingSchedules: {
    type: Array,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'schedulesUpdated']);

const dates = ref([]);

// Helper function to calculate patients per shift
const calculatePatientsPerShift = (startTime, endTime, slot) => {
  if (!startTime || !endTime || !slot) return 0;

  const start = new Date(`1970-01-01T${startTime}`);
  const end = new Date(`1970-01-01T${endTime}`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

function formatTimeForInput(time) {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  return `${hours}:${minutes}`;
}

const processExistingSchedules = (schedules) => {
  const processedDates = new Map();

  schedules.forEach(schedule => {
    const dateKey = schedule.date;
    
    if (!processedDates.has(dateKey)) {
      processedDates.set(dateKey, {
        date: dateKey,
        morningStartTime: '',
        morningEndTime: '',
        morningPatients: 0,
        afternoonStartTime: '',
        afternoonEndTime: '',
        afternoonPatients: 0,
      });
    }

    const dateEntry = processedDates.get(dateKey);
    const timePrefix = schedule.shift_period.toLowerCase();
    
    dateEntry[`${timePrefix}StartTime`] = formatTimeForInput(schedule.start_time);
    dateEntry[`${timePrefix}EndTime`] = formatTimeForInput(schedule.end_time);
    dateEntry[`${timePrefix}Patients`] = schedule.number_of_patients_per_day;
  });

  return Array.from(processedDates.values());
};

// Calculate and update patients for a specific date and shift
const updatePatientsForShift = (dateInfo, shift) => {
  const startTime = dateInfo[`${shift}StartTime`];
  const endTime = dateInfo[`${shift}EndTime`];

  if (startTime && endTime && props.patients_based_on_time) {
    dateInfo[`${shift}Patients`] = calculatePatientsPerShift(
      startTime,
      endTime,
      props.time_slot
    );
  }
};

const handlePatientsChange = (index, shift, event) => {
  if (!props.patients_based_on_time) {
    const value = parseInt(event.target.value) || 0;
    dates.value[index][`${shift}Patients`] = value;
    emitUpdate();
  }
};

onMounted(() => {
  if (props.existingSchedules && props.existingSchedules.length > 0) {
    dates.value = processExistingSchedules(props.existingSchedules);
  } else if (!props.modelValue || props.modelValue.length === 0) {
    dates.value = [{
      date: '',
      morningStartTime: '',
      morningEndTime: '',
      morningPatients: 0,
      afternoonStartTime: '',
      afternoonEndTime: '',
      afternoonPatients: 0,
    }];
  } else {
    dates.value = props.modelValue.map(date => {
      if (typeof date === 'string') {
        return {
          date,
          morningStartTime: '',
          morningEndTime: '',
          morningPatients: 0,
          afternoonStartTime: '',
          afternoonEndTime: '',
          afternoonPatients: 0,
        };
      }
      return date;
    });
  }
});

const addDate = () => {
  dates.value.push({
    date: '',
    morningStartTime: '',
    morningEndTime: '',
    morningPatients: 0,
    afternoonStartTime: '',
    afternoonEndTime: '',
    afternoonPatients: 0,
  });
  emitUpdate();
};

import axios from 'axios'; // Import Axios

const removeDate = async (index) => {
  const removedDate = dates.value[index];
  console.log(props.doctorId);
  

  // Only send a delete request if the schedule exists in the backend
  if (removedDate.date && props.doctorId) {
    try {
      await axios.delete(`/api/schedules/${props.doctorId}`, {
        data: { date: removedDate.date }, // Pass the date to identify the record
      });

      // Remove from the local state only after successful API response
      dates.value.splice(index, 1);
      emitUpdate();
    } catch (error) {
      console.error('Error deleting schedule:', error);
    }
  } else {
    // If not in backend, just remove from the UI
    dates.value.splice(index, 1);
    emitUpdate();
  }
};

const handleChange = (index, field, event) => {
  dates.value[index][field] = event.target.value;

  // If time fields change, update patient calculations
  if (field.includes('Time')) {
    const shift = field.includes('morning') ? 'morning' : 'afternoon';
    updatePatientsForShift(dates.value[index], shift);
  }

  emitUpdate();
};

const emitUpdate = () => {
  const schedulesData = dates.value.flatMap((dateInfo) => {
    const records = [];
    const day = new Date(dateInfo.date).getDay();
    const dayName = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][day];

    // Morning shift
    if (dateInfo.morningStartTime && dateInfo.morningEndTime) {
      records.push({
        date: dateInfo.date,
        day_of_week: dayName,
        shift_period: 'morning',
        start_time: dateInfo.morningStartTime,
        end_time: dateInfo.morningEndTime,
        is_active: true,
        number_of_patients_per_day: dateInfo.morningPatients,
      });
    }

    // Afternoon shift
    if (dateInfo.afternoonStartTime && dateInfo.afternoonEndTime) {
      records.push({
        date: dateInfo.date,
        day_of_week: dayName,
        shift_period: 'afternoon',
        start_time: dateInfo.afternoonStartTime,
        end_time: dateInfo.afternoonEndTime,
        is_active: true,
        number_of_patients_per_day: dateInfo.afternoonPatients,
      });
    }

    return records;
  });

  emit('update:modelValue', dates.value);
  emit('schedulesUpdated', schedulesData);
};

// Watch for changes in props that affect patient calculations
watch(
  [() => props.patients_based_on_time, () => props.time_slot],
  () => {
    dates.value.forEach(dateInfo => {
      updatePatientsForShift(dateInfo, 'morning');
      updatePatientsForShift(dateInfo, 'afternoon');
    });
    emitUpdate();
  }
);
</script>

<template>
  <div class="custom-dates">
    <div v-for="(dateInfo, index) in dates" 
         :key="index" 
         class="mb-4 border p-3 rounded">
      <div class="row">
        <!-- Date Input -->
        <div class="col-12 mb-3">
          <label class="form-label">Date</label>
          <div class="input-group">
            <Field
              type="date"
              :name="'customDate-' + index"
              :value="dateInfo.date"
              @input="(e) => handleChange(index, 'date', e)"
              class="form-control form-control-md"
            />
          </div>
        </div>

        <!-- Morning Session -->
        <div class="col-12 mb-3">
          <div class="p-3 rounded">
            <h6 class="mb-3">Morning Session</h6>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Start Time</label>
                <Field
                  type="time"
                  :name="'morningStartTime-' + index"
                  :value="dateInfo.morningStartTime"
                  @input="(e) => handleChange(index, 'morningStartTime', e)"
                  class="form-control form-control-md"
                />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">End Time</label>
                <Field
                  type="time"
                  :name="'morningEndTime-' + index"
                  :value="dateInfo.morningEndTime"
                  @input="(e) => handleChange(index, 'morningEndTime', e)"
                  class="form-control form-control-md"
                />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Number of Patients</label>
                <input
                  type="number"
                  class="form-control"
                  :value="dateInfo.morningPatients"
                  @input="(e) => handlePatientsChange(index, 'morning', e)"
                  min="0"
                  :disabled="props.patients_based_on_time"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Afternoon Session -->
        <div class="col-12 mb-3">
          <div class="p-3 rounded">
            <h6 class="mb-3">Afternoon Session</h6>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Start Time</label>
                <Field
                  type="time"
                  :name="'afternoonStartTime-' + index"
                  :value="dateInfo.afternoonStartTime"
                  @input="(e) => handleChange(index, 'afternoonStartTime', e)"
                  class="form-control form-control-md"
                />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">End Time</label>
                <Field
                  type="time"
                  :name="'afternoonEndTime-' + index"
                  :value="dateInfo.afternoonEndTime"
                  @input="(e) => handleChange(index, 'afternoonEndTime', e)"
                  class="form-control form-control-md"
                />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Number of Patients</label>
                <input
                  type="number"
                  class="form-control"
                  :value="dateInfo.afternoonPatients"
                  @input="(e) => handlePatientsChange(index, 'afternoon', e)"
                  min="0"
                  :disabled="props.patients_based_on_time"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Remove Button -->
        <div class="col-12 d-flex justify-content-end">
          <button
            v-if="dates.length > 1"
            type="button"
            class="btn btn-outline-danger"
            @click="removeDate(index)"
          >
            <i class="fas fa-trash"></i> Remove Date
          </button>
        </div>
      </div>
    </div>

    <!-- Add Date Button -->
    <div class="d-flex justify-content-end">
      <button type="button" class="btn btn-outline-primary" @click="addDate">
        <i class="fas fa-plus"></i> Add Date
      </button>
    </div>
  </div>
</template>

<style scoped>
.custom-dates {
  width: 100%;
}

.input-group {
  display: flex;
  align-items: center;
}

h6 {
  color: #495057;
  font-weight: 600;
}
</style>