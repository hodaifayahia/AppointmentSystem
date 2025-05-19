<template>
    <tr>
      <td>{{ index + 1 }}</td>
      <td>
        <strong>{{ attribute.name }}</strong>
      </td>
      <td>{{ attribute.value }}</td>
      <td>{{ attribute.placeholder_id }}</td>
      <td>
        <div class="btn-group">
          <button 
            class="btn btn-sm btn-outline-primary" 
            @click="handleEdit"
            title="Edit"
          >
            <i class="fas fa-edit"></i>
          </button>
          <button 
            class="btn btn-sm btn-outline-danger" 
            @click="handleDelete"
            title="Delete"
          >
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
      </td>
    </tr>
  </template>
  
  <script setup>
  import { useToastr } from '../../../Components/toster';
  import { useSweetAlert } from '../../../Components/useSweetAlert';
  
  const props = defineProps({
    attribute: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    }
  });
  
  const emit = defineEmits(['edit', 'delete']);
  
  const swal = useSweetAlert();
  const toaster = useToastr();
  
  const handleEdit = () => {
    emit('edit', props.attribute);
  };
  
  const handleDelete = async () => {
    try {
      const result = await swal.fire({
        title: 'Are you sure?',
        text: `Delete attribute "${props.attribute.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
      });
  
      if (result.isConfirmed) {
        emit('delete', props.attribute.id);
      }
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete attribute');
    }
  };
  </script>
  
  <style scoped>
  .btn-group {
    display: flex;
    gap: 0.25rem;
  }
  </style>