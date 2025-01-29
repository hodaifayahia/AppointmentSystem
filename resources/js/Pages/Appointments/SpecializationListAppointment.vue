<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

// Reactive state
const specializations = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const router = useRouter();

// Fetch specializations
const getSpecializations = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching specializations:', error);
    alert(error.response?.data?.message || 'Failed to load specializations');
  } finally {
    loading.value = false;
  }
};

// Navigate to doctors page
const goToDoctorsPage = (specialization) => {
  router.push({
    name: 'admin.appointments.doctors',
    params: { id: specialization.id },
    query: { name: specialization.name },
  });
};

// Debounced search function
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      // Add search logic here if needed
    }, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

// Fetch data on mount
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
        <h2 class="text-center mb-4">Specializations</h2>
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

        <!-- Specialization Cards -->
        <div class="row">
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
                <div class="mx-auto" style="width: 120px; height: 120px; overflow: hidden;">
                  <img
                    :src="specialization.photo_url"
                    alt="Specialization image"
                    class="w-100 h-100"
                    style="object-fit: contain"
                  />
                </div>
              </div>

              <!-- Card Body -->
              <div class="card-body bg-light">
                <p class="card-text text-dark fw-bold fs-5">
                  {{ specialization.name }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- No Results Message -->
        <div v-if="specializations.length === 0 && !loading" class="text-center mt-4">
          No Results Found...
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: scale(1.05);
  cursor: pointer;
}

.search-container {
  width: 100%;
  position: relative;
}

.search-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #007bff;
  border-radius: 50px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.premium-search {
  border: none;
  border-radius: 50px 0 0 50px;
  flex-grow: 1;
  padding: 10px 15px;
  font-size: 16px;
  outline: none;
}

.premium-search:focus {
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.search-button {
  border: none;
  background: #007bff;
  color: white;
  padding: 10px 20px;
  border-radius: 0 50px 50px 0;
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s ease;
}

.search-button:hover {
  background: #0056b3;
}

.search-button i {
  margin-right: 5px;
}

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