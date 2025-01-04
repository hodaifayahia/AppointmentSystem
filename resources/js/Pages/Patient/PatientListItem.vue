<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";

import { useRouter } from 'vue-router';



const router = useRouter();
const Patient = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();



const users = ref([]);
const pagination = ref({});
const selectedUser = ref({ name: '', email: '', phone: '', password: '' });
// const isModalOpen = ref(false);
const searchQuery = ref('');
const isLoading = ref(false);
// const selectedUserBox = ref([]);
// const loading = ref(false);
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);

const isModalOpen = ref(false);
const selectedPatient = ref([]);



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


const exportUsers = async () => {
  try {
    // Make the API call to export users
    const response = await axios.get('/api/export/Patients', {
      responseType: 'blob', // Ensure the response is treated as a binary file
    });

    // Create a Blob from the response
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const downloadUrl = window.URL.createObjectURL(blob);

    // Create a temporary link element
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'Patients.xlsx'; // The name of the file
    document.body.appendChild(link);
    link.click();
    link.remove(); // Clean up
  } catch (error) {
    console.error('Failed to export Patients:', error);
  }
};
const uploadFile = async () => {
  if (!file.value) {
    errorMessage.value = 'Please select a file.';
    return;
  }

  // Add file type validation
  const allowedTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
    'application/vnd.ms-excel', // xls
    'text/csv', // csv
    'application/csv', // some systems use this mime type for CSV
    'text/x-csv', // another possible CSV mime type
  ];

  console.log('File type:', file.value.type); // Debug line

  if (!allowedTypes.includes(file.value.type)) {
    errorMessage.value = 'Please upload a CSV or Excel file (xlsx, csv)';
    return;
  }

  const formData = new FormData();
  formData.append('file', file.value);

  try {
    loading.value = true;
    const response = await axios.post('/api/import/Patients', formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
    });

    if (response.data.success) {
      successMessage.value = response.data.message;
      errorMessage.value = '';
      emit('import-complete');
      getPatient();
    } else {
      errorMessage.value = response.data.message;
      successMessage.value = '';
    }
  } catch (error) {
    console.error('Import error:', error);
    errorMessage.value = error.response?.data?.message || 'An error occurred during the file import.';
    successMessage.value = '';
  } finally {
    loading.value = false;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

// Add this method to handle file selection
const handleFileChange = (event) => {
  const selectedFile = event.target.files[0];
  if (selectedFile) {
    console.log('Selected file type:', selectedFile.type); // Debug line
    file.value = selectedFile;
    errorMessage.value = '';
    successMessage.value = '';
  }
};
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
        <h2 class="text-center mb-4">Patient Management</h2>
        <div class="row">
          
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">

              <!-- Actions -->
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Add User Button -->
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 mb-4 py-2" title="Add User"
                  @click="openModal">
                  <i class="fas fa-plus-circle"></i>
                  <span>Add Patient</span>
                </button>

                <!-- Delete Users Button -->
                <!-- <div v-if="selectedUserBox.length > 0">
                  <button @click="bulkDelete"
                    class="btn btn-danger btn-sm d-flex align-items-center gap-1 px-3 ml-2 py-2" title="Delete Users">
                    <i class="fas fa-trash-alt mr-1"></i>
                    <span>Delete Users</span>
                  </button>
                  <span class="ml-2 mt-1 small-text">{{ selectedUserBox.length }} selected</span>
                </div> -->
              </div>

              <!-- Search and Import -->
              <div class="d-flex flex-column align-items-end">
                <!-- Search Bar -->
                <div class="mb-2">
                  <input type="text" class="form-control" v-model="searchQuery" placeholder="Search users" />
                </div>

                <!-- File Upload -->
                <div class="d-flex flex-column align-items-center">

                  <div v-if="loading" class="loading-indicator text-center ">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden"></span>
                    </div>
                    <span class="ml-2">Importing users...</span>
                  </div>

                  <div class="custom-file mb-3 " style="width: 200px; margin-left: 160px;">
                    <label for="fileUpload" class="btn btn-primary w-100 premium-file-button">
                      <i class="fas fa-file-upload mr-2"></i> Choose File
                    </label>
                    <input ref="fileInput" type="file"  accept=".csv,.xlsx" @change="handleFileChange"
                      class="custom-file-input d-none" id="fileUpload">
                  </div>
                  <div class="d-flex justify-content-between align-items-center ml-5 pl-5 ">
                    <button @click="uploadFile" :disabled="loading || !file" class="btn btn-success mr-2 ml-5">
                      Import Users
                    </button>
                    <button @click="exportUsers" class="btn btn-primary">
                      Export File
                    </button>
                  </div>


                </div>
              </div>

              <!-- Loading Indicator -->
              <div v-if="isLoading" class="text-center">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

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
                    <tr v-for="(Patient, index) in Patient" :key="Patient.id"
                      @click="goToPatientAppointmentsPage(Patient.id)" style="cursor: pointer;">
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
    <PatientModel :show-modal="isModalOpen" :spec-data="selectedPatient" @close="closeModal"
      @patientsUpdate="refreshPatient" />
  </div>
</template>

<style scoped>
/* ... Your existing styles ... */
</style>