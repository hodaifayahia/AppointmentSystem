<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios'; // Import axios for API calls
import { useToastr } from '../../Components/toster';

// Reactive user object
const user = ref({
    name: '',
    email: '',
    avatar: '', // Store the avatar URL
});

// New password fields
const toaster = useToastr();

const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const errorMessage = ref('');

const saveChanges = async () => {
    try {
        // Prepare the user data
        const data = {
            name: user.value.name,
            email: user.value.email,
        };

        // If avatar is selected, convert it to base64
        if (user.value.avatarFile) {
            const reader = new FileReader();
            reader.readAsDataURL(user.value.avatarFile);
            reader.onload = async () => {
                // Set the base64 avatar string
                data.avatar = reader.result;

                // Send the data
                const response = await axios.put('/api/setting/user', data);

                toaster.success('update avatar successfully');
            };
            reader.onerror = (error) => {
                console.error('Error reading file:', error);
            };
        } else {
            // Send data without avatar
            const response = await axios.put('/api/setting/user', data);
            toaster.success('update user info successfully');
        }
    } catch (error) {
        console.error('Error updating user:', error.response ? error.response.data : error);
    }
};



// Handle avatar change (called automatically when file is selected)
const changeAvatar = (event) => {
    const file = event.target.files[0];
    if (file) {
        user.value.avatarFile = file; // Set the new avatar file
        const reader = new FileReader();
        reader.onloadend = () => {
            user.value.avatar = reader.result; // Update avatar preview
        };
        reader.readAsDataURL(file); // Read the file as a data URL
        // Save changes automatically after selecting the new avatar
        saveChanges();
    }
};

// Update password

// Function to update the password
const updatePassword = async () => {
  // Reset the error message before starting the update process
  errorMessage.value = '';

  // Check if the new passwords match
  if (newPassword.value !== confirmPassword.value) {
    errorMessage.value = "Passwords do not match!";
    toaster.error('Passwords do not match!');

    return;
  }

  try {
    // Prepare the password update data
    const passwordData = {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmPassword.value, // New password confirmation field
    };

    // Send the data to the backend
    const response = await axios.put('/api/setting/password', passwordData);

    // Handle successful password update
    toaster.success('Passwords was updated successfully!');
  } catch (error) {
    // Display error message from the backend or a general error message
    if (error.response && error.response.data && error.response.data.error) {
      errorMessage.value = error.response.data.error;
    } else {
      errorMessage.value = "Error updating password. Please try again later.";
    }
    console.error('Error updating password:', error.response ? error.response.data : error);
  }
};

// Fetch user data on mounted
const getUser = async () => {
    try {
        const response = await axios.get('/api/setting/user');
        user.value = response.data.data; // Populate user data
    } catch (error) {
        console.error('Error fetching user data:', error);
    }
};

// Call getUser when component is mounted
onMounted(() => {
    getUser();
});
</script>


<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Setting</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Setting</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">

        <div class="row">
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <input type="file" class="d-none">
                            <div class="text-center">
                                <!-- Change profile picture -->
                                <input type="file" class="d-none" @change="changeAvatar" ref="avatarInput">
                                <img class="profile-user-img img-circle"
                                    :src="user.avatar ? user.avatar : '/storage/default.png'" alt="User profile picture"
                                    @click="$refs.avatarInput.click()">
                            </div>
                        </div>

                        <h3 class="profile- text-center">{{ user.name }}</h3>

                        <p class="text-muted text-center">{{ user.role }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab"><i
                                        class="fa fa-user mr-1"></i> Edit Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#changePassword" data-toggle="tab"><i
                                        class="fa fa-key mr-1"></i> Change
                                    Password</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="profile">
                                <form class="form-horizontal">
                                    <div class="form-group row">
                                        <label for="inputName" class="col-sm-2 col-form-label">Name</label>
                                        <div class="col-sm-10">
                                            <!-- Bind the input value to the user.name -->
                                            <input type="text" class="form-control" id="inputName" v-model="user.name"
                                                placeholder="Name">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                        <div class="col-sm-10">
                                            <!-- Bind the input value to the user.email -->
                                            <input type="email" class="form-control" id="inputEmail"
                                                v-model="user.email" placeholder="Email">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary" @click.prevent="saveChanges">
                                                <i class="fa fa-save mr-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>

                            <div class="tab-pane" id="changePassword">
    <form class="form-horizontal" @submit.prevent="updatePassword">
      <div class="form-group row">
        <label for="currentPassword" class="col-sm-3 col-form-label">Current Password</label>
        <div class="col-sm-9">
          <input
            type="password"
            class="form-control"
            id="currentPassword"
            v-model="currentPassword"
            placeholder="Current Password"
            required
          />
        </div>
      </div>

      <div class="form-group row">
        <label for="newPassword" class="col-sm-3 col-form-label">New Password</label>
        <div class="col-sm-9">
          <input
            type="password"
            class="form-control"
            id="newPassword"
            v-model="newPassword"
            placeholder="New Password"
            required
          />
        </div>
      </div>

      <div class="form-group row">
        <label for="passwordConfirmation" class="col-sm-3 col-form-label">Confirm New Password</label>
        <div class="col-sm-9">
          <input
            type="password"
            class="form-control"
            id="passwordConfirmation"
            v-model="confirmPassword"
            placeholder="Confirm New Password"
            required
          />
        </div>
      </div>

      <!-- Display error message -->
      <div class="form-group row" v-if="errorMessage">
        <div class="offset-sm-3 col-sm-9">
          <div class="alert alert-danger">
            <strong>Error:</strong> {{ errorMessage }}
          </div>
        </div>
      </div>

      <div class="form-group row">
        <div class="offset-sm-3 col-sm-9">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save mr-1"></i> Save Changes
          </button>
        </div>
      </div>
    </form>
  </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</template>