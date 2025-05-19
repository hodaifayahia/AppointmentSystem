<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useSweetAlert } from '../../Components/useSweetAlert';
const swal = useSweetAlert();
import { useRouter } from 'vue-router';


const props = defineProps({
  role: {
    type: String,
    required: true,
  },

});
const router = useRouter();
const Patient = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();
// const isModalOpen = ref(false);
const searchQuery = ref('');
// const selectedUserBox = ref([]);
// const loading = ref(false);
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);

const isModalOpen = ref(false);
const selectedPatient = ref([]);
const pagiante = ref([]);

const emit = defineEmits(['import-complete', 'delete', 'close', 'patientsUpdate']);
const getPatient = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/patients?page=${page}`); // Pass the page parameter
    Patient.value = response.data.data || response.data; // Adjust based on your API response structure
    pagiante.value = response.data.meta; // Ensure this matches the meta data from the backend

    console.log('Pagination Meta:', pagiante.value); // Debugging: Check the meta data
  } catch (error) {
    console.error('Error fetching Patient:', error);
    error.value = error.response?.data?.message || 'Failed to load Patient';
  } finally {
    loading.value = false;
  }
};

// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        const response = await axios.get('/api/patients/search', {
          params: { query: searchQuery.value },
        });
        Patient.value = response.data.data;
      } catch (error) {
        toaster.error('Failed to search users');
        console.error('Error searching users:', error);
      } finally {
      }
    }, 300); // 300ms delay
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);


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

const goToPatientAppointmentsPage = (PatientId) => {
  // Navigate using the router
  router.push({ name: 'admin.patient.appointments', params: { id: PatientId } });
};


const deletePatient = async (id) => {

  try {
    // Show SweetAlert confirmation dialog using the configured swal instance
    const result = await swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    });

    // If user confirms, proceed with deletion
    if (result.isConfirmed) {
      await axios.delete(`/api/patients/${id}`);
      toaster.success('Patient deleted successfully');
      refreshPatient(); // Refresh the list after deletion
      // Show success message
      swal.fire(
        'Deleted!',
        'patient has been deleted.',
        'success'
      );

      // Emit event to notify parent component
      getPatients();
      closeModal();
    }
  } catch (error) {
    // Handle error
    if (error.response?.data?.message) {
      swal.fire(
        'Error!',
        error.response.data.message,
        'error'
      );
    }
  }
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
            <div class="search-wrapper  mb-2">
                <input type="text" class="form-control premium-search" v-model="searchQuery"
                  placeholder="Search doctors" @focus="onSearchFocus" @blur="onSearchBlur" />
                <button class="search-button" @click="performSearch">
                  <i class="fas fa-search"></i> <!-- Assuming you're using Font Awesome for icons -->
                </button>
              </div>
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


                <!-- File Upload -->
                <div class="d-flex flex-column align-items-center">

                  <div class="custom-file mb-3 " style="width: 200px; margin-left: 160px;">
                    <label for="fileUpload" class="btn btn-primary w-100 premium-file-button">
                      <i class="fas fa-file-upload mr-2"></i> Choose File
                    </label>
                    <input ref="fileInput" type="file" accept=".csv,.xlsx" @change="handleFileChange"
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

            </div>

            <!-- Patient List -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <table v-else class="table table-hover ">
                  <thead class="table-primary">
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">Parent Name</th>
                      <th scope="col">last Name</th>
                      <th scope="col">First Name</th>
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
                      <td>{{ Patient.Parent }}</td>
                      <td>{{ Patient.first_name }}</td>
                      <td>{{ Patient.last_name }}</td>
                      <td>{{ Patient.Idnum }}</td>
                      <td>{{ Patient.dateOfBirth }}</td>
                      <td>{{ Patient.phone }}</td>
                      <td>
                        <button @click.stop="openModal(Patient)" class="btn btn-sm btn-outline-primary me-2">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button v-if="role === 'admin' || role === 'SuperAdmin'" @click.stop="deletePatient(Patient.id)"
                          class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="m-4">
                <Bootstrap5Pagination :data="pagiante" :limit="5"
                  @pagination-change-page="(page) => getPatient(page)" />
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

.search-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #007BFF;
  /* Blue border for a premium feel */
  border-radius: 50px;
  /* Rounded corners for a modern look */
  overflow: hidden;
  /* Ensures the border-radius applies to children */
  transition: all 0.3s ease;
  /* Smooth transition for focus/blur effects */
}

.premium-search {
  border: none;
  /* Remove default border */
  border-radius: 50px 0 0 50px;
  /* Round only left corners */
  flex-grow: 1;
  /* Expand to fill available space */
  padding: 10px 15px;
  /* Adequate padding for text */
  font-size: 16px;
  /* Clear, readable text size */
  outline: none;
  /* Remove the outline on focus */
}

.premium-search:focus {
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
  /* Focus effect */
}

.search-button {
  border: none;
  background: #007BFF;
  /* Blue background for the search button */
  color: white;
  padding: 10px 20px;
  border-radius: 0 50px 50px 0;
  /* Round only right corners */
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s ease;
  /* Smooth transition for hover effect */
}

.search-button:hover {
  background: #0056b3;
  /* Darker blue on hover */
}

.search-button i {
  margin-right: 5px;
  /* Space between icon and text */
}

/* Optional: Animation for focus */
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
  }

  70% {
    box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
  }
}

.search-wrapper:focus-within {
  animation: pulse 1s;
}
</style>