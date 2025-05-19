<script setup>
import { ref, onMounted, watch ,reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppointmentListItem from './AppointmentListItem.vue';
import headerDoctorAppointment from '@/Components/Doctor/headerDoctorAppointment.vue';
import DoctorWaitlist from '@/Components/Doctor/DoctorWaitlist.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useAuthStore } from '../../stores/auth';
import { useAppointmentStore } from '../../stores/AppointmentStata';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue'; // Import the modal


import { storeToRefs } from 'pinia';
const pagination = ref({});
const selectedWaitlist = ref(null);
const showAddModal = ref(false);

// Initialize all refs
const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const currentFilter = ref(0);
const route = useRoute();
const router = useRouter();
const doctorId =route.params.id;
const specializationId =route.params.specializationId;
const file = ref(null);
const countWithDoctor = ref(0);
const countWithoutDoctor = ref(0);
const todaysAppointmentsCount = ref(0);
const NotForYou = ref(false);
const WaitlistDcotro = ref(false);
const isDcotro = ref(false);
const userRole = ref("");

const fileInput = ref(null);
const uploadProgress = ref(0);
const currentFileIndex = ref(0);
const selectedFiles = ref([]);

const results = reactive({
  success: [],
  errors: []
});
const appointmentStore = useAppointmentStore();
const authStore = useAuthStore();
const { appointments: storeAppointments, pagination: storePagination } = storeToRefs(appointmentStore);

// const { user, isLoading } = storeToRefs(authStore);

// Initialize with loading state and fetch data
onMounted(() => {
  loading.value = true;
  
  // Load user data - no need to await this if not dependent
  authStore.getUser();
  userRole.value = authStore.user.role;

  
  // Fetch appointments for the current doctor
  appointmentStore.getAppointments(doctorId, 1, 0)
    .then(() => {
      // This will run after the appointments are fetched
      loading.value = false;
    })
    .catch(error => {
      console.error('Error loading appointments:', error);
      loading.value = false;
    });
});

// Create a watcher for the store values to keep local refs updated
watch(
  [storeAppointments, storePagination],
  ([newAppointments, newPagination]) => {
    appointments.value = newAppointments;
    pagination.value = newPagination;
  },
  { immediate: true } // This will update as soon as the store values change
);

// Watch for doctor ID changes to reload appointments
watch(
  () => route.params.id,
  (newDoctorId, oldDoctorId) => {
    if (newDoctorId && newDoctorId !== oldDoctorId) {
      loading.value = true;
      
      appointmentStore.getAppointments(newDoctorId, 1, 0)
        .then(() => {
          loading.value = false;
        })
        .catch(error => {
          console.error('Error loading appointments:', error);
          loading.value = false;
        });
    }
  }
);



const showResults = () => {
  let message = '';
  
  if (results.success.length) {
    message += `Successfully processed ${results.success.length} files.\n`;
  }
  
  if (results.errors.length) {
    message += `\nFailed to process ${results.errors.length} files:\n`;
    results.errors.forEach(error => {
      message += `${error.filename}: ${error.error}\n`;
    });
  }
  
  // Assuming you're using a notification library like vue-notification
  notify({
    title: 'Import Results',
    message: message,
    type: results.errors.length ? 'warning' : 'success'
  });
};

const getAppointments = async (page = 1, status = null, filter = null, date = null) => {
  try {
    const params = {
      page,
      status: status ?? currentFilter.value,
      filter,
      date
    };

    const { data } = await axios.get(`/api/appointments/${doctorId}`, { params });
    
    if (data.success) {
      appointments.value = data.data;
      pagination.value = data.meta;
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = 'Failed to load appointments. Please try again.';
  } finally {
    loading.value = false;
  }
};

// Optimized initialization function
const initializeComponent = async () => {
  try {
    loading.value = true;
    
    // Get user data first
     authStore.getUser();
    userRole.value = authStore.user.role;
    
    // Fetch appointments status and appointments in parallel
     Promise.all([
      getAppointmentsStatus(),
      fetchWaitlists(),
      

         // Fetch scheduled appointments (assuming 0 = Scheduled)
    ]);
    
    
  } catch (err) {
    console.error('Error initializing component:', err);
    error.value = 'Failed to initialize. Please refresh the page.';
  } finally {
    loading.value = false;
  }
};
const handleGetAppointments = (data) => {
  appointments.value = data.data; // Update the appointments list
};
// Fetch today's appointments
// Open modal for adding a new waitlist
const openAddModal = () => {
  showAddModal.value = true;
};

// Close modal
const closeAddModal = () => {
  showAddModal.value = false;
};

// Optimized file upload using Axios
const uploadFiles = async () => {
  if (!selectedFiles.value.length) return;
  
  loading.value = true;
  uploadProgress.value = 0;
  currentFileIndex.value = 0;
  results.success = [];
  results.errors = [];
  
  for (let i = 0; i < selectedFiles.value.length; i++) {
    currentFileIndex.value = i;
    const file = selectedFiles.value[i];
    const formData = new FormData();
    formData.append('file', file);
    
    try {
      const response = await axios.post(
        `/api/import/appointment/${doctorId}`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
          onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
            uploadProgress.value = percentCompleted;
          },
        }
      );
      
      results.success.push({
        filename: file.name,
        message: response.data.message
      });
      
    } catch (error) {
      results.errors.push({
        filename: file.name,
        error: error.response?.data?.message || 'Upload failed'
      });
    }
  }
  
  // Refresh appointments list and status
   getAppointments(currentFilter.value);
   getAppointmentsStatus();
  
  // Show results
  showResults();
  
  // Reset state
  loading.value = false;
  selectedFiles.value = [];
  fileInput.value.value = '';
};

const handleFileSelection = (event) => {
  const files = Array.from(event.target.files);
  const validTypes = [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv'
  ];
  
  const validFiles = files.filter(file => 
    validTypes.includes(file.type) || 
    file.name.endsWith('.csv') ||
    file.name.endsWith('.xlsx') ||
    file.name.endsWith('.xls')
  );
  
  selectedFiles.value = validFiles;
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

const exportAppointments = async () => {
  try {
    const response = await axios.get('/api/export/appointment', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'appointments.xlsx');
    document.body.appendChild(link);
    link.click();
  } catch (error) {
    console.error('Error exporting appointments:', error);
  }
};
// Fetch appointment statuses
const getAppointmentsStatus = async () => {
  try {
    loading.value = true; // Set loading state
    error.value = null; // Clear any previous errors

    const response = await axios.get(`/api/appointmentStatus/${doctorId}`);

    statuses.value = [
      { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list' }, // Default "ALL" option
      ...response.data // Spread the response data into the array
    ];
  } catch (err) {
    console.error('Error fetching appointment statuses:', err);
    error.value = 'Failed to load status filters. Please try again later.'; // User-friendly error message
  } finally {
    loading.value = false; // Reset loading state
  }
};


const fetchWaitlists = async (filters = {}) => {
  try {
    const params = { ...filters, is_Daily: 1 };
    params.doctor_id = NotForYou.value ? "null" : doctorId; // Set doctor_id based on NotForYou
    params.specialization_id = specializationId;

    const response = await axios.get('/api/waitlists', { params });
    
    countWithDoctor.value = response.data.count_with_doctor; // Assign count where doctor_id is not null
    countWithoutDoctor.value = response.data.count_without_doctor; // Assign count where doctor_id is null

  } catch (error) {
    console.error('Error fetching waitlists:', error);
  }
};

const openWaitlistForYouModal = () => {
  WaitlistDcotro.value = true; // Open the Waitlist modal
  NotForYou.value = false; // Set the NotForYou state to false
};

const openWaitlistNotForYouModal = () => {
  WaitlistDcotro.value = true; // Open the Waitlist modal
  NotForYou.value = true; // Set the NotForYou state to true
};

const closeWaitlistModal = () => {
  WaitlistDcotro.value = false; // Close the Waitlist modal
};

watch(
  () => [doctorId, route.params.id],
  async ([newDoctorId]) => {
    if (newDoctorId) {
      await getAppointments();
      await fetchWaitlists();
    }
  },
  { immediate: false }
);
onMounted(()=>{
  fetchWaitlists();
})
const statuses = ref([
  { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list', count: 0 },
  { name: 'SCHEDULED', value: 0, color: 'primary', icon: 'fa fa-calendar-check', count: 0 },
  { name: 'CONFIRMED', value: 1, color: 'success', icon: 'fa fa-check', count: 0 },
  { name: 'CANCELED', value: 2, color: 'danger', icon: 'fa fa-ban', count: 0 },
  { name: 'PENDING', value: 3, color: 'warning', icon: 'fa fa-hourglass-half', count: 0 },
  { name: 'DONE', value: 4, color: 'info', icon: 'fa fa-check-circle', count: 0 },
]);

// Update the status filter handler
const handleStatusFilter = (status) => {
  getAppointments(1, status ); // Reset to page 1 when changing status
};
const handleFileChange = (event) => {
  file.value = event.target.files[0];
  
};

// Update other methods to use the new getAppointments signature
const handleFilterByDate = (date) => {
  if (date) {
    getAppointments(1, currentFilter.value, null, date);
  } else {
    getAppointments(1, currentFilter.value);
  }
};

const handleSearchResults = (searchData) => {
  appointments.value = searchData.data;
  pagination.value = searchData.meta;
};

// Navigate to create appointment page
const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: { doctorId }
  });
};

const getTodaysAppointments = async () => {
  try {
    loading.value = true; // Set loading state
    error.value = null; // Clear any previous errors

    const response = await axios.get(`/api/appointments/${doctorId}`, {
      params: { filter: 'today' } // Pass filter=today to fetch today's appointments
    });

    if (response.data.success) {
      appointments.value = response.data.data;
      todaysAppointmentsCount.value = response.data.data.length; // Update todaysAppointmentsCount
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching today\'s appointments:', err);
    error.value = 'Failed to load today\'s appointments. Please try again later.'; // User-friendly error message
  } finally {
    loading.value = false; // Reset loading state
  }
};


// Watch for route changes to reload appointments
watch(
  () => route.params.id,
  (newDoctorId) => {
    if (newDoctorId) {
      getAppointments(currentFilter.value);
      
    }
  }
);
// Use a single onMounted hook
onMounted(() => {
  initializeComponent();
});

// Watch for route changes
watch(
  () => route.params.id,
  (newDoctorId) => {
    if (newDoctorId) {
      initializeComponent();
    }
  }
);
</script>
<template>
  <div class="appointment-page">
    <!-- Page header -->
    <div class="pb-2">
      <!-- Ensure header-doctor-appointment is rendered only after doctorId is initialized -->
      <header-doctor-appointment v-if="doctorId" :isDcotro="isDcotro" :doctor-id="doctorId" />
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- Search and Import/Export Section -->
          <div class="col-lg-12">
            <!-- Actions toolbar -->
            <div class="d-flex   justify-content-between align-items-center mb-4 gap-3">
              <!-- Add Appointment Button -->
                <!-- Add to WaitList Button -->
                <button @click="goToAddAppointmentPage" class="btn btn-primary rounded-pill flex-shrink-0">
                <i class="fas fa-plus me-2"></i>
                Add Appointment
              </button> 
              <div class=" ">
                <button class=" btn btn-primary rounded-pill flex-shrink-0 mr-2" @click="openAddModal">
                  <i class="fas fa-plus"></i> Add to WaitList
                </button>
            
            
            </div>

              <!-- Status Filters -->
              <div class="d-flex flex-wrap gap-2">
                <!-- Today's Appointments Tab -->
                <button @click="getTodaysAppointments()" :class="[
                  'btn btn-sm rounded-pill mr-1',
                  currentFilter === 'TODAY' ? 'btn-info' : 'btn-outline-info'
                ]">
                  <i class="fas fa-calendar-day me-1"></i>
                  Today's Appointments
                  <span class="badge rounded-pill ms-1" :class="currentFilter === 'TODAY' ? 'bg-light text-dark' : 'bg-info'">
                    {{ todaysAppointmentsCount }}
                  </span>
                </button>

                <!-- Existing Status Filters -->
                <button v-for="status in statuses" :key="status.name" @click="handleStatusFilter(status.value)" :class="[
                  'btn btn-sm rounded-pill mr-1',
                  currentFilter === status.name ? `btn-${status.color}` : `btn-outline-${status.color}`
                ]">
                  <i :class="status.icon" class="me-1"></i>
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1" :class="currentFilter === status.name ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ status.count }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Waitlist Buttons -->
            <div class="d-flex flex-wrap gap-2 mb-4">
              <!-- Waitlist for You Button -->
              <button class="btn btn-outline-success position-relative" type="button" @click="openWaitlistForYouModal">
                <i class="fas fa-user-clock me-2"></i>
                Waitlist for You
                <span v-if="countWithDoctor > 0" class="badge bg-danger ms-1">{{ countWithDoctor }}</span>
              </button>

              <!-- Waitlist Not for You Button -->
              <button class="btn btn-outline-warning position-relative ml-1" type="button" @click="openWaitlistNotForYouModal">
                <i class="fas fa-user-times me-2"></i>
                Waitlist Not for You
                <span v-if="countWithoutDoctor > 0" class="badge bg-danger ms-1">{{ countWithoutDoctor }}</span>
              </button>
            </div>

            <!-- File Upload and Export Section -->
            <div class="d-flex flex-column flex-md-row gap-3 mb-4">
              <!-- File Upload -->
              <div class="d-flex flex-column flex-md-row gap-2 w-100">
                <input type="file" @change="handleFileSelection" multiple accept=".csv,.xlsx,.xls" ref="fileInput" class="d-none" />
                <button @click="$refs.fileInput.click()" class="btn btn-outline-primary w-100" :disabled="loading">
                  <i class="fas fa-upload me-2"></i>
                  Select Files
                </button>
                <button @click="uploadFiles" :disabled="loading || !selectedFiles" class="btn btn-success w-100">
                  <i class="fas fa-file-import me-2"></i>
                  Import {{ selectedFiles.length }} Files
                </button>
                <button @click="exportAppointments" class="btn btn-primary w-100">
                  <i class="fas fa-file-export me-2"></i>
                  Export File
                </button>
              </div>
            </div>

           
            <!-- Progress Bar -->
            <div v-if="loading" class="mb-4">
              <div class="progress">
                <div class="progress-bar" :style="{ width: `${uploadProgress}%` }">
                  {{ uploadProgress }}%
                </div>
              </div>
              <div class="mt-2 text-center">
                Uploading file {{ currentFileIndex + 1 }} of {{ selectedFiles.length }}
              </div>
            </div>

            <!-- Appointments List -->
            <AppointmentListItem :appointments="appointments" :userRole="userRole" :error="error" :doctor-id="doctorId"
              @update-appointment="getAppointments(currentFilter)" @update-status="getAppointmentsStatus"
              @get-appointments="handleSearchResults" @filterByDate="handleFilterByDate" />

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-center">
              <Bootstrap5Pagination :data="pagination" :limit="5" @pagination-change-page="(page) => getAppointments(page)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Waitlist Modal -->
    <DoctorWaitlist :WaitlistDcotro="WaitlistDcotro" :NotForYou="NotForYou" :specializationId="specializationId"
      :doctorId="doctorId" @close="closeWaitlistModal" />

        <!-- Add/Edit Waitlist Modal -->
        <AddWaitlistModal
      :show="showAddModal"
      :editMode="false"
      :specializationId="specializationId"
      @close="closeAddModal"
      @save="handleSave"
      @update="handleUpdate"
    />
  </div>
</template>
<style scoped>
.bg-gradient {
  background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}
.custom-time{
  position: absolute;
    top: -8px;
    right: -7px;
    background-color: red;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    color: white;
}
/* Ensure buttons and inputs are touch-friendly */
.btn,
.custom-file-label {
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
}

/* Adjust spacing for mobile */
@media (max-width: 768px) {
  .btn-group {
    flex-direction: column;
    width: 100%;
  }

  .btn-group .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.flex-column.flex-md-row {
    flex-direction: column;
  }

  .d-flex.flex-column.flex-md-row .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
}
</style>