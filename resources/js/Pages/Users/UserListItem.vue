<!-- UserListItem.vue -->
<script setup>
import { ref } from 'vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import DeleteUserModel from '@/Components/DeleteUserModel.vue';
import { useToastr } from '../../Components/toster';

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  }
});

const toaster = useToastr();
const emit = defineEmits(['user-updated']);

const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null);



// Close modals
const closeModal = () => {
  isModalOpen.value = false;
  showDeleteModel.value = false;
  selectedUser.value = null;
};

// Handle editing user
const editUser = () => {
  selectedUser.value = { ...props.user };
  isModalOpen.value = true;
};

// Open delete confirmation modal
const openDeleteModal = () => {
  selectedUser.value = { ...props.user };
  showDeleteModel.value = true;
};

// Handle user updates
const handleUserUpdate = () => {
  emit('user-updated');
  closeModal();
};

const ChangeRole = async (user, role) => {
  axios.patch(`/api/users/${user.id}/change-role`, { role })
  .then(()=>{
    toaster.success('Role Changed Successfully');
  })
  
};


const roles = [
  { name: 'doctor', value: 'admin' },
  { name: 'receptionist', value: 'receptionist' },
  { name: 'admin', value: 'doctor' }
];

</script>

<template>
  <tr>
    <td>{{ index + 1 }}</td>
    <td>{{ user.name }}</td>
    <td>{{ user.email }}</td>
    <td>{{ user.phone || 'N/A' }}</td>
    <td>
      <select @change="ChangeRole(user,$event.target.value)" class="form form-control">
  <option 
    v-for="role in roles" 
    :key="role.value" 
    :value="role.value" 
    :selected="(user.role == role.name)"
  >
    {{ role.name }}
  </option>
</select>

    </td>
    <td>{{ user.created_at}}</td>
    <td>
      <div class="d-flex justify-content-center gap-2">
        <button
          class="btn btn-outline-success btn-sm"
          title="Edit"
          @click="editUser"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button
          class="btn btn-outline-danger btn-sm"
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
    <AddUserComponent
      :show-modal="isModalOpen"
      :user-data="selectedUser"
      @close="closeModal"
      @user-updated="handleUserUpdate"
    />

    <DeleteUserModel
      v-if="showDeleteModel"
      :show-modal="showDeleteModel"
      :user-data="selectedUser"
      @close="closeModal"
      @user-deleted="handleUserUpdate"
    />
  </Teleport>
</template>

<style scoped>
.badge {
  padding: 0.5em 0.75em;
  font-size: 0.85em;
  border-radius: 0.25rem;
  text-transform: capitalize;
}

.btn-sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.5rem;
}

.btn i {
  font-size: 0.875rem;
}
</style>