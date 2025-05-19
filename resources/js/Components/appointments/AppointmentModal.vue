<template>
  <div v-if="isVisible" class="modal-overlay">
    <div class="modal-content p-5">

      <button class="close-button bg-danger rounded-pill" @click="closeModal"> <i class="fas fa-times"></i>
      </button>
      <div class="modal-body">
        <AppointmentForm :doctorId="doctorId" :edit-mode="true" :specialization_id="specialization_id" @close="closeModal" :NextAppointment="true" :appointmentId="appointmentId" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import AppointmentForm from '../../Pages/Appointments/AppointmentForm.vue';

const props = defineProps({
  doctorId: {
    type: String,
    required: true
  },
  specialization_id: {
    type: String,
    required: true
  },
  appointmentId: {
    type: String,
    required: true
  },
  editMode: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['close']);

const isVisible = ref(true);

const closeModal = () => {
  isVisible.value = false;
  emit('close');
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: auto;
  /* Allow the overlay to scroll if content exceeds viewport */
  z-index: 100;
  /* Ensure it's on top of other content */
}

.modal-content {
  background-color: white;
  padding: 20px;
  z-index: 1000;
  /* Ensure it's on top of other content */
  border-radius: 10px;
  width: 100%;
  max-width: 1000px;
  position: relative;
  margin: 20px;
  /* Add margin to avoid sticking to edges */
  max-height: 90vh;
  /* Limit height to 90% of the viewport height */
  overflow-y: auto;
  /* Make the content scrollable */
}

.modal-body {
  max-height: calc(90vh - 100px);
  /* Adjust height to account for padding and close button */
  overflow-y: auto;
  /* Ensure the body content is scrollable */
}

.close-button {
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  z-index: 1001;
  /* Ensure the close button is above the content */
}
</style>