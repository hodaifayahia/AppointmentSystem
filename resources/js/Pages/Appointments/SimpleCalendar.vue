<script setup>
import { ref, watch } from 'vue';

const emit = defineEmits(['dateTimeSelected']);

const selectedDate = ref('');
const selectedTime = ref('');
const error = ref('');
const confirmed = ref(false);

const handleDateChange = (event) => {
  selectedDate.value = event.target.value;
  error.value = ''; 
  confirmed.value = false; // Reset confirmation message when changing selections
};

const handleTimeChange = (event) => {
  selectedTime.value = event.target.value;
  error.value = ''; 
  confirmed.value = false; // Reset confirmation message when changing selections
};

const confirmDateTime = () => {
  if (selectedDate.value && selectedTime.value) {
    emit('dateTimeSelected', { date: selectedDate.value, time: selectedTime.value });
    confirmed.value = true; // Show confirmation message
    error.value = ''; // Clear any error
  } else {
    error.value = 'Please select both a date and a time.';
  }
};

watch([selectedDate, selectedTime], () => {
  if (selectedDate.value && selectedTime.value) {
    error.value = '';
  }
});
</script>

<template>
    <div class="premium-calendar">
      <div class="date-time-select">
        <div class="input-group">
          <label for="dateInput" class="premium-label">Select Date:</label>
          <input type="date" id="dateInput" v-model="selectedDate" @change="handleDateChange" class="premium-input">
        </div>
        <div class="input-group">
          <label for="timeInput" class="premium-label">Select Time:</label>
          <input type="time" id="timeInput" v-model="selectedTime" @change="handleTimeChange" class="premium-input">
        </div>
      </div>
      <button @click="confirmDateTime" class="bg-primary premium-button mt-4">Confirm Selection</button>
      <div v-if="error" class="error-message">
        <p class="text-danger">{{ error }}</p>
      </div>
      <div v-if="confirmed" class="success-message">
        <p class="text-success">Your time has been selected.</p>
      </div>
    </div>
  </template>
  <style scoped>
  .premium-calendar {
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 100%;
    margin: 0 auto;
  }
  
  .date-time-select {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  
  .input-group {
    display: flex;
    flex-direction: column;
  }
  
  .premium-label {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
  }
  
  .premium-input {
    border: 1px solid #d1d1d1;
    border-radius: 5px;
    padding: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease-in-out;
  }
  
  .premium-input:focus {
    outline: none;
    border-color: #42b983;
    box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.1);
  }
  
  .premium-button {
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  
  .premium-button:hover {
    background-color: #36a475;
  }
  
  .error-message, .success-message {
    text-align: center;
    margin-top: 10px;
  }
  
  .text-danger {
    color: #ff4136;
  }
  
  .text-success {
    color: #28a745;
  }
  </style>