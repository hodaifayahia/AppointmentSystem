<script setup>
const patientInfo = ref({
  name: '',
  id: '',
  age: '',
  gender: ''
});
</script>

<template>
  <div class="premium-tab-content">
    <div class="premium-section-title">Patient Information</div>
    <div class="premium-form">
      <div class="premium-form-row">
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Patient Name</label>
          <input v-model="patientInfo.name" class="premium-input" placeholder="Enter patient name">
        </div>
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Patient ID</label>
          <input v-model="patientInfo.id" class="premium-input" placeholder="Enter patient ID">
        </div>
      </div>
      <div class="premium-form-row">
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Age</label>
          <input v-model="patientInfo.age" class="premium-input" type="number" placeholder="Enter age">
        </div>
        <div class="premium-form-group premium-input-item">
          <label class="premium-label">Gender</label>
          <select v-model="patientInfo.gender" class="premium-input">
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>