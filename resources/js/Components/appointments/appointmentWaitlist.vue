<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { Form } from 'vee-validate';
import { useToastr } from '../../Components/toster';

const toastr = useToastr();

// Props
const props = defineProps({
    show: Boolean, // Control modal visibility
    editMode: { type: Boolean, default: false }, // Edit mode
    waitlist: { type: Object, default: null }, // Waitlist data for editing
    add_to_waitlist: { type: Boolean, default: false }, // Add to waitlist flag
});

// Emits
const emit = defineEmits(['close', 'save', 'update']);

// Form data
const form = reactive({
    doctor_id: props.waitlist?.doctor_id ?? null,
    patient_id: props.waitlist?.patient_id ?? null,
    specialization_id: props.waitlist?.specialization_id ?? null,
    is_Daily: false,
    importance: 1,
    notes: '',
});

// Fetch importance enum values
const importanceLevels = ref([]);

// Fetch importance enum values
const fetchImportanceEnum = async () => {
    const response = await axios.get('/api/importance-enum');
    importanceLevels.value = response.data;
};

// Pre-fill form when in edit mode or add_to_waitlist is true
const populateForm = () => {
    if (props.editMode && props.waitlist) {
        // Populate form with waitlist data
        Object.assign(form, props.waitlist);
    } else if (props.add_to_waitlist && props.waitlist) {
        // Pre-fill form with waitlist data if add_to_waitlist is true
        form.patient_id = props.waitlist.patient_id;
        form.doctor_id = props.waitlist.doctor_id;
        form.specialization_id = props.waitlist.specialization_id;
        form.is_Daily = props.waitlist.is_Daily ?? false;
        form.importance = props.waitlist.importance ?? 1;
        form.notes = props.waitlist.notes ?? '';
    } else {
        // Set default values for patient_id, doctor_id, and specialization_id
        form.patient_id = props.waitlist?.patient_id ?? null;
        form.doctor_id = props.waitlist?.doctor_id ?? null;
        form.specialization_id = props.waitlist?.specialization_id ?? null;
    }
};

// Watch for changes in waitlist and add_to_waitlist props
watch(() => props.waitlist, populateForm);
watch(() => props.add_to_waitlist, populateForm);

// Handle form submission
const handleSubmit = async () => {
    try {
        const method = props.editMode ? 'put' : 'post';
        const url = props.editMode
            ? `/api/waitlists/${props.waitlist.id}`
            : '/api/waitlists';

        // Include all fields (even hidden ones) in the submission
        const payload = {
            ...form, // Includes doctor_id, patient_id, specialization_id, is_Daily, importance, notes
        };

        const response = await axios[method](url, payload);
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

// Fetch data on mount
onMounted(async () => {
    await fetchImportanceEnum();
    populateForm(); // Pre-fill form if in edit mode or add_to_waitlist is true
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
                        <!-- Patient ID (Hidden) -->
                        <input type="hidden" v-model="form.patient_id" />

                        <!-- Doctor ID (Hidden) -->
                        <input type="hidden" v-model="form.doctor_id" />

                        <!-- Specialization ID (Hidden) -->
                        <input type="hidden" v-model="form.specialization_id" />

                        <!-- Importance Level -->
                        <div class="form-group mb-4">
                            <label for="importance" class="form-label">Importance</label>
                            <select id="importance" v-model="form.importance" class="form-control" required>
                                <option v-for="(level, key) in importanceLevels" :key="key" :value="level.value">
                                    <i :class="level.icon"></i> {{ level.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Is Daily -->
                        <div class="form-group mb-4">
                            <label for="is_Daily" class="form-label d-block ml-2 text-md">Is Daily?</label>
                            <input type="checkbox" id="is_Daily" v-model="form.is_Daily" class="ml-2" />
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