<script setup>
import { defineProps, defineEmits, ref, onMounted } from 'vue';
import DoctorModel from '../../Components/DoctorModel.vue';
import { useRouter } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth';

import axios from 'axios';

const swal = useSweetAlert();
const router = useRouter();

// Props and emits
const props = defineProps({
  doctor: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  },
  selectAll: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['doctorUpdated', 'toggleSelection']);

// Refs
const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null);
const userRole = ref(null); // Track user role


const authStore = useAuthStore();
// const { user, isLoading } = storeToRefs(authStore);

onMounted( () => {
 userRole.value =authStore.user.role 
});


// Modal and user actions
const closeModal = () => {
  isModalOpen.value = false;
  showDeleteModel.value = false;
  selectedUser.value = null;
};

const editUser = () => {
  selectedUser.value = { ...props.doctor };
  isModalOpen.value = true;
};

const openDeleteModal = () => {
  selectedUser.value = { ...props.doctor };
  showDeleteModel.value = true;
};

const handleUserUpdate = () => {
  emit('doctorUpdated');
  closeModal();
};

// Format time
const formatTime = (time) => {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  const hour = parseInt(hours, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12; 
  return `${formattedHour}:${minutes} ${ampm}`;
};

// Navigate to doctor schedule page
const goToDoctorSchdulePage = (doctorId) => {
  router.push({ name: 'admin.doctors.schedule', params: { id: doctorId } });
};

// Handle delete
const handleDelete = async (id) => {
  try {
    const result = await swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
      await axios.delete(`/api/doctors/${id}`, { params: { id: props.doctor.id } });
      swal.fire('Deleted!', 'Doctor has been deleted.', 'success');
      emit('doctorDeleted');
      closeModal();
    }
  } catch (error) {
    if (error.response?.data?.message) {
      swal.fire('Error!', error.response.data.message, 'error');
    } else {
      swal.fire('Error!', 'Failed to delete Doctor.', 'error');
    }
  }
};
</script>

<template>
  <tr 
    class="doctor-item"
    @click="goToDoctorSchdulePage(doctor.id)" 
    style="cursor: pointer;" 
  >
    <td class="select-column">
      <input 
        type="checkbox" 
        :checked="selectAll" 
        @change.prevent.stop="() => emit('toggle-selection', doctor)"
      >
    </td>
    <td>{{ index + 1 }}</td>
    <td>
      <img v-if="doctor.avatar" :src="`${doctor.avatar}`"
        :alt="`Photo for ${doctor.name}`" class="img-thumbnail rounded-pill" style="max-width: 40px; max-height: 40px;" />
      <span v-else>No Photo</span>
    </td>
    <td class="doctor-name">{{ doctor.name }}</td>
    <td class="doctor-email">{{ doctor.email }}</td>
    <td class="doctor-phone">{{ doctor.phone }}</td>
    <td class="doctor-specialization">{{ doctor.specialization }}</td>
    <td class="doctor-frequency">{{ doctor.frequency }}</td>
    <td class="doctor-patients">
      {{ `${doctor.time_slots} min slots`  }}
    </td>
    <td class="doctor-actions">
      <div class="btn-group" v-if="userRole === 'admin' || userRole === 'SuperAdmin'">
        <button 
          class="btn btn-sm btn-outline-primary mx-1" 
          title="Edit"
          @click.stop="editUser"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button 
          class="btn btn-sm btn-outline-danger" 
          title="Delete"
          @click.stop="handleDelete(doctor.id)"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>

  <!-- Modals -->
  <Teleport to="body">
    <DoctorModel
      v-if="isModalOpen"
      :show-modal="isModalOpen"
      :doctor-data="selectedUser"
      @close="closeModal"
      @doctorUpdated="handleUserUpdate"
    />
  </Teleport>
</template>

<style scoped>
.doctor-item {
  transition: background-color 0.3s ease;
}

.doctor-item:hover {
  background-color: rgba(0, 123, 255, 0.075);
}

.select-column {
  width: 5%;
}

.doctor-name, .doctor-email, .doctor-phone, .doctor-specialization, .doctor-frequency, .doctor-start-time, .doctor-end-time, .doctor-patients {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doctor-name {
  max-width: 150px;
}

.doctor-email, .doctor-phone {
  max-width: 180px;
}

.doctor-specialization, .doctor-frequency {
  max-width: 120px;
}

.doctor-start-time, .doctor-end-time {
  width: 100px;
}

.doctor-patients {
  width: 150px;
}

.doctor-actions {
  width: 120px;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
}

.btn-sm {
  border-radius: 50px;
}
</style>