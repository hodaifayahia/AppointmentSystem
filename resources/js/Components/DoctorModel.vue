<script setup>
import { ref, computed, defineProps, defineEmits, watch, onUnmounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from '../Components/toster';
import DoctorSchedules from './Doctor/DoctorSchedules.vue';
import CustomDates from './Doctor/CustomDates.vue';

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

console.log(props.doctorData);

const emit = defineEmits(['close', 'doctorUpdated']);
const toaster = useToastr();
const errors = ref({});
const specializations = ref({});
const showPassword = ref(false);
const patients_based_on_time = ref(false);
const number_of_patients_per_day = ref(false);
const time_slot = ref('');

const doctor = ref({
  id: props.doctorData?.id || null,
  name: props.doctorData?.name || '',
  email: props.doctorData?.email || '',
  phone: props.doctorData?.phone || '',
  patients_based_on_time: props.doctorData?.patients_based_on_time || '',
  specialization: props.doctorData?.specialization || 0,  // Changed from 0 to ''
  frequency: props.doctorData?.frequency || '',
  avatar: props.doctorData?.avatar || null,
  customDates: [''],// Start with at least one date input
  appointmentBookingWindow: props.doctorData?.appointmentBookingWindow || 1,
  password: ''
});
const getSpecializations = async (page = 1) => {
  try {
    const response = await axios.get('/api/specializations'); // Changed endpoint to plural
    specializations.value = response.data.data || response.data; // Adjust based on your API response structure
  } catch (error) {
    console.error('Error fetching specializations:', error);
    error.value = error.response?.data?.message || 'Failed to load specializations';
  } finally {
  }
};
const isEditMode = computed(() => !!props.doctorData?.id);

const handlePatientSelectionChange = () => {
  if (patients_based_on_time.value) {
    number_of_patients_per_day.value = 0;
  } else {
    time_slot.value = '';
  }
};

const handleFrequencySelectionChange = () => {
  if (doctor.frequency !== 'monthly') {
    doctor.customDates = []; // Clear custom dates if not monthly
  } else if (doctor.customDates.length === 0) {
    doctor.customDates = ['']; // Ensure at least one date input if switching to monthly
  }
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
// Added specialization to validation schema
const getDoctorSchema = (isEditMode) => {
  const baseSchema = {
    name: yup.string().required('Name is required'),
    email: yup.string().email('Invalid email format').required('Email is required'),
    phone: yup
      .string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
    specialization: yup.string().required('Specialization is required'),  // Added validation
    frequency: yup.string().required('Frequency is required'),
    appointmentBookingWindow: yup
      .number()
      .required('Please select a booking window')
      .oneOf([1, 3, 5], 'Invalid booking window'),
  };

  if (!isEditMode) {
    baseSchema.password = yup.string()
      .required('Password is required')
      .min(8, 'Password must be at least 8 characters');
  } else {
    baseSchema.password = yup.string()
      .transform((value) => (value === '' ? undefined : value))
      .optional()
      .min(8, 'Password must be at least 8 characters');
  }

  return yup.object(baseSchema);
};
getSpecializations();

// Update the watch function to correctly set specialization

watch(
  () => props.doctorData,
  (newValue) => {
    doctor.value = {
      id: newValue?.id || null,
      name: newValue?.name || '',
      email: newValue?.email || '',
      phone: newValue?.phone || '',
      specialization: newValue?.specialization || '',
      frequency: newValue?.frequency || '',
      avatar: newValue?.avatar || null,
      appointmentBookingWindow: newValue?.appointmentBookingWindow || 1,
      customDates: Array.isArray(newValue?.customDates) ? [...newValue.customDates] : [], // Ensure it's always an array
      schedules: Array.isArray(newValue?.schedules) ? [...newValue.schedules] : [], // Ensure it's always an array
      password: ''
    };

    patients_based_on_time.value = newValue?.patients_based_on_time || false;
    number_of_patients_per_day.value = newValue?.number_of_patients_per_day || 0;
    time_slot.value = newValue?.time_slot || '';
  },
  { immediate: true, deep: true }
);


const calculatePatientsPerDay = (startTime, endTime, timeSlot) => {
  const start = new Date(`2000-01-01 ${startTime}`);
  const end = new Date(`2000-01-01 ${endTime}`);
  const diffInMinutes = (end - start) / (1000 * 60);
  return Math.floor(diffInMinutes / timeSlot);
};

// In your parent component
const scheduleData = ref([]);

const handleSchedulesUpdated = (newSchedules) => {
  scheduleData.value = newSchedules;
};
const imagePreview = ref(null);


const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (!file) {
    return;
  }

  // Check file size (2MB limit)
  if (file.size > 2 * 1024 * 1024) {
    errors.value = { ...errors.value, avatar: 'Image must be less than 2MB' };
    e.target.value = ''; // Reset the input
    return;
  }

  // Clear previous error if exists
  if (errors.value.avatar) {
    errors.value = { ...errors.value, avatar: null };
  }

  // Set the file and create preview
  doctor.value = {
    ...doctor.value,
    avatar: file
  };
  // Create and set preview URL
  const previewURL = URL.createObjectURL(file);
  imagePreview.value = previewURL;

  // Clean up previous preview URL to prevent memory leaks
  return () => {
    URL.revokeObjectURL(previewURL);
  };
};

// Clean up function for component unmount
onUnmounted(() => {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value);
  }
});
const submitForm = async (values, { resetForm }) => {
  try {
    const formData = new FormData();

    // Basic fields
    Object.entries(values).forEach(([key, value]) => {
      if (value !== null && value !== undefined && key !== 'avatar') {
        formData.append(key, value);
      }
    });

    // Handle boolean value explicitly
    formData.append('patients_based_on_time', patients_based_on_time.value ? '1' : '0');

    // Other fields
    formData.append('id', doctor.value.id);
    formData.append('appointmentBookingWindow', doctor.value.appointmentBookingWindow);
    formData.append('specialization', doctor.value.specialization);
    formData.append('time_slot', time_slot.value);

    // Handle schedules array
    scheduleData.value.forEach((schedule, index) => {
      Object.entries(schedule).forEach(([key, value]) => {
        formData.append(`schedules[${index}][${key}]`, value);
      });
    });

    // Handle days array
    if (Array.isArray(values.days)) {
      values.days.forEach((day, index) => {
        formData.append(`days[${index}]`, day);
      });
    }

    // Handle avatar
    if (doctor.value.avatar instanceof File) {
      formData.append('avatar', doctor.value.avatar);
    }

    // Method handling
    const method = isEditMode.value ? 'PUT' : 'POST';
    formData.append('_method', method);

    const url = isEditMode.value ? `/api/doctors/${doctor.value.id}` : '/api/doctors';

    await axios.post(url, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    toaster.success(`Doctor ${isEditMode.value ? 'updated' : 'added'} successfully`);
    handleUserUpdate();
    resetForm();
  } catch (error) {
    handleBackendErrors(error);
  }
};
onUnmounted(() => {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value);
  }
});


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
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm" :validation-schema="getDoctorSchema">
            <!-- First Row: Name and Email -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="name" class="form-label fs-5">Name</label>
                <Field type="text" id="name" name="name" :class="{ 'is-invalid': validationErrors.name }"
                  v-model="doctor.name" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.name }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="email" class="form-label fs-5">Email</label>
                <Field type="email" id="email" name="email" :class="{ 'is-invalid': validationErrors.email }"
                  v-model="doctor.email" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.email }}</span>
              </div>
            </div>

            <!-- Second Row: Phone and Specialization -->
            <div class="row">
              
              <div class="col-md-6 mb-4">
                <label for="phone" class="form-label fs-5">Phone</label>
                <Field type="tel" id="phone" name="phone" :class="{ 'is-invalid': validationErrors.phone }"
                  v-model="doctor.phone" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.phone }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="specialization" class="form-label fs-5">Specialization</label>
                <Field as="select" id="specialization" name="specialization"
                  :class="{ 'is-invalid': validationErrors.specialization }" v-model="doctor.specialization"
                  class="form-control form-control-md">
                  <option value="">Select Specialization</option>
                  <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                    {{  spec.name }} 
                  </option>
                </Field>
                <span class="text-sm invalid-feedback">{{ validationErrors.specialization }}</span>
              </div>

            </div>

            <!-- Patient Selection Row -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="patients_based_on_time" class="form-label fs-5">Patients Based on Time</label>
                <Field as="select" name="patients_based_on_time" v-model="doctor.patients_based_on_time"
                  class="form-control form-control-md" @change="handlePatientSelectionChange">
                  <option :value="false">Fixed Number of Patients</option>
                  <option :value="true">Based on Time</option>
                </Field>
              </div>
              <div v-if="!patients_based_on_time" class="col-md-6 mb-4">
                <label for="number_of_patients_per_day" class="form-label fs-5">Number of Patients Per Day</label>
                <Field type="number" name="number_of_patients_per_day" v-model="number_of_patients_per_day"
                  class="form-control form-control-md" min="0" />
              </div>
              <div v-if="patients_based_on_time" class="col-md-6 mb-4">
                <label for="time_slot" class="form-label fs-5">Time Slot for Patients</label>
                <Field name="time_slot" v-model="time_slot" class="form-control form-control-md"
                  placeholder="Select time slot" />
              </div>
            </div>

            <!-- Frequency and Start Time -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="frequency" class="form-label fs-5">Frequency</label>
                <Field as="select" id="frequency" name="frequency" :class="{ 'is-invalid': validationErrors.frequency }"
                  v-model="doctor.frequency" @change="handleFrequencySelectionChange"
                  class="form-control form-control-md">
                  <option value="" disabled>Select Frequency</option>
                  <option value="Daily">Daily</option>
                  <option value="Weekly">Weekly</option>
                  <option value="Monthly">Monthly</option>
                </Field>
                <span class="invalid-feedback text-sm" v-if="validationErrors.frequency">
                  {{ validationErrors.frequency }}
                </span>
              </div>

              <div class="col-md-6 mb-4">
                <div class="form-group">
                  <label for="avatar" class="form-label">Doctor's Photo</label>
                  <input type="file" id="avatar" accept="image/*" @change="handleImageChange" class="form-control-file"
                    :class="{ 'is-invalid': errors.avatar }" />
                  <div v-if="imagePreview" class="mt-2">
                    <img :src="imagePreview" class="img-thumbnail" style="max-height: 150px;">
                  </div>
                  <span v-if="errors.avatar" class="invalid-feedback">{{ errors.avatar }}</span>
                </div>
              </div>
            </div>


            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="appointmentBookingWindow" class="form-label fs-5">Appointment Booking Window</label>
                <Field as="select" id="appointmentBookingWindow" name="appointmentBookingWindow"
                  v-model="doctor.appointmentBookingWindow" class="form-control form-control-md"
                  :class="{ 'is-invalid': validationErrors.appointmentBookingWindow }">
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
                    class="form-control form-control-md" />
                  <button type="button" class="btn btn-outline-secondary" @click="togglePasswordVisibility">
                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <span class="text-sm invalid-feedback">{{ validationErrors.password }}</span>
              </div>
            </div>
           
            <div class="row">
              <div class="col-12" v-if="doctor.frequency === 'Daily'  || doctor.frequency === 'Weekly'">
                <DoctorSchedules :doctorId="doctor.id" :existingSchedules="doctor.schedules" :patients_based_on_time="patients_based_on_time"
                  :time_slot="time_slot" :number_of_patients_per_day="number_of_patients_per_day"
                  @schedulesUpdated="handleSchedulesUpdated" />
              </div>
              <div class="col-md-12 mb-4" v-if="doctor.frequency === 'Monthly'">
                <label class="form-label fs-5">Custom Dates</label>
                <CustomDates :existingSchedules="doctor.schedules" v-model="doctor.schedules" :patients_based_on_time="patients_based_on_time"
                  :time_slot="time_slot" :number_of_patients_per_day="number_of_patients_per_day"
                  @schedulesUpdated="handleSchedulesUpdated" />
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