<!-- UserList.vue -->
<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import UserListItem from './UserListItem.vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';

const users = ref([]);
const selectedUser = ref({ name: '', email: '', phone: '', password: '' });
const isModalOpen = ref(false);
const toaster = useToastr();
const searchQuery = ref(" ");

// Fetch users from the server
const getUsers = async () => {
  try {
    const response = await axios.get('/api/users');
    users.value = response.data.data;
  } catch (error) {
    toaster.error('Failed to fetch users');
    console.error('Error fetching users:', error);
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
const isLoading = ref(false);

// Debounced search function
const debouncedSearch = () => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/users/search', {
          params: {
            query: searchQuery.value
          }
        });
        users.value = response.data.data;
      } catch (error) {
        toaster.error('Failed to fetch users');
        console.error('Error fetching users:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300); // 300ms delay
  };
};

// Watch for search query changes
watch(searchQuery, debouncedSearch());

// Fetch users when component is mounted
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
          <div class="d-flex justify-content-between align-items mb-3">
            <!-- Search Bar -->

            <div class="mb-1">
              <input type="text" class="form-control" v-model="searchQuery" placeholder="Search users">
            </div>

            <!-- Loading indicator -->
            <div v-if="isLoading" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"></span>
              </div>
            </div>
            <!-- Add User Button -->
            <button class="btn btn-primary btn-sm w-2 rounded-lg" title="Add User" @click="openModal">
              <i class="fas fa-plus-circle"></i> Add User
            </button>

          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Created at</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody v-if="users.length > 0">
              <UserListItem v-for="(user, index) in users" :key="user.id" :user="user" :index="index"
                @user-updated="refreshUsers" />
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="6" class="text-center">No Result Fount...</td>
              </tr>
            </tbody>
          </table>
        </div>
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
</style>