<script setup>
import { ref, computed, defineProps, defineEmits, watch, onUnmounted, onMounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from '../Components/toster';
import DoctorSchedules from './Doctor/DoctorSchedules.vue';
import CustomDates from './Doctor/CustomDates.vue';
import AppointmentBookingWindowModel from './Doctor/AppointmentBookingWindowModel.vue';

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
const specializations = ref({});
const showPassword = ref(false);
const isLoading = ref(false);
const patients_based_on_time = ref(props.doctorData.patients_based_on_time);
const numberOfPatients = ref(
  Array.isArray(props.doctorData?.schedules)
    ? Math.max(0, ...props.doctorData.schedules.map(s => s.number_of_patients_per_day ?? 0))
    : 0
);

const selectedMonths = ref([]);
const time_slot = ref(props.doctorData.time_slot);

const doctor = ref({
  id: props.doctorData?.id || null,
  name: props.doctorData?.name || '',
  email: props.doctorData?.email || '',
  phone: props.doctorData?.phone || '',
  start_time_force: props.doctorData?.start_time_force || '',
  end_time_force: props.doctorData?.end_time_force || '',
  number_of_patients: props.doctorData?.number_of_patients || '',
  patients_based_on_time: props.doctorData?.patients_based_on_time || false,
  specialization: props.doctorData?.specialization || '',
  specialization_id: props.doctorData?.specialization_id || '',
  frequency: props.doctorData?.frequency || '',
  avatar: props.doctorData?.avatar || null,
  customDates: props.doctorData?.schedules,
  schedules: props.doctorData?.schedules || [],
  appointmentBookingWindow: props.doctorData?.appointment_booking_window,
  password: '',
  number_of_patients_per_day: Array.isArray(props.doctorData?.schedules)
    ? Math.max(0, ...props.doctorData.schedules.map(s => s.number_of_patients_per_day ?? 0))
    : 0, // Get the highest number_of_patients_per_day
  time_slot: props.doctorData?.time_slots || null,
});


const imagePreview = ref(props.doctorData?.avatar ? props.doctorData.avatar : null);

const getSpecializations = async (page = 1) => {
  try {
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching specializations:', error);
    error.value = error.response?.data?.message || 'Failed to load specializations';
  }
};

const isEditMode = computed(() => !!props.doctorData?.id);

const handleFrequencySelectionChange = () => {
  if (doctor.value.frequency !== 'Monthly') {
    doctor.value.customDates = [];
  } else if (doctor.value.customDates.length === 0) {
    doctor.value.customDates = [''];
  }
};

const closeModal = () => {
  errors.value = {};
  emit('close');
};

const handleUserUpdate = () => {
  emit('doctorUpdated');
  closeModal();
};

const getDoctorSchema = (isEditMode) => {
  const baseSchema = {
    name: yup.string().required('Name is required'),
    email: yup.string('the Username should not be the same as another username'),
    phone: yup
      .string(),
    specialization: yup.string().required('Specialization is required'),
    frequency: yup.string().required('Frequency is required'),
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
watch(
  () => props.doctorData,

  (newValue) => {
    console.log(props.doctorData);

    if (newValue) {
      // Compute the number from schedules
      const computedNumber = Array.isArray(newValue?.schedules)
        ? Math.max(0, ...newValue.schedules.map(s => s.number_of_patients_per_day ?? 0))
        : 0;

      doctor.value = {
        ...doctor.value,
        id: newValue?.id || null,
        name: newValue?.name || '',
        email: newValue?.email || '',
        phone: newValue?.phone || '',
        start_time_force: newValue?.start_time_force || '',
        end_time_force: newValue?.end_time_force || '',
        number_of_patients: newValue?.number_of_patients || '',
        patients_based_on_time: newValue?.patients_based_on_time || false,
        specialization: newValue?.specialization || '',
        specialization_id: newValue?.specialization_id || '',
        frequency: newValue?.frequency || '',
        avatar: newValue?.avatar || null,
        appointmentBookingWindow: newValue?.appointment_booking_window,
        customDates: Array.isArray(newValue?.schedules) ? [...newValue.schedules] : [],
        schedules: Array.isArray(newValue?.schedules) ? [...newValue.schedules] : [],
        password: '',
        // Only update number_of_patients_per_day if it is currently undefined
        number_of_patients_per_day: (doctor.value.number_of_patients_per_day === undefined)
          ? computedNumber
          : doctor.value.number_of_patients_per_day,
        time_slot: newValue?.time_slots ?? '',
      };

      if (newValue.avatar) {
        imagePreview.value = newValue.avatar;
      }

      if (newValue.appointment_booking_window) {
        selectedMonths.value = newValue.appointment_booking_window
          .filter(month => month.is_available)
          .map(month => ({
            value: month.month,
            is_available: true,
          }));
      }
    }
  },
  { immediate: true, deep: true }
);



const handleSchedulesUpdated = (newSchedules) => {
  if (Array.isArray(newSchedules)) {
    doctor.value.schedules = newSchedules;
  } else {
    console.error('New schedules is not an array:', newSchedules);
  }
};

const handlecustomDatesUpdated = (newSchedules) => {
  doctor.value.customDates = newSchedules;
};

const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (!file) {
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    errors.value = { ...errors.value, avatar: 'Image must be less than 2MB' };
    e.target.value = '';
    return;
  }

  if (errors.value.avatar) {
    errors.value = { ...errors.value, avatar: null };
  }

  doctor.value = {
    ...doctor.value,
    avatar: file
  };

  const previewURL = URL.createObjectURL(file);
  imagePreview.value = previewURL;

  return () => {
    URL.revokeObjectURL(previewURL);
  };
};

const handlePatientSelectionChange = () => {
  if (doctor.value.patients_based_on_time) {
    doctor.value.number_of_patients_per_day = 0;
  } else {
    doctor.value.time_slot = '';
  }
};
watch(
  selectedMonths,
  (newSelectedMonths) => {
    // Update doctor.appointmentBookingWindow with the latest selected months
    doctor.value.appointmentBookingWindow = newSelectedMonths.map((month) => ({
      value: month.value,
      is_available: month.is_available,
    }));
  },
  { deep: true }
);
onUnmounted(() => {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value);
  }
});
const submitForm = async (values, { setErrors, resetForm }) => {

  isLoading.value = true;    //
  try {
    // Update appointmentBookingWindow with the latest selected months
    doctor.value.appointmentBookingWindow = selectedMonths.value.map((month) => ({
      month: month.value,
      is_available: month.is_available,
    }));

    const formData = new FormData();

    // Basic fields
    Object.entries(values).forEach(([key, value]) => {
      if (value !== null && value !== undefined && key !== 'avatar') {
        formData.append(key, value);
      }
    });

    // Handle boolean value explicitly
    formData.append('patients_based_on_time', doctor.value.patients_based_on_time ? 1 : 0);

    // Other fields

    formData.append('id', doctor.value.id);
    formData.append('specialization', doctor.value.specialization_id);
    formData.append('time_slot', doctor.value.time_slot);
    formData.append('start_time', doctor.value.start_time_force);
    formData.append('end_time', doctor.value.end_time_force);
    formData.append('number_of_patients', doctor.value.number_of_patients);

    // Handle appointmentBookingWindow
    if (doctor.value.appointmentBookingWindow && Array.isArray(doctor.value.appointmentBookingWindow)) {
      doctor.value.appointmentBookingWindow.forEach((month, index) => {
        formData.append(`appointmentBookingWindow[${index}][month]`, parseInt(month.month, 10)); // Ensure month is an integer
        formData.append(`appointmentBookingWindow[${index}][is_available]`, month.is_available ? 1 : 0); // Convert boolean to 1 or 0
      });
    } else {
      console.error('appointmentBookingWindow is missing or not an array:', doctor.value.appointmentBookingWindow);
      throw new Error('appointmentBookingWindow is required and must be an array.');
    }

    // Handle schedules or customDates based on frequency
    if (doctor.value.frequency === 'Monthly') {
      if (doctor.value.customDates && Array.isArray(doctor.value.customDates)) {
        doctor.value.customDates.forEach((dateObj, index) => {
          if (typeof dateObj === 'object' && dateObj !== null) {
            Object.entries(dateObj).forEach(([key, value]) => {
              formData.append(`customDates[${index}][${key}]`, value);
            });
          } else {
            formData.append(`customDates[${index}]`, dateObj);
          }
        });
      } else {
        console.error('Custom dates is not an array:', doctor.value.customDates);
        throw new Error('Custom dates are required for Monthly frequency.');
      }
    } else {
      // Ensure schedules is always an array
      const schedulesArray = Array.isArray(doctor.value.schedules)
        ? doctor.value.schedules
        : doctor.value.schedules?.schedules
          ? doctor.value.schedules.schedules
          : [];

      if (schedulesArray && Array.isArray(schedulesArray)) {
        schedulesArray.forEach((schedule, index) => {
          if (schedule && typeof schedule === 'object') {
            Object.entries(schedule).forEach(([key, value]) => {
              formData.append(`schedules[${index}][${key}]`, value);
            });
          } else {
            console.error('Schedule is not an object:', schedule);
          }
        });
      } else {
        console.error('Schedules is not an array:', schedulesArray);
        throw new Error('Schedules are required for Daily or Weekly frequency.');
      }
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
    isLoading.value = false; handleUserUpdate();
    resetForm();
  } catch (error) {
    if (error.response?.data?.errors) {
      setErrors(error.response.data.errors);
    } else if (error.response?.data?.message) {
      toaster.error(error.response.data.message);
    } else {
      toaster.error('An unexpected error occurred');
    }
  }
};


onMounted(() => {
  getSpecializations();
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
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm"
            :validation-schema="getDoctorSchema(isEditMode)"> <!-- First Row: Name and Email -->
            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="name" class="form-label fs-5">Name</label>
                <Field type="text" id="name" name="name" :class="{ 'is-invalid': validationErrors.name }"
                  v-model="doctor.name" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.name }}</span>
              </div>
              <div class="col-md-6 mb-4">
                <label for="email" class="form-label fs-5">Username</label>
                <Field type="text" id="email" name="email" :class="{ 'is-invalid': validationErrors.email }"
                  v-model="doctor.email" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.email }}</span>
              </div>
            </div>
            <!-- Second Row: Phone and Specialization -->
            <div class="row ms-4">
              <!-- Force Setting Label -->
              <div class="col-12 mb-3">
                <h5 class="mb-0">Force Setting</h5>
                <small class="text-muted">Configure the appointment booking window.</small>
              </div>

              <!-- Start Time -->
              <div class="col-md-6 mb-3">
                <label for="start_time_force" class="form-label">Start Time</label>
                <Field type="time" id="start_time_force" name="start_time_force" v-model="doctor.start_time_force"
                  :class="{ 'is-invalid': validationErrors.start_time_force }" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.start_time_force }}</span>
              </div>

              <!-- End Time -->
              <div class="col-md-6 mb-3">
                <label for="end_time_force" class="form-label">End Time</label>
                <Field type="time" id="end_time_force" name="end_time_force" v-model="doctor.end_time_force"
                  :class="{ 'is-invalid': validationErrors.end_time_force }" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.end_time_force }}</span>
              </div>

              <!-- Number of Patients -->
              <div class="col-md-4 mb-3">
                <label for="number_of_patients" class="form-label">Patients</label>
                <Field type="text" id="number_of_patients" name="number_of_patients" v-model="doctor.number_of_patients"
                  :class="{ 'is-invalid': validationErrors.number_of_patients }" class="form-control form-control-md" />
                <span class="text-sm invalid-feedback">{{ validationErrors.number_of_patients }}</span>
              </div>
            </div>

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
                    :class="{ 'is-invalid': validationErrors.specialization }" v-model="doctor.specialization_id"
                    class="form-control form-control-md">
                    <option value="">Select Specialization</option>
                    <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                      {{ spec.name }}
                    </option>
                  </Field>
                  <span class="text-sm invalid-feedback">{{ validationErrors.specialization }}</span>
                </div>

              </div>

              <!-- Patient Selection Row -->
              <div class="row">
                <div class="mb-4"
                  :class="{ 'col-md-6': doctor.patients_based_on_time, 'col-md-12': !doctor.patients_based_on_time }">
                  <label for="patients_based_on_time" class="form-label fs-5">Patients Based on Time</label>
                  <select v-model="doctor.patients_based_on_time" class="form-control form-control-md"
                    @change="handlePatientSelectionChange">
                    <option :value="false">Fixed Number of Patients</option>
                    <option :value="true">Based on Time</option>
                  </select>
                </div>


                <div v-if="doctor.patients_based_on_time" class="col-md-6 mb-4">
                  <label for="time_slot" class="form-label fs-5">Time Slot for Patients</label>
                  <input v-model="doctor.time_slot" class="form-control form-control-md"
                    placeholder="Select time slot" />
                </div>
              </div>

              <!-- Frequency and Start Time -->
              <div class="row">
                <div class="col-md-6 mb-4">
                  <label for="frequency" class="form-label fs-5">Frequency</label>
                  <Field as="select" id="frequency" name="frequency"
                    :class="{ 'is-invalid': validationErrors.frequency }" v-model="doctor.frequency"
                    @change="handleFrequencySelectionChange" class="form-control form-control-md">
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
                    <input type="file" id="avatar" accept="image/*" @change="handleImageChange"
                      class="form-control-file" :class="{ 'is-invalid': errors.avatar }" />
                    <div v-if="imagePreview" class="mt-2">
                      <img :src="imagePreview" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                    <span v-if="errors.avatar" class="invalid-feedback">{{ errors.avatar }}</span>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-4">
                  <AppointmentBookingWindowModel :isEditMode="isEditMode"
                    :appointment-booking-window="doctor.appointmentBookingWindow" v-model="selectedMonths" />
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
                <div class="col-12" v-if="doctor.frequency === 'Daily' || doctor.frequency === 'Weekly'">
                  <DoctorSchedules :doctorId="doctor.id" :existingSchedules="doctor.schedules"
                    :patients_based_on_time="doctor.patients_based_on_time" :time_slot="doctor.time_slot"
                    v-model="doctor.schedules" @schedulesUpdated="handleSchedulesUpdated" />
                </div>
                <div class="col-md-12 mb-4" v-if="doctor.frequency === 'Monthly'">
                  <label class="form-label fs-5">Custom Dates</label>
                  <CustomDates :doctorId="doctor.id" :existingSchedules="doctor.schedules" v-model="doctor.customDates"
                    :patients_based_on_time="doctor.patients_based_on_time" :time_slot="doctor.time_slot"
                    :number_of_patients_per_day="doctor.number_of_patients_per_day"
                    @schedulesUpdated="handlecustomDatesUpdated" />
                </div>

              </div>

              <div v-if="loading" class="modal-overlay">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-outline-primary" :disabled="isLoading">
                  {{ isEditMode ? 'Update Doctor' : 'Add Doctor' }}
                  <span v-if="isLoading" class="spinner-border spinner-border-sm ms-2" role="status"
                    aria-hidden="true"></span>
                </button>
              </div>
              <div v-if="loading" class="modal-overlay">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
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
