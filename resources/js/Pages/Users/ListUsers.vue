<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import UserListItem from './UserListItem.vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

const users = ref([]);
const pagination = ref({});
const selectedUser = ref({ name: '', email: '', phone: '', password: '' });
const isModalOpen = ref(false);
const toaster = useToastr();
const searchQuery = ref('');
const isLoading = ref(false);
const selectedUserBox = ref([]);
const loading = ref(false);
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);

// Handle file upload




// Fetch users from the server
const getUsers = async (page = 1) => {
  try {
    const response = await axios.get(`/api/users?page=${page}`);
    users.value = response.data.data;
    pagination.value = response.data.meta; // Store meta data for pagination
    selectAll.value = false;
    selectAllUsers.value = [];
  } catch (error) {
    toaster.error('Failed to fetch users');
    console.error('Error fetching users:', error);
  }
};



const exportUsers = async () => {
  try {
    // Make the API call to export users
    const response = await axios.get('/api/export/users', {
      responseType: 'blob', // Ensure the response is treated as a binary file
    });

    // Create a Blob from the response
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const downloadUrl = window.URL.createObjectURL(blob);

    // Create a temporary link element
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'users.xlsx'; // The name of the file
    document.body.appendChild(link);
    link.click();
    link.remove(); // Clean up
  } catch (error) {
    console.error('Failed to export users:', error);
  }
};
const handleFileChange = (event) => {
  file.value = event.target.files[0];
  errorMessage.value = '';
  successMessage.value = '';
};
const uploadFile = async () => {
  if (!file.value) {
    errorMessage.value = 'Please select a file.';
    return;
  }

  // Validate file type
  const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  if (!allowedTypes.includes(file.value.type)) {
    errorMessage.value = 'Please upload a CSV or XLSX file.';
    return;
  }

  const formData = new FormData();
  formData.append('file', file.value);

  try {
    loading.value = true; // Add loading state
    const response = await axios.post('/api/import/users', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
    });

    if (response.data.success) {
      successMessage.value = response.data.message;
      refreshUsers();
      toastr.success('Users imported successfully!');
      errorMessage.value = '';
      // Optionally refresh your data or emit an event
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
    // Reset file input
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};
// Open modal for adding a new user
const openModal = () => {
  selectedUser.value = { name: '', email: '', phone: '' }; // Clear form for new user
  isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
  isModalOpen.value = false;
};

// Refresh user list
const refreshUsers = () => {
  getUsers();
};

// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/users/search', {
          params: { query: searchQuery.value },
        });
        users.value = response.data.data;
        pagination.value = response.data.meta; // Update pagination on search
      } catch (error) {
        toaster.error('Failed to search users');
        console.error('Error searching users:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300); // 300ms delay
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

const toggleSelection = (user) => {
  const index = selectedUserBox.value.indexOf(user.id);
  if (index === -1) {
    selectedUserBox.value.push(user.id);
  } else {
    selectedUserBox.value.splice(index, 1);
  }
};

const bulkDelete = async () => {
  try {
    const response = await axios.delete('/api/users', {
      params: { ids: selectedUserBox.value },
    });
    toaster.success('Users deleted successfully!');
    selectedUserBox.value = [];
    selectAll.value = false;
    getUsers();
  } catch (error) {
    toaster.error('Failed to delete users');
    console.error('Error deleting users:', error);
  }
};
const selectAll = ref(false);

const selectAllUsers = () => {
  if (selectAll.value) {
    selectedUserBox.value = users.value.map(user => user.id); // Map user IDs
  } else {
    selectedUserBox.value = []; // Clear selection
  }
};



// Fetch users when the component is mounted
onMounted(() => {
  getUsers();
});
</script>

<template>
  <div>
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Users</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container">
        <h2 class="text-center mb-4">User Management</h2>
        <div class="text-right mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- Actions -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <!-- Add User Button -->
              <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 mb-4 py-2" title="Add User"
                @click="openModal">
                <i class="fas fa-plus-circle"></i>
                <span>Add User</span>
              </button>

              <!-- Delete Users Button -->
              <div v-if="selectedUserBox.length > 0">
                <button @click="bulkDelete" class="btn btn-danger btn-sm d-flex align-items-center gap-1 px-3 ml-2 py-2"
                  title="Delete Users">
                  <i class="fas fa-trash-alt mr-1"></i>
                  <span>Delete Users</span>
                </button>
                <span class="ml-2 mt-1 small-text">{{ selectedUserBox.length }} selected</span>
              </div>
            </div>

            <!-- Search and Import -->
            <div class="d-flex flex-column align-items-end">
              <!-- Search Bar -->
              <div class="mb-2">
                <input type="text" class="form-control" v-model="searchQuery" placeholder="Search users" />
              </div>

              <!-- File Upload -->
              <div class="d-flex flex-column align-items-center">

                <div v-if="loading" class="loading-indicator text-center mb-3">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"></span>
                  </div>
                  <span class="ml-2">Importing users...</span>
                </div>

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

            <!-- Loading Indicator -->
            <div v-if="isLoading" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-primary">
              <tr>
                <th><input type="checkbox" v-model="selectAll" @change="selectAllUsers"></th>
                <th>#</th>
                <th>photo</th>
                <th>Name</th>
                <th>Username</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Created at</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody v-if="users.length > 0">
              <UserListItem v-for="(user, index) in users" :key="user.id" :user="user" :index="index"
                @user-updated="refreshUsers" @toggle-selection="toggleSelection" :selectAll="selectAll" />
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="8" class="text-center">No Results Found...</td>
              </tr>
            </tbody>
          </table>

        </div>
        <Bootstrap5Pagination :data="pagination" @pagination-change-page="getUsers" />
      </div>
    </div>

    <!-- Add User Modal -->
    <AddUserComponent :show-modal="isModalOpen" :user-data="selectedUser" @close="closeModal"
      @user-updated="refreshUsers" />
  </div>
</template>

<style scoped>
.table {
  min-width: 800px;
}

.table th,
.table td {
  vertical-align: middle;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
}

.custom-file-label::after {
  content: "Browse";
}

.premium-file-button {
  display: inline-block;
  font-weight: 600;
  text-align: center;
  vertical-align: middle;
  user-select: none;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  border-radius: 0.25rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}

.premium-file-button:hover {
  color: #fff;
  background-color: #0069d9;
  border-color: #0062cc;
}

.custom-file-input.d-none {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
