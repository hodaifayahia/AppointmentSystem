<script setup>
import { ref, computed, defineProps, watch } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';

const props = defineProps({
  showModal: Boolean,
  specData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'specUpdate']);
const toastr = useToastr();

const specialization = ref({
  id: props.specData?.id || null,
  name: props.specData?.name || '',
  description: props.specData?.description || '',
  photo: props.specData?.photo || null
});

const isEditMode = computed(() => !!specialization.value.id);

// Schema validation
const specializationSchema = yup.object({
  name: yup.string().required('Name is required'),
  description: yup.string().nullable(),
  photo: yup.mixed().nullable().test('fileSize', 'File must be less than 2MB', (value) => {
    if (!value || !(value instanceof File)) return true;
    return value.size <= 2000000;
  })
});

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    specialization.value.photo = file;
  }
};
watch(() => props.specData, (newVal) => {
  specialization.value = {
    id: newVal?.id || null,
    name: newVal?.name || '',
    description: newVal?.description || '',
    photo: newVal?.photo || null
  };
}, { immediate: true , deep: true });

const closeModal = () => {
  specialization.value = {
    id: null,
    name: '',
    description: '',
    photo: null
  };
  emit('close');
};

const createFormData = (values) => {
  const formData = new FormData();
  
  // Add basic fields
  formData.append('name', values.name || '');
  formData.append('description', values.description || '');
  
  // Handle photo field
  if (values.photo instanceof File) {
    formData.append('photo', values.photo);
  }
  // Don't append anything if it's an existing photo URL
  // The backend will keep the existing photo
  
  // Add the ID for edit mode
  if (specialization.value.id) {
    formData.append('id', specialization.value.id);
  }

  return formData;
};
const submitForm = async (values) => {
  try {
    const formData = createFormData({
      ...specialization.value,
      ...values
    });

      const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json'
      }
    };

    if (isEditMode.value) {
      await axios.post(`/api/specializations/${specialization.value.id}`, formData, {
        ...config,
        headers: {
            ...config.headers,
            'X-HTTP-Method-Override': 'PUT'
          }
      });
      toastr.success('Specialization updated successfully');
    } else {
      await axios.post('/api/specializations', formData, config);
      toastr.success('Specialization created successfully');
    }

    emit('specUpdate');
    closeModal();
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.entries(error.response.data.errors).forEach(([_, messages]) => {
        messages.forEach(message => toastr.error(message));
      });
    } else {
      toastr.error(error.response?.data?.message || 'An unexpected error occurred');
    }
  }
};
</script>

<template>
  <div 
    v-if="showModal"
    class="modal fade show" 
    role="dialog"
    aria-modal="true"
    tabindex="-1"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEditMode ? 'Edit' : 'Add' }} Specialization
          </h5>
          <button 
            type="button" 
            class="btn btn-danger" 
            @click="closeModal"
            aria-label="Close"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <Form 
          @submit="submitForm" 
          :validation-schema="specializationSchema" 
          v-slot="{ errors }"
        >
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field 
                type="text"
                id="name"
                name="name"
                v-model="specialization.name"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
              />
              <div class="invalid-feedback" v-if="errors.name">
                {{ errors.name }}
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <Field 
                as="textarea"
                id="description"
                name="description"
                v-model="specialization.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
              />
              <div class="invalid-feedback" v-if="errors.description">
                {{ errors.description }}
              </div>
            </div>

            <div class="mb-3">
              <label for="photo" class="form-label">Photo</label>
              <input 
                type="file"
                id="photo"
                name="photo"
                class="form-control"
                :class="{ 'is-invalid': errors.photo }"
                @change="handleFileChange"
                accept="image/*"
              />
              <div class="invalid-feedback" v-if="errors.photo">
                {{ errors.photo }}
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="closeModal"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn btn-outline-primary"
            >
              {{ isEditMode ? 'Update' : 'Add' }} Specialization
            </button>
          </div>
        </Form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.invalid-feedback {
  display: block;
}
</style>