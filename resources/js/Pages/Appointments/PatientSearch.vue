<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';
import PatientModel from '@/Components/PatientModel.vue';

const props = defineProps({
  modelValue: String,
  patientId: Number,
  onSelectPatient: Function
});

const emit = defineEmits(['update:modelValue', 'patientSelected']);

const toastr = useToastr();
const patients = ref([]);
const showDropdown = ref(false);
const isLoading = ref(false);
const isModalOpen = ref(false);
const selectedPatient = ref(null);
const searchQuery = ref('');
const isEditMode = ref(false);



// Watch for modelValue changes to update the input
watch(() => props.modelValue, (newValue) => {
  if (newValue && !searchQuery.value) {
    searchQuery.value = newValue;
  }
}, { immediate: true });

const resetSearch = () => {
  searchQuery.value = '';
  selectedPatient.value = null;
  patients.value = [];
  showDropdown.value = false;
  emit('update:modelValue', '');
  emit('patientSelected', null);
};

const handleSearch = debounce(async (query) => {
  searchQuery.value = query;
  emit('update:modelValue', query);

  if (!query || query.length < 2) {
    patients.value = [];
    showDropdown.value = false;
    return;
  }

  try {
    isLoading.value = true;
    showDropdown.value = true;
    const response = await axios.get('/api/patients/search', {
      params: { query }
    });
    patients.value = response.data.data || [];

    // Auto-select if there's an exact match
    const exactMatch = patients.value.find(p =>
      `${p.first_name} ${p.last_name} ${p.dateOfBirth} ${p.phone}` === query
    );
    if (exactMatch) {
      selectPatient(exactMatch);
    }
  } catch (error) {
    console.error('Error searching patients:', error);
    toastr.error('Failed to search patients');
    patients.value = [];
  } finally {
    isLoading.value = false;
  }
}, 500);

// Watch for changes in patientId to fetch and select patient
watch(() => props.patientId, async (newId) => {
  if (newId) {
    await fetchPatientById(newId);
  }
}, { immediate: true });

const fetchPatientById = async (id) => {
  try {
    const response = await axios.get(`/api/patients/${id}`);
    if (response.data.data) {
      const patient = response.data.data;
      selectedPatient.value = patient;
      searchQuery.value = `${patient.first_name} ${patient.last_name} ${patient.dateOfBirth} ${patient.phone}`;
      emit('patientSelected', patient);
    } else {
      console.error('Patient not found:', response.data.message);
    }
  } catch (error) {
    console.error('Error fetching patient by ID:', error);
    toastr.error('Could not find patient by ID');
  }
};

const closeDropdown = () => {
  showDropdown.value = false;
};

const openModal = (edit = false) => {
  isModalOpen.value = true;
  isEditMode.value = edit;
  if (!edit) {
    selectedPatient.value = null;
  }
};

const closeModal = () => {
  isModalOpen.value = false;
  isEditMode.value = false;
};

const handlePatientAdded = (newPatient) => {
  closeModal();
  selectPatient(newPatient);
  refreshPatients();
  toastr.success(isEditMode.value ? 'Patient updated successfully' : 'Patient added successfully');
};

const refreshPatients = async () => {
  if (searchQuery.value && searchQuery.value.length >= 2) {
    await handleSearch(searchQuery.value);
  }
};
const formatDateOfBirth = (date) => {
  if (!date) return '';
  const [year, month, day] = date.split('-');
  return `${year}/${month}/${day}`;
};

onMounted(() => {
  document.addEventListener('click', (e) => {
    const dropdown = document.querySelector('.patient-search-wrapper');
    if (dropdown && !dropdown.contains(e.target)) {
      closeDropdown();
    }
  });
});

const selectPatient = (patient) => {
  selectedPatient.value = patient;
  emit('patientSelected', patient);
  const patientString = `${patient.first_name} ${patient.last_name} ${patient.dateOfBirth} ${patient.phone}`;
  emit('update:modelValue', patientString);
  searchQuery.value = patientString;
  showDropdown.value = false;
};
</script>

<template>
  <div class="patient-search-wrapper">
    <div class="row">
      <div class="col-md-9 position-relative">
        <input 
          :value="searchQuery" 
          @input="e => handleSearch(e.target.value)" 
          type="text"
          class="form-control form-control-lg rounded-lg mb-2" 
          placeholder="Search patients by name or phone..."
          @focus="showDropdown = searchQuery && searchQuery.length >= 2" 
        />
        <button 
          v-if="searchQuery" 
          type="button" 
          style="position: absolute; top: 8px; right: 7px;"
          class="btn btn-link translate-middle-y" 
          @click="resetSearch"
        >
          <i class="fas fa-times fs-3"></i>
        </button>
      </div>

      <div class="col-md-3 d-flex gap-2 mt-2">
        <button 
          v-if="selectedPatient" 
          type="button" 
          class="btn btn-secondary mr-2 rounded-pill custom-small-btn" 
          @click="openModal(true)"
        >
          Edit Patient
        </button>
        <button 
          type="button" 
          class="btn btn-primary rounded-pill custom-small-btn" 
          @click="openModal(false)"
        >
          Add New Patient
        </button>
      </div>
    </div>

    <div v-if="showDropdown && (isLoading || (patients.length > 0 && searchQuery.length >= 2))">
      <div v-if="isLoading" class="loading-state">
        <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
          <span class="visually-hidden"></span>
        </div>
        Searching
      </div>
      <template v-else>
  <div class="dropdown-header">Search Results</div>
  <div class="patient-list">
    <div 
      v-for="patient in patients" 
      :key="patient.id" 
      class="patient-item row" 
      @click="selectPatient(patient)"
    >
      <div class="patient-info col-12 d-flex align-items-center p-3">
        <span class="patient-name text-sm mr-2">{{ patient.first_name }} {{ patient.last_name }}</span>
        <span class="patient-phone text-sm mr-2"><i class="fas fa-phone-alt text-danger mr-2"></i> {{ patient.phone }}</span>
        <span class="patient-id text-sm mr-2">{{ patient.Idnum }}</span>
        <strong>Date of Birth:</strong>{{ formatDateOfBirth(patient.dateOfBirth) }}
      </div>
    </div>
  </div>
  <div v-if="patients.length === 0 && searchQuery.length >= 2" class="no-results text-center p-3">
    <div class="no-results-icon">🔍</div>
    <div class="no-results-text">No patients found</div>
  </div>
</template>
    </div>
  </div>

  <!-- Patient Modal -->
  <PatientModel 
    :show-modal="isModalOpen" 
    :spec-data="selectedPatient" 
    @close="closeModal"
    @specUpdate="handlePatientAdded" 
  />
</template>

<style scoped>
/* Your styles here */
.patient-item {
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.patient-item:hover {
  background-color: #f8f9fa; /* Light grey background on hover */
}

.patient-info {
  border-bottom: 1px solid #e9ecef; /* Adds a separator line between items */
}

.no-results {
  color: #6c757d;
}
</style>