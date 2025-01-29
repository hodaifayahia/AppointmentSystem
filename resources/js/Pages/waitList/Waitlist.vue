<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import WaitlistListItem from './WaitlistListItemModal.vue';

const router = useRouter();
const importanceOptions = ref([]);
const waitlists = ref([]);
const showAddModal = ref(false);
const currentFilter = ref(null);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);

// Access the route to get the isDaily query parameter
const route = useRoute();
const isDaily = route.query.isDaily; // This will be '1' or '0'
const specializationId = parseInt(route.query.specialization_id); // Get specialization_id from the route

const fetchWaitlists = async (importance = null, specializationId = null) => {
  const params = {};
  if (importance !== null) {
    params.importance = importance;
  }
  if (specializationId !== null) {
    params.specialization_id = specializationId; // Add specialization_id to the request
  }

  // Call the API with the isDaily and specialization_id parameters
  const response = await axios.get(`/api/waitlists/${isDaily}`, { params });
  waitlists.value = response.data.data;
};

// Fetch importance options
const fetchImportanceOptions = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceOptions.value = response.data;
};

// Add a new waitlist entry
const addWaitlist = async (newWaitlist) => {
  await axios.post('/api/waitlists', newWaitlist);
  fetchWaitlists(currentFilter.value);
};

// Update importance of a waitlist entry
const updateImportance = async (id, importance) => {
  await axios.put(`/api/waitlists/${id}/importance`, { importance });
  fetchWaitlists(currentFilter.value);
};

// Move a waitlist entry to appointments
const moveToAppointments = async (waitlist) => {
  await axios.post(`/api/waitlists/${waitlist.id}/add-to-appointments`, {
    doctor_id: waitlist.doctor_id,
    waitlist_id: waitlist.id,
    patient_id: waitlist.patient_id,
    notes: waitlist.notes,
  });
  fetchWaitlists(currentFilter.value);
};

// Open modal for adding or editing
const openAddModal = (waitlist = null) => {
  if (waitlist) {
    // Edit mode
    isEditMode.value = true;
    selectedWaitlist.value = waitlist;
  } else {
    // Add mode
    isEditMode.value = false;
    selectedWaitlist.value = null;
  }
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
  addWaitlist(newWaitlist);
};

// Handle update event (for editing)
const handleUpdate = (updatedWaitlist) => {
  axios.put(`/api/waitlists/${updatedWaitlist.id}`, updatedWaitlist)
    .then(() => {
      fetchWaitlists(currentFilter.value);
    });
};

// Importance filter functions
const filterWaitlists = (importance) => {
  currentFilter.value = importance;
  fetchWaitlists(importance);
};

const isImportanceFilterActive = (importance) => {
  return currentFilter.value === importance;
};

onMounted(() => {
  fetchImportanceOptions();
  fetchWaitlists();
});
</script>
<template>
  <div class="content">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">WaitList</h1>
          </div>
          <div class="col-sm-12">
            <button class=" float-left btn btn-ligh bg-primary rounded-pill " @click="router.go(-1)">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">WaitList</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-end align-items-center">
              <div>
                <div  class="btn-group" role="group">
                  <button 
                    v-for="option in importanceOptions" 
                    :key="option.value" 
                    class="btn btn-sm"
                    :class="{
                      'btn-outline-primary': !isImportanceFilterActive(option.value),
                      [`btn-${option.color}`]: isImportanceFilterActive(option.value)
                    }"
                    @click="filterWaitlists(option.value)"
                  >
                    <i :class="option.icon"></i> {{ option.label }}
                  </button>
                  <button 
                    class="btn btn-sm btn-primary text-n" 
                    @click="filterWaitlists(null)"
                    :class="{'btn-primary': isImportanceFilterActive(null)}"
                  >
                    All
                  </button>
                </div>
                <button 
                  class="btn btn-primary mr-2" 
                  @click="openAddModal()"
                >
                  <i class="fas fa-plus"></i> Add to WaitList
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="">
                <table class="table table-striped table-bordered">
                  <thead class="thead-dark">
                    <tr>
                      <th scope="col">Doctor</th>
                      <th scope="col">specialization</th>
                      <th scope="col">Patient Name</th>
                      <th scope="col">Patient Phone</th>
                      <th scope="col">Importance</th>
                      <th scope="col" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <WaitlistListItem
                      v-for="(waitlist , index) in waitlists"
                      :key="waitlist.id"
                      :waitlist="waitlist"
                      :importance-options="importanceOptions"
                      :index="index"
                      @update="openAddModal(waitlist)" 
                      @update-importance="updateImportance"
                      @delete="deleteWaitlist"
                      @move-to-appointments="moveToAppointments"
                    />
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Waitlist Modal -->
  <AddWaitlistModal
  :show="showAddModal"
  :editMode="isEditMode"
  :waitlist="selectedWaitlist"
  :specializationId="specializationId" 
  :isDaily="isDaily"
  @close="closeAddModal"
  @save="handleSave"
  @update="handleUpdate"
/>
</template>

<style scoped>
/* Custom styles for better look */
.card-header {
  padding: 1rem 1.25rem;
}

.btn-group .btn {
  margin-right: 5px;
}

.table th, .table td {
  vertical-align: middle;
}

.table-responsive {
  max-height: 60vh;
  overflow-y: auto;
}
</style>