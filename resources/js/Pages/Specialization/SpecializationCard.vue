<template>
    <div class="col-md-3 mb-4 d-flex justify-content-center">
      <div
        class="card text-center shadow-lg"
        style="width: 100%; max-width: 250px; border-radius: 15px;"
        @click="handleClick"
      >
        <!-- Specialization Image -->
        <div class="p-3">
          <div class="mx-auto" style="width: 120px; height: 120px; overflow: hidden;">
            <img :src="specialization.photo_url" alt="Specialization image" class="w-100 h-100" style="object-fit: contain" />
          </div>
        </div>
  
        <!-- Card Body -->
        <div class="card-body bg-light">
          <!-- Specialization Name -->
          <p class="card-text text-dark fw-bold fs-5">
            {{ specialization.name }}
          </p>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { useRouter } from 'vue-router';
  
  const props = defineProps({
    specialization: {
      type: Object,
      required: true,
    },
    routeName: {
      type: String,
      required: true,
    },
  });
  
  const router = useRouter();
  
  const handleClick = () => {
    router.push({
      name: props.routeName,
      params: { id: props.specialization.id },
      query: { name: props.specialization.name },
    });
  };
  </script>
  
  <style scoped>
  .card {
    transition: transform 0.2s;
  }
  
  .card:hover {
    transform: scale(1.05);
    cursor: pointer;
  }
  </style>