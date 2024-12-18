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
  userData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'userUpdated']);
const toaster = useToastr();
const errors = ref({});

const user = ref({ 
  id: props.userData?.id || null,
  name: props.userData?.name || '',
  email: props.userData?.email || '',
  phone: props.userData?.phone || '',
  password: ''
});

const isEditMode = computed(() => !!props.userData?.id);
const showPassword = ref(false);

watch(
  () => props.userData,
  (newValue) => {
    user.value = {
      id: newValue?.id || null,
      name: newValue?.name || '',
      email: newValue?.email || '',
      phone: newValue?.phone || '',
      password: ''
    };
  },
  { immediate: true, deep: true }
);

const getUserSchema = (isEditMode) => {
  const baseSchema = {
    name: yup.string().required('Name is required'),
    email: yup.string().email('Invalid email format').required('Email is required'),
    phone: yup
      .string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
  };

  // Only add password validation if not in edit mode or if password is provided
  if (!isEditMode) {
    // For new users, password is required
    baseSchema.password = yup.string()
      .required('Password is required')
      .min(8, 'Password must be at least 8 characters');
  } else {
    // For edit mode, only validate password if it's provided
    baseSchema.password = yup.string()
      .transform(value => value === '' ? undefined : value)
      .optional()
      .min(8, 'Password must be at least 8 characters');
  }

  return yup.object(baseSchema);
};

const userSchema = computed(() => getUserSchema(isEditMode.value));

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const closeModal = () => {
  errors.value = {};
  emit('close');
};

const handleBackendErrors = (error) => {
  if (error.response?.data?.errors) {
    // Check and handle validation errors for email and phone only
    const fieldErrors = ['email', 'phone'];

    fieldErrors.forEach((field) => {
      if (error.response.data.errors[field]) {
        toaster.error(error.response.data.errors[field][0]);
      }
    });
  } else if (error.response?.data?.message) {
    // Handle single error message
    toaster.error(error.response.data.message);
  } else {
    // Handle generic error
    toaster.error('An unexpected error occurred');
  }
};


const submitForm = async (values) => {
  try {
    const submissionData = { 
      ...user.value, 
      ...values 
    };
    
    // Only include password in submission if it's not empty
    if (!submissionData.password || submissionData.password.trim() === '') {
      delete submissionData.password;
    }

    if (isEditMode.value) {
      await axios.put(`/api/users/${submissionData.id}`, submissionData);
      toaster.success('User updated successfully');
    } else {
      await axios.post('/api/users', submissionData);
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
  <div class="modal fade" :class="{ show: showModal }" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true"
    v-if="showModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditMode ? 'Edit User' : 'Add User' }}</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm"
            :validation-schema="userSchema">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field type="text" id="name" name="name" 
                :class="{ 'is-invalid': validationErrors.name || errors.name }" 
                v-model="user.name"
                class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.name || (errors.name && errors.name[0]) }}
              </span>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <Field type="email" id="email" name="email" 
                :class="{ 'is-invalid': validationErrors.email || errors.email }" 
                v-model="user.email"
                class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.email || (errors.email && errors.email[0]) }}
              </span>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <Field type="tel" id="phone" name="phone" 
                :class="{ 'is-invalid': validationErrors.phone || errors.phone }" 
                v-model="user.phone"
                class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.phone || (errors.phone && errors.phone[0]) }}
              </span>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">
                {{ isEditMode ? 'Password (leave blank to keep current)' : 'Password' }}
              </label>
              <div class="input-group">
                <Field :type="showPassword ? 'text' : 'password'" 
                  id="password" 
                  name="password"
                  :class="{ 'is-invalid': validationErrors.password || errors.password }" 
                  v-model="user.password" 
                  class="form-control" />
                <button type="button" class="btn btn-outline-secondary" @click="togglePasswordVisibility">
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <span class="text-sm invalid-feedback">
                {{ validationErrors.password || (errors.password && errors.password[0]) }}
              </span>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-outline-primary">
                {{ isEditMode ? 'Update User' : 'Add User' }}
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

.input-group {
  display: flex;
  align-items: center;
}

.invalid-feedback {
  display: block;
  color: red;
  font-size: 0.875rem;
}
</style>