
<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { useToastr } from '../../Components/toster.js';
import DoctorModel from '../../Components/DoctorModel.vue';
const users = ref([]);
const selectedUser = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  specialization: '', // Added specialization field
  number_of_patients_per_day: '', // Added specialization field
  patients_based_on_time: '', // Added specialization field
  time_slot: '', // Added specialization field
  frequency: '', // Added frequency field
  start_time: '', // Added start time field
  end_time: '', // Added end time field
  days: [], // Added days field as an array (checkboxes)
  number_patients: false, // Added field for selecting whether patients are based on time
 
});
const isModalOpen = ref(false);

const toaster = useToastr();
// Fetch users from the server
const getUsers = () => {
  axios
    .get('/api/doctors')
    .then((response) => {
        
      users.value = response.data;
    })
    .catch((error) => {
      console.error('Error fetching users:', error);
    });
};


// Method to open modal for adding a new user
const openModal = () => {
  
  selectedUser.value ={
  name: '',
  email: '',
  phone: '',
  password: '',
  specialization: '', // Added specialization field
  number_of_patients_per_day: '', // Added specialization field
  patients_based_on_time: '', // Added specialization field
  time_slot: '', // Added specialization field
  frequency: '', // Added frequency field
  start_time: '', // Added start time field
  end_time: '', // Added end time field
  days: [], // Added days field as an array (checkboxes)
  number_patients: false, // Added field for selecting whether patients are based on time
 
}; // Clear form for new user
  isModalOpen.value = true;
};

// Method to handle form submission
const handleSubmit = (userData=> {
  // Ensure we have the complete user data
  const submissionData = { ...userData };

  // Remove password if it's empty (for edit mode)
  if (!submissionData.password) {
    delete submissionData.password;
  }
  if (userData.id) {
    // Update existing user logic (PUT request)
    axios
      .put(`/api/doctors/${userData.id}`, submissionData)
      .then(() => {
        getUsers(); // Refresh the user list
        closeModal(); // Close the modal after submitting
        toaster.success('User updated sccuessfully');
      })
      .catch((error) => {
        // Optionally handle error (show message to user)
      });
  } else {
    // Add new user logic (POST request)
    axios
      .post('/api/doctors', submissionData)
      .then(() => {
        getUsers(); // Refresh the user list
        closeModal(); // Close the modal after submitting
        toaster.success('User updated sccuessfully');

      })
      .catch((error) => {

        // Optionally handle error (show message to user)
      });
  }
});


// Method to close modal
const closeModal = () => {
  isModalOpen.value = false;
};

const editUser = (user) => {

  selectedUser.value = { ...user };  
  isModalOpen.value = true;
};

const deleteUser = (user) => {
  const isConfirmed = confirm(`Are you sure you want to delete ${user.name}?`);
  if (!isConfirmed) return;

  axios
    .delete(`/api/users/${user}`)
    .then(() => {
      getUsers(); // Refresh the user list
      toaster.success('User soft deleted successfully');
    })
    .catch((error) => {
      console.error('Error soft deleting user:', error);
      toaster.error('Failed to soft delete user');
    });
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
        <h2 class="text-center mb-4">Doctors Management</h2>
        <div class="text-right mb-4">
          <button 
            class="btn btn-primary btn-sm w-2 rounded-lg " 
            title="Add User" 
            @click="openModal">
            <i class="fas fa-plus-circle"></i> Add Doctors
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>specialization</th>
                <th>frequency</th>
                <th>Daily Patients</th>
                <th>start_time </th>
                <th>end_time</th>
                <th>days</th>
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
                  <button class="btn btn-outline-success mx-1 btn-sm me-2" title="Edit" @click="editUser(user)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-outline-danger btn-sm" title="Delete" @click="deleteUser(user)">
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
    <DoctorModel
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
