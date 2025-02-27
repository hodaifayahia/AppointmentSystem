<script setup>
import { ref } from 'vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import DeleteUserModel from '@/Components/DeleteUserModel.vue';
import { useToastr } from '../../Components/toster';
import {  useRouter } from 'vue-router';

import axios from 'axios';
const router = useRouter();

import { useSweetAlert } from '../../Components/useSweetAlert';
const swal = useSweetAlert();

const props = defineProps({
  appointment: { // Changed prop name from user to appointment to reflect appointment data
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

const toaster = useToastr();
const emit = defineEmits(['appointment-updated', 'toggleSelection']);
const selectedDate = ref();

const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null); // Changed ref name to selectedAppointment if needed, but user can be generic for modal

const closeModal = () => {
  isModalOpen.value = false;
  showDeleteModel.value = false;
  selectedUser.value = null; // Keep it selectedUser as it's used for modals which might be user-related
};

const editUser = () => { // Keep editUser as it might be related to editing user info in appointment
  selectedUser.value = { ...props.appointment }; // Pass appointment data to modal, adjust modal to handle appointment data
  isModalOpen.value = true;
};


const goToEditAppointmentPage = (appointment) => {
    router.push({
        name: 'admin.appointments.edit',
        params: { id: appointment.doctor_id, specialization_id:appointment.specialization_id, appointmentId: appointment.id }
    });
    emit('appointment-updated')
};



const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
const formatTime = (timeString) => {
  const date = new Date(`2000-01-01T${timeString}`); // Using a dummy date to parse time
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
};
const formatDateOfBirth = (dateString) => {
  if (!dateString || dateString === 'Unknown') return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
  <tr class="user-item">
    <td class="">
      <input type="checkbox" :checked="selectAll" @change="$emit('toggleSelection', props.appointment)">
    </td>
    <td>{{ index + 1 }}</td>
    <td>{{ appointment.doctor_name }}</td>
    <td>{{ appointment.patient_first_name }} {{ appointment.patient_last_name }}</td>
    <td class="user-phone">{{ appointment.phone || 'N/A' }}</td>
    <td>{{ formatDateOfBirth(appointment.patient_Date_Of_Birth) }}</td>
    <td>{{ formatDate(appointment.appointment_date) }}</td>
    <td>{{ formatTime(appointment.appointment_time) }}</td>
    <td class="user-description" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ appointment.description }}</td>
    <td class="user-reason" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ appointment.reason }}</td>

    <td class="user-actions">
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-primary mx-1" title="Edit" @click="goToEditAppointmentPage(appointment)">
          <i class="fas fa-edit"></i>
        </button>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.user-item {
  transition: background-color 0.3s ease;
}

.user-item:hover {
  background-color: rgba(0, 123, 255, 0.075);
}

.select-column {
  width: 5%;
}

.user-name,
.user-email,
.user-phone,
.user-role,
.user-created-at {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-name {
  max-width: 150px;
}

.user-email,
.user-phone {
  max-width: 180px;
}

.user-role {
  width: 150px;
}

.user-created-at {
  width: 120px;
}

.user-actions {
  width: 120px;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 50px;
}

.form-select-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 50px;
}
</style>