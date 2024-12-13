<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';

// Props received by the component
const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  userData: {
    type: Object,
    default: () => ({ name: '', email: '', phone: '', password: '' }),
  },
});

// Validation schema
const Schema = yup.object({
  name: yup.string().required('Name is required'),
  email: yup.string().email('Invalid email format').required('Email is required'),
  password: yup.string().min(8, 'Password must be at least 8 characters').required('Password is required'),
  phone: yup.string().matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits').required('Phone number is required'),
});

// Emit events to parent
const emit = defineEmits(['close', 'submit']);

// Local reactive state
const user = ref({ ...props.userData });
const isEditMode = ref(!!props.userData.id);
const showPassword = ref(false);

// Methods
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
watch(
  () => props.userData,
  (newValue) => {
    user.value = { ...newValue };
    isEditMode.value = !!newValue.id;
  },
  { immediate: true, deep: true }
);


const closeModal = () => {
  emit('close');
};

const submitForm = (values) => {
  emit('submit', values);
  closeModal();
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
          <Form @submit="submitForm" :validation-schema="Schema" v-slot="{ errors }">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field type="text" id="name" name="name" :class="{ 'is-invalid': errors.name }" class="form-control" />
              <span class="text-sm invalid-feedback">{{ errors.name }}</span>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <Field type="email" id="email" name="email" :class="{ 'is-invalid': errors.email }"
                class="form-control" />
              <span class="text-sm invalid-feedback">{{ errors.email }}</span>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <Field type="tel" id="phone" name="phone" :class="{ 'is-invalid': errors.phone }" class="form-control" />
              <span class="text-sm invalid-feedback">{{ errors.phone }}</span>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <!-- Bind the 'name' attribute to 'password' -->
                <Field :type="showPassword ? 'text' : 'password'" id="password" name="password"
                  :class="{ 'is-invalid': errors.password }" class="form-control" />
                <button type="button" class="btn btn-outline-secondary" @click="togglePasswordVisibility">
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <!-- Display error message -->
              <span class="text-sm invalid-feedback">{{ errors.password }}</span>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-outline-primary">{{ isEditMode ? 'Update User' : 'Add User' }}</button>
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
  color: red;
  font-size: 0.875rem;
}
</style>
