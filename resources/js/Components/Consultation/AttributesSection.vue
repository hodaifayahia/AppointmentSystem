<script setup>
const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  attributes: {
    type: Array,
    default: () => []
  },
  placeholderId: {
    type: [String, Number],
    required: true
  }
});
</script>

<template>
  <div>
    <div v-if="loading" class="premium-loading-state">
      <div class="premium-spinner small"></div>
      <p>Loading attributes...</p>
    </div>
    
    <div v-else-if="attributes?.length" class="premium-attributes-container">
      <!-- Inputs section -->
      <div class="premium-inputs-row">
        <div 
          v-for="attribute in attributes.filter(a => a.input_type !== 0)" 
          :key="attribute.id" 
          class="premium-form-group premium-input-item"
        >
          <label class="premium-label">{{ attribute.name }}</label>
          <input
            class="premium-input"
            v-model="attribute.value"
            :placeholder="`Enter ${attribute.name} value`"
          >
        </div>
      </div>
      
      <!-- Textareas section -->
      <div class="premium-textareas-row">
        <div 
          v-for="attribute in attributes.filter(a => a.input_type === 0)" 
          :key="attribute.id" 
          class="premium-form-group premium-textarea-item"
        >
          <label class="premium-label">{{ attribute.name }}</label>
          <textarea
            class="premium-input-textarea"
            v-model="attribute.value"
            :placeholder="`Enter ${attribute.name} value`"
          ></textarea>
        </div>
      </div>
    </div>
    
    <div v-else class="premium-no-data">
      No attributes found for this placeholder
    </div>
  </div>
</template>

<style scoped>
.premium-attributes-container {
  padding: 1rem;
}

.premium-inputs-row,
.premium-textareas-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.premium-form-group {
  margin-bottom: 1rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.premium-input,
.premium-input-textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.premium-input-textarea {
  min-height: 100px;
  resize: vertical;
}

.premium-loading-state {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.premium-no-data {
  padding: 1rem;
  text-align: center;
  color: #666;
}
</style>