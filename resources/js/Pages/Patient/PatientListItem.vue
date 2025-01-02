<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel  from "../../Components/PatientModel.vue";

import { useRouter } from 'vue-router';



const router = useRouter();
const Patient = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();

const getPatient = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get('/api/patients'); // Changed endpoint to plural
    Patient.value = response.data.data || response.data; // Adjust based on your API response structure
    console.log(Patient.value);
  } catch (error) {
    console.error('Error fetching Patient:', error);
    error.value = error.response?.data?.message || 'Failed to load Patient';
  } finally {
    loading.value = false;
  }
};

const isModalOpen = ref(false);
const selectedPatient = ref([]);

const openModal = (Patient = null) => {
  selectedPatient.value = Patient ? { ...Patient } : {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};


const refreshPatient = async () => {
  await getPatient();
};

const deletePatient = async (id) => {
  if (!confirm('Are you sure you want to delete this Patient?')) return;

  try {
    await axios.delete(`/api/Patients/${id}`);
    toaster.success('Patient deleted successfully');
    refreshPatient(); // Refresh the list after deletion
  } catch (error) {
    handleBackendErrors(error);
  }
};
const goToPatientAppointmentsPage = (PatientId) => {
  // Navigate using the router
  router.push({ name: 'admin.patient.appointments', params: { id: PatientId } });
};


onMounted(() => {
  getPatient();
})
</script>

<template>
  <div class="appointment-page">
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row ">
          <div class="col-sm-6">
            <h1 class="m-0">Patient</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Patient</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-primary btn-sm d-flex rounded-pill align-items-center gap-1 px-3 py-2" title="Add User"
                  @click="openModal">
                  <i class="fas fa-plus-circle pr-1"></i>
                  <span>Add Patient</span>
                </button>
            </div>

            <!-- Patient List -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"></span>
                  </div>
                </div>

                <table v-else class="table table-hover ">
                  <thead class="table-primary">
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">First Name</th>
                      <th scope="col">last Name</th>
                      <th scope="col">idantifcation number</th>
                      <th scope="col">date of birth</th>
                      <th scope="col">Phone</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="Patient.length === 0">
                      <td colspan="6" class="text-center">No Patient found</td>
                    </tr>
                    <tr   v-for="(Patient, index) in Patient" :key="Patient.id"
                    @click="goToPatientAppointmentsPage(Patient.id)"
                    style="cursor: pointer;" >
                      <td>{{ index + 1 }}</td>
                      <td>{{ Patient.Firstname }}</td>
                      <td>{{ Patient.Lastname }}</td>
                      <td>{{ Patient.Idnum }}</td>
                      <td>{{ Patient.dateOfBirth }}</td>
                      <td>{{ Patient.phone }}</td>
                      <td>
                        <button @click="openModal(Patient)" class="btn btn-sm btn-outline-primary me-2">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="deletePatient(Patient.id)" class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Patient Modal -->
    <PatientModel
      :show-modal="isModalOpen"
      :spec-data="selectedPatient"
      @close="closeModal"
      @patientsUpdate="refreshPatient"
    />
  </div>
</template>

<style scoped>
/* ... Your existing styles ... */
</style>