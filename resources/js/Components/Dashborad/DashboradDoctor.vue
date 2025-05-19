<script setup>
import { ref, onMounted, computed, watch } from 'vue'
// import Chart from 'chart.js/auto'
// import 'chartjs-adapter-date-fns'
import axios from 'axios';
import { useAuthStoreDoctor } from '../../stores/AuthDoctor';


const {doctorData} = useAuthStoreDoctor(); // Initialize Pinia store

   
// Reactive state
const dashboardData = ref({
  todayAppointmentsCount: 0,
  totalDoctorsCount: 0,
  totalPatientsCount: 0,
  totalSpecializationsCount: 0,
  totalAppointmentsCount: 0,
  upcomingAppointmentsCount: 0,
  cancelledAppointmentsCount: 0,
  pendingAppointmentsCount: 0
})

// Display data for animations
const displayData = ref({
  todayAppointmentsCount: 0,
  totalDoctorsCount: 0,
  totalPatientsCount: 0,
  totalSpecializationsCount: 0,
  totalAppointmentsCount: 0,
  upcomingAppointmentsCount: 0,
  cancelledAppointmentsCount: 0,
  pendingAppointmentsCount: 0
})

const charts = ref({})
const loading = ref(true)
const error = ref(null)
const chartsReady = ref(false)
const dataUpdated = ref(false)

// Card configuration with expanded metrics
const cardConfig = {
  todayAppointmentsCount: { 
    color: 'primary', 
    label: 'Today\'s Appointments', 
    icon: 'bi-calendar-check' ,
    link: '/calander'
  },
  totalDoctorsCount: { 
    color: 'success', 
    label: ' Doctors', 
    icon: 'bi-person-fill-gear',
    link: '/admin/doctors' 
  },
  totalPatientsCount: { 
    color: 'info', 
    label: 'Total Patients', 
    icon: 'bi-people-fill' ,
    link: '/admin/patient'

  },
  cancelledAppointmentsCount: { 
    color: 'danger', 
    label: 'Cancelled Appointments', 
    icon: 'bi-x-circle' ,
    link: '/calander'

  },
  pendingAppointmentsCount: { 
    color: 'warning', 
    label: 'Pending Appointments', 
    icon: 'bi-hourglass-split' ,
    link: '/admin/pending'
  },
  upcomingAppointmentsCount: { 
    color: 'secondary', 
    label: 'Upcoming Appointments', 
    icon: 'bi-calendar-date' ,
    link: '/calander'

  },
  totalAppointmentsCount: { 
    color: 'dark', 
    label: 'Total Appointments', 
    icon: 'bi-calendar-fill' ,
    link: '/admin/appointments/specialization'
  },
  totalSpecializationsCount: { 
    color: 'dark', 
    label: 'Specializations', 
    icon: 'bi-award' ,
    link: '/admin/specializations'
  }
}

// Computed property to get primary KPIs for the top row
const primaryKPIs = computed(() => {
  return {
    todayAppointmentsCount: displayData.value.todayAppointmentsCount,
    totalDoctorsCount: displayData.value.totalDoctorsCount,
    totalPatientsCount: displayData.value.totalPatientsCount,
    cancelledAppointmentsCount: displayData.value.cancelledAppointmentsCount
  }
})

// Computed property to get secondary KPIs for the second row
const secondaryKPIs = computed(() => {
  return {
    pendingAppointmentsCount: displayData.value.pendingAppointmentsCount,
    upcomingAppointmentsCount: displayData.value.upcomingAppointmentsCount,
    totalAppointmentsCount: displayData.value.totalAppointmentsCount,
    totalSpecializationsCount: displayData.value.totalSpecializationsCount
  }
})

// Animate numbers with CSS class triggers and Vue transitions
const animateNumbers = () => {
  // Set initial values to animate from if first load
  if (!dataUpdated.value) {
    Object.keys(dashboardData.value).forEach(key => {
      displayData.value[key] = 0
    })
  }
  
  // Use a timeout to make sure the animation triggers properly
  setTimeout(() => {
    Object.keys(dashboardData.value).forEach(key => {
      displayData.value[key] = dashboardData.value[key] || 0
    })
    dataUpdated.value = true
  }, 100)
}

// Data handling
const fetchMedicalData = async () => {
  try {
    loading.value = true
    
    const response = await axios.get('/api/medical-dashboard',{
        doctorId = doctorData?.value.id
    })
    dashboardData.value = response.data
    
   
    
    // Animate the numbers
    animateNumbers()
    loading.value = false
  } catch (err) {
    console.error('Data fetch error:', err)
    error.value = 'Failed to load dashboard data'
    loading.value = false
  }
}

// Setup watchers for number animation
const setupWatchers = () => {
  Object.keys(dashboardData.value).forEach(key => {
    watch(() => dashboardData.value[key], (newVal) => {
      // Create a counter that increments from current to new value
      const startVal = displayData.value[key] || 0
      const endVal = newVal || 0
      const duration = 2000 // 2 seconds
      const interval = 20 // Update every 20ms
      const steps = duration / interval
      const step = (endVal - startVal) / steps
      
      let current = startVal
      let currentStep = 0
      
      
    })
  })
}


// Lifecycle hooks
onMounted(() => {
  setupWatchers()
  fetchMedicalData()
  
  // Refresh data every 30 seconds
})
</script>

<template>
  <main class="medical-dashboard" role="main">
    <!-- Header -->
    <header class="dashboard-header" aria-label="Medical Dashboard header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="h3 mb-0">
              <i class="bi bi-hospital me-2 "></i>Medical Dashboard
            </h1>
          </div>
          <div class="col-md-6">
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
              <ol class="breadcrumb float-md-end">
                <li class="breadcrumb-item"><a href="#">Clinic</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dashboard-content">
      <div class="container-fluid">
        <!-- Initial loading indicator -->
        <div class="initial-loading" v-if="loading && !chartsReady">
          <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3">Loading dashboard data...</p>
        </div>

        <!-- Error alert -->
        <div class="alert alert-danger" v-if="error">
          <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
        </div>

        <div v-if="!loading || chartsReady">
          <!-- Primary KPIs -->
          <section class="kpi-grid row g-4 mb-4" aria-label="Primary Medical KPIs">
            <div class="col-lg-3 col-md-6" v-for="(value, key) in primaryKPIs" :key="key">
              <div class="kpi-card animate-card" :class="`bg-${cardConfig[key].color}`">
                <div class="kpi-content">
                  <i :class="`icon bi ${cardConfig[key].icon}`"></i>
                  <h3 class="value count-up" :class="{ 'animating': dataUpdated }">
                    {{ Math.round(value) }}
                  </h3>
                  <p class="label">{{ cardConfig[key].label }}</p>
                </div>
                <div class="kpi-overlay"></div>
                <router-link :to="cardConfig[key].link" class="kpi-link">View Details <i class="bi bi-arrow-right-circle"></i></router-link>
           {{ cardConfig[key].link }}
              </div>
            </div>
          </section>

          <!-- Secondary KPIs -->
          <section class="kpi-grid row g-4 mb-4" aria-label="Secondary Medical KPIs">
            <div class="col-lg-3 col-md-6" v-for="(value, key) in secondaryKPIs" :key="key">
              <div class="kpi-card animate-card" :class="`bg-${cardConfig[key].color}`">
                <div class="kpi-content">
                  <i :class="`icon bi ${cardConfig[key].icon}`"></i>
                  <h3 class="value count-up" :class="{ 'animating': dataUpdated }">
                    {{ Math.round(value) }}
                  </h3>
                  <p class="label">{{ cardConfig[key].label }}</p>
                </div>
                <div class="kpi-overlay"></div>
               <router-link :to="cardConfig[key].link" class="kpi-link">View Details <i class="bi bi-arrow-right-circle"></i></router-link>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>
</template>

<style scoped>
.medical-dashboard {
  background-color: #fffefe;
  color: #fff;
  min-height: 100vh;
}

.dashboard-header {
  padding: 1.5rem 0;
  background-color: #fffefe;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  color:black;
}

.dashboard-content {
  padding: 2rem 0;
}

/* KPI Cards */
.kpi-card {
  position: relative;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(241, 241, 241, 0.1);
  overflow: hidden;
  height: 180px;
  transition: transform 0.3s ease;
}

.kpi-card:hover {
  transform: translateY(-5px);
}

.kpi-content {
  position: relative;
  z-index: 2;
}

.kpi-card .icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.kpi-card .value {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  transition: all 2s cubic-bezier(0.16, 1, 0.3, 1);
}

.kpi-card .value.animating {
  color: #ffffff;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.kpi-card .label {
  font-size: 1rem;
  opacity: 0.8;
  margin-bottom: 0;
}

.kpi-overlay {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
  z-index: 1;
}

.kpi-link {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  z-index: 2;
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.kpi-link:hover {
  color: rgba(255, 255, 255, 1);
}

/* Chart Cards */
.chart-card {
  background-color: #2d3748;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.chart-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chart-header h4 {
  margin: 0;
  font-size: 1.2rem;
}

.chart-body {
  padding: 1.5rem;
  height: 300px;
  position: relative;
}

/* Action Card */
.action-card {
  background-color: #2d3748;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-card .btn {
  margin-right: 0.5rem;
  margin-bottom: 0.5rem;
}

/* Loading styles */
.initial-loading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 300px;
}

.chart-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(26, 32, 44, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10;
  backdrop-filter: blur(3px);
}

/* Count up animation */
.count-up {
  position: relative;
}

.count-up::after {
  content: '';
  position: absolute;
  bottom: -3px;
  left: 0;
  width: 100%;
  height: 3px;
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

/* Pulsing button */
.btn-pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
  }
}

/* Background Colors */
.bg-primary {
  background-color: #3b82f6;
}

.bg-success {
  background-color: #10b981;
}

.bg-info {
  background-color: #0ea5e9;
}

.bg-warning {
  background-color: #f59e0b;
}

.bg-danger {
  background-color: #dc2626;
}

.bg-secondary {
  background-color: #6b7280;
}

.bg-dark {
  background-color: #1e293b;
}

.bg-light {
  background-color: #4b5563;
  color: #fff;
}

/* Card animations */
.animate-card {
  opacity: 0;
  animation: fadeInUp 0.6s ease-out forwards;
}

.kpi-grid:first-child > div:nth-child(1) .animate-card { animation-delay: 0.1s; }
.kpi-grid:first-child > div:nth-child(2) .animate-card { animation-delay: 0.2s; }
.kpi-grid:first-child > div:nth-child(3) .animate-card { animation-delay: 0.3s; }
.kpi-grid:first-child > div:nth-child(4) .animate-card { animation-delay: 0.4s; }

.kpi-grid:nth-child(2) > div:nth-child(1) .animate-card { animation-delay: 0.5s; }
.kpi-grid:nth-child(2) > div:nth-child(2) .animate-card { animation-delay: 0.6s; }
.kpi-grid:nth-child(2) > div:nth-child(3) .animate-card { animation-delay: 0.7s; }
.kpi-grid:nth-child(2) > div:nth-child(4) .animate-card { animation-delay: 0.8s; }

.charts-section .col-lg-6:first-child .animate-card { animation-delay: 0.9s; }
.charts-section .col-lg-6:last-child .animate-card { animation-delay: 1.0s; }
.quick-actions .animate-card { animation-delay: 1.1s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translate3d(0, 40px, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .kpi-card {
    height: 150px;
  }
  
  .kpi-card .icon {
    font-size: 2rem;
  }
  
  .kpi-card .value {
    font-size: 2rem;
  }
  
  .chart-body {
    height: 250px;
  }
}

/* Flash animation for number updates */
@keyframes numberFlash {
  0% {
    color: #ffffff;
    text-shadow: 0 0 0 rgba(255, 255, 255, 0);
  }
  50% {
    color: #ffffff;
    text-shadow: 0 0 20px rgba(255, 255, 255, 0.8);
  }
  100% {
    color: #ffffff;
    text-shadow: 0 0 0 rgba(255, 255, 255, 0);
  }
}

.value.count-up.animating {
  animation: numberFlash 2s ease-out;
}
</style>