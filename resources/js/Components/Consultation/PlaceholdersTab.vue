<script setup>
import { ref } from 'vue';
import AttributesSection from '@/Components/Consultation/AttributesSection.vue'; // Add this import

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  placeholders: {
    type: Array,
    default: () => []
  },
  selectedPlaceholder: {
    type: Object,
    default: null
  },
  attributes: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['togglePlaceholder']);

const handleTogglePlaceholder = (placeholder) => {
  emit('togglePlaceholder', placeholder);
};
</script>

<template>
  <div>
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
      <div v-for="placeholder in placeholders" :key="placeholder.id" class="premium-accordion-item">
        <div class="premium-accordion-header" @click="handleTogglePlaceholder(placeholder)">
          <span class="premium-accordion-title">
            {{ placeholder.name || `Placeholder ${placeholder.id}` }}
          </span>
          <i :class="selectedPlaceholder?.id === placeholder.id ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </div>
        <transition name="fade">
          <div v-if="selectedPlaceholder?.id === placeholder.id" class="premium-accordion-body">
            <AttributesSection 
              :loading="loading" 
              :attributes="attributes[placeholder.id]" 
              :placeholderId="placeholder.id" 
            />
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>