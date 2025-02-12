<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from '../../Pages/Appointments/PatientSearch.vue';
import { useToastr } from '../../Components/toster';

const toastr = useToastr();

const props = defineProps({
  show: Boolean,
  editMode: { type: Boolean, default: false },
  waitlist: { type: Object, default: null },
  specializationId: { type: Number, default: null }, // Add specializationId prop
  isDaily: { type: [Number, String], default: null }, // Add isDaily prop
});

// Emits
const emit = defineEmits(['close', 'save', 'update']);

const form = reactive({
  doctor_id: null,
  patient_id: null,
  specialization_id: props.specializationId, // Initialize with specializationId
  is_Daily: props.isDaily !== null ? !!props.isDaily : false, // Initialize with isDaily (convert to boolean)
  created_by: null,
  importance: null,
  notes: '',
});

// Fetch data
const doctors = ref([]);
const specializations = ref([]);
const importanceLevels = ref([]);
const searchQuery = ref('');

// Fetch specializations
const fetchSpecializations = async () => {
  const response = await axios.get('/api/specializations');
  specializations.value = response.data.data;
};

// Fetch doctors based on specialization
const fetchDoctors = async (specialization_id) => {
  if (specialization_id) {
    const response = await axios.get(`/api/doctors/specializations/${specialization_id}`);
    doctors.value = response.data.data;
  } else {
    doctors.value = [];
  }
};

// Fetch importance enum values
const fetchImportanceEnum = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceLevels.value = response.data;
};

// Pre-fill form when in edit mode
const populateForm = () => {
  if (props.editMode && props.waitlist) {
    console.log(props.waitlist);

    // Populate form fields one by one
    form.doctor_id = props.waitlist.doctor_id;
    form.patient_id = props.waitlist.patient_id;
    form.specialization_id = parseInt(props.waitlist.specialization_id); // Convert to integer
    form.is_Daily = props.waitlist.is_Daily;
    form.created_by = props.waitlist.created_by;
    form.importance = props.waitlist.importance;
    form.appointmentId = props.waitlist.appointmentId;
    form.notes = props.waitlist.notes;

    // Set the patient search query
    searchQuery.value = `${props.waitlist.patient_first_name} ${props.waitlist.patient_last_name}`;
  }
};
watch(() => props.waitlist, populateForm); // Watch for changes in waitlist prop

// Handle patient selection
const handlePatientSelect = (patient) => {
  form.patient_id = patient.id;
};

// Handle form submission
const handleSubmit = async () => {
  try {
    const method = props.editMode ? 'put' : 'post';
    const url = props.editMode
      ? `/api/waitlists/${props.waitlist.id}`
      : '/api/waitlists';

    const response = await axios[method](url, form);
    toastr.success(`${props.editMode ? 'Waitlist updated' : 'Waitlist created'} successfully`);

    // Emit appropriate event
    if (props.editMode) {
      emit('update', response.data); // Notify parent of update
    } else {
      emit('save', response.data); // Notify parent of save
    }

    closeModal();
  } catch (error) {
    console.error(`${props.editMode ? 'Error updating waitlist:' : 'Error creating waitlist:'}`, error);
    toastr.error('An error occurred while processing your request');
  }
};

// Close modal
const closeModal = () => {
  emit('close');
};

// Watch for changes in specialization_id
watch(() => props.specializationId, (newSpecializationId) => {
  if (newSpecializationId) {
    form.specialization_id = newSpecializationId;
    fetchDoctors(newSpecializationId);
  }
});

// Computed property to check if the checkbox should be disabled
const isCheckboxDisabled = computed(() => {
  return props.isDaily !== null;
});

onMounted(async () => {
  await fetchSpecializations();
  await fetchImportanceEnum();

  // Set the default specialization_id if provided
  if (props.specializationId) {
    form.specialization_id = props.specializationId;
    fetchDoctors(form.specialization_id); // Fetch doctors for the default specialization
  }

  // Set the default is_Daily value
  form.is_Daily = props.isDaily == 1;

  populateForm(); // Pre-fill form if in edit mode
});

// Watch for changes in specialization_id
watch(() => form.specialization_id, (newSpecializationId) => {
  fetchDoctors(newSpecializationId);
});
</script>

<template>
  <div v-if="show" class="modal fade show" tabindex="-1" role="dialog"
    style="display: block; background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ editMode ? 'Edit Waitlist' : 'Create Waitlist' }}</h5>
          <button type="button" class="close" @click="closeModal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <Form @submit="handleSubmit" v-slot="{ errors }">
            <!-- Patient Search -->
            <PatientSearch v-model="searchQuery" :patientId="form.patient_id" @patientSelected="handlePatientSelect" />
            <!-- Specialization Selection -->
            <div class="form-group mb-4">
              <label for="specialization_id" class="form-label">Specialization</label>
              <select id="specialization_id" v-model="form.specialization_id" class="form-control" required>
                <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                  {{ spec.name }}
                </option>
              </select>
            </div>
            <!-- Doctor Selection -->
            <div class="form-group mb-4">
              <label for="doctor_id" class="form-label">Doctor</label>
              <select id="doctor_id" v-model="form.doctor_id" class="form-control" required>
                <option value="">ALL Doctors</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.name }}
                </option>
              </select>
            </div>

            <!-- Importance Level (Conditional Rendering) -->
            <div class="form-group mb-4" v-if="form.is_Daily">
              <label for="importance" class="form-label">Importance</label>
              <select id="importance" v-model="form.importance" class="form-control" required>
                <option v-for="(level, key) in importanceLevels" :key="key" :value="level.value">
                  <i :class="level.icon"></i> {{ level.label }}
                </option>
              </select>
            </div>

            <!-- Is Daily Checkbox -->
            <div class="form-group mb-4">
              <label for="is_Daily" class="form-label d-block ml-2 text-md">Is Daily?</label>
              <input type="checkbox" id="is_Daily" v-model="form.is_Daily" class="ml-2"
                :disabled="isCheckboxDisabled" />
            </div>

            <!-- Notes -->
            <div class="form-group mb-4">
              <label for="notes" class="form-label">Notes</label>
              <textarea id="notes" v-model="form.notes" class="form-control" rows="3"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="form-group">
              <button type="submit" class="btn btn-primary">
                {{ editMode ? 'Update Waitlist' : 'Create Waitlist' }}
              </button>
            </div>
          </Form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add your custom styles here */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1050;
}

.modal-dialog {
  margin: 1.75rem auto;
}

.modal-content {
  background: white;
  border-radius: 0.3rem;
  print-color-adjust: exact;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.modal-title {
  margin: 0;
}

.modal-body {
  padding: 1rem;
}
</style>