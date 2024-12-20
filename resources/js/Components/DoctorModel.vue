<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';

import { useToastr } from '../Components/toster';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  doctorData: {
    type: Object,
    default: () => ({}),
  },
});


const emit = defineEmits(['close', 'doctorUpdated']);
const toaster = useToastr();
const errors = ref({});
const showPassword = ref(false);
const patients_based_on_time = ref(false);
const number_of_patients_per_day = ref(0);
const time_slot = ref('');
const appointmentBookingWindow = ref(0);


const doctor = ref({
  id: props.doctorData?.id || null,
  name: props.doctorData?.name || '',
  email: props.doctorData?.email || '',
  phone: props.doctorData?.phone || '',
  specialization: props.doctorData?.specialization || '',
  frequency: props.doctorData?.frequency || '',
  start_time: props.doctorData?.start_time || '',
  end_time: props.doctorData?.end_time || '',
  days: Array.isArray(props.doctorData?.days)
    ? props.doctorData?.days
    : props.doctorData?.days?.split(',').filter(Boolean) || [],
    appointmentBookingWindow: props.doctorData?.appointmentBookingWindow || 1, 
    password: ''
});

const isEditMode = computed(() => !!props.doctorData?.id);


const handlePatientSelectionChange = () => {
  if (patients_based_on_time.value) {
    number_of_patients_per_day.value = 0;
  } else {
    time_slot.value = '';
  }
};

watch(
  () => props.doctorData,
  (newValue) => {
    let parsedDays = [];
    if (newValue?.days) {
      try {
        // Handle both string JSON and array cases
        parsedDays = typeof newValue.days === 'string'
          ? JSON.parse(newValue.days)
          : newValue.days;
      } catch (e) {
        parsedDays = newValue.days.split(',').filter(Boolean);
      }
    }

    doctor.value = {
      ...doctor.value,
      id: newValue?.id || null,
      name: newValue?.name || '',
      email: newValue?.email || '',
      phone: newValue?.phone || '',
      specialization: newValue?.specialization || '',
      frequency: newValue?.frequency || '',
      start_time: newValue?.start_time || '',
      end_time: newValue?.end_time || '',
      appointmentBookingWindow: newValue?.appointmentBookingWindow || 1,
      days: parsedDays,

      password: ''
    };

    patients_based_on_time.value = newValue?.patients_based_on_time || false;
    number_of_patients_per_day.value = newValue?.number_of_patients_per_day || 0;
    time_slot.value = newValue?.time_slot || '';
  },
  { immediate: true, deep: true }
);



const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const closeModal = () => {
  errors.value = {};
  emit('close');
};

const handleUserUpdate = () => {
  emit('doctorUpdated');  // This should trigger getDoctors() in the parent component
  closeModal();
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
const getDoctorSchema = (isEditMode) => {
  const baseSchema = {
    name: yup.string().required('Name is required'),
    email: yup.string().email('Invalid email format').required('Email is required'),
    phone: yup
      .string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
    specialization: yup.string().required('Specialization is required'),
    frequency: yup.string().required('Frequency is required'),
    start_time: yup.string().required('Start time is required'),
    end_time: yup.string().required('End time is required'),
    days: yup.array().min(1, 'Select at least one day'),
    appointmentBookingWindow: yup
  .number()
  .required('Please select a booking window')
  .oneOf([1, 3, 5], 'Invalid booking window'),
  };

  // Only add password validation if not in edit mode or if password is provided
  if (!isEditMode) {
    // For new doctors, password is required
    baseSchema.password = yup.string()
      .required('Password is required')
      .min(8, 'Password must be at least 8 characters');
  } else {
    // For edit mode, only validate password if it's provided
    baseSchema.password = yup.string()
      .transform((value) => (value === '' ? undefined : value))
      .optional()
      .min(8, 'Password must be at least 8 characters');
  }

  return yup.object(baseSchema);
};

const doctorSchema = computed(() => getDoctorSchema(isEditMode.value));

const submitForm = async (values, { resetForm }) => {
  try {
    const submissionData = {
      ...doctor.value,
      ...values,
      ppointmentBookingWindow: doctor.value.appointmentBookingWindow,
      days: Array.isArray(values.days) ? values.days : [],
      patients_based_on_time: patients_based_on_time.value,
      number_of_patients_per_day: number_of_patients_per_day.value,
      time_slot: time_slot.value
    };

    if (!submissionData.password?.trim()) {
      delete submissionData.password;
    }

    if (isEditMode.value) {
      await axios.put(`/api/doctors/${submissionData.id}`, submissionData);
      toaster.success('Doctor updated successfully');

    } else {
      console.log(submissionData);
      
      await axios.post('/api/doctors', submissionData);
      toaster.success('Doctor added successfully');
    }

    handleUserUpdate();
    resetForm();
  } catch (error) {
    handleBackendErrors(error);
  }
};
const validateDays = (value) => {
  if (!Array.isArray(value) || value.length === 0) {
    return 'Select at least one day';
  }
  return true;
};


</script>

<template>
  <div class="modal fade overflow-auto" :class="{ show: showModal }" tabindex="-1" aria-labelledby="doctorModalLabel"
    aria-hidden="true" v-if="showModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Doctor' : 'Add Doctor' }}</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm" :validation-schema="doctorSchema">
            <!-- First Row: Name and Email -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="name" class="form-label fs-5">Name</label>
                <Field type="text" id="name" name="name" :class="{ 'is-invalid': validationErrors.name }"
                  v-model="doctor.name" class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.name }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="email" class="form-label fs-5">Email</label>
                <Field type="email" id="email" name="email" :class="{ 'is-invalid': validationErrors.email }"
                  v-model="doctor.email" class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.email }}</span>
              </div>
            </div>

            <!-- Second Row: Phone and Specialization -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="phone" class="form-label fs-5">Phone</label>
                <Field type="tel" id="phone" name="phone" :class="{ 'is-invalid': validationErrors.phone }"
                  v-model="doctor.phone" class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.phone }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="specialization" class="form-label fs-5">Specialization</label>
                <Field type="text" id="specialization" name="specialization"
                  :class="{ 'is-invalid': validationErrors.specialization }" v-model="doctor.specialization"
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
                <Field type="number" name="number_of_patients_per_day" v-model="number_of_patients_per_day"
                  class="form-control form-control-lg" min="0" />
              </div>
              <div v-if="patients_based_on_time" class="col-md-6 mb-4">
                <label for="time_slot" class="form-label fs-5">Time Slot for Patients</label>
                <Field name="time_slot" v-model="time_slot" class="form-control form-control-lg"
                  placeholder="Select time slot" />
              </div>
            </div>

            <!-- Frequency and Start Time -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="frequency" class="form-label fs-5">Frequency</label>
                <Field as="select" id="frequency" name="frequency" :class="{ 'is-invalid': validationErrors.frequency }"
                  v-model="doctor.frequency" class="form-control form-control-lg">
                  <option value="" disabled selected>Select Frequency</option>
                  <option value="weekly">daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </Field>
                <span class="text-sm invalid-feedback">{{ validationErrors.frequency }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="start_time" class="form-label fs-5">Start Time</label>
                <Field type="time" id="start_time" name="start_time"
                  :class="{ 'is-invalid': validationErrors.start_time }" v-model="doctor.start_time"
                  class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.start_time }}</span>
              </div>
            </div>

            <!-- End Time and Days -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="end_time" class="form-label fs-5">End Time</label>
                <Field type="time" id="end_time" name="end_time" :class="{ 'is-invalid': validationErrors.end_time }"
                  v-model="doctor.end_time" class="form-control form-control-lg" />
                <span class="text-sm invalid-feedback">{{ validationErrors.end_time }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="days" class="form-label fs-5">Days</label>
                <div class="d-flex flex-wrap">
                  <!-- Remove this line -->
                  <!-- {{  JSON.stringify(doctor.days) }} -->
                  <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']"
                    :key="day" class="form-check me-3">
                    <Field type="checkbox" :id="day.toLowerCase()" name="days" :value="day" v-model="doctor.days"
                      class="form-check-input" />
                    <label class="form-check-label mx-1" :for="day.toLowerCase()">{{ day }}</label>
                  </div>
                </div>
                <span class="text-sm invalid-feedback" v-if="validationErrors.days">{{ validationErrors.days }}</span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-4">
                  <label for="appointmentBookingWindow" class="form-label fs-5">Appointment Booking Window</label>
                  <Field
  as="select"
  id="appointmentBookingWindow"
  name="appointmentBookingWindow"
  v-model="doctor.appointmentBookingWindow"
  class="form-control form-control-lg"
  :class="{ 'is-invalid': validationErrors.appointmentBookingWindow }"
>
  <option value="" disabled>Select Booking Window</option>
  <option :value="1">1 Month</option>
  <option :value="3">3 Months</option>
  <option :value="5">5 Months</option>
</Field>

                  <span class="text-sm invalid-feedback">
                    {{ validationErrors.appointmentBookingWindow }}
                  </span>
                </div>
              <div class="col-md-6 mb-4">
                <label for="password" class="form-label fs-5">
                  {{ isEditMode ? 'Password (leave blank to keep current)' : 'Password' }}
                </label>
                <div class="input-group">
                  <Field :type="showPassword ? 'text' : 'password'" id="password" name="password"
                    :class="{ 'is-invalid': validationErrors.password }" v-model="doctor.password"
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
                {{ isEditMode ? 'Update Doctor' : 'Add Doctor' }}
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