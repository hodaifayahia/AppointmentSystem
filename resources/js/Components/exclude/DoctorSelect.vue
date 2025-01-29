<script setup>
const props = defineProps({
  modelValue: String,
  doctors: {
    type: Array,
    default: () => []
  }
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class=" mb-4 shadow-sm">
    <div class="">
      <label for="doctor-select" class="form-label">
        <i class="bi bi-person-badge me-2"></i>Select Doctor:
      </label>
      <select
        id="doctor-select"
        class="  form-control "
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
      >
        <option value="">All Doctors</option>
        <option v-for="doctor in doctors" :key="doctor" :value="doctor.id">
          {{ doctor.name }}
        </option>
      </select>
    </div>
  </div>
</template>


