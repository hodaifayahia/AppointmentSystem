<script setup>
import { ref } from 'vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import DeleteUserModel from '@/Components/DeleteUserModel.vue';
import { useToastr } from '../../Components/toster';
import axios from 'axios';

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

const openDeleteModal = () => {
  selectedUser.value = { ...props.user };
  showDeleteModel.value = true;
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
  { name: 'admin', value: 'admin' }
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
        :disabled="user.role === 'admin'">
        <option v-for="role in roles" :key="role.value" :value="role.value" :selected="user.role === role.name">
          {{ capitalize(role.name) }}
        </option>
      </select>
    </td>

    <td class="user-created-at">{{ formatDate(user.created_at) }}</td>
    <td class="user-actions">
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-primary mx-1" title="Edit" @click="editUser">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" title="Delete" @click="openDeleteModal">
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

.form-select-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 50px;
}
</style>