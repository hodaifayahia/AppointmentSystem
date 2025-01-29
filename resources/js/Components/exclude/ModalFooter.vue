<template>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="$emit('close')">
            <i class="bi bi-x-lg me-2"></i>Close
        </button>
        <button type="button" class="btn btn-primary" @click="$emit('submit')" :disabled="!isFormValid">
            <i class="bi bi-check-lg me-2"></i>
            {{ mode === 'single' ? 'Add Date' : 'Add Range' }}
        </button>
    </div>
</template>

<script setup>
defineProps({
    mode: String,
    isFormValid: Boolean,
});
</script>