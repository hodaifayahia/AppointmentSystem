<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import WaitlistListItem from '../../Pages/waitList/WaitlistListItemModal.vue';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useRouter, useRoute } from 'vue-router';
const router = useRouter();
const swal = useSweetAlert();
const importanceOptions = ref([]);
const waitlists = ref([]);
const showAddModal = ref(false);
const currentFilter = ref({ importance: null, doctor_id: null, is_Daily: 0 }); // Add is_Daily to filters
const route = useRoute();
const isDaily = route.query.isDaily; // This will be '1' or '0'
const specializationId = parseInt(route.query.specialization_id); // Get specialization_id from the route
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const doctors = ref([]);

const fetchWaitlists = async (filters = {}) => {
  try {
    const params = {
      ...filters,
      specialization_id: specializationId || null,
      is_Daily: isDaily !== undefined ? isDaily : 0,
    };
    const response = await axios.get(`/api/waitlists`, { params });
    waitlists.value = response.data.data;
  } catch (error) {
    console.error("Error fetching waitlists:", error);
  }
};


// Fetch importance options
const fetchImportanceOptions = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceOptions.value = response.data;
};

// Fetch doctors based on specialization
const fetchDoctors = async () => {
  if (specializationId) {
    const response = await axios.get(`/api/doctors/specializations/${specializationId}`);
    doctors.value = response.data.data;
  } else {
    doctors.value = [];
  }
};

// Delete a waitlist entry
const deleteWaitlist = async (id) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete this waitlist entry. This action cannot be undone!',
    icon: 'warning',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/waitlists/${id}`);
      swal.fire('Deleted!', 'The waitlist entry has been deleted.', 'success');
      fetchWaitlists(currentFilter.value);
    } catch (error) {
      swal.fire('Error!', 'Failed to delete the waitlist entry. Please try again.', 'error');
      console.error('Error deleting waitlist entry:', error);
    }
  }
};

// Move a waitlist entry to the end
const moveToEnd = async (id) => {
  await axios.post(`/api/waitlists/${id}/move-to-end`);
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
  isEditMode.value = !!waitlist;
  selectedWaitlist.value = waitlist;
  showAddModal.value = true;
};

// Close modal
const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedWaitlist.value = null;
};

// Handle save event (for adding)
const handleSave = () => {
  fetchWaitlists(currentFilter.value);
};

// Handle update event (for editing)
const handleUpdate = () => {
  fetchWaitlists(currentFilter.value);
};

// Filter waitlists by importance and doctor_id
const filterWaitlists = (importance = null, doctor_id = null) => {
  currentFilter.value = { ...currentFilter.value, importance, doctor_id };
  fetchWaitlists(currentFilter.value);
};


// Clear all filters
const clearFilters = () => {
  currentFilter.value = { importance: null, doctor_id: null, is_Daily: isDaily };
  fetchWaitlists(currentFilter.value);
};

// Check if an importance filter is active
const isImportanceFilterActive = (importance) => {
  return currentFilter.value.importance === importance;
};

onMounted(() => {
  fetchImportanceOptions();
  fetchWaitlists();
  fetchDoctors();
});
</script>

<template>
  <div class="content">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">General WaitList</h1>
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
            <div class="card-header ">
              <div class="d-flex justify-content-between align-items-center">
                <div class="mb-3">
                  <button v-for="doctor in doctors" :key="doctor.id" @click="filterWaitlists(null, doctor.id)"
                    :class="{ 'active': currentFilter.doctor_id === doctor.id }"
                    class="btn btn-sm btn-outline-primary ml-2">
                    {{ doctor.name }}
                  </button>
                  <button @click="filterWaitlists(null, null)" class="btn btn-sm btn-outline-primary ml-2"
                    :class="{ 'active': currentFilter.doctor_id === null }">
                    All Doctors
                  </button>
                  <button @click="filterWaitlists(null, 'null')" class="btn btn-sm btn-outline-primary ml-2"
                    :class="{ 'active': currentFilter.doctor_id === 'null' }">
                    No Doctor Assigned
                  </button>

                </div>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-primary text-n" @click="filterWaitlists(null)"
                    :class="{ 'btn-primary': isImportanceFilterActive(null) }">
                    All
                  </button>
                  <button class="btn btn-primary mr-2" @click="openAddModal()">
                    <i class="fas fa-plus"></i> Add to WaitList
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="">
                <table class="table table-striped table-bordered">
                  <thead class="thead-dark">
                    <tr>
                      <th scope="col">Doctor Name</th>
                      <th scope="col">Specialization</th>
                      <th scope="col">Patient Name</th>
                      <th scope="col">Patient Phone</th>
                      <th scope="col">Created At</th>
                      <th scope="col" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <WaitlistListItem v-for="(waitlist, index) in waitlists" :key="waitlist.id" :waitlist="waitlist"
                      :isDaily="isDaily" :index="index" @move-to-end="moveToEnd" :importance-options="importanceOptions"
                      @update="openAddModal(waitlist)" @update-importance="updateImportance" @delete="deleteWaitlist"
                      @move-to-appointments="moveToAppointments" />
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
  <AddWaitlistModal :show="showAddModal" :editMode="isEditMode" :waitlist="selectedWaitlist"
    :specializationId="specializationId" :isDaily="isDaily" @close="closeAddModal" @save="handleSave"
    @update="handleUpdate" />
</template>

<style scoped>
/* Custom styles for better look */
.card-header {
  padding: 1rem 1.25rem;
}

.btn-group .btn {
  margin-right: 5px;
}

.table th,
.table td {
  vertical-align: middle;
}

.table-responsive {
  max-height: 60vh;
  overflow-y: auto;
}
</style>