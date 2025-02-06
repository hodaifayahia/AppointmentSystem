<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import WaitlistListItem from '../../Pages/waitList/WaitlistListItemModal.vue';
import { useRouter, useRoute } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';
const swal = useSweetAlert();
const importanceOptions = ref([]);
const waitlists = ref([]);
const router = useRouter();

const showAddModal = ref(false);
const currentFilter = ref({ importance: null, doctor_id: null });
const route = useRoute();
const isDaily = route.query.isDaily;
const specializationId = parseInt(route.query.specialization_id);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const doctors = ref([]);

const fetchWaitlists = async (filters = {}) => {
  const params = { ...filters, is_Daily: 1 };
  const response = await axios.get(`/api/waitlists`, { params });
  waitlists.value = response.data.data;
};

const fetchImportanceOptions = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceOptions.value = response.data;
};

const fetchDoctors = async () => {
  if (specializationId) {
    const response = await axios.get(`/api/doctors/specializations/${specializationId}`);
    doctors.value = response.data.data;
  } else {
    doctors.value = [];
  }
};

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

const moveToAppointments = async (waitlist) => {
  await axios.post(`/api/waitlists/${waitlist.id}/add-to-appointments`, {
    doctor_id: waitlist.doctor_id,
    waitlist_id: waitlist.id,
    patient_id: waitlist.patient_id,
    notes: waitlist.notes,
  });
  fetchWaitlists(currentFilter.value);
};

const openAddModal = (waitlist = null) => {
  isEditMode.value = !!waitlist;
  selectedWaitlist.value = waitlist;
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedWaitlist.value = null;
};

const handleSave = () => {
  fetchWaitlists(currentFilter.value);
};

const handleUpdate = () => {
  fetchWaitlists(currentFilter.value);
};

const filterWaitlists = (importance = null, doctor_id = null) => {
  currentFilter.value = { importance, doctor_id };
  fetchWaitlists(currentFilter.value);
};

const clearFilters = () => {
  currentFilter.value = { importance: null, doctor_id: null };
  fetchWaitlists(currentFilter.value);
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
            <h1 class="m-0">Daily WaitList</h1>
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
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <div class="mb-3">
                  <button @click="filterWaitlists(null, doctor.id)"
                   v-for="doctor in doctors" :key="doctor.id"
                    :class="{ 'active': currentFilter.doctor_id === doctor.id }"
                    class="btn btn-sm btn-outline-primary ml-2">
                    {{ doctor.name }}
                  </button>
                  <button  @click="filterWaitlists(null, null)"
                   class="btn btn-sm btn-outline-primary ml-2"
                   :class="{ 'active': currentFilter.doctor_id === null }">
                    All Doctors
                  </button>
                  <!-- Change this button -->
                  <button @click="filterWaitlists(null, 'null')" class="btn btn-sm btn-outline-primary ml-2"
                    :class="{ 'active': currentFilter.doctor_id === 'null' }">
                    No Doctor Assigned
                  </button>
                </div>
                <div class="btn-group" role="group">
                  <button v-for="option in importanceOptions" :key="option.value" class="btn btn-sm" :class="{
                    'btn-outline-primary': currentFilter.importance !== option.value,
                    [`btn-${option.color}`]: currentFilter.importance === option.value
                  }" @click="filterWaitlists(option.value, currentFilter.doctor_id)">
                    <i :class="option.icon"></i> {{ option.label }}
                  </button>
                  <button class="btn btn-sm btn-primary" @click="clearFilters">
                    Clear Filters
                  </button>
                  <button class="btn btn-primary ml-2" @click="openAddModal()">
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
                      <th scope="col">Importance</th>
                      <th scope="col">Created At</th>
                      <th scope="col" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <WaitlistListItem v-for="waitlist in waitlists" :key="waitlist.id" :waitlist="waitlist"
                      :importance-options="importanceOptions" @update="openAddModal(waitlist)"
                      @update-importance="updateImportance" @delete="deleteWaitlist"
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

  <AddWaitlistModal :show="showAddModal" :editMode="isEditMode" :waitlist="selectedWaitlist"
    :specializationId="specializationId" :isDaily="isDaily" @close="closeAddModal" @save="handleSave"
    @update="handleUpdate" />
</template>

<style scoped>
.btn.active {
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  border-color: #0056b3;
}
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