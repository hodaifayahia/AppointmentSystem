<template>
    <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">
            <i class="bi bi-calendar me-2"></i>
            {{ mode === 'single' ? 'Add Single Date' : 'Add Date Range' }}
        </h5>
        <button type="button" class="btn-close" @click="$emit('close')" aria-label="Close"></button>
    </div>
</template>

<script setup>
defineProps({
    mode: String,
});
</script>