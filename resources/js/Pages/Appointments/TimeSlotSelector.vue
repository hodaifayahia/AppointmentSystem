<script setup>
import { computed } from 'vue';

const props = defineProps({
  slots: {
    type: Array,
    required: true
  },
  modelValue: String
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
  <div class="form-group mb-4">
    <label for="appointment_time" class="text-muted">Appointment Time</label>
    <select 
      :value="modelValue"
      @input="e => emit('update:modelValue', e.target.value)"
      id="appointment_time"
      class="form-control form-select-sm rounded-pill"
    >
      <option v-for="slot in slots" 
        :key="slot.time" 
        :value="slot.time"
      >
        {{ slot.time }}
      </option>
    </select>
  </div>
</template>
