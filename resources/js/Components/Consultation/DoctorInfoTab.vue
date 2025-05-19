<script setup>
const doctorInfo = ref({
  name: '',
  specialty: ''
});
</script>

<template>
  <div class="premium-tab-content">
    <div class="premium-section-title">Doctor Information</div>
    <div class="premium-form">
      <div class="premium-form-row">
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Doctor Name</label>
          <input v-model="doctorInfo.name" class="premium-input" placeholder="Enter doctor name">
        </div>
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Specialty</label>
          <input v-model="doctorInfo.specialty" class="premium-input" placeholder="Enter specialty">
        </div>
      </div>
    </div>
  </div>
</template>