<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import specializationModel from "../../Components/specializationModel.vue";
import { useSweetAlert } from '../../Components/useSweetAlert';
const swal = useSweetAlert();

const specializations = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();

const getSpecializations = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get('/api/specializations'); // Changed endpoint to plural
    specializations.value = response.data.data || response.data; // Adjust based on your API response structure
    console.log(specializations.value);
  } catch (error) {
    console.error('Error fetching specializations:', error);
    error.value = error.response?.data?.message || 'Failed to load specializations';
  } finally {
    loading.value = false;
  }
};

const isModalOpen = ref(false);
const selectedSpecialization = ref([]);

const openModal = (specialization = null) => {
  selectedSpecialization.value = specialization ? { ...specialization } : {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};


const refreshSpecializations = async () => {
  await getSpecializations();
};

const deleteSpecialization = async (id) => {
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
      await axios.delete(`/api/specializations/${id}`);
    toaster.success('Specialization deleted successfully');
    refreshSpecializations(); // Refresh the list after deletion
      // Show success message
      swal.fire(
        'Deleted!',
        'specialization has been deleted.',
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
        'Failed to delete Doctor.',
        'error'
      );
    }
  }
};


onMounted(() => {
  getSpecializations();
})
</script>

<template>
  <div class="appointment-page">
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Specializations</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Specializations</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 py-2" title="Add User"
                @click="openModal">
                <i class="fas fa-plus-circle"></i>
                <span>Add specialization</span>
              </button>
            </div>

            <!-- Specialization List -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                  </div>
                </div>

                <table v-else class="table table-hover">
                  <thead>
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">Photo</th>
                      <th scope="col">Specialization</th>
                      <th scope="col">Description</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="specializations.length === 0">
                      <td colspan="5" class="text-center">No specializations found</td>
                    </tr>
                    <tr v-else v-for="(specialization, index) in specializations" :key="specialization.id">
                      <td>{{ index + 1 }}</td>
                      <td>
                        <img v-if="specialization.photo_url" :src="`${specialization.photo_url}`"
                          :alt="`Photo for ${specialization.photo_url}`" class="img-thumbnail"
                          style="max-width: 40px; max-height: 40px;" />
                        <span v-else>No Photo</span>
                      </td>
                      <td>{{ specialization.name }}</td>
                      <td>{{ specialization.description }}</td>
                      <td>
                        <button @click="openModal(specialization)" class="btn btn-sm btn-outline-primary me-2">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="deleteSpecialization(specialization.id)"
                          class="btn btn-sm btn-outline-danger ml-1">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Specialization Modal -->
    <specializationModel :show-modal="isModalOpen" :spec-data="selectedSpecialization" @close="closeModal"
      @specUpdate="refreshSpecializations" />
  </div>
</template>

<style scoped>
/* ... Your existing styles ... */
</style>