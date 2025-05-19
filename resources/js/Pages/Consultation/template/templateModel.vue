<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import mammoth from 'mammoth';
import { useToastr } from '../../../Components/toster';
const dbPlaceholders = ref([]);
const dbAttributes = ref({});
const selectedDbPlaceholder = ref(null);
const selectedDbAttribute = ref(null);
const props = defineProps({
  showModal: Boolean,
  templateData: Object,
  isEdit: [
    Boolean,
    false
  ]
});


const emit = defineEmits(['close', 'refresh']);

const toaster = useToastr();
const loading = ref(false);
const error = ref(null);
const doctors = ref([]);
const activeTab = ref('basic');
const documentContent = ref('');
const wordFile = ref(null);
const uploadProgress = ref(0);
const placeholders = ref([]);
const selectedPlaceholderType = ref('');
const selectedPlaceholderField = ref('');
const customPlaceholder = ref('');
const previewMode = ref(false);
const editorRef = ref(null);

// Form data
const form = ref({
  name: '',
  description: '',
  content: '',
  doctor_id: '',
  mime_type: 'application/msword',
  placeholders: []
});

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    content: '',
    doctor_id: '',
    mime_type: 'application/msword',
    placeholders: []
  };
  documentContent.value = '';
  placeholders.value = [];
  wordFile.value = null;
  uploadProgress.value = 0;
  error.value = null;
  selectedPlaceholderType.value = '';
  selectedPlaceholderField.value = '';
  customPlaceholder.value = '';
};


// Add these functions with other API methods
const fetchPlaceholders = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/placeholders');
    dbPlaceholders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load placeholders';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const fetchAttributes = async (placeholderId) => {
  try {
    if (!placeholderId) return;

    loading.value = true;
    const response = await axios.get(`/api/attributes/${placeholderId}`);
    dbAttributes.value = {
      ...dbAttributes.value,
      [placeholderId]: response.data.data || []
    };
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load attributes';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

// Call fetchPlaceholders in onMounted or created hook
onMounted(() => {
  fetchDoctors();
  fetchPlaceholders();
});

const mimeTypeOptions = [
  { value: 'application/msword', label: 'Word Document' },
  { value: 'text/html', label: 'HTML' },
  { value: 'text/plain', label: 'Plain Text' }
];

// Update the computed property for placeholder text

// Update the computed property for canInsertPlaceholder


// Add a watch for selectedDbPlaceholder to load its attributes
watch(() => selectedDbPlaceholder.value, (newValue) => {
  if (newValue) {
    fetchAttributes(newValue.id);
  } else {
    selectedDbAttribute.value = null;
  }
});
const placeholderFields = {
  
};

// Computed
const availablePlaceholderFields = computed(() => {
  if (!selectedPlaceholderType.value || selectedPlaceholderType.value === 'custom') {
    return [];
  }
  return placeholderFields[selectedPlaceholderType.value] || [];
});



// Watchers
watch(() => props.templateData, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
    form.value = {
      name: newValue.name || '',
      description: newValue.description || '',
      content: newValue.content || '',
      doctor_id: newValue.doctor ? newValue.doctor.id : '',
      mime_type: newValue.mime_type || 'application/msword',
      placeholders: newValue.placeholders || []
    };
    documentContent.value = newValue.content || '';
    placeholders.value = newValue.placeholders || [];
    scanForPlaceholders();
  } else {
    resetForm();
  }
}, { immediate: true, deep: true })

watch(() => documentContent.value, (newContent) => {
  form.value.content = newContent;
  scanForPlaceholders();

});

// ... existing code ...

const fetchDoctors = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/doctors');
    doctors.value = response.data.data || response.data;
    console.log();

  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load doctors';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};
fetchDoctors();

// ... existing code ...

const closeModal = () => {
  resetForm();
  emit('close');
};

const saveTemplate = async () => {
  if (!validateForm()) return;

  try {
    loading.value = true;
    if (editorRef.value && !previewMode.value) {
      form.Dibbling.value.content = editorRef.value.innerHTML;
    }

    form.value.placeholders = placeholders.value;
    const payload = { ...form.value };
    const url = props.isEdit ? `/api/templates/${props.templateData.id}` : '/api/templates';
    const method = props.isEdit ? 'put' : 'post';

    await axios[method](url, payload);
    toaster.success(`Template ${props.isEdit ? 'updated' : 'created'} successfully`);
    // i want to go back to admin.consultation.template
    route.push({ name: 'admin.consultation.template' });
    closeModal();
  } catch (err) {
    error.value = err.response?.data?.message || `Failed to ${props.isEdit ? 'update' : 'create'} template`;
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const validateForm = () => {
  if (!form.value.name.trim()) {
    error.value = 'Template name is required';
    toaster.error(error.value);
    return false;
  }
  if (!form.value.content.trim()) {
    error.value = 'Template content is required';
    toaster.error(error.value);
    return false;
  }
  return true;
};

const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  try {
    wordFile.value = file;
    uploadProgress.value = 30;
    const arrayBuffer = await file.arrayBuffer();
    uploadProgress.value = 60;

    // Enhanced Mammoth conversion with style mapping and image handling
    const result = await mammoth.convertToHtml({
      arrayBuffer: arrayBuffer,
      includeEmbeddedStyles: true,
      styleMap: [
        // Preserve heading styles
        "p[style-name='Heading 1'] => h1:fresh",
        "p[style-name='Heading 2'] => h2:fresh",
        "p[style-name='Heading 3'] => h3:fresh",
        // Preserve alignment
        "p[style-name*='center'] => p.text-center:fresh",
        "p[style-name*='right'] => p.text-right:fresh",
        // Preserve lists
        "p[style-name*='List'] => li:fresh",
        // Preserve bold and italic
        "b => strong:fresh",
        "i => em:fresh"
      ],
      convertImage: mammoth.images.inline({
        convertImageElement: async (image) => {
          const arrayBuffer = await image.read();
          const contentType = image.contentType;
          const base64 = Buffer.from(arrayBuffer).toString('base64');
          return {
            element: {
              tagName: 'img',
              attributes: {
                src: `data:${contentType};base64,${base64}`,
                class: 'document-image',
                style: 'max-width: 100%; height: auto; margin: 10px 0;'
              }
            }
          };
        }
      })
    });

    uploadProgress.value = 90;
    documentContent.value = `<div class="document-content">${result.value}</div>`;

    if (!form.value.name) {
      form.value.name = file.name.replace(/\.[^/.]+$/, "");
    }

    activeTab.value = 'content';
    uploadProgress.value = 100;

    setTimeout(() => {
      uploadProgress.value = 0;
    }, 1500);

    scanForPlaceholders();
  } catch (err) {
    console.error("Word conversion error:", err);
    error.value = 'Failed to convert Word document: ' + (err.message || 'Unknown error');
    toaster.error(error.value);
    uploadProgress.value = 0;
  }
};

const scanForPlaceholders = () => {
  const placeholderRegex = /\{\{([^}]+)\}\}/g;
  const content = documentContent.value;
  const found = [];
  let match;

  while ((match = placeholderRegex.exec(content)) !== null) {
    const placeholder = match[0];
    if (!found.includes(placeholder)) {
      found.push(placeholder);
    }
  }

  placeholders.value = found;
  form.value.placeholders = found;
};
const placeholderText = computed(() => {
  if (selectedDbPlaceholder.value && selectedDbAttribute.value) {
    return `{{${selectedDbPlaceholder.value.name}.${selectedDbAttribute.value.name}}}`;
  }
  return null;
});
const insertPlaceholder = () => {
  if (!placeholderText.value || !editorRef.value) return;

  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    const range = selection.getRangeAt(0);
    const placeholderSpan = document.createElement('span');
    placeholderSpan.className = 'template-placeholder';
    placeholderSpan.textContent = placeholderText.value;

    range.deleteContents();
    range.insertNode(placeholderSpan);

    // Add a space after the placeholder
    const space = document.createTextNode(' ');
    range.setStartAfter(placeholderSpan);
    range.insertNode(space);
    
    // Update selection
    range.setStartAfter(space);
    range.setEndAfter(space);
    selection.removeAllRanges();
    selection.addRange(range);

    // Update content and placeholders
    documentContent.value = editorRef.value.innerHTML;
    if (!placeholders.value.includes(placeholderText.value)) {
      placeholders.value.push(placeholderText.value);
    }

    // Reset selections
    selectedDbPlaceholder.value = null;
    selectedDbAttribute.value = null;
  }
};

const handleEditorInput = (event) => {
  if (editorRef.value) {
    // Store current scroll position
    const scrollTop = editorRef.value.scrollTop;

    // Update content
    documentContent.value = editorRef.value.innerHTML;

    // Restore scroll position
    editorRef.value.scrollTop = scrollTop;
  }
};

const togglePreview = () => {
  previewMode.value = !previewMode.value;
};

const removePlaceholder = (placeholder) => {
  const index = placeholders.value.indexOf(placeholder);
  if (index !== -1) {
    placeholders.value.splice(index, 1);
  }

  if (editorRef.value) {
    const scrollTop = editorRef.value.scrollTop;
    const content = documentContent.value;
    documentContent.value = content.replace(placeholder, '');
    editorRef.value.innerHTML = documentContent.value;
    editorRef.value.scrollTop = scrollTop;
  }
};

const switchTab = (tab) => {
  activeTab.value = tab;
};
</script>

<template>
  <div class="premium-container">
    <div class="premium-editor-header">
      <div class="branding">
        <h3 class="header-title">{{ isEdit ? 'Edit' : 'Create' }} Template</h3>
      </div>
      <div class="actions">
        <button type="button" class="btn-premium-outline" @click="closeModal">
          <i class="fas fa-times me-2"></i>Cancel
        </button>
      </div>
    </div>

    <div class="premium-tabs">
      <div class="premium-tab" :class="{ active: activeTab === 'basic' }" @click="switchTab('basic')">
        <i class="fas fa-info-circle me-2"></i>Basic Info
      </div>
      <div class="premium-tab" :class="{ active: activeTab === 'content' }" @click="switchTab('content')">
        <i class="fas fa-file-alt me-2"></i>Content & Placeholders
      </div>
      <div class="premium-tab" :class="{ active: activeTab === 'preview' }" @click="switchTab('preview')">
        <i class="fas fa-eye me-2"></i>Preview
      </div>
    </div>

    <div class="premium-body">
      <div v-if="error" class="premium-alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>

      <div v-if="activeTab === 'basic'" class="premium-tab-content">
        <div class="premium-card">
          <div class="premium-card-header">
            <i class="fas fa-pencil-alt me-2"></i>Template Details
          </div>
          <div class="premium-card-body">
            <div class="premium-form-group">
              <label for="templateName" class="premium-label">Template Name *</label>
              <input type="text" class="premium-input" id="templateName" v-model="form.name"
                placeholder="Enter a descriptive name for your template" required />
            </div>

            <div class="premium-form-group">
              <label for="templateDescription" class="premium-label">Description</label>
              <textarea class="premium-textarea" id="templateDescription" v-model="form.description"
                placeholder="Add a detailed description of this template's purpose" rows="3"></textarea>
            </div>

            <div class="premium-row">
              <div class="premium-col">
                <label for="doctorSelect" class="premium-label">Associated Doctor</label>
                <div class="premium-select-wrapper">
                  <select class="premium-select" id="doctorSelect" v-model="form.doctor_id">
                    <option value="">No specific doctor</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                      {{ doctor.name }}
                    </option>
                  </select>
                  <i class="fas fa-chevron-down"></i>
                </div>
                <div v-if="loading && !doctors.length" class="premium-loading">
                  <span class="premium-spinner"></span>
                  <span class="ms-2">Loading doctors...</span>
                </div>
              </div>

              <div class="premium-col">
                <label for="mimeType" class="premium-label">Document Format</label>
                <div class="premium-select-wrapper">
                  <select class="premium-select" id="mimeType" v-model="form.mime_type">
                    <option v-for="option in mimeTypeOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                  <i class="fas fa-chevron-down"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="premium-card mt-4">
          <div class="premium-card-header">
            <i class="fas fa-cloud-upload-alt me-2"></i>Document Upload
          </div>
          <div class="premium-card-body">
            <div class="premium-form-group">
              <label class="premium-label">Upload Word Document</label>
              <div class="premium-upload-container">
                <input type="file" id="wordFileInput" class="premium-file-input" accept=".docx,.doc"
                  @change="handleFileUpload" />
                <label for="wordFileInput" class="premium-file-label">
                  <i class="fas fa-cloud-upload-alt me-2"></i>
                  {{ wordFile ? wordFile.name : 'Drop your Word document here or click to browse' }}
                </label>
              </div>

              <div v-if="uploadProgress > 0" class="premium-progress-container mt-3">
                <div class="premium-progress-text">Uploading: {{ uploadProgress }}%</div>
                <div class="premium-progress">
                  <div class="premium-progress-bar" :style="{ width: uploadProgress + '%' }"></div>
                </div>
              </div>

              <div class="premium-help-text">
                <i class="fas fa-info-circle me-1"></i>
                Upload a Word document to create your template. You'll be able to add placeholders in the next step.
              </div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <div></div>
          <button type="button" class="btn-premium" @click="switchTab('content')" :disabled="!documentContent">
            Continue to Content <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'content'" class="premium-tab-content">
        <div class="premium-editor-container">
          <div class="premium-editor-main">
            <div class="premium-editor-header-controls">
              <div class="premium-editor-title">Document Editor</div>
              <div class="premium-editor-controls">
                <button type="button" class="btn-premium-control" @click="togglePreview"
                  :class="{ active: previewMode }">
                  <i :class="previewMode ? 'fas fa-edit' : 'fas fa-eye'" class="me-1"></i>
                  {{ previewMode ? 'Edit Mode' : 'Preview' }}
                </button>
              </div>
            </div>

            <div v-if="!previewMode" ref="editorRef" class="premium-document-editor" contenteditable="true"
              @input="handleEditorInput" v-html="documentContent"></div>

            <div v-else class="premium-document-preview" v-html="documentContent"></div>
          </div>

          <div class="premium-editor-sidebar">
            <div class="premium-sidebar-panel">
              <div class="premium-sidebar-header">
                <i class="fas fa-puzzle-piece me-2"></i>Placeholders
              </div>

              <div class="premium-sidebar-content">


                <!-- Database placeholders section -->
                <div>
                  <div class="premium-form-group">
                    <label class="premium-label">Placeholder</label>
                    <div class="premium-select-wrapper">
                      <select class="premium-select" v-model="selectedDbPlaceholder">
                        <option :value="null">Select placeholder</option>
                        <option v-for="placeholder in dbPlaceholders" :key="placeholder.id" :value="placeholder">
                          {{ placeholder.name }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down"></i>
                    </div>
                  </div>

                  <!-- Add attributes dropdown when placeholder is selected -->
                  <div v-if="selectedDbPlaceholder" class="premium-form-group">
                    <label class="premium-label">Attribute</label>
                    <div class="premium-select-wrapper">
                      <select 
                        class="premium-select" 
                        v-model="selectedDbAttribute"
                      >
                        <option :value="null">Select attribute</option>
                        <option 
                          v-for="attribute in dbAttributes[selectedDbPlaceholder.id]" 
                          :key="attribute.id" 
                          :value="attribute"
                        >
                          {{ attribute.name }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down"></i>
                    </div>
                    <div v-if="loading" class="premium-loading-state">
                      <span class="premium-spinner"></span>
                      <span class="ms-2">Loading attributes...</span>
                    </div>
                  </div>
                </div>

                <!-- Custom placeholder section -->
                <!-- <div v-if="selectedPlaceholderType === 'custom'" class="premium-form-group">
        <label class="premium-label">Custom Name</label>
        <input 
          type="text" 
          class="premium-input" 
          v-model="customPlaceholder"
          placeholder="Enter placeholder name"
        />
      </div> -->

                <!-- Standard fields section -->
                <!-- <div v-else-if="selectedPlaceholderType && selectedPlaceholderType !== 'database'" class="premium-form-group">
        <label class="premium-label">Field</label>
        <div class="premium-select-wrapper">
          <select 
            class="premium-select" 
            v-model="selectedPlaceholderField"
          >
            <option value="">Select field</option>
            <option 
              v-for="field in availablePlaceholderFields" 
              :key="field" 
              :value="field"
            >
              {{ field }}
            </option>
          </select>
          <i class="fas fa-chevron-down"></i>
        </div>
      </div> -->

                <button class="btn-premium btn-full-width" @click="insertPlaceholder" >
                  <i class="fas fa-plus me-2"></i>Insert Placeholder
                </button>

                <div v-if="placeholders.length > 0" class="premium-placeholders-list">
                  <div class="premium-list-header">Used Placeholders</div>
                  <div class="premium-placeholder-items">
                    <div v-for="(placeholder, index) in placeholders" :key="index" class="premium-placeholder-item">
                      <span class="premium-placeholder-text">{{ placeholder }}</span>
                      <button class="btn-premium-icon" @click="removePlaceholder(placeholder)"
                        title="Remove placeholder">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <button type="button" class="btn-premium-light" @click="switchTab('basic')">
            <i class="fas fa-arrow-left me-2"></i>Back
          </button>

          <button type="button" class="btn-premium" @click="switchTab('preview')">
            Preview Template <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'preview'" class="premium-tab-content">
        <div class="premium-preview-container">
          <div class="premium-preview-sidebar">
            <div class="premium-card">
              <div class="premium-card-header">
                <i class="fas fa-list-alt me-2"></i>Template Details
              </div>
              <div class="premium-card-body">
                <div class="premium-detail-item">
                  <div class="premium-detail-label">Name</div>
                  <div class="premium-detail-value">{{ form.name }}</div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Description</div>
                  <div class="premium-detail-value">{{ form.description || 'No description provided' }}</div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Associated Doctor</div>
                  <div class="premium-detail-value">
                    {{doctors.find(d => d.id === form.doctor_id)?.name || 'No specific doctor'}}
                  </div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Document Format</div>
                  <div class="premium-detail-value">
                    {{mimeTypeOptions.find(m => m.value === form.mime_type)?.label || form.mime_type}}
                  </div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Placeholders</div>
                  <div class="premium-detail-value">
                    <div v-if="placeholders.length" class="premium-tags">
                      <span v-for="(placeholder, index) in placeholders" :key="index" class="premium-tag">
                        {{ placeholder }}
                      </span>
                    </div>
                    <span v-else class="premium-no-data">No placeholders added</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="premium-preview-main">
            <div class="premium-preview-header">
              <i class="fas fa-file-alt me-2"></i>Document Preview
            </div>
            <div class="premium-document-preview-container">
              <div class="premium-document-preview" v-html="documentContent"></div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <button type="button" class="btn-premium-light" @click="switchTab('content')">
            <i class="fas fa-arrow-left me-2"></i>Back to Editing
          </button>

          <button type="button" class="btn-premium-success" @click="saveTemplate" :disabled="loading">
            <span v-if="loading" class="premium-spinner white me-2"></span>
            <i v-else class="fas fa-save me-2"></i>
            {{ isEdit ? 'Update' : 'Save' }} Template
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-container {
  background-color: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  overflow: hidden;
}

.premium-editor-header {
  background: linear-gradient(135deg, #3a5bb1, #4d78ef);
  color: white;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.branding {
  display: flex;
  align-items: center;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.premium-tabs {
  display: flex;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 0 1.5rem;
}

.premium-tab {
  padding: 1.25rem 1.5rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.3s;
  display: flex;
  align-items: center;
}

.premium-tab:hover {
  color: #3a5bb1;
}

.premium-tab.active {
  color: #3a5bb1;
  border-bottom-color: #3a5bb1;
}

.premium-body {
  padding: 1.5rem;
}

.premium-tab-content {
  min-height: 600px;
}

.premium-card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.premium-card-header {
  padding: 1rem 1.5rem;
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
}

.premium-card-body {
  padding: 1.5rem;
}

.premium-form-group {
  margin-bottom: 1.25rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #334155;
}

.premium-input,
.premium-textarea,
.premium-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #f8fafc;
  font-size: 1rem;
  transition: all 0.3s;
}

.premium-input:focus,
.premium-textarea:focus,
.premium-select:focus {
  outline: none;
  border-color: #4d78ef;
  box-shadow: 0 0 0 3px rgba(77, 120, 239, 0.1);
}

.premium-textarea {
  resize: vertical;
  min-height: 100px;
}

.premium-select-wrapper {
  position: relative;
}

.premium-select-wrapper i {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
}

.premium-select {
  appearance: none;
  padding-right: 2.5rem;
}

.premium-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
}

.premium-col {
  flex: 1;
}

.btn-premium,
.btn-premium-light,
.btn-premium-outline,
.btn-premium-success,
.btn-premium-control {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  border: none;
}

.btn-premium {
  background-color: #3a5bb1;
  color: white;
}

.btn-premium:hover {
  background-color: #2c4890;
}

.btn-premium:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-premium-light {
  background-color: #e2e8f0;
  color: #334155;
}

.btn-premium-light:hover {
  background-color: #cbd5e1;
}

.btn-premium-outline {
  background-color: transparent;
  border: 1px solid white;
  color: white;
}

.btn-premium-outline:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.btn-premium-success {
  background-color: #10b981;
  color: white;
}

.btn-premium-success:hover {
  background-color: #059669;
}

.btn-premium-success:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-premium-control {
  background-color: transparent;
  color: #64748b;
  border: 1px solid #e2e8f0;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-premium-control:hover {
  background-color: #f8fafc;
}

.btn-premium-control.active {
  background-color: #f1f5f9;
  color: #3a5bb1;
  border-color: #cbd5e1;
}

.btn-premium-icon {
  background-color: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  height: 28px;
  width: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-premium-icon:hover {
  background-color: #f1f5f9;
  color: #ef4444;
}

.btn-full-width {
  width: 100%;
}

.premium-upload-container {
  position: relative;
}

.premium-file-input {
  position: absolute;
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  z-index: -1;
}

.premium-file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.5rem;
  background-color: #f8fafc;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s;
  color: #64748b;
}

.premium-file-label:hover {
  background-color: #f1f5f9;
  border-color: #94a3b8;
}

.premium-progress-container {
  margin-top: 1rem;
}

.premium-progress-text {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.premium-progress {
  height: 8px;
  background-color: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.premium-progress-bar {
  height: 100%;
  background: linear-gradient(to right, #3a5bb1, #4d78ef);
  border-radius: 4px;
}

.premium-help-text {
  margin-top: 1rem;
  color: #64748b;
  font-size: 0.875rem;
}

.premium-editor-container {
  display: flex;
  gap: 1.5rem;
  height: 600px;
}

.premium-editor-main {
  flex: 2;
  display: flex;
  flex-direction: column;
}

.premium-editor-sidebar {
  flex: 1;
  max-width: 350px;
}

.premium-editor-header-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.premium-editor-title {
  font-weight: 600;
  color: #334155;
}

.premium-editor-controls {
  display: flex;
  gap: 0.5rem;
}

.premium-document-editor,
.premium-document-preview {
  flex: 1;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  overflow-y: auto;
  position: relative;
}

.premium-document-editor:focus {
  outline: none;
}

.premium-document-preview {
  background-color: #f8fafc;
}

.premium-sidebar-panel {
  background-color: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.premium-sidebar-header {
  padding: 1rem;
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}

.premium-sidebar-content {
  padding: 1rem;
  overflow-y: auto;
  flex: 1;
}

.premium-placeholders-list {
  margin-top: 1.5rem;
}

.premium-list-header {
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.premium-placeholder-items {
  max-height: 250px;
  overflow-y: auto;
}

.premium-placeholder-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.5rem;
  background-color: #f1f5f9;
  border-radius: 6px;
}

.premium-placeholder-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 80%;
  font-size: 0.875rem;
}

.premium-preview-container {
  display: flex;
  gap: 1.5rem;
  height: 600px;
}

.premium-preview-sidebar {
  flex: 1;
  max-width: 350px;
}

.premium-preview-main {
  flex: 2;
  display: flex;
  flex-direction: column;
}

.premium-preview-header {
  font-weight: 600;
  color: #334155;
  margin-bottom: 1rem;
}

.premium-document-preview-container {
  flex: 1;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  overflow: hidden;
  position: relative;
}

.premium-detail-item {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.premium-detail-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.premium-detail-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.premium-detail-value {
  color: #334155;
  word-break: break-word;
}

.premium-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.premium-tag {
  background-color: #eef2ff;
  color: #4f46e5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.premium-no-data {
  color: #94a3b8;
  font-style: italic;
}

.premium-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.premium-loading {
  display: flex;
  align-items: center;
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.premium-spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 50%;
  border-top-color: #3a5bb1;
  animation: spin 1s ease-in-out infinite;
}

.premium-spinner.white {
  border-color: rgba(255, 255, 255, 0.3);
  border-top-color: white;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.premium-actions-row {
  display: flex;
  justify-content: space-between;
  margin-top: 2rem;
}

:deep(.template-placeholder) {
  background-color: #eef2ff;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px dashed #4f46e5;
  font-weight: 600;
  color: #4338ca;
  display: inline-block;
  margin: 2px;
  cursor: default;
  user-select: none;
}

:deep(.document-content) {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  line-height: 1.6;
  color: #333;

  p {
    margin: 0 0 1rem 0;
  }

  .text-center {
    text-align: center;
  }

  .text-right {
    text-align: right;
  }

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    margin: 1.5rem 0 1rem;
    font-weight: 600;
    color: #059669;
  }

  h1 {
    font-size: 2rem;
  }

  h2 {
    font-size: 1.75rem;
  }

  h3 {
    font-size: 1.5rem;
  }

  ul,
  ol {
    margin: 1rem 0;
    padding-left: 2rem;
  }

  li {
    margin-bottom: 0.5rem;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin: 1rem 0;
    background-color: #fff;
  }

  th,
  td {
    border: 1px solid #e2e8f0;
    padding: 0.75rem;
    text-align: left;
  }

  th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #334155;
  }

  img.document-image {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 1rem 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  strong {
    font-weight: 700;
  }

  em {
    font-style: italic;
  }
}
</style>