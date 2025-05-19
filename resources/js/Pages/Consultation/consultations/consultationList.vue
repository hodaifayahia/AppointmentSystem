<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import ConsultationListItem from './consultationListItem.vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router';
const router = useRouter();
const route = useRoute();
const swal = useSweetAlert();
const toaster = useToastr();

// State
const consultations = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');

// State
const showModal = ref(false); // Add this state variable

// Computed
const filteredConsultations = computed(() => {
  if (!searchQuery.value.trim()) return consultations.value;
  const query = searchQuery.value.toLowerCase();
  return consultations.value.filter(c =>
    c.patient_name.toLowerCase().includes(query) ||
    (c.doctor_name && c.doctor_name.toLowerCase().includes(query))
  );
});

const hasConsultations = computed(() => consultations.value.length > 0);

// API
const getConsultations = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/consultations');
    consultations.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load consultations';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const deleteConsultation = async (id, name) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: `Delete consultation "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/consultations/${id}`);
      await getConsultations();
      toaster.success('Consultation deleted successfully');
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete consultation');
    }
  }
};
// Navigate to create Consulation page
const goToAddConsulationPage = () => {
  router.push({
    name: 'admin.consultations.consulation.add',
  });
};

// Replace goToAddConsulationPage with this
const openConsultationModal = () => {
  showModal.value = true;
};

// Lifecycle
onMounted(() => {
  getConsultations();
});
</script>

<template>
  <div class="consultation-page">
    <div class="card">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="mb-0">Consultations</h3>
          <div class="d-flex gap-3 align-items-center">
            <div class="d-flex gap-3 align-items-center">
            <div class="position-relative">
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control" 
                placeholder="Search consultations..." 
              />
              <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
            </div>
          </div>
            <button 
              class="btn btn-primary d-flex align-items-center gap-2" 
              @click="goToAddConsulationPage"
            >
              <i class="fas fa-plus"></i>
              <span>Make Consultation</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <p class="mb-0">Loading consultations...</p>
        </div>

        <div v-if="error" class="alert alert-danger m-3">{{ error }}</div>

        <div v-if="!loading && !hasConsultations" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-file-alt fa-3x text-muted"></i>
          </div>
          <p class="mb-3">No consultations found.</p>
        </div>

        <div v-if="!loading && filteredConsultations.length === 0 && searchQuery.trim()" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-search fa-3x text-muted"></i>
          </div>
          <p class="mb-0">No consultations match your search.</p>
        </div>

        <div v-if="filteredConsultations.length" class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">#</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <ConsultationListItem
                v-for="(consultation, index) in filteredConsultations"
                :key="consultation.id"
                :consultation="consultation"
                :index="index"
                @delete="deleteConsultation"
              />
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.consultation-page {
  padding: 1rem;
}

.card {
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  overflow: hidden;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

@media (max-width: 768px) {
  .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
  
  .d-flex.gap-3 {
    width: 100%;
  }
  
  input.form-control {
    width: 100%;
  }
}
</style>