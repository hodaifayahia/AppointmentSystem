<script setup>
import { ref, onMounted, watch  } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const specializations = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const router = useRouter();


console.log();

// Function to fetch specialzaition
const getSpecializations = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get('/api/specializations'); // Changed endpoint to plural
    specializations.value = response.data.data || response.data; // Adjust based on your API response structure
  } catch (error) {
    console.error('Error fetching specializations:', error);
    error.value = error.response?.data?.message || 'Failed to load specializations';
  } finally {
    loading.value = false;
  }
};


const goToDoctorsPage = (specialization) => {
  router.push({
    name: 'admin.appointments.doctors',
    params: { id: specialization.id }, // Pass specialization ID
    query: { name: specialization.name }, // Optionally pass the name
  });
};


// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(getDoctors, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);


onMounted(() => {
  getSpecializations();
});
</script>

<template>
  <div>
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"></h1>
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
        <h2 class="text-center mb-4">specialization </h2>
        <div class="mb-4">
          <!-- Search Bar -->
          <div class="mb-1 search-container">
            <div class="search-wrapper">
              <input 
                type="text" 
                class="form-control premium-search" 
                v-model="searchQuery" 
                placeholder="Search doctors" 
              />
              <button class="search-button">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="row" v-if="loading">
          <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden"></span>
            </div>
          </div>
        </div>
        <div v-else class="row">
  <div
    v-for="specialization in specializations"
    :key="specialization.id"
    class="col-md-3 mb-4 d-flex justify-content-center"
  >
    <div
      class="card text-center shadow-lg"
      style="width: 100%; max-width: 250px; border-radius: 15px;"
      @click="goToDoctorsPage(specialization)"
    >
      <!-- Specialization Image -->
      <div class="p-3">
        <div
          class=" mx-auto  "
          style="width: 120px; height: 120px; overflow: hidden;"
        >
          <img
            src="/heart.png"
            alt="Specialization image"
            class="w-100 h-100"
            style="object-fit:contain"
          />
        </div>
      </div>

      <!-- Card Body -->
      <div class="card-body bg-light">
        <!-- Specialization Name -->
        <p class="card-text text-dark fw-bold fs-5">
          {{ specialization.name }}
        </p>
      </div>
    </div>
  </div>
</div>


        <div v-if="specializations.length === 0" class="text-center mt-4">
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
  width: 100%; /* Ensure full width */
  position: relative; /* For positioning the search button */
}

.search-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #007BFF; /* Blue border for a premium feel */
  border-radius: 50px; /* Rounded corners for a modern look */
  overflow: hidden; /* Ensures the border-radius applies to children */
  transition: all 0.3s ease; /* Smooth transition for focus/blur effects */
}

.premium-search {
  border: none; /* Remove default border */
  border-radius: 50px 0 0 50px; /* Round only left corners */
  flex-grow: 1; /* Expand to fill available space */
  padding: 10px 15px; /* Adequate padding for text */
  font-size: 16px; /* Clear, readable text size */
  outline: none; /* Remove the outline on focus */
}

.premium-search:focus {
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Focus effect */
}

.search-button {
  border: none;
  background: #007BFF; /* Blue background for the search button */
  color: white;
  padding: 10px 20px;
  border-radius: 0 50px 50px 0; /* Round only right corners */
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s ease; /* Smooth transition for hover effect */
}

.search-button:hover {
  background: #0056b3; /* Darker blue on hover */
}

.search-button i {
  margin-right: 5px; /* Space between icon and text */
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
