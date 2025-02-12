<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const loginError = ref(null);
const form = reactive({
    email: '',
    password: '',
});
const showPassword = ref(false);

const handleLogin = async (event) => {
    event.preventDefault(); // Prevent default form submission
    try {
        const response = await axios.post('/login', form);
        // Assuming a successful login redirects to the dashboard
        window.location.href = '/dashboard';
    } catch (error) {
        // Handle the error, assuming the backend returns a message for invalid credentials
        if (error.response && error.response.data.message) {
            loginError.value = error.response.data.message;
        } else {
            loginError.value = 'An error occurred while logging in. Please try again.';
        }
        console.error('Login error:', error);
    }
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex justify-center items-center p-3">
        <div class="login-box bg-white shadow-lg rounded-lg overflow-hidden max-w-md w-full ">
            <!-- Image at the Top -->
            <div class="bg-primary text-center p-4">
                <div class="rounded-circle mx-auto border border-primary shadow"
                    style="width: 120px; height: 120px; overflow: hidden;">
                    <img src="/login.png" alt="Doctor image" class="w-100 h-100" style="object-fit: cover;" />
                </div>
                <h1 class="text-white text-2xl font-bold">Appointment System</h1>
                <h2 class="text-white text-lg">Oasis Clinic</h2>
            </div>

            <!-- Login Form -->
            <div class="p-6 m-3">
                <p class="text-center text-gray-600 mb-4">Sign in to start your session</p>
                <form @submit="handleLogin">
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <input v-model="form.email" type="text" id="email" class="form-control"
                                placeholder="Enter your email" required>
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password"
                                class="form-control" placeholder="Enter your password" required>
                            <span class="input-group-text cursor-pointer" @click="togglePasswordVisibility">
                                <i :class="['fas', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Remember Me & Submit -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="remember" />
                            <label class="form-check-label text-sm text-muted" for="remember">
                                Remember Me
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Sign In
                        </button>
                    </div>
                </form>

                <!-- Error Message -->
                <div v-if="loginError"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i>{{ loginError }}</span>
                </div>

                <!-- Forgot Password -->
               
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-primary {
    background-color: #4F46E5;
    /* Adjust to your primary brand color */
}

.bg-primary-dark {
    background-color: #3730A3;
    /* A darker shade of the primary color */
}

.form-input {
    border-radius: 0.375rem;
    padding: 0.5rem;
    width: 100%;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #4B5563;
    background-color: #F9FAFB;
    border: 1px solid #D1D5DB;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #4F46E5;
    background-color: #E5E7EB;
}
</style>