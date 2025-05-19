<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import { useRouter } from 'vue-router';

const router = useRouter();
const swal = useSweetAlert();
const toaster = useToastr();

// State
const consultations = ref([]);
const placeholders = ref([]);
const attributes = ref({});
const loading = ref(false);
const error = ref(null);
const selectedPlaceholder = ref(null);
const activeTab = ref('placeholders'); // Add this for tab management
const consultationData = ref({});
const templates = ref([]);
const selectedTemplates = ref([]);

// API methods
const getConsultations = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/consultations');
    consultations.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load consultations';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const getPlaceholders = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get('/api/placeholders');
    placeholders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load placeholders';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const getAttributes = async (placeholderId) => {
  try {
    if (!placeholderId) return;
    
    // Set loading state for specific placeholder
    const loadingState = ref(true);
    attributes.value = {
      ...attributes.value,
      [placeholderId]: { loading: loadingState }
    };

    const response = await axios.get(`/api/attributes/${placeholderId}`);
    
    // Update attributes for this specific placeholder
    attributes.value[placeholderId] = response.data.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load attributes';
    toaster.error(error.value);
    attributes.value[placeholderId] = []; // Reset on error
  } finally {
    loading.value = false;
  }
};

// const togglePlaceholder = async (placeholder) => {
//   if (selectedPlaceholder.value?.id === placeholder.id) {
//     selectedPlaceholder.value = null;
//   } else {
//     selectedPlaceholder.value = placeholder;
//     // Always fetch fresh attributes when toggling
//     await getAttributes(placeholder.id);
//   }
// };

const deleteConsultation = async (id, name) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: `Delete consultation "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/consultations/${id}`);
      await getConsultations();
      toaster.success('Consultation deleted successfully');
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete consultation');
    }
  }
};

// Add this function to handle tab navigation
// Update the goToNextTab function
const goToNextTab = () => {
// Store selected templates with consultation data
consultationData.value = {
...consultationData.value,
selectedTemplates: selectedTemplates.value
};

console.log("consultationData: ", consultationData.value);

const currentIndex = tabs.findIndex(tab => tab.id === activeTab.value);
if (currentIndex < tabs.length - 1) {
activeTab.value = tabs[currentIndex + 1].id;
}
};

// Update the goToAddConsulationPage function
const goToAddConsulationPage = () => {
  goToNextTab();
};

const togglePlaceholder = async (placeholder) => {
  if (selectedPlaceholder.value && selectedPlaceholder.value.id === placeholder.id) {
    selectedPlaceholder.value = null;
  } else {
    selectedPlaceholder.value = placeholder;
    if (!attributes.value[placeholder.id]) {
      await getAttributes(placeholder.id);
    }
  }
};

// Tab management
const tabs = [
  { id: 'placeholders', label: 'Placeholders', icon: 'fas fa-puzzle-piece' },
  { id: 'Templates', label: 'Templates selection', icon: 'fas fa-file-alt' },
  { id: 'Generate', label: 'Generate Pdf', icon: 'fas fa-download' },
  { id: 'summary', label: 'Summary', icon: 'fas fa-clipboard-list' }
];
const getTemplates = async () => {
  try {
    const response = await axios.get('/api/templates');
    templates.value = response.data.data || response.data;
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to load templates');
  }
};

// Add to onMounted
onMounted(() => {
  getConsultations();
  getPlaceholders();
  getTemplates(); // Add this line
});

const setActiveTab = (tabId) => {
  activeTab.value = tabId;
};

// Lifecycle
onMounted(() => {
  getConsultations();
  getPlaceholders();
});
// Add this function to generate PDF
const generatePdf = async () => {
  try {
    loading.value = true;
    
    // Prepare data for PDF generation
    const pdfData = {
      templateIds: selectedTemplates.value,
      placeholderData: consultationData.value,
      patientInfo: {
        name: consultationData.value.patientName || '',
        id: consultationData.value.patientId || '',
        age: consultationData.value.patientAge || '',
        gender: consultationData.value.patientGender || ''
      }
    };
    
    // Call API to generate PDF
    const response = await axios.post('/api/Consulation/generate-pdf', pdfData, {
      responseType: 'blob' // Important for handling PDF binary data
    });
    
    // Create a download link for the PDF
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'consultation.pdf');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toaster.success('PDF generated successfully');
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to generate PDF');
  } finally {
    loading.value = false;
  }
};

</script>

<template>
  <div class="premium-placeholders-page">
    <div class="premium-container">
      <div class="premium-header">
        <h2 class="premium-title">Consultation</h2>
        <button 
          class="btn-premium-outline" 
          @click="goToAddConsulationPage"
        >
          <i class="fas fa-arrow-right me-2"></i>Next
        </button>
      </div>

      <!-- Add tabs navigation -->
      <div class="premium-tabs">
        <div 
          v-for="tab in tabs" 
          :key="tab.id"
          :class="['premium-tab', { active: activeTab === tab.id }]"
          @click="setActiveTab(tab.id)"
        >
          <i :class="[tab.icon, 'me-2']"></i>
          <span>{{ tab.label }}</span>
        </div>
      </div>

      <div class="premium-card">
        <!-- Placeholders Tab Content -->
        <div v-if="activeTab === 'placeholders'">
          <div v-if="loading && !placeholders.length" class="premium-loading-state">
            <div class="premium-spinner"></div>
          </div>

          <div v-if="error && !placeholders.length" class="premium-alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
          </div>

          <div v-if="!loading && !placeholders.length" class="premium-empty-state">
            <i class="fas fa-puzzle-piece fa-3x text-muted mb-3"></i>
            <p>No placeholders found. Start by adding a new consultation.</p>
          </div>

          <div v-if="placeholders.length" class="premium-accordion">
            <!-- Existing placeholders accordion content -->
            <div 
              v-for="placeholder in placeholders" 
              :key="placeholder.id" 
              class="premium-accordion-item"
            >
              <div 
                class="premium-accordion-header" 
                @click="togglePlaceholder(placeholder)"
              >
                <span class="premium-accordion-title">
                  {{ placeholder.name || `Placeholder ${placeholder.id}` }}
                </span>
                <i :class="selectedPlaceholder?.id === placeholder.id ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
              </div>
              <transition name="fade">
                <div 
                  v-if="selectedPlaceholder?.id === placeholder.id" 
                  class="premium-accordion-body"
                >
                  <div v-if="loading" class="premium-loading-state">
                    <div class="premium-spinner small"></div>
                    <p>Loading attributes...</p>
                  </div>
                <div v-else-if="attributes[placeholder.id]?.length" class="premium-attributes-container">
                  <!-- Inputs section - displayed in a row -->
                  <div class="premium-inputs-row">
  <div 
    v-for="attribute in attributes[placeholder.id].filter(a => a.input_type !== 0)" 
    :key="attribute.id" 
    class="premium-form-group premium-input-item"
  >
    <label class="premium-label">{{ attribute.name }}</label>
    <input
      class="premium-input"
      v-model="consultationData[placeholder.name + '.' + attribute.name]"
      :placeholder="`Enter ${attribute.name} value`"
    >
  </div>
</div>

<div class="premium-textareas-row">
  <div 
    v-for="attribute in attributes[placeholder.id].filter(a => a.input_type === 0)" 
    :key="attribute.id" 
    class="premium-form-group premium-textarea-item"
  >
    <label class="premium-label">{{ attribute.name }}</label>
    <textarea
      class="premium-input-textarea"
      v-model="consultationData[placeholder.name + '_' + attribute.name]"
      :placeholder="`Enter ${attribute.name} value`"
    ></textarea>
  </div>
</div>
                  </div>
                  <div v-else class="premium-no-data">
                    No attributes found for this placeholder
                  </div>
                </div>
              </transition>
            </div>
          </div>
        </div>

        <!-- Patient Info Tab Content -->
        <div v-if="activeTab === 'Generate'" class="premium-tab-content">
          <div class="premium-section-title">Generate Consultation PDF</div>
          
          <div class="premium-form">
            <div class="premium-section-subtitle">Patient Information</div>
            <div class="premium-form-row">
              <div class="premium-form-group premium-input-item">
                <label class="premium-label">Patient Name</label>
                <input class="premium-input" v-model="consultationData.patientName" placeholder="Enter patient name">
              </div>
              <div class="premium-form-group premium-input-item">
                <label class="premium-label">Patient ID</label>
                <input class="premium-input" v-model="consultationData.patientId" placeholder="Enter patient ID">
              </div>
            </div>
            <div class="premium-form-row">
              <div class="premium-form-group premium-input-item">
                <label class="premium-label">Age</label>
                <input class="premium-input" type="number" v-model="consultationData.patientAge" placeholder="Enter age">
              </div>
              <div class="premium-form-group premium-input-item">
                <label class="premium-label">Gender</label>
                <select class="premium-input" v-model="consultationData.patientGender">
                  <option value="">Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>
            </div>
            
            <div class="premium-summary-section">
              <div class="premium-section-subtitle">Selected Data Summary</div>
              
              <div class="premium-summary-card">
                <div class="premium-summary-item">
                  <strong>Selected Templates:</strong> 
                  <span v-if="selectedTemplates.length">
                    {{ templates.filter(t => selectedTemplates.includes(t.id)).map(t => t.name).join(', ') }}
                  </span>
                  <span v-else class="premium-no-data-text">No templates selected</span>
                </div>
                
                <div class="premium-summary-item">
                  <strong>Placeholder Data:</strong>
                  <span v-if="Object.keys(consultationData).length > 4" class="premium-data-filled">
                    Data filled for {{ Object.keys(consultationData).length - 4 }} fields
                  </span>
                  <span v-else class="premium-no-data-text">No placeholder data entered</span>
                </div>
              </div>
            </div>
            
            <div class="premium-generate-actions">
              <button 
                class="premium-btn premium-generate-btn" 
                @click="generatePdf"
                :disabled="loading || selectedTemplates.length === 0"
              >
                <i class="fas fa-file-pdf me-2"></i>
                {{ loading ? 'Generating...' : 'Generate PDF' }}
              </button>
              <p class="premium-generate-help">
                This will generate a PDF document with your selected templates and entered data.
              </p>
            </div>
          </div>
        </div>

        <!-- Doctor Info Tab Content -->
        <div v-if="activeTab === 'Templates'" class="premium-tab-content">
  <div class="premium-section-title">Templates</div>
  <div class="premium-form">
    <div class="templates-container">
      <div v-for="template in templates" :key="template.id" class="template-item">
        <label class="template-checkbox">
          <input 
            type="checkbox" 
            :value="template.id"
            v-model="selectedTemplates"
          >
          <span class="template-name">{{ template.name }}</span>
        </label>
      </div>
    </div>
  </div>
</div>

        <!-- Summary Tab Content -->
        <div v-if="activeTab === 'summary'" class="premium-tab-content">
          <div class="premium-section-title">Consultation Summary</div>
          <div class="premium-form">
            <div class="premium-form-group">
              <label class="premium-label">Diagnosis</label>
              <textarea class="premium-input-textarea" placeholder="Enter diagnosis"></textarea>
            </div>
            <div class="premium-form-group">
              <label class="premium-label">Treatment Plan</label>
              <textarea class="premium-input-textarea" placeholder="Enter treatment plan"></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-placeholders-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
  min-height: 100vh;
  width: 100%;
}

.premium-container {
  max-width: 100%;
  margin: 0 auto;
}

.premium-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  width: 100%;
}

.premium-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.premium-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}
.templates-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  padding: 1rem;
}

.template-item {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.template-item:hover {
  background: #e9ecef;
}

.template-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.template-name {
  font-size: 1rem;
  color: #495057;
}
.premium-accordion-item {
  border-bottom: 1px solid #e2e8f0;
}

.premium-accordion-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: background-color 0.5s;
}

.premium-accordion-header:hover {
  background-color: #f8fafc;
}

.premium-accordion-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #334155;
}

.premium-accordion-body {
  padding: 1.5rem;
  background-color: #f8fafc;
}

.premium-form-group {
  margin-bottom: 1.25rem;
  margin-left: 1rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #334155;
  font-size: 0.875rem;
}

.premium-input {
  width:100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.premium-input-textarea {
  width: 100%; /* Changed from 160% to 100% */
  min-height: 120px;
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #334155;
  transition: all 0.3s ease;
  resize: vertical;
}

.premium-textareas-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  width: 100%;
}

.premium-textarea-item {
  width: 100%;
  margin-left: 0; /* Remove left margin */
}

.premium-input-textarea:focus {
  outline: none;
  border-color: #3a5bb1;
  box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.1);
}

.premium-input-textarea::placeholder {
  color: #94a3b8;
  font-style: italic;
}

.premium-input-textarea:hover {
  border-color: #cbd5e1;
}

.premium-input:focus {
  outline: none;
  border-color: #3a5bb1;
  box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.1);
}

.premium-btn, .btn-premium-outline {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  transition: all 0.4s;
  border: none;
}

.btn-premium-outline {
  background: transparent;
  border: 1px solid #3a5bb1;
  color: #3a5bb1;
}

.btn-premium-outline:hover {
  background: #3a5bb1;
  color: white;
}

.premium-loading-state {
  text-align: center;
  padding: 2rem;
}

.premium-spinner {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  border: 3px solid #e2e8f0;
  border-radius: 50%;
  border-top-color: #3a5bb1;
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 1rem;
}

.premium-spinner.small {
  width: 1.5rem;
  height: 1.5rem;
}

.premium-empty-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

.premium-no-data {
  color: #64748b;
  font-style: italic;
  padding: 1rem;
}

.premium-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 8px;
  margin: 1.5rem;
  display: flex;
  align-items: center;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, max-height 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  max-height: 0;
}

.premium-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.premium-tab {
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  background-color: #f1f5f9;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.3s;
  white-space: nowrap;
}

.premium-tab:hover {
  background-color: #e2e8f0;
  color: #334155;
}

.premium-tab.active {
  background-color: #3a5bb1;
  color: white;
}

.premium-tab-content {
  padding: 1.5rem;
}

.premium-section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.premium-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.premium-form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.premium-textareas-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.premium-textarea-item {
  width: 100%;
}

@media (max-width: 768px) {
  .premium-placeholders-page {
    padding: 1rem;
  }

  .premium-title {
    font-size: 1.5rem;
  }

  .premium-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .premium-btn, .btn-premium-outline {
    width: 100%;
    justify-content: center;
  }
}
</style>