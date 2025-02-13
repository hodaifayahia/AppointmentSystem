<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import WaitlistListItemModal from '../../Pages/waitList/WaitlistListItemModal.vue';
import { useRoute } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';

const swal = useSweetAlert();
const importanceOptions = ref([]);
const waitlists = ref([]);
const showAddModal = ref(false);
const currentFilter = ref({ importance: null });
const route = useRoute();
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const doctors = ref([]);

const props = defineProps({
  WaitlistDcotro: {
    type: Boolean,
    required: true,
  },
  doctorId: {
    type: Number,
    default: null,
  },
  specializationId: {
    type: Number,
    default: null,
  },
  NotForYou: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const showModal = ref(props.WaitlistDcotro);

// Watch for changes in the prop
watch(
  () => props.WaitlistDcotro,
  (newVal) => {
    showModal.value = newVal;
  }
);

const closeModal = () => {
  showModal.value = false;
  waitlists.value = [];
  emit('close');
};
const fetchWaitlists = async (filters = {}) => {
  try {
    const params = { ...filters, is_Daily: 1 };
    params.doctor_id = props.NotForYou ? "null" : props.doctorId; // Set doctor_id based on NotForYou
    params.specialization_id = props.specializationId;

    const response = await axios.get('/api/waitlists', { params });
    waitlists.value = response.data.data;
  } catch (error) {
    console.error('Error fetching waitlists:', error);
    swal.fire('Error!', 'Failed to fetch waitlists. Please try again.', 'error');
  }
};

// Watch for changes in the WaitlistDcotro prop to fetch waitlists
watch(
  () => props.WaitlistDcotro,
  (newVal) => {
    if (newVal) {
      fetchWaitlists(currentFilter.value);
    }
  }
);
const fetchImportanceOptions = async () => {
  try {
    const response = await axios.get('/api/importance-enum');
    importanceOptions.value = response.data;
  } catch (error) {
    console.error('Error fetching importance options:', error);
    swal.fire('Error!', 'Failed to fetch importance options. Please try again.', 'error');
  }
};

const deleteWaitlist = async (id) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete this waitlist entry. This action cannot be undone!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
  });
  
  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/waitlists/${id}`);
      swal.fire('Deleted!', 'The waitlist entry has been deleted.', 'success');
      fetchWaitlists(currentFilter.value);
    } catch (error) {
      console.error('Error deleting waitlist entry:', error);
      swal.fire('Error!', 'Failed to delete the waitlist entry. Please try again.', 'error');
    }
  }
};

const moveToAppointments = async (waitlist) => {
  const result = await swal.fire({
    title: 'Move to Appointments?',
    text: 'Are you sure you want to move this waitlist entry to appointments?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, move it!',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    try {
      await axios.post(`/api/waitlists/${waitlist.id}/add-to-appointments`, {
        doctor_id: props.doctorId,
        waitlist_id: waitlist.id,
        patient_id: waitlist.patient_id,
        appointmentId: waitlist.appointmentId??null,
        notes: waitlist.notes,
      });
      fetchWaitlists();
      swal.fire('Success!', 'Entry moved to appointments successfully.', 'success');
    } catch (error) {
      console.error('Error moving waitlist to appointments:', error);
      swal.fire('Error!', 'Failed to move waitlist to appointments. Please try again.', 'error');
    }
  }
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

const updateImportance = async (waitlistId, importance) => {
  try {
    await axios.put(`/api/waitlists/${waitlistId}`, { importance });
    fetchWaitlists(currentFilter.value);
  } catch (error) {
    console.error('Error updating importance:', error);
    swal.fire('Error!', 'Failed to update importance. Please try again.', 'error');
  }
};

const filterWaitlists = (importance = null) => {
  currentFilter.value = { importance };
  fetchWaitlists(currentFilter.value);
};

const clearFilters = () => {
  currentFilter.value = { importance: null };
  fetchWaitlists(currentFilter.value);
};

onMounted(() => {
  fetchImportanceOptions();
  fetchWaitlists();
});
</script>

<template>
  <div class="modal fade" :class="{ show: showModal }" tabindex="-1" aria-labelledby="waitlistModalLabel"
    aria-hidden="true" v-if="showModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="waitlistModalLabel">Waitlist</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <div class="card">
                  <div class="card-header">
                    <div class="d-flex justify-content-end align-items-center">
                      <div class="btn-group" role="group">
                        <button v-for="option in importanceOptions" :key="option.value" class="btn btn-sm" :class="{
                          'btn-outline-primary': currentFilter.importance !== option.value,
                          [`btn-${option.color}`]: currentFilter.importance === option.value
                        }" @click="filterWaitlists(option.value)">
                          <i :class="option.icon"></i> {{ option.label }}
                        </button>
                        <button class="btn btn-sm btn-primary" @click="clearFilters">
                          Clear Filters
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="">
                      <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                          <tr>
                            <th scope="col">Patient Name</th>
                            <th scope="col">Patient Phone</th>
                            <th scope="col">Importance</th>
                            <th scope="col">created_at</th>
                            <th scope="col" class="text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <WaitlistListItemModal v-for="waitlist in waitlists" :key="waitlist.id" :waitlist="waitlist"
                            :importance-options="importanceOptions" :isDoctor="true" @update="openAddModal(waitlist)"
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
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal.show {
  display: block;
}
</style>