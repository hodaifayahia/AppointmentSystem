<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import PatientListItem from './PatientListItem.vue';

const patients = ref([]);
const loading = ref(false);
const error = ref(null);
const toaster = useToastr();

const getPatients = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get('/api/patients');
    patients.value = response.data.data || response.data;
    console.log(patients.value);
  } catch (err) {
    console.error('Error fetching patients:', err);
    error.value = err.response?.data?.message || 'Failed to load patients';
  } finally {
    loading.value = false;
  }
};

const isModalOpen = ref(false);
const selectedPatient = ref([]);

const openModal = (patient = null) => {
  selectedPatient.value = patient ? { ...patient } : {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const refreshPatients = async () => {
  await getPatients();
};

const deletePatient = async (id) => {
  if (!confirm('Are you sure you want to delete this Patient?')) return;

  try {
    await axios.delete(`/api/Patients/${id}`);
    toaster.success('Patient deleted successfully');
    refreshPatients();
  } catch (error) {
    handleBackendErrors(error);
  }
};

onMounted(() => {
  getPatients();
});
</script>

<template>
  <div class="appointment-page">
    <!-- Header -->

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <PatientListItem
              :patients="patients"
              :loading="loading"
              :error="error"
              @edit="openModal"
              @delete="deletePatient"
            />
          </div>
        </div>
      </div>
    </div>
    <!-- Patient Modal -->
    <PatientModel
      :show-modal="isModalOpen"
      :spec-data="selectedPatient"
      @close="closeModal"
      @specUpdate="refreshPatients"
    />
  </div>
</template>