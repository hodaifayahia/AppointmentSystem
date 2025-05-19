<script setup>
const summary = ref({
  diagnosis: '',
  treatmentPlan: ''
});
</script>

<template>
  <div class="premium-tab-content">
    <div class="premium-section-title">Consultation Summary</div>
    <div class="premium-form">
      <div class="premium-form-group">
        <label class="premium-label">Diagnosis</label>
        <textarea v-model="summary.diagnosis" class="premium-input-textarea" placeholder="Enter diagnosis"></textarea>
      </div>
      <div class="premium-form-group">
        <label class="premium-label">Treatment Plan</label>
        <textarea v-model="summary.treatmentPlan" class="premium-input-textarea" placeholder="Enter treatment plan"></textarea>
      </div>
    </div>
  </div>
</template>