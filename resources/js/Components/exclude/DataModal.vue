<script setup>
import { ref, computed, watch } from 'vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import DoctorSelect from '../../Components/exclude/DoctorSelect.vue';
import ModalHeader from './ModalHeader.vue';
import ModalFooter from './ModalFooter.vue';
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
    patients_based_on_time: {
        type: Boolean,
        default: false
    }
});


const toast = useToastr();
const emit = defineEmits(['update:show', 'close', 'updateDATA']);

// Reactive state
const newDate = ref('');
const dateRange = ref({ from: '', to: '' });
const selectedDoctor = ref(null);
const reason = ref('');
const applyForAllYears = ref(false);
const excludedDates = ref([]);
const isEditMode = ref(false);
const currentEditId = ref(null);

// New state for exclusion options
const exclusionType = ref('complete'); // 'complete' or 'limited'
const maxPatients = ref(0); // Number of patients allowed if limited

// Time slots state - single session
const startTime = ref();
const endTime = ref();
const patientsPerDay = ref(0);

// Morning session state
const morningStartTime = ref('');
const morningEndTime = ref('');
const morningPatients = ref(0);
const isMorningActive = ref(false);

// Afternoon session state
const afternoonStartTime = ref();
const afternoonEndTime = ref();
const afternoonPatients = ref(0);
const isAfternoonActive = ref(false);

// Use shifts option

// Watch doctorId prop
watch(() => props.doctorId, (newDoctorId) => {
    if (newDoctorId) {
        selectedDoctor.value = newDoctorId;
    }
}, { immediate: true });

// Computed properties
const isSingleMode = computed(() => props.mode === 'single');
const isRangeMode = computed(() => props.mode === 'range');
const isFormValid = computed(() => {
    const hasValidDate = isSingleMode.value ? newDate.value : (dateRange.value.from && dateRange.value.to);
    const hasValidLimitedExclusion = true
    return hasValidDate && hasValidLimitedExclusion;
});
watch(() => props.editData, (newEditData) => {
    if (newEditData) {
        isEditMode.value = true;
        currentEditId.value = newEditData.id;
        selectedDoctor.value = newEditData.doctor_id;
        reason.value = newEditData.reason;
        applyForAllYears.value = newEditData.apply_for_all_years === 1;

        // Set exclusion type
        exclusionType.value = newEditData.exclusionType || 'complete';

        // Reset sessions
        isMorningActive.value = false;
        isAfternoonActive.value = false;

        // Set date values
        if (isSingleMode.value) {
            newDate.value = newEditData.start_date;
        } else if (isRangeMode.value) {
            dateRange.value = {
                from: newEditData.start_date,
                to: newEditData.end_date,
            };
        }

        // Set morning shift if it exists
        if (newEditData.shifts?.morning) {
            morningStartTime.value = newEditData.shifts.morning.start_time || null;
            morningEndTime.value = newEditData.shifts.morning.end_time || null;
            morningPatients.value = newEditData.shifts.morning.number_of_patients_per_day || null;
            isMorningActive.value = true; // Activate morning session
        }

        // Set afternoon shift if it exists
        if (newEditData.shifts?.afternoon) {
            afternoonStartTime.value = newEditData.shifts.afternoon.start_time || null;
            afternoonEndTime.value = newEditData.shifts.afternoon.end_time || null;
            afternoonPatients.value = newEditData.shifts.afternoon.number_of_patients_per_day || null;
            isAfternoonActive.value = true; // Activate afternoon session
        }
    }
}, { immediate: true });

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
    exclusionType.value = 'complete';
    maxPatients.value = 0;
    startTime.value = '09:00';
    endTime.value = '17:00';
    patientsPerDay.value = 0;
    morningStartTime.value = '09:00';
    morningEndTime.value = '12:00';
    morningPatients.value = 0;
    isMorningActive.value = true;
    afternoonStartTime.value = '13:00';
    afternoonEndTime.value = '17:00';
    afternoonPatients.value = 0;
    isAfternoonActive.value = true;
};
const handleBackdropClick = (event) => {
    if (event.target.classList.contains('modal-backdrop')) {
        closeModal();
    }
};

const handleTimeChange = (timeType, event) => {
    if (timeType === 'start') {
        startTime.value = event.target.value;
    } else if (timeType === 'end') {
        endTime.value = event.target.value;
    }
};

const handleShiftTimeChange = (session, timeType, event) => {
    if (session === 'morning') {
        if (timeType === 'start') {
            morningStartTime.value = event.target.value;
        } else if (timeType === 'end') {
            morningEndTime.value = event.target.value;
        }
    } else if (session === 'afternoon') {
        if (timeType === 'start') {
            afternoonStartTime.value = event.target.value;
        } else if (timeType === 'end') {
            afternoonEndTime.value = event.target.value;
        }
    }
};

const handlePatientsChange = (event) => {
    patientsPerDay.value = parseInt(event.target.value) || 0;
};

const handleShiftPatientsChange = (session, event) => {
    if (session === 'morning') {
        morningPatients.value = parseInt(event.target.value) || 0;
    } else if (session === 'afternoon') {
        afternoonPatients.value = parseInt(event.target.value) || 0;
    }
};
const formatTime = (time) => {
    if (!time) return null;
    // Split time, pad with zero if needed
    const [hours, minutes] = time.split(':').map(part => part.padStart(2, '0'));
    return `${hours}:${minutes}`;
};
const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-CA') : null;
};

const saveExcludedDate = async () => {
    if (!isFormValid.value) {
        console.warn('Form is not valid');
        if (!isSingleMode.value && !(dateRange.value.from && dateRange.value.to)) {
            toast.warning('Please select a valid date range.');
        } else if (isSingleMode.value && !newDate.value) {
            toast.warning('Please select a valid date.');
        }
        return;
    }


    try {
        const payload = {
            doctor_id: selectedDoctor.value || props.doctorId,
            start_date: formatDate(newDate.value) || formatDate(dateRange.value.from),
            end_date: formatDate(dateRange.value.to),
            reason: reason.value || null,
            apply_for_all_years: applyForAllYears.value || false,
            exclusionType: exclusionType.value || 'complete',
        };

        console.log(payload);



        // Add session data based on exclusion type
        if (exclusionType.value === 'limited') {
            // Add morning session data if active
            if (isMorningActive.value) {
                payload.morning_start_time = formatTime(morningStartTime.value),
                    payload.morning_end_time = formatTime(morningEndTime.value),
                    payload.morning_patients = morningPatients.value;
                payload.is_morning_active = isMorningActive.value ? 1 : 0;
                payload.shift_period = 'morning';

            }

            // Add afternoon session data if active
            if (isAfternoonActive.value) {
                payload.afternoon_start_time = formatTime(afternoonStartTime.value);
                payload.afternoon_end_time = formatTime(afternoonEndTime.value);
                payload.afternoon_patients = afternoonPatients.value;
                payload.is_afternoon_active = isAfternoonActive.value ? 1 : 0;
                payload.shift_period = 'afternoon';

            }


        } else {
            // For complete exclusion, set all session values to null/0
            payload.morning_start_time = null;
            payload.morning_end_time = null;
            payload.morning_patients = 0;
            payload.is_morning_active = 0;
            payload.afternoon_start_time = null;
            payload.afternoon_end_time = null;
            payload.afternoon_patients = 0;
            payload.is_afternoon_active = 0;
            payload.shift_period = null;
        }

        let response;
        if (isEditMode.value) {
            response = await axios.put(`/api/excluded-dates/${currentEditId.value}`, payload);
            toast.success('Excluded date updated successfully!');
        } else {
            response = await axios.post('/api/excluded-dates', payload);
            toast.success(isSingleMode.value ? 'Date added to excluded list!' : 'Date range added to excluded list!');
        }

        excludedDates.value.push(response.data);
        emit('updateDATA');
        closeModal();
    } catch (error) {
        toast.error(isSingleMode.value ? 'Failed to save excluded date' : 'Failed to save excluded date range');
        console.error('Error:', error);
    }
};

</script>

<template>
    <div v-if="show">
        <!-- Modal -->
        <div class="modal fade show overflow-auto" tabindex="-1" role="dialog" aria-labelledby="modalTitle"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content p-2">
                    <!-- Modal Header -->
                    <ModalHeader :mode="mode" :isEditMode="isEditMode" @close="closeModal" />

                    <!-- Doctor Select -->
                    <DoctorSelect v-model="selectedDoctor" v-if="!props.doctorId" :doctors="doctors" />

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

                        <!-- Exclusion Type Selection -->
                        <div class="mt-4">
                            <label class="form-label">
                                <i class="bi bi-sliders me-2"></i>Exclusion Type:
                            </label>
                            <div class="d-flex">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="radio" id="completeExclusion"
                                        v-model="exclusionType" value="complete">
                                    <label class="form-check-label" for="completeExclusion">
                                        Complete Exclusion
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="limitedExclusion"
                                        v-model="exclusionType" value="limited">
                                    <label class="form-check-label" for="limitedExclusion">
                                        Limited Patients
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Shift-based Time Settings -->
                        <div v-if="exclusionType === 'limited'" class="mt-2">
                            <!-- Morning Session -->
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <strong>Morning Session</strong>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" v-model="isMorningActive"
                                            id="morningActive">
                                        <label class="form-check-label" for="morningActive">Active</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" :value="morningStartTime"
                                                @input="(e) => handleShiftTimeChange('morning', 'start', e)"
                                                :disabled="!isMorningActive" />
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" :value="morningEndTime"
                                                @input="(e) => handleShiftTimeChange('morning', 'end', e)"
                                                pattern="[0-9]{2}:[0-9]{2}" :disabled="!isMorningActive" />
                                        </div>
                                        <div class="col-md-6 mb-3" v-if="!props.patients_based_on_time">
                                            <label class="form-label">Number of Patients</label>
                                            <input type="number" class="form-control" :value="morningPatients"
                                                @input="(e) => handleShiftPatientsChange('morning', e)" min="0"
                                                :disabled="props.patients_based_on_time || !isMorningActive" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Afternoon Session -->
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <strong>Afternoon Session</strong>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" v-model="isAfternoonActive"
                                            id="afternoonActive">
                                        <label class="form-check-label" for="afternoonActive">Active</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" :value="afternoonStartTime"
                                                @input="(e) => handleShiftTimeChange('afternoon', 'start', e)"
                                                :disabled="!isAfternoonActive" />
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" :value="afternoonEndTime"
                                                @input="(e) => handleShiftTimeChange('afternoon', 'end', e)"
                                                :disabled="!isAfternoonActive" />
                                        </div>
                                        <div class="col-md-6 mb-3" v-if="!props.patients_based_on_time">
                                            <label class="form-label">Number of Patients</label>
                                            <input type="number" class="form-control" :value="afternoonPatients"
                                                @input="(e) => handleShiftPatientsChange('afternoon', e)" min="0"
                                                :disabled="props.patients_based_on_time || !isAfternoonActive" />
                                        </div>
                                    </div>
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
                                <input type="checkbox" id="applyForAllYears" v-model="applyForAllYears"
                                    class="form-check-input" />
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

/* Style for the form elements */
.form-check-input {
    margin-top: 0.3rem;
}

/* Style for the number input */
input[type="number"] {
    appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Highlight section */
.exclusion-options {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

/* Card styles for sessions */
.card-header {
    font-weight: 500;
    padding: 0.75rem 1.25rem;
}

.card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>