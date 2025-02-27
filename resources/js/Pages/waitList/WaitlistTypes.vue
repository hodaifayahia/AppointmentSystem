<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue'; // Import the modal

const searchQuery = ref('');
const loading = ref(false);
const selectedOption = ref('daily'); // Default selected option
const router = useRouter();
const route = useRoute();

// State for the AddWaitlistModal
const showAddModal = ref(false);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const specializationId = parseInt(route.params.id); // Convert to integer



// Function to handle option change
const handleOptionChange = (option) => {
  selectedOption.value = option;

  // Determine the value of isDaily based on the selected option
  const isDaily = option === 'daily' ? 1 : 0;

  // Navigate to the Waitlist route with the isDaily and specialization_id query parameters
  if (isDaily) {
    router.push({
      name: 'admin.Waitlist.Daily',
      query: { isDaily, specialization_id: specializationId },
    });
  } else {
    router.push({
      name: 'admin.Waitlist.General',
      query: { isDaily, specialization_id: specializationId },
    });
  }
 
};

// Open modal for adding a new waitlist
const openAddModal = () => {
  selectedWaitlist.value = specializationId; // Pass specialization_id
  showAddModal.value = true;
};

// Close modal
const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedWaitlist.value = null;
};

// Handle save event (for adding)
const handleSave = (newWaitlist) => {
  console.log('New Waitlist:', newWaitlist);
  // Add logic to save the new waitlist
  closeAddModal();
};

// Handle update event (for editing)
const handleUpdate = (updatedWaitlist) => {
  console.log('Updated Waitlist:', updatedWaitlist);
  // Add logic to update the waitlist
  closeAddModal();
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

onMounted(() => {
  // Fetch any initial data if needed
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
          <div class="col-sm-12">
            <button class=" float-left btn btn-ligh bg-primary rounded-pill " @click="router.go(-1)">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <!-- Back Arrow Button -->
              </li>
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
        <h2 class="text-center mb-4">Daily or Not Daily</h2>

        <!-- Add to WaitList Button -->
        <div class="mb-4 d-flex justify-content-end">
          <button class="btn btn-primary" @click="openAddModal">
            <i class="fas fa-plus"></i> Add to WaitList
          </button>
        </div>

        <!-- Waitlist Cards -->
        <div class="row">
          <!-- Daily Waitlist Card -->
          <div class="col-md-6 mb-4 d-flex justify-content-center">
            <div
              class="card text-center shadow-lg"
              style="width: 100%; max-width: 350px; border-radius: 15px; cursor: pointer"
              @click="handleOptionChange('daily')"
            >
              <div class="card-body">
                <i class="fas fa-calendar-day fa-3x text-warning mb-3 d-block"></i>
                <h3 class="text-center">Daily Waitlist</h3>
              </div>
            </div>
          </div>

          <!-- General Waitlist Card -->
          <div class="col-md-6 mb-4 d-flex justify-content-center">
            <div
              class="card text-center shadow-lg"
              style="width: 100%; max-width: 350px; border-radius: 15px; cursor: pointer"
              @click="handleOptionChange('general')"
            >
              <div class="card-body">
                <i class="fas fa-calendar-alt fa-3x text-primary mb-3 d-block"></i>
                <h3 class="text-center">General Waitlist</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Waitlist Modal -->
     
    <AddWaitlistModal
      :show="showAddModal"
      :editMode="false"
      :specializationId="specializationId"
      @close="closeAddModal"
      @save="handleSave"
      @update="handleUpdate"
    />
  </div>
</template>

<style scoped>
.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: scale(1.05);
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