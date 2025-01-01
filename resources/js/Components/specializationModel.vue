<script setup>
import { ref, computed, defineProps, watch } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  specData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'specUpdate']);
const toastr = useToastr();
const errors = ref({});

const specialization = ref({
  id: props.specData?.id || null,
  name: props.specData?.name || '',
  description: props.specData?.description || '',
  photo: props.specData?.photo || null, // Add photo field
});

const isEditMode = computed(() => !!props.specData?.id);

watch(
  () => props.specData,
  (newValue) => {
    specialization.value = {
      id: newValue?.id || null,
      name: newValue?.name || '',
      description: newValue?.description || '',
      photo: newValue?.photo || null, // Update photo field
    };
  },
  { immediate: true, deep: true }
);

const specializationSchema = computed(() => yup.object({
  name: yup.string().required('Name is required'),
  description: yup.string().nullable(),
  photo: yup.mixed().nullable().test('fileSize', 'The file is too large', (value) => {
    if (value) {
      return value.size <= 2000000; // 2 MB size limit
    }
    return true;
  })
}));

const closeModal = () => {
  errors.value = {};
  emit('close');
};

const handleBackendErrors = (error) => {
  if (error.response?.data?.errors) {
    Object.keys(error.response.data.errors).forEach(field => {
      toastr.error(error.response.data.errors[field][0]);
    });
  } else if (error.response?.data?.message) {
    toastr.error(error.response.data.message);
  } else {
    toastr.error('An unexpected error occurred');
  }
};

const createFormData = (data) => {
  const formData = new FormData();

  // Iterate through each key in the provided data
  Object.keys(data).forEach((key) => {
    if (key === 'avatar' && data[key] instanceof File) {
      // For avatar, check if it's a file before appending
      formData.append(key, data[key], data[key].name);
    } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
      // Append other non-null, non-undefined, and non-empty fields
      formData.append(key, data[key]);
    }
  });

  return formData;
};

const submitForm = async (values) => {
  try {
    // Ensure merging with `user.value` does not overwrite essential properties
    const mergedValues = {
      ...user.value,
      ...values,
    };

    // Add `_method` for method spoofing only if in edit mode
    if (isEditMode.value) {
      mergedValues._method = 'PUT';
    }

    const formData = createFormData(mergedValues);

    // Determine the correct HTTP method and endpoint
    if (isEditMode.value) {
      await axios.post(`/api/users/${user.value.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      toaster.success('User updated successfully');
    } else {
      await axios.post('/api/users', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      toaster.success('User added successfully');
    }

    emit('userUpdated');
    closeModal();
  } catch (error) {
    handleBackendErrors(error);
  }
};

</script>
<template>
  <div class="modal fade" :class="{ show: showModal }" tabindex="-1" aria-labelledby="specializationModalLabel" aria-hidden="true" v-if="showModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Specialization' : 'Add Specialization' }}</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm" :validation-schema="specializationSchema">
            <!-- Existing fields -->
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field type="text" id="name" name="name" 
                :class="{ 'is-invalid': validationErrors.name || errors.name }" 
                v-model="specialization.name"
                class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.name || (errors.name && errors.name[0]) }}
              </span>
            </div>
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <Field as="textarea" id="description" name="description" 
                :class="{ 'is-invalid': validationErrors.description || errors.description }" 
                v-model="specialization.description"
                class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.description || (errors.description && errors.description[0]) }}
              </span>
            </div>
            <!-- New Photo Field -->
            <div class="mb-3">
              <label for="photo" class="form-label">Photo</label>
              <Field type="file" id="photo" name="photo" 
                :class="{ 'is-invalid': validationErrors.photo || errors.photo }" 
                class="form-control" 
                @change="(event) => specialization.value.photo = event.target.files[0]" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.photo || (errors.photo && errors.photo[0]) }}
              </span>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-outline-primary">
                {{ isEditMode ? 'Update Specialization' : 'Add Specialization' }}
              </button>
            </div>
          </Form>
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

.invalid-feedback {
  display: block;
  color: red;
  font-size: 0.875rem;
}
</style>