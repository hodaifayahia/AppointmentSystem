<script setup>
import { ref, computed, watch } from 'vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import DoctorSelect from '../../Components/exclude/DoctorSelect.vue';
import ModalHeader from './ModalHeader.vue'; // Extracted component
import ModalFooter from './ModalFooter.vue'; // Extracted component
import { useToastr } from '../../Components/toster';

const props = defineProps({
    show: Boolean,
    modelValue: String,
    doctors: {
        type: Array,
        default: () => []
    },
    doctorId: {
        type: Number,
        default: null,
    },
    mode: {
        type: String,
        validator: (value) => ['single', 'range'].includes(value),
    },
    editData: {
        type: Object,
        default: null,
    },
});
const toast = useToastr();
const emit = defineEmits(['update:show', 'close', 'updateDATA']);

// Reactive state
const newDate = ref('');
const dateRange = ref({ from: '', to: '' });
const selectedDoctor = ref(null);
const reason = ref(''); // Reason field
const applyForAllYears = ref(false); // New field: Apply for all years
const excludedDates = ref([]);
const isEditMode = ref(false); // Track if the modal is in edit mode
const currentEditId = ref(null); // Track the ID of the item being edited

// Watch doctorId prop
watch(() => props.doctorId, (newDoctorId) => {
    if (newDoctorId) {
        selectedDoctor.value = newDoctorId; // Set selectedDoctor to doctorId
    }
}, { immediate: true }); // Run immediately when the component is created

// Computed properties
const isSingleMode = computed(() => props.mode === 'single');
const isRangeMode = computed(() => props.mode === 'range');
const isFormValid = computed(() => {
    if (isSingleMode.value) return newDate.value;
    if (isRangeMode.value) return dateRange.value.from && dateRange.value.to;
    return false;
});

// Watch editData prop
watch(() => props.editData, (newEditData) => {
    if (newEditData) {
        isEditMode.value = true;
        currentEditId.value = newEditData.id;
        selectedDoctor.value = newEditData.doctor_id;
        reason.value = newEditData.reason;
        applyForAllYears.value = newEditData.apply_for_all_years === 1; // Convert to boolean

        if (isSingleMode.value) {
            newDate.value = new Date(newEditData.start_date);
        } else if (isRangeMode.value) {
            dateRange.value = {
                from: new Date(newEditData.start_date),
                to: new Date(newEditData.end_date),
            };
        }
    } else {
        resetForm();
    }
});

// Methods
const closeModal = () => {
    emit('close', false);
    resetForm();
};

const resetForm = () => {
    newDate.value = '';
    dateRange.value = { from: '', to: '' };
    selectedDoctor.value = null;
    reason.value = '';
    applyForAllYears.value = false;
    isEditMode.value = false;
    currentEditId.value = null;
};

const handleBackdropClick = (event) => {
    if (event.target.classList.contains('modal-backdrop')) {
        closeModal();
    }
};

const saveExcludedDate = async () => {
    if (!isFormValid.value) {
        toast.warning(isSingleMode.value ? 'Please select a valid date.' : 'Please select a valid date range.');
        return;
    }

    try {
        const payload = {
            doctor_id: selectedDoctor.value || null,
            start_date: isSingleMode.value ? new Date(newDate.value).toISOString().split('T')[0] : new Date(dateRange.value.from).toISOString().split('T')[0],
            end_date: isSingleMode.value ? null : new Date(dateRange.value.to).toISOString().split('T')[0],
            reason: reason.value, // Include the reason field
            applyForAllYears: applyForAllYears.value, // Include the applyForAllYears field
        };

        let response;
        if (isEditMode.value) {
            // Update existing excluded date
            response = await axios.put(`/api/excluded-dates/${currentEditId.value}`, payload);
            toast.success('Excluded date updated successfully!');
        } else {
            // Add new excluded date
            response = await axios.post('/api/excluded-dates', payload);
            toast.success(isSingleMode.value ? 'Date added to excluded list!' : 'Date range added to excluded list!');
        }

        excludedDates.value.push(response.data);
        closeModal();
        emit('updateDATA');
    } catch (error) {
        toast.error(isSingleMode.value ? 'Failed to save excluded date' : 'Failed to save excluded date range');
        console.error('Error:', error);
    }
};
</script>

<template>
    <div v-if="show">
        <!-- Modal -->
        <div class="modal fade show" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content p-2">
                    <!-- Modal Header -->
                    <ModalHeader :mode="mode" :isEditMode="isEditMode" @close="closeModal" />

                    <!-- Doctor Select -->
                    <DoctorSelect v-model="selectedDoctor"  :doctors="doctors" />

                    <!-- Modal Body -->
                    <div class="modal-body">
                        <!-- Single Date Picker -->
                        <div v-if="isSingleMode">
                            <label for="date-picker" class="form-label">
                                <i class="bi bi-calendar me-2"></i>Select Date:
                            </label>
                            <Datepicker v-model="newDate" :enable-time-picker="false" placeholder="Select a date"
                                class="mb-3" />
                        </div>

                        <!-- Date Range Picker -->
                        <div v-if="isRangeMode">
                            <label class="form-label">
                                <i class="bi bi-calendar-range me-2"></i>Select Date Range:
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <Datepicker v-model="dateRange.from" :enable-time-picker="false"
                                        placeholder="From" />
                                </div>
                                <div class="col-md-6">
                                    <Datepicker v-model="dateRange.to" :enable-time-picker="false" placeholder="To" />
                                </div>
                            </div>
                        </div>

                        <!-- Reason Field -->
                        <div class="mt-3">
                            <label for="reason" class="form-label">
                                <i class="bi bi-card-text me-2"></i>Reason (Optional):
                            </label>
                            <textarea id="reason" v-model="reason" class="form-control"
                                placeholder="Enter a reason for exclusion" rows="3"></textarea>
                        </div>

                        <!-- Apply for All Years Field -->
                        <div class="mt-3">
                            <div class="form-check">
                                <input type="checkbox" checked="applyForAllYears" id="applyForAllYears"
                                    v-model="applyForAllYears" class="form-check-input" />
                                <label for="applyForAllYears" class="form-check-label">
                                    Apply for all years
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <ModalFooter :mode="mode" :isFormValid="isFormValid" :isEditMode="isEditMode" @close="closeModal"
                        @submit="saveExcludedDate" />
                </div>
            </div>
        </div>

        <!-- Backdrop -->
        <div class="modal-backdrop fade show" @click="handleBackdropClick"></div>
    </div>
</template>

<style scoped>
.modal {
    display: block;
    background: rgba(0, 0, 0, 0.5);
}

.modal.show {
    display: block;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.modal-content {
    border-radius: 12px;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}

/* Style for the reason textarea */
textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Style for the applyForAllYears checkbox */
.form-check-input {
    margin-top: 0.3rem;
}
</style>