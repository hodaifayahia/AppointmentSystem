<script setup>
import { defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { useToastr } from './toster.js';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  doctorData: {  // Changed from doctorta to doctorData
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'doctorDeleted']);
const toaster = useToastr();

const closeModal = () => {
  emit('close');
};

const handleDelete = async () => {
  try {
    
    await axios.delete(`/api/doctors/${props.doctorData.id}`,{params: {id:props.doctorData.id}});
    toaster.success('Doctor deleted successfully');
    emit('doctorDeleted');
    closeModal();
  } catch (error) {
    if (error.response?.data?.message) {
      toaster.error(error.response.data.message);
    } else {
      toaster.error('Failed to delete Doctor');
    }
  }
};
</script>

<template>
  <div class="modal fade" :class="{ show: showModal }" tabindex="-1" aria-hidden="true" v-if="showModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete User</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete Doctor "{{ doctorData.name }}"?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-outline-danger" @click="handleDelete">Delete User</button>
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
</style>