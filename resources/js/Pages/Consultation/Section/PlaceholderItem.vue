
<script setup>
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import { useRouter } from 'vue-router';
import { ref } from 'vue';

const props = defineProps({
  placeholder: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  }
});
const router = useRouter();

const emit = defineEmits(['edit', 'delete']);

const swal = useSweetAlert();
const toaster = useToastr();

const handleEdit = () => {
  emit('edit', props.placeholder);
};
const goToAttributes = (placeholderId) => {
  
  // Navigate using the router
  router.push({ name: 'admin.section.attributes', params: {
     id: placeholderId 
    } });
};

const handleDelete = async () => {
  try {
    const result = await swal.fire({
      title: 'Are you sure?',
      text: `Delete placeholder "${props.placeholder.name}"? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    });

    if (result.isConfirmed) {
      emit('delete', props.placeholder.id);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to delete placeholder');
  }
};

</script>

<template>
  <tr @click="goToAttributes(placeholder.id)" style="cursor: pointer;">
    <td>{{ index + 1 }}</td>

    <td>
      <strong>{{ placeholder.name }}</strong>
    </td>

    <td>
      <span v-if="placeholder.description">{{ placeholder.description }}</span>
      <span v-else class="text-muted fst-italic">No Description</span>
    </td>
    

    <td>
      <span v-if="placeholder.specialization">{{ placeholder.specialization.name }}</span>
      <span v-else class="text-muted fst-italic">No Specialization</span>
    </td>
    <td>
      <span v-if="placeholder.doctor">{{ placeholder.doctor.name }}</span>
      <span v-else class="text-muted fst-italic">No Doctor</span>
    </td>


    <td>
      <div class="btn-group">
        <button 
          class="btn btn-sm btn-outline-primary" 
          @click.stop="handleEdit"
          title="Edit"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button 
          class="btn btn-sm btn-outline-danger" 
          @click.stop="handleDelete"
          title="Delete"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.btn-group {
  display: flex;
  gap: 0.25rem;
}
</style>
