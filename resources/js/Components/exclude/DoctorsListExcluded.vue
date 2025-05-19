<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

// Reactive state
const searchQuery = ref('');
const loading = ref(false);
const router = useRouter();

// Fetch doctors

const props = defineProps({
    doctors: {
      type: Object,
      required: true,
    }
  });
  


// Navigate to doctors page
const goToDoctorsExcluded = (doctor) => {
  router.push({
    name: 'admin.excludeDates.doctor',
    params: { id: doctor.id },
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

</script>

<template>
  <div>
    <!-- Content Header -->
   

    <!-- Main Content -->
    <div class="content">
      <div class="container">
        <h2 class="text-center mb-4">doctors</h2>
        <div class="mb-4">
          <!-- Search Bar -->
          <!-- <div class="mb-1 search-container">
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
          </div> -->
        </div>

        <!-- Specialization Cards -->
        <div class="row">
          <div
            v-for="doctor in doctors"
            :key="doctor.id"
            class="col-md-3 mb-4 d-flex justify-content-center"
          >
            <div
              class="card text-center shadow-lg"
              style="width: 100%; max-width: 250px; border-radius: 15px;"
              @click="goToDoctorsExcluded(doctor)"
            >
              <!-- Specialization Image -->
              <div class="p-3">
                <div class="mx-auto" style="width: 120px; height: 120px; overflow: hidden;">
                  <img
                    :src="doctor.avatar"
                    alt="Specialization image"
                    class="w-100 h-100"
                    style="object-fit: contain"
                  />
                </div>
              </div>

              <!-- Card Body -->
              <div class="card-body bg-light">
                <p class="card-text text-dark fw-bold fs-5">
                  {{ doctor.name }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- No Results Message -->
        <div v-if="doctors.length === 0 && !loading" class="text-center mt-4">
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