<script setup>
import AppointmentForm from './AppointmentForm.vue';
import { useRoute } from 'vue-router';
import { computed } from 'vue';

const route = useRoute();

const doctorId = route.params.id;

const editMode = computed(() => route.name === 'admin.appointments.edit');
</script>

<template>
  <div class="appointment-page">
    <div class="content-header text-black">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ editMode ? 'Edit' : 'Create' }} Appointment</h1>
          </div>
          <div class="col-sm-6 text-right">
            <ol class="breadcrumb float-sm-right breadcrumb-dark">
              <li class="breadcrumb-item">
                <router-link to="/admin/dashboard" class="text-white">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/admin/appointments" class="text-white">Appointments</router-link>
              </li>
              <li class="breadcrumb-item active">{{ editMode ? 'Edit' : 'Create' }}</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-md-10">
            <div class="card">
              <div class="card-body">
                <AppointmentForm :doctorId="doctorId" :edit-mode="editMode" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.vue-select {
    border-radius: 50px;
}

.appointment-page {
    background-color: #f8f9fa;
}

.content-header {
    border-radius: 0 0 1rem 1rem;
}

.breadcrumb-dark {
    background: transparent;
}

.card {
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0;
}

.form-group label {
    font-weight: 500;
}

.slot-container {
    max-height: 200px;
    overflow-y: auto;
}

.slot-btn {
    width: 100px;
    margin-bottom: 5px;
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
}

@media (max-width: 768px) {
    .slot-btn {
        width: 100%;
    }
}

.search-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.search-wrapper input {
    padding: 1rem 1.5rem;
    font-size: 1rem;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.search-wrapper input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.patient-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 350px;
    overflow-y: auto;
    z-index: 1050;
    border: 1px solid #e2e8f0;
    animation: dropdownFade 0.2s ease-out;
}

.loading-state {
    padding: 1.5rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.95rem;
}

.dropdown-header {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
}

.patient-list {
    padding: 0.5rem 0;
}

.patient-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.patient-item:hover {
    background-color: #f1f5f9;
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.patient-phone {
    color: #64748b;
    font-size: 0.875rem;
}

.select-icon {
    color: #94a3b8;
    margin-left: 1rem;
}

.no-results {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.no-results-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.no-results-text {
    font-size: 0.95rem;
}

/* Custom Scrollbar */
.patient-dropdown::-webkit-scrollbar {
    width: 8px;
}

.patient-dropdown::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 0 12px 12px 0;
}

.patient-dropdown::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.patient-dropdown::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>