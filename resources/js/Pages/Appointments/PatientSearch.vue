<script setup>
import { ref, computed,onMounted } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';

const props = defineProps({
  modelValue: String,
  onSelectPatient: Function
});

const emit = defineEmits(['update:modelValue', 'patientSelected']);

const toastr = useToastr();
const patients = ref([]);
const showDropdown = ref(false);
const isLoading = ref(false);

const handleSearch = debounce(async (query) => {
  if (query.length < 2) {
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
    showDropdown.value = true;
  } catch (error) {
    console.error('Error searching patients:', error);
    toastr.error('Failed to search patients');
    patients.value = [];
  } finally {
    isLoading.value = false;
  }
}, 300);

const selectPatient = (patient) => {
  if (!patient) return;
  emit('patientSelected', patient);
  emit('update:modelValue', `${patient.firstname} ${patient.lastname} ${patient.phone}`);
  showDropdown.value = false;
};

const closeDropdown = () => {
  showDropdown.value = false;
};

// Mount click listener
onMounted(() => {
  document.addEventListener('click', (e) => {
    const dropdown = document.querySelector('.patient-search-wrapper');
    if (dropdown && !dropdown.contains(e.target)) {
      closeDropdown();
    }
  });
});
</script>

<template>
  <div class="patient-search-wrapper">
    <div class="row">
      <div class="col-md-9">
        <input 
          :value="modelValue"
          @input="e => { emit('update:modelValue', e.target.value); handleSearch(e.target.value); }"
          type="text"
          class="form-control form-control-lg rounded-lg mb-2"
          placeholder="Search patients by name or phone..."
          @focus="showDropdown = true"
        />
      </div>
      <div class="col-md-3">
        <button 
          type="button" 
          class="btn btn-primary rounded-pill"
          @click="emit('update:modelValue', '')"
        >
          Add New Patient
        </button>
      </div>
    </div>

    <div v-if="showDropdown && (isLoading || patients.length > 0 || modelValue?.length >= 2)"
      >
      <div v-if="isLoading" class="loading-state">
        <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
          <span class="visually-hidden"></span>
        </div>
        Searching
      </div>

      <template v-else>
        <div class="dropdown-header">Search Results</div>
        <div class="patient-list">
          <div v-for="patient in patients" 
            :key="patient.id"
            class="patient-item"
            @click="selectPatient(patient)"
          >
            <div class="patient-info">
              <div class="patient-name">
                {{ patient.firstname }} {{ patient.lastname }}
              </div>
              <div class="patient-phone">
                📞 {{ patient.phone }}
              </div>
            </div>
            <div class="select-icon">
              <i class="fas fa-chevron-right"></i>
            </div>
          </div>
        </div>

        <div v-if="patients.length === 0 && modelValue?.length >= 2"
          class="no-results">
          <div class="no-results-icon">🔍</div>
          <div class="no-results-text">No patients found</div>
        </div>
      </template>
    </div>
  </div>
</template>
<style scoped>

.search-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.search-wrapper input {
    padding: 1rem 1.5rem;
    font-size: 1rem;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.search-wrapper input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.patient-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 350px;
    overflow-y: auto;
    z-index: 1050;
    border: 1px solid #e2e8f0;
    animation: dropdownFade 0.2s ease-out;
}

.loading-state {
    padding: 1.5rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.95rem;
}

.dropdown-header {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
}

.patient-list {
    padding: 0.5rem 0;
}

.patient-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.patient-item:hover {
    background-color: #f1f5f9;
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.patient-phone {
    color: #64748b;
    font-size: 0.875rem;
}

.select-icon {
    color: #94a3b8;
    margin-left: 1rem;
}

.no-results {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.no-results-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.no-results-text {
    font-size: 0.95rem;
}

/* Custom Scrollbar */
.patient-dropdown::-webkit-scrollbar {
    width: 8px;
}

.patient-dropdown::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 0 12px 12px 0;
}

.patient-dropdown::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.patient-dropdown::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>

