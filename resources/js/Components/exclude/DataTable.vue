<template>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="bg-light">
        <tr>
          <th>#</th>
          <th>Doctor Name</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Reason</th>
          <th>Applies to All Years</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(date, index) in dates" :key="date.id">
          <td>{{ index + 1 }}</td>
          <td>{{ date.doctor_name || 'All Doctors' }}</td>
          <td>{{ formatDate(date.start_date) }}</td>
          <td>{{ formattedEndDate(date.end_date) }}</td>
          <td>{{ date.reason || 'No reason provided' }}</td>
          <td>
            <span v-if="date.apply_for_all_years == '1'">Yes</span>
            <span v-else>No</span>
          </td>
          <td>
            <button  class="btn btn-sm btn-outline-primary ml-1" @click="$emit('edit', date)">
              <i class="fas fa-edit"></i>
            </button>
            <button  class="btn btn-sm btn-outline-danger ml-1" @click="$emit('remove', date.id)">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
  dates: {
    type: Array,
    required: true,
  },
});

// Helper function to format dates
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// computed property to format end date
const formattedEndDate = (endDate) => {
  return endDate === null ? 'N/A' : formatDate(endDate);
};
</script>
