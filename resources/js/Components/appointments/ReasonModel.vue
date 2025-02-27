<template>
    <div v-if="show" class="modal fade show" tabindex="-1" role="dialog" style="display: block; background: rgba(0, 0, 0, 0.5);">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Select Reason for Cancellation</h5>
            <button type="button" class="close" @click="closeModal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="reason-dropdown">
              <label for="reason" class="form-label">Reason</label>
              <div class="dropdown-wrapper">
                <select id="reason" v-model="selectedReason" @change="onChange" class="form-control" required>
                  <option v-for="(reason, index) in reasons" :key="index" :value="reason.value">
                    <i :class="reason.icon"></i> {{ reason.label }}
                  </option>
                </select>
                <i class="fas fa-chevron-down dropdown-icon"></i>
              </div>
            </div>
            <!-- Custom reason input field -->
            <div v-if="selectedReason === 'OTHER'" class="custom-reason mt-3">
              <label for="customReason" class="form-label">Please specify:</label>
              <input
                id="customReason"
                v-model="customReasonText"
                type="text"
                class="form-control"
                placeholder="Enter your reason"
              />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
            <button type="button" class="btn btn-primary" @click="submitReason">Submit</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  <script setup>
  import { ref } from 'vue';
  
  // Props
  const props = defineProps({
    show: { type: Boolean, required: true }, // Control modal visibility
  });
  
  // Emits
  const emit = defineEmits(['close', 'submit']);
  
// Hardcoded reasons for canceling a doctor's appointment with Font Awesome icons
const reasons = ref([
  { value: 'NOT_RESPONDED', label: 'Not Responded', icon: 'fas fa-calendar-times' },
  { value: 'PATIENT_CANCELED', label: 'Patient Canceled', icon: 'fas fa-thermometer-half' },
  { value: 'OTHER', label: 'Other Reasons', icon: 'fas fa-question-circle' },
]);

  // Selected reason
  const selectedReason = ref('');
  
  // Custom reason text
  const customReasonText = ref('');
  
  // Emit the selected reason to the parent
  const onChange = () => {
    if (selectedReason.value !== 'OTHER') {
      emit('submit', selectedReason.value);
    }
  };
  
  // Close modal
  const closeModal = () => {
    emit('close');
  };
  
  // Submit reason
  const submitReason = () => {
    if (selectedReason.value === 'OTHER') {
      emit('submit',customReasonText.value);
    } else {
      emit('submit', selectedReason.value);
    }
    closeModal();
  };
  </script>
  
  <style scoped>
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
  
  .modal-footer {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
  }
  
  .reason-dropdown {
    margin-bottom: 1.5rem;
  }
  
  .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
  }
  
  .dropdown-wrapper {
    position: relative;
  }
  
  
  .form-control:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
  }
  
  .dropdown-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #666;
  }
  </style>