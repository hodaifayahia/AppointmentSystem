<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute ,useRouter} from 'vue-router';

import axios from 'axios';

const doctors = ref([]);
const searchQuery = ref('');
const isLoading = ref(false);
const route = useRoute();
const router = useRouter();
const specializationId = route.params.id; // Access specialization ID

// Watch for route changes to fetch doctors

// Function to fetch doctors
const getDoctors = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/doctors', {
      params: { query: specializationId },
    });
    doctors.value = response.data.data;
  } catch (error) {
    console.error('Error fetching doctors:', error);
  } finally {
    isLoading.value = false;
  }
};


const goToAppointmentPage = (doctor) => {
  router.push({
    name: 'admin.appointments',
    params: { id: doctor.id }, // Pass d ID
  });
};


// Debounced search function

// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/doctors/search', {
          params: { query: searchQuery.value },
        });
        doctors.value = response.data.data;
      
      } catch (error) {
        toaster.error('Failed to search doctors');
        console.error('Error searching doctors:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

// Function to handle click on doctor card - assuming you have a route for doctor profiles
const goToDoctorProfile = (doctorId) => {
  // This is a placeholder. Replace with actual navigation logic
  console.log('Navigating to doctor profile:', doctorId);
  // Example with Vue Router:
  // router.push(`/doctors/${doctorId}`);
};

onMounted(() => {
  getDoctors();
});
</script>

<template>
  <div>
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Doctors</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Doctors</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container">
        <h2 class="text-center mb-4">Doctor Management</h2>
        <div class="mb-4">
          <!-- Search Bar -->
          <div class="mb-1 search-container">
            <div class="search-wrapper">
              <input type="text" class="form-control premium-search" v-model="searchQuery" placeholder="Search doctors"
                @focus="onSearchFocus" @blur="onSearchBlur" />
              <button class="search-button" @click="performSearch">
                <i class="fas fa-search"></i> <!-- Assuming you're using Font Awesome for icons -->
              </button>
            </div>
          </div>
        </div>

        <div class="row" v-if="isLoading">
          <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden"></span>
            </div>
          </div>
        </div>
        <div v-else class="row">
          <div v-for="doctor in doctors" :key="doctor.id" class="col-md-3 mb-4 d-flex justify-content-center">
            <div class="card text-center shadow-lg" style="width: 100%; max-width: 250px; border-radius: 15px;"
              @click="goToAppointmentPage(doctor)">
              <!-- Doctor Image -->
              <div class="p-3">
                <div class="rounded-circle mx-auto border border-primary shadow"
                  style="width: 120px; height: 120px; overflow: hidden;">
                  <img src="/doctor.png" alt="Doctor image" class="w-100 h-100" style="object-fit: cover;" />
                </div>
              </div>

              <!-- Card Body -->
              <div class="card-body bg-light">
                <!-- Doctor Name -->
                <h5 class="  fw-bold text-dark ">{{ doctor.name }}</h5>

                <!-- Specialization -->
                <p class="card-text mb-2 text-secondary">
                  <strong>Specialization:</strong> {{ doctor.specialization }}
                </p>

                <!-- Start and End Time -->
                <p class="card-text mb-2 text-secondary">
                  <strong>Start Time:</strong> {{ doctor.start_time }}
                </p>
                <p class="card-text mb-2 text-secondary">
                  <strong>End Time:</strong> {{ doctor.end_time }}
                </p>

                <!-- Soonest Appointment -->
                <p class="card-text text-primary fw-bold mb-0">
                  <strong>Next Appointment:</strong> 20/10/2024
                </p>
              </div>
            </div>
          </div>

        </div>

        <div v-if="doctors.length === 0" class="text-center mt-4">
          No Results Found...
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
.card {
  transition: transform .2s;
}

.card:hover {
  transform: scale(1.05);
  cursor: pointer;
}

.card-title {
  font-size: 1rem;
 
}

.card-text {
  font-size: 0.875rem;
}

.search-container {
  width: 100%;
  /* Ensure full width */
  position: relative;
  /* For positioning the search button */
}

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