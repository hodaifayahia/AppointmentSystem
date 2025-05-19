
<script setup>
import { ref, watch ,computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';

const props = defineProps({
  showModal: Boolean,
  attributeData: Object,
  placeholderId: Number,
  isEditmode: Boolean,
});
const resetForm = () => {
  formData.value = {
    name: '',
    value: '',
    input_type: false,
    placeholder_id: props.placeholderId,
  };
};
const emit = defineEmits(['close', 'attributeUpdate']);

const toaster = useToastr();
const formData = ref({
  name: '',
  value: '',
  input_type: false,
  placeholder_id: props.placeholderId,
});

const isEditmode = computed(() => {
  return props.isEditmode || (props.attributeData && props.attributeData.id);
});

// Fix: Move the form initialization logic inside the watch
watch(() => props.attributeData, (newVal) => {
  if (newVal && isEditmode.value) {
    formData.value = { ...newVal, placeholder_id: props.placeholderId };
    console.log('Editing attribute:', formData.value);
  } else {
    resetForm();
    console.log('Creating new attribute');
  }
}, { immediate: true });

// Remove the standalone if-else block for form initialization



const closeModal = () => {
  emit('close');
  resetForm();
};

const handleSubmit = async () => {
  if (!formData.value.name) {
    toaster.error('Name is required');
    return;
  }

  try {
    const url = isEditmode.value 
      ? `/api/attributes/${formData.value.id}`
      : '/api/attributes';
    const method = isEditmode.value ? 'put' : 'post';
    
    await axios[method](url, formData.value);
    toaster.success(`Attribute ${isEditmode.value ? 'updated' : 'created'} successfully`);
    emit('attributeUpdate');
    closeModal();
  } catch (error) {
    toaster.error(error.response?.data?.message || 'An error occurred');
  }
};
</script>

<template>
    <div class="modal fade" :class="{ show: showModal, 'd-block': showModal }" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">{{ isEditmode ? 'Edit Attribute' : 'Add New Attribute' }}</h5>
            <button type="button" class="close text-white" @click="closeModal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form @submit.prevent="handleSubmit">
            <div class="modal-body">
              <div class="form-group">
                <label for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" v-model="formData.name" required>
              </div>
              <div class="form-group">
                <label for="name">Textarea</label>
                <input type="checkbox" class="ml-2 w-20 h-20" id="input_type" v-model="formData.input_type" >
              </div>
              <div class="form-group">
                <label for="value">Value (optional)</label>
                <input type="text" class="form-control" id="value" v-model="formData.value">
                <small class="form-text text-muted">Leave blank if not applicable</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i :class="isEditmode ? 'fas fa-save' : 'fas fa-plus'"></i>
                {{ isEditmode ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
</template>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-header {
  padding: 1rem;
}

.modal-title {
  font-size: 1.25rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-control {
  border-radius: 0.25rem;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #004085;
}
</style>