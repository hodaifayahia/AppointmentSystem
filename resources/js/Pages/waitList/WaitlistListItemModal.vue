<script setup>
import { ref ,onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import AppointmentFormWaitlist from '../../Components/appointments/appointmentFormWaitlist.vue';
import { useAuthStore } from '../../stores/auth';


const router = useRouter();
const toastr = useToastr();
const dropdownStates = ref({});
const isModalOpen = ref(false);


const props = defineProps({
  waitlist: {
    type: Object,
    required: true,
  },
  isDoctor: {
    type: Boolean,
    required: false,
  },
  importanceOptions: {
    type: Object,
    required: true,
  },
  isDaily: {
    type: Boolean,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
});

const userRole = ref("");


const authStore = useAuthStore();

onMounted(async () => {
    await authStore.getUser();
    userRole.value = authStore.user.role;

});

const emit = defineEmits(['update', 'delete', 'move-to-appointments', 'update-importance', 'move-to-end']);

// Helper function to map numeric importance to string key
const getImportanceKey = (importance) => {
  switch (importance) {
    case 0:
      return 'Urgent';
    case 1:
      return 'Normal';
    default:
      return null; // Fallback for unknown importance
  }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (time) => {
    if (!time) return "00:00";
    try {
        if (time.includes('T')) {
            const [, timePart] = time.split('T');
            if (timePart.length === 6) return timePart;
            const [hours, minutes] = timePart.split(':');
            return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
        }
        const [hours, minutes] = time.split(':');
        return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
    } catch (error) {
        console.error("Error formatting time:", error);
        return "00:00";
    }
};
// Toggle dropdown
const toggleDropdown = (waitlistId) => {
  dropdownStates.value[waitlistId] = !dropdownStates.value[waitlistId];
};

// Update importance status
const updateImportanceStatus = async (waitlistId, newImportance) => {
  try {
    await axios.patch(`/api/waitlists/${waitlistId}/importance`, { importance: newImportance });
    dropdownStates.value[waitlistId] = false; // Close the dropdown
    toastr.success('Importance status updated successfully');
    emit('update-importance'); // Notify parent to refresh the list
  } catch (err) {
    console.error('Error updating importance:', err);
  }
};
// Open modal
const openModal = () => {
  isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
  isModalOpen.value = false;
  emit('update-importance');
};


</script>
<template>
  <tr :class="{ 'move-to-end': !!waitlist.MoveToEnd }">
    <!-- Doctor Name -->
    <td v-if="!isDoctor">
      <span v-if="waitlist.MoveToEnd !== null" class="circle-number">{{ waitlist.MoveToEnd }}</span>
      {{ waitlist.doctor_name }}
    </td>
    <td v-if="!isDoctor">{{ waitlist.specialization_name }}</td>

    <!-- Patient Name -->
    <td >{{ waitlist.patient_first_name }} {{ waitlist.patient_last_name }}</td>

    <!-- Patient Phone -->
    <td>{{ waitlist.patient_phone }}</td>

    <!-- Importance Status -->
    <td v-if="!isDaily && !isDoctor">
      <span
        :class="`text-${importanceOptions[getImportanceKey(waitlist.importance)]?.color || 'secondary'} text-uppercase`">
        <i
          :class="[importanceOptions[getImportanceKey(waitlist.importance)]?.icon || 'fa fa-question-circle', 'fa-lg ml-2']"></i>
        {{ importanceOptions[getImportanceKey(waitlist.importance)]?.label || 'Unknown' }}
      </span>
    </td>
    <td >
      {{ formatDate(waitlist.created_at) }} {{ formatTime(waitlist.created_at) }}
    </td>
     <td v-if="isDoctor">

       <div class="dropdown" :class="{ 'show': dropdownStates[waitlist.id] }">
           <button class="btn dropdown-toggle status-button" type="button" @click="toggleDropdown(waitlist.id)">
             <span
               :class="`text-${importanceOptions[getImportanceKey(waitlist.importance)]?.color || 'secondary'} text-uppercase`">
               <i
                 :class="[importanceOptions[getImportanceKey(waitlist.importance)]?.icon || 'fa fa-question-circle', 'fa-lg ml-2']"></i>
               {{ importanceOptions[getImportanceKey(waitlist.importance)]?.label || 'Unknown' }}
             </span>
           </button>
           <ul class="dropdown-menu dropdown-menu-end" style="z-index: 100;"
             :class="{ 'show': dropdownStates[waitlist.id] }">
             <li v-for="(option, key) in importanceOptions" :key="key">
               <a class="dropdown-item d-flex align-items-center" href="#"
                 @click.prevent="updateImportanceStatus(waitlist.id, option.value)">
                 <i :class="[option.icon, `text-${option.color}`]"></i>
                 <span class="status-text rounded-pill fw-bold"
                   :class="[`text-${option.color}`, 'fs-6 ml-1 text-uppercase']">
                   {{ option.label }}
                 </span>
               </a>
             </li>
           </ul>
         </div>
     </td>

    <!-- Actions -->
     
    <td>
      <button v-if="(userRole !== 'admin' || userRole !== 'receptionist')" class="btn btn-sm btn-outline-primary ml-2" @click="$emit('update', waitlist.id)">
        <i class="fas fa-edit"></i>
      </button>
      <button   class="btn btn-sm btn-outline-danger ml-2" @click="$emit('delete', waitlist.id)">
        <i class="fas fa-trash"></i>
      </button>
      <button v-if="userRole ==='doctor'  || userRole ==='admin'" class="btn btn-sm btn-outline-success ml-2" @click="$emit('move-to-appointments', waitlist)">
        <i class="fas fa-calendar-check"></i>
      </button>
      <button v-if="(isDaily && index === 0 && (!isDoctor) && waitlist.importance === null)" 
        @click="openModal"
        class="btn btn-sm btn-outline-primary ml-2">
    <i class="fas fa-calendar-alt"></i> <!-- Icon for changing appointment -->
</button>

      <button v-if="isDaily && index === 0 && !isDoctor && waitlist.importance === null" @click="$emit('move-to-end', waitlist.id)"
        class="btn btn-sm btn-outline-primary ml-2">
        <i class="fas fa-forward"></i>
      </button>
    </td>
  </tr>

  <!-- Modal for AppointmentFormWaitlist -->
  <AppointmentFormWaitlist :showModal="isModalOpen" :waitlist="waitlist" :isDaily="isDaily"
    :editMode="!!waitlist.appointmentId" @close="closeModal" />
</template>

<style scoped>
/* Add custom styles if needed */
.status-indicator {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 8px;
}

.dropdown-menu {
  min-width: 200px;
}

.move-to-end {
  border-left: 4px solid red; /* Red border on the left */
}

.circle-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background-color: red;
  border-radius: 50%;
  color: white;
  font-size: 12px;
  font-weight: bold;
  margin-right: 8px;
}
</style>
