<script setup>
import { ref, computed, defineProps, watch } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';

const props = defineProps({
  showModal: Boolean,
  placeholderData: {
    type: Object,
  },
  editMode: Boolean
});

const emit = defineEmits(['close', 'placeholderUpdate']);
const toastr = useToastr();

const placeholder = ref({
  id: props.placeholderData?.id || null,
  name: props.placeholderData?.name || '',
  description: props.placeholderData?.description || '',
  doctor_id: props.placeholderData?.doctor_id || null,
  specializations_id: props.placeholderData?.specializations_id || null
});

const isEditMode = computed(() => !!placeholder.value.id);

// Schema validation
const placeholderSchema = yup.object({
  name: yup.string().required('Name is required'),
  description: yup.string().nullable(),
  doctor_id: yup.number().required('Doctor is required'),
  specializations_id: yup.number().required('Specialization is required')
});

watch(() => props.placeholderData, (newVal) => {
  placeholder.value = {
    id: newVal?.id || null,
    name: newVal?.name || '',
    description: newVal?.description || '',
    doctor_id: newVal?.doctor_id || null,
    specializations_id: newVal?.specializations_id || null
  };
}, { immediate: true, deep: true });

// Fetch doctors
const doctors = ref([]);
const fetchDoctors = async () => {
  try {
    const response = await axios.get(`/api/doctors`);
    doctors.value = response.data.data;
  } catch (error) {
    console.error('Error fetching doctors:', error);
    toastr.error('Failed to fetch doctors. Please try again later.');
  }
};

// Fetch specializations
const specializations = ref([]);
const fetchSpecializations = async () => {
  try {
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data;
  } catch (error) {
    console.error('Failed to fetch specializations:', error);
    toastr.error('Failed to fetch specializations. Please try again later.');
  }
};

fetchSpecializations();
fetchDoctors();

const closeModal = () => {
  placeholder.value = {
    id: null,
    name: '',
    description: '',
    doctor_id: null,
    specializations_id: null
  };
  emit('close');
};

const submitForm = async (values) => {
  try {
    const formData = {
      ...placeholder.value,
      ...values
    };

    const config = {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    if (isEditMode.value) {
      await axios.put(`/api/placeholders/${placeholder.value.id}`, formData, config);
      toastr.success('Placeholder updated successfully');
    } else {
      await axios.post('/api/placeholders', formData, config);
      toastr.success('Placeholder created successfully');
    }

    emit('placeholderUpdate');
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
            {{ isEditMode ? 'Edit' : 'New' }} Section
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
          :validation-schema="placeholderSchema" 
          v-slot="{ errors }"
        >
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field 
                type="text"
                id="name"
                name="name"
                v-model="placeholder.name"
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
                v-model="placeholder.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
              />
              <div class="invalid-feedback" v-if="errors.description">
                {{ errors.description }}
              </div>
            </div>
            
            <div class="mb-3">
              <label for="specializations_id" class="form-label">Select Specializations</label>
              <Field 
                as="select"
                id="specializations_id"
                name="specializations_id"
                v-model="placeholder.specializations_id"
                class="form-control"
                :class="{ 'is-invalid': errors.specializations_id }"
              >
                <option v-for="specialization in specializations" :key="specialization.id" :value="specialization.id">
                  {{ specialization.name }}
                </option>
              </Field>
              <div class="invalid-feedback" v-if="errors.specializations_id">
                {{ errors.specializations_id }}
              </div>
              <small class="form-text text-muted">Hold Ctrl (or Cmd) to select multiple specializations</small>
            </div>

            <div class="mb-3">
              <label for="doctor_id" class="form-label">Select Doctor</label>
              <Field 
                as="select"
                id="doctor_id"
                name="doctor_id"
                v-model="placeholder.doctor_id"
                class="form-control"
                :class="{ 'is-invalid': errors.doctor_id }"
              >
                <option value="" disabled>Select a doctor</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.name }}
                </option>
              </Field>
              <div class="invalid-feedback" v-if="errors.doctor_id">
                {{ errors.doctor_id }}
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
              {{ isEditMode ? 'Update' : 'New' }} Section
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