<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import 'font-awesome/css/font-awesome.min.css';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  isEditMode: {
    type: Boolean,
    default: false,
  },
  appointmentBookingWindow: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const months = ref([
  { name: 'January', value: 1, is_available: false },
  { name: 'February', value: 2, is_available: false },
  { name: 'March', value: 3, is_available: false },
  { name: 'April', value: 4, is_available: false },
  { name: 'May', value: 5, is_available: false },
  { name: 'June', value: 6, is_available: false },
  { name: 'July', value: 7, is_available: false },
  { name: 'August', value: 8, is_available: false },
  { name: 'September', value: 9, is_available: false },
  { name: 'October', value: 10, is_available: false },
  { name: 'November', value: 11, is_available: false },
  { name: 'December', value: 12, is_available: false },
]);

const isDropdownOpen = ref(false);
const selectedMonths = ref([]);
const validationErrors = ref({
  selectedMonths: '',
});

const currentMonth = computed(() => new Date().getMonth() + 1);

const isMonthDisabled = (monthValue) => monthValue < currentMonth.value;

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

const toggleMonthSelection = (month) => {
  if (!isMonthDisabled(month.value)) {
    month.is_available = !month.is_available;

    if (month.is_available) {
      selectedMonths.value.push(month);
    } else {
      const index = selectedMonths.value.findIndex((m) => m.value === month.value);
      selectedMonths.value.splice(index, 1);
    }

    emit('update:modelValue', selectedMonths.value);
    validationErrors.value.selectedMonths = '';
  }
};

const removeMonth = (index) => {
  const removedMonth = selectedMonths.value[index];
  removedMonth.is_available = false;
  selectedMonths.value.splice(index, 1);
  emit('update:modelValue', selectedMonths.value);
};

const validateMonths = () => {
  if (selectedMonths.value.length === 0) {
    validationErrors.value.selectedMonths = 'Please select at least one month.';
    return false;
  }
  validationErrors.value.selectedMonths = '';
  return true;
};

// Updated onMounted logic to properly handle month names
onMounted(() => {
  if (props.isEditMode && props.appointmentBookingWindow.length > 0) {
    props.appointmentBookingWindow.forEach((booking) => {
      const monthObj = months.value.find((m) => m.value === booking.month);
      if (monthObj && booking.is_available) {
        monthObj.is_available = true;
        selectedMonths.value.push({
          name: monthObj.name,
          value: monthObj.value,
          is_available: true
        });
      }
    });
  }
});

watch(
  () => props.modelValue,
  (newValue) => {
    selectedMonths.value = newValue.map(month => {
      const monthObj = months.value.find(m => m.value === month.value);
      return {
        ...month,
        name: monthObj ? monthObj.name : '' // Ensure name is included
      };
    });

    months.value.forEach((month) => {
      month.is_available = newValue.some((m) => m.value === month.value);
    });
  },
  { deep: true }
);
</script>

<template>
  <div class="mb-3">
    <label for="monthDropdown" class="form-label">
      Appointment Booking Window
    </label>


    <div class="dropdown">
      <button @click="toggleDropdown"
        class="btn btn-secondary dropdown-toggle w-100 d-flex justify-content-between align-items-center" type="button"
        id="monthDropdown" :aria-expanded="isDropdownOpen">
        <span>
          {{ selectedMonths.length ? `${selectedMonths.length} months selected` : 'Select Months' }}
        </span>
      </button>

      <ul v-if="isDropdownOpen" class="dropdown-menu w-100 show" aria-labelledby="monthDropdown">
        <li v-for="month in months" :key="month.value">
          <a class="dropdown-item d-flex justify-content-between align-items-center" href="#"
            @click.prevent="toggleMonthSelection(month)" :class="{
              'disabled text-muted': isMonthDisabled(month.value),
              'active': month.is_available,
            }">
            <span>{{ month.name }}</span>
            <span v-if="month.is_available" class="badge bg-primary rounded-pill">✓</span>
          </a>
        </li>
      </ul>
    </div>

    <div class="mt-2 d-flex flex-wrap gap-2">
      <div v-for="(month, index) in selectedMonths" :key="index"
        class="badge bg-primary d-flex align-items-center p-2 ml-1 mb-2 gap-2">
        <span>{{ month.name }}</span>
        <button @click="removeMonth(index)" class="fas fa-times text-white" aria-label="Remove"
          style="background: none; border: none; cursor: pointer; font-size: 0.875rem;"></button>
      </div>
    </div>

    <div v-if="validationErrors.selectedMonths" class="invalid-feedback d-block">
      {{ validationErrors.selectedMonths }}
    </div>
  </div>
</template>