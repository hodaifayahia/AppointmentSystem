
<script setup>
import { defineProps, defineEmits , ref} from 'vue';
import DoctorModel from '../../Components/DoctorModel.vue';
import DeleteDoctorModel from '../../Components/DeleteDoctorModel.vue';

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
const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null);


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


// Utility functions
// const formatDays = (daysData) => {
//   if (Array.isArray(daysData)) {
//     return daysData;
//   } else if (typeof daysData === 'string') {
//     try {
//       return JSON.parse(daysData);
//     } catch (error) {
//       console.error('Error parsing days:', error);
//       return [daysData];
//     }
//   }
//   return [];
// };

const formatTime = (time) => {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  const hour = parseInt(hours, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12; 
  return `${formattedHour}:${minutes} ${ampm}`;
};



</script>

<template>
  <tr class="doctor-item">
    <td class="select-column">
      <input 
        type="checkbox" 
        :checked="selectAll" 
        @change="() => emit('toggle-selection', doctor)"
      >
    </td>
    <td>{{ index + 1 }}</td>
    <td class="doctor-name">{{ doctor.name }}</td>
    <td class="doctor-email">{{ doctor.email }}</td>
    <td class="doctor-phone">{{ doctor.phone }}</td>
    <td class="doctor-specialization">{{ doctor.specialization }}</td>
    <td class="doctor-frequency">{{ doctor.frequency }}</td>
    <td class="doctor-start-time">{{ formatTime(doctor.start_time) }}</td>
    <td class="doctor-end-time">{{ formatTime(doctor.end_time) }}</td>
    <td class="doctor-patients">
      {{ doctor.patients_based_on_time ? `${doctor.time_slot} min slots` : `${doctor.number_of_patients_per_day} patients/day` }}
    </td>
    <td class="doctor-actions">
      <div class="btn-group">
        <button 
          class="btn btn-sm btn-outline-primary mx-1" 
          title="Edit"
          @click="editUser"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button 
          class="btn btn-sm btn-outline-danger" 
          title="Delete"
          @click="openDeleteModal"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>



  
  <!-- Modals -->
  <Teleport to="body">
    <DoctorModel
      :show-modal="isModalOpen"
      :doctor-data="selectedUser"
      @close="closeModal"
      @doctorUpdated="handleUserUpdate"
    />

    <DeleteDoctorModel
      v-if="showDeleteModel"
      :show-modal="showDeleteModel"
      :doctor-data="selectedUser"
      @close="closeModal"
      @doctor-deleted="handleUserUpdate"
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