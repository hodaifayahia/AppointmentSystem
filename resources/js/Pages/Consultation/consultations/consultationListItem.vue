<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  consultation: Object,
  index: Number
});

const emit = defineEmits(['delete']);

const handleDelete = () => {
  emit('delete', props.consultation.id, props.consultation.patient_name);
};

// Helper to determine status badge color
const getStatusBadgeClass = (status) => {
  switch (status.toLowerCase()) {
    case 'completed':
      return 'bg-success';
    case 'pending':
      return 'bg-warning';
    case 'cancelled':
      return 'bg-danger';
    default:
      return 'bg-secondary';
  }
};
</script>

<template>
  <tr class="consultation-row">
    <td class="ps-4">{{ index + 1 }}</td>
    <td>
      <div class="d-flex align-items-center">
        <i class="fas fa-user me-2 text-primary"></i>
        <strong>{{ consultation.patient_name }}</strong>
      </div>
    </td>
    <td>
      <div v-if="consultation.doctor_name" class="d-flex align-items-center">
        <i class="fas fa-user-md me-1 text-secondary"></i>
        {{ consultation.doctor_name }}
      </div>
      <span v-else class="text-muted fst-italic">No Doctor</span>
    </td>
    <td>{{ consultation.date }}</td>
    <td>
      <span 
        class="badge rounded-pill"
        :class="getStatusBadgeClass(consultation.status)"
      >
        {{ consultation.status }}
      </span>
    </td>
    <td>
      <div class="btn-group d-flex justify-content-center">
        <button 
          class="btn btn-sm btn-outline-danger action-btn" 
          @click.stop="handleDelete" 
          title="Delete Consultation"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.consultation-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.consultation-row:hover {
  background-color: rgba(0, 123, 255, 0.05) !important;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>