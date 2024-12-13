

<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue';
import addUserComponent from '@/Components/addUserComponent.vue';

const users = ref([]);
const selectedUser = ref({ name: '', email: '', phone: '' });
const isModalOpen = ref(false);

// Fetch users from the server
const getUsers = () => {
  axios
    .get('/api/users')
    .then((response) => {
      users.value = response.data;
    })
    .catch((error) => {
      console.error('Error fetching users:', error);
    });
};

// Method to open modal for adding a new user
const openModal = () => {
  selectedUser.value = { name: '', email: '', phone: '' }; // Clear form for new user
  isModalOpen.value = true;
};

// Method to handle form submission
const handleSubmit = (userData) => {
  if (userData.id) {
    // Update existing user logic
    axios
      .put(`/api/users/${userData.id}`, userData)
      .then(() => {
        getUsers(); // Refresh the user list
      })
      .catch((error) => console.error(error));
  } else {
    // Add new user logic
    axios
      .post('/api/users', userData)
      .then(() => {
        getUsers(); // Refresh the user list
      })
      .catch((error) => console.error(error));
  }
};

// Method to close modal
const closeModal = () => {
  isModalOpen.value = false;
};

const editUser = (user) => {
  selectedUser.value = { ...user };
  isModalOpen.value = true;
};

const deleteUser = (user) => {
  axios
    .delete(`/api/users/${user.id}`)
    .then(() => {
      getUsers(); // Refresh the user list
    })
    .catch((error) => console.error('Error deleting user:', error));
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
          <button 
            class="btn btn-primary btn-sm w-2 rounded-lg " 
            title="Add User" 
            @click="openModal">
            <i class="fas fa-plus-circle"></i> Add User
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(user, index) in users" :key="user.id">
                <td>{{ index + 1 }}</td>
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>{{ user.phone }}</td>
                <td>
                  <button class="btn btn-success mx-1 btn-sm me-2" title="Edit" @click="editUser(user)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-danger btn-sm" title="Delete" @click="deleteUser(user)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- User Modal -->
    <addUserComponent
      :showModal="isModalOpen"
      :userData="selectedUser"
      @close="closeModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<style scoped>
/* Custom styles */
</style>
