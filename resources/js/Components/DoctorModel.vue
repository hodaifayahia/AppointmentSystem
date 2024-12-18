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
const showPassword = ref(false);
const patients_based_on_time = ref(false);
const number_of_patients_per_day = ref(0);
const time_slot = ref('');

const user = ref({ 
  id: props.userData?.id || null,
  name: props.userData?.name || '',
  email: props.userData?.email || '',
  phone: props.userData?.phone || '',
  specialization: props.userData?.specialization || '',
  frequency: props.userData?.frequency || '',
  start_time: props.userData?.start_time || '',
  end_time: props.userData?.end_time || '',
  days: props.userData?.days || [],
  password: ''
});

const isEditMode = computed(() => !!props.userData?.id);

const handlePatientSelectionChange = () => {
  if (patients_based_on_time.value) {
    number_of_patients_per_day.value = 0;
  } else {
    time_slot.value = '';
  }
};

watch(
  () => props.userData,
  (newValue) => {
    user.value = {
      id: newValue?.id || null,
      name: newValue?.name || '',
      email: newValue?.email || '',
      phone: newValue?.phone || '',
      specialization: newValue?.specialization || '',
      frequency: newValue?.frequency || '',
      start_time: newValue?.start_time || '',
      end_time: newValue?.end_time || '',
      days: newValue?.days || [],
      password: ''
    };
  },
  { immediate: true, deep: true }
);

const getUserSchema = (isEditMode) => {
  return yup.object({
    name: yup.string().required('Name is required'),
    email: yup.string().email('Invalid email format').required('Email is required'),
    phone: yup.string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
    specialization: yup.string().required('Specialization is required'),
    frequency: yup.string().required('Frequency is required'),
    start_time: yup.string().required('Start time is required'),
    end_time: yup.string().required('End time is required'),
    days: yup.array().min(1, 'Select at least one day'),
    password: isEditMode 
      ? yup.string().optional().min(8, 'Password must be at least 8 characters')
      : yup.string().required('Password is required').min(8, 'Password must be at least 8 characters')
  });
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
    Object.entries(error.response.data.errors).forEach(([field, messages]) => {
      toaster.error(messages[0]);
    });
  } else if (error.response?.data?.message) {
    toaster.error(error.response.data.message);
  } else {
    toaster.error('An unexpected error occurred');
  }
};

const submitForm = async (values) => {
  try {
    const submissionData = { 
      ...user.value, 
      ...values,
      patients_based_on_time: patients_based_on_time.value,
      number_of_patients_per_day: number_of_patients_per_day.value,
      time_slot: time_slot.value
    };
    
    if (!submissionData.password?.trim()) {
      delete submissionData.password;
    }

    if (isEditMode.value) {
      await axios.put(`/api/doctors/${submissionData.id}`, submissionData);
      toaster.success('User updated successfully');
    } else {
      await axios.post('/api/doctors', submissionData);
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
  <div class="modal fade overflow-auto" :class="{ show: showModal }" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true"
    v-if="showModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditMode ? 'Edit User' : 'Add User' }}</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm" :validation-schema="userSchema">
            <!-- First Row: Name and Email -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="name" class="form-label fs-5">Name</label>
                <Field type="text" id="name" name="name" 
                  :class="{ 'is-invalid': validationErrors.name }" 
                  v-model="user.name"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.name }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="email" class="form-label fs-5">Email</label>
                <Field type="email" id="email" name="email" 
                  :class="{ 'is-invalid': validationErrors.email }" 
                  v-model="user.email"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.email }}</span>
              </div>
            </div>

            <!-- Second Row: Phone and Specialization -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="phone" class="form-label fs-5">Phone</label>
                <Field type="tel" id="phone" name="phone" 
                  :class="{ 'is-invalid': validationErrors.phone }" 
                  v-model="user.phone"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.phone }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="specialization" class="form-label fs-5">Specialization</label>
                <Field type="text" id="specialization" name="specialization"
                  :class="{ 'is-invalid': validationErrors.specialization }" 
                  v-model="user.specialization"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.specialization }}</span>
              </div>
            </div>

            <!-- Patient Selection Row -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="patients_based_on_time" class="form-label fs-5">Patients Based on Time</label>
                <Field as="select" name="patients_based_on_time" v-model="patients_based_on_time"
                  class="form-control form-control-lg" @change="handlePatientSelectionChange">
                  <option :value="false">Fixed Number of Patients</option>
                  <option :value="true">Based on Time</option>
                </Field>
              </div>
              <div v-if="!patients_based_on_time" class="col-md-6 mb-4">
                <label for="number_of_patients_per_day" class="form-label fs-5">Number of Patients Per Day</label>
                <Field type="number" name="number_of_patients_per_day"
                  v-model="number_of_patients_per_day" 
                  class="form-control form-control-lg" 
                  min="0" />
              </div>
              <div v-if="patients_based_on_time" class="col-md-6 mb-4">
                <label for="time_slot" class="form-label fs-5">Time Slot for Patients</label>
                <Field name="time_slot" v-model="time_slot" 
                  class="form-control form-control-lg"
                  placeholder="Select time slot" />
              </div>
            </div>

            <!-- Frequency and Start Time -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="frequency" class="form-label fs-5">Frequency</label>
                <Field as="select" id="frequency" name="frequency"
                  :class="{ 'is-invalid': validationErrors.frequency }" 
                  v-model="user.frequency"
                  class="form-control form-control-lg">
                  <option value="" disabled selected>Select Frequency</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </Field>
                <span class="text-sm invalid-feedback">{{ validationErrors.frequency }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="start_time" class="form-label fs-5">Start Time</label>
                <Field type="time" id="start_time" name="start_time"
                  :class="{ 'is-invalid': validationErrors.start_time }" 
                  v-model="user.start_time"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.start_time }}</span>
              </div>
            </div>

            <!-- End Time and Days -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="end_time" class="form-label fs-5">End Time</label>
                <Field type="time" id="end_time" name="end_time"
                  :class="{ 'is-invalid': validationErrors.end_time }" 
                  v-model="user.end_time"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.end_time }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="days" class="form-label fs-5">Days</label>
                <div class="d-flex flex-wrap">
                  <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']"
                    :key="day" class="form-check me-3">
                    <Field type="checkbox" :id="day.toLowerCase()" name="days" :value="day"
                      v-model="user.days" class="form-check-input" />
                    <label class="form-check-label mx-1" :for="day.toLowerCase()">{{ day }}</label>
                  </div>
                </div>
                <span class="text-sm invalid-feedback">{{ validationErrors.days }}</span>
              </div>
            </div>

            <!-- Password Field -->
            <div class="row">
              <div class="col-md-12 mb-4">
                <label for="password" class="form-label fs-5">
                  {{ isEditMode ? 'Password (leave blank to keep current)' : 'Password' }}
                </label>
                <div class="input-group">
                  <Field :type="showPassword ? 'text' : 'password'" 
                    id="password" 
                    name="password"
                    :class="{ 'is-invalid': validationErrors.password }" 
                    v-model="user.password" 
                    class="form-control form-control-lg" />
                  <button type="button" class="btn btn-outline-secondary" @click="togglePasswordVisibility">
                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <span class="text-sm invalid-feedback">{{ validationErrors.password }}</span>
              </div>
            </div>

            <!-- Modal Footer -->
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

.modal-dialog {
  max-width: 800px;
}
</style>