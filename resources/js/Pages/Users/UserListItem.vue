<script setup>
import { ref } from 'vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import DeleteUserModel from '@/Components/DeleteUserModel.vue';
import { useToastr } from '../../Components/toster';
import axios from 'axios';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth'

const swal = useSweetAlert();
const props = defineProps({
  user: {
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
const authStore = useAuthStore();

const userRole = ref(authStore.user?.role);

const toaster = useToastr();
const emit = defineEmits(['user-updated', 'toggleSelection']);

const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null);

const closeModal = () => {
  isModalOpen.value = false;
  showDeleteModel.value = false;
  selectedUser.value = null;
};

const editUser = () => {
  selectedUser.value = { ...props.user };
  isModalOpen.value = true;
};

// Check if the current user can edit a specific user
const canEdit = (targetUser) => {
  // SuperAdmin can edit anyone
  if (userRole.value === 'SuperAdmin') return true;
  
  // Admin can edit doctors and receptionists, but not other admins or SuperAdmins
  if (userRole.value === 'admin') {
    return targetUser.role === 'doctor' || targetUser.role === 'receptionist';
  }
  
  // Other roles can't edit
  return false;
};

// Check if the current user can delete a specific user
const canDelete = (targetUser) => {
  // SuperAdmin can delete anyone
  if (userRole.value === 'SuperAdmin') return true;
  
  // Admin can delete doctors and receptionists, but not other admins or SuperAdmins
  if (userRole.value === 'admin') {
    return targetUser.role === 'doctor' || targetUser.role === 'receptionist';
  }
  
  // Other roles can't delete
  return false;
};

const handleDelete = async (id) => {
  try {
    // Show SweetAlert confirmation dialog using the configured swal instance
    const result = await swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    });

    // If user confirms, proceed with deletion
    if (result.isConfirmed) {
      await axios.delete(`/api/users/${id}`);
      toaster.success('User deleted successfully');
      handleUserUpdate();

      // Show success message
      swal.fire(
        'Deleted!',
        'User has been deleted.',
        'success'
      );

      // Emit event to notify parent component
      emit('doctorDeleted');
      closeModal();
    }
  } catch (error) {
    // Handle error
    if (error.response?.data?.message) {
      swal.fire(
        'Error!',
        error.response.data.message,
        'error'
      );
    } else {
      swal.fire(
        'Error!',
        'Failed to delete User.',
        'error'
      );
    }
  }
};

const handleUserUpdate = () => {
  emit('user-updated');
  closeModal();
};

const ChangeRole = async (user, role) => {
  try {
    await axios.patch(`/api/users/${user.id}/change-role`, { role });
    toaster.success('Role Changed Successfully');
    emit('user-updated');
  } catch (error) {
    console.error('Error changing role:', error);
    toaster.error('Failed to change role');
  }
};

const roles = [
  { name: 'doctor', value: 'doctor' },
  { name: 'receptionist', value: 'receptionist' },
  { name: 'admin', value: 'admin' },
];

const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);

const formatDate = (dateString) => {
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
    <td class="select-column">
      <input type="checkbox" :checked="selectAll" @change="$emit('toggleSelection', props.user)">
    </td>
    <td>{{ index + 1 }}</td>
    <td>
      <img v-if="user.avatar" :src="`${user.avatar}`"
        :alt="`Photo for ${user.name}`" class="img-thumbnail rounded-pill" style="max-width: 40px; max-height: 40px;" />
      <span v-else>No Photo</span>
    </td>
    
    <td class="user-name">{{ user.name }}</td>
    <td class="user-email">{{ user.email }}</td>
    <td class="user-phone">{{ user.phone || 'N/A' }}</td>
    <td class="user-role">
      <select @change="ChangeRole(user, $event.target.value)" class="form-control form-select-sm"
        :disabled="!canEdit(user) || user.role === 'SuperAdmin'">
        <option v-for="role in roles" :key="role.value" :value="role.value" :selected="user.role === role.name">
          {{ capitalize(role.name) }}
        </option>
      </select>
    </td>

    <td class="user-created-at">{{ formatDate(user.created_at) }}</td>
    <td class="user-actions">
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-primary mx-1" title="Edit" @click="editUser" 
               :disabled="!canEdit(user)">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" title="Delete" @click.stop="handleDelete(user.id)"
               :disabled="!canDelete(user)">
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>

  <!-- Modals -->
  <Teleport to="body">
    <AddUserComponent :show-modal="isModalOpen" :user-data="selectedUser" @close="closeModal"
      @user-updated="handleUserUpdate" />

    <DeleteUserModel v-if="showDeleteModel" :show-modal="showDeleteModel" :user-data="selectedUser" @close="closeModal"
      @user-deleted="handleUserUpdate" />
  </Teleport>
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

.btn-group .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 50px;
}
</style>