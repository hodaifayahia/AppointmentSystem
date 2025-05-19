
<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';


const appointments = ref([]);
const loading = ref(false);
const error = ref(null);
const currentFilter = ref(null);
const pagination = ref(null);
const currentDate = ref(new Date());
const selectedDate = ref(new Date());
const doctors = ref([]);
const doctor_id = ref(null);
const role = ref(null);
const displayMode = ref('working'); // 'all', 'holidays', or 'working'

const filters = ref({
  patientName: '',
  phone: '',
  dateOfBirth: '',
  time: '',
  status: '',
  doctorName: '',
});

const initializeRole = async () => {
  try {
    const user = await axios.get('/api/role');
    if (user.data.role === 'doctor') {
      role.value = user.data.role;
      doctor_id.value = user.data.id;

      // Immediately fetch appointments after setting doctor_id
      await getAppointments();
    }
  } catch (err) {
    console.error('Error fetching user role:', err);
  }
};


const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const getAppointments = async (status = null, filter = null, date = null) => {
  try {
    loading.value = true;
    error.value = null;

    currentFilter.value = status || 'ALL';

    const params = {
      status: status === 'ALL' ? null : status,
      filter: filter,
      date: date || filters.value.date,
      doctorName: filters.value.doctorName,
      // Add doctor_id to params if it exists
      ...(doctor_id.value && { doctor_id: doctor_id.value })

    };


    const response = await axios.get(`/api/appointments`, { params });
    console.log(response.data);
    
    pagination.value = response.data.meta;

    if (response.data.success) {
      appointments.value = response.data.data;
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = err.message || 'Failed to load appointments';
    appointments.value = [];
  } finally {
    loading.value = false;
  }
};
const fetchDoctorsworkingDates = async (month) => {
  try {
    loading.value = true;
    error.value = null;

    // Make the API call for working dates
    const response = await axios.get('/api/doctors/WorkingDates', {
      params: {
        month,
        doctorId: doctor_id.value
      }
    });

    if (response.data.data) {
      const colors = [
    '#CC0000',  // Vibrant Red
    '#FF9933',  // Warm Orange
    '#CCCC00',  // Bright Yellow
    '#99FF33',  // Electric Lime
    '#00CC66',  // Cool Green
    '#33FF99',  // Minty Fresh
    '#00CCCC',  // Cyan Splash
    '#33CCFF',  // Sky Blue
    '#0000CC',  // Deep Navy
    '#6666FF',  // Soft Periwinkle
    '#CC00CC',  // Fuchsia Magenta
    '#FF66FF',  // Cotton Candy Pink
    '#CC66CC',  // Lavender Mist
    '#FF3399',  // Hot Pink
    '#CC3366'   // Dusty Rose
];

      if (doctor_id.value) {
        // If doctor_id exists, we expect a single doctor's data
        const doctorData = Array.isArray(response.data.data) ? response.data.data[0] : response.data.data;
        doctors.value = [{
          ...doctorData,
          working_dates: doctorData.working_dates.map(date => formatLocalDate(new Date(date))),
          // The excludedDates are already in the response
          excludedDates: doctorData.excludedDates || [],
          color: colors[0]
        }];
      } else {
        // If no doctor_id, handle multiple doctors as before
        doctors.value = response.data.data.map((doctor, index) => ({
          ...doctor,
          working_dates: doctor.working_dates.map(date => formatLocalDate(new Date(date))),
          // The excludedDates are already in the response
          excludedDates: doctor.excludedDates || [],
          color: colors[index % colors.length]
        }));
      }
    } else {
      throw new Error('Failed to fetch doctors');
    }
    
    // Also fetch appointments for the same month to ensure we have complete data
    await getAppointments();
    
  } catch (err) {
    console.error('Error fetching doctors:', err);
    error.value = err.message || 'Failed to load doctors';
    doctors.value = [];
  } finally {
    loading.value = false;
  }
};
// Update the getDoctorsWorkingOnDate function to handle excluded dates
const getDoctorsWorkingOnDate = (date) => {
  const dateStr = formatLocalDate(date);
  const result = [];

  doctors.value.forEach(doctor => {
    // Check if doctor is working on this date
    const isWorking = doctor.working_dates.some(workingDate => {
      return formatLocalDate(new Date(workingDate)) === dateStr;
    });

    if (isWorking) {
      result.push({
        name: doctor.name,
        color: doctor.color,
      });
    }
    
    // Check for excluded dates (holidays)
    if (doctor.excludedDates && doctor.excludedDates.length > 0) {
      const isHoliday = doctor.excludedDates.some(exclusion => {
        // Check if date falls within an excluded range
        const startDate = new Date(exclusion.start_date);
        const endDate = exclusion.end_date ? new Date(exclusion.end_date) : startDate;
        const currentDate = new Date(dateStr);
        
        // Normalize to compare just the dates
        const normalizedStart = new Date(startDate.setHours(0, 0, 0, 0));
        const normalizedEnd = new Date(endDate.setHours(0, 0, 0, 0));
        const normalizedCurrent = new Date(currentDate.setHours(0, 0, 0, 0));
        
        // Check if date is within range and exclusionType is complete
        return normalizedCurrent >= normalizedStart && 
               normalizedCurrent <= normalizedEnd && 
               exclusion.exclusionType === 'complete';
      });
      
      if (isHoliday) {
        result.push({
          name: `${doctor.name}: Holiday`,
          color: '#FF0000', // Red color for holidays
          isHoliday: true
        });
      }
    }
  });

  return result;
};
const setDisplayMode = (mode) => {
  displayMode.value = mode;
};
const hasAppointments = (date) => {
  const dateStr = formatLocalDate(date);
  return appointments.value.some(apt => {
    const aptDate = formatLocalDate(new Date(apt.appointment_date));
    return aptDate === dateStr;
  });
};

// Calendar navigation
const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1);
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
};

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1);
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
};

// Format month as 'YYYY-MM'
const formatMonthYear = (date) => {
  return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
};
const formatLocalDate = (date) => {
  if (date === null) {
    return "";
  }
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Calendar computations
const currentMonthYear = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
  }).format(currentDate.value);
});
const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const days = [];

  // Add days from previous month
  const firstDayOfWeek = firstDay.getDay();
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const date = new Date(year, month, -i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days of current month
  for (let date = new Date(firstDay); date <= lastDay; date.setDate(date.getDate() + 1)) {
    days.push({
      date: new Date(date),
      isCurrentMonth: true,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days from next month
  const remainingDays = 42 - days.length;
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  return days;
});

// Helper functions
const isSameDate = (date1, date2) => {
  return date1.getDate() === date2.getDate() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getFullYear() === date2.getFullYear();
};

const isSelectedDate = (date) => {
  return isSameDate(date, selectedDate.value);
};

const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return `${selectedDate.value.getFullYear()}/${(selectedDate.value.getMonth() + 1).toString().padStart(2, '0')}/${selectedDate.value.getDate().toString().padStart(2, '0')}`;
});

const selectDate = (date) => {
  selectedDate.value = date;
  filters.value.date = formattedDate.value;
  filters.value.doctorName = "";
  getAppointments(null, null, formattedDate.value); // Pass the selected date
};



// Filter by doctor name
const filterByDoctor = (doctorName) => {
  filters.value.doctorName = doctorName;
  getAppointments(null, null, filters.value.date); // Pass the selected date
};

const formattedSelectedDate = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(selectedDate.value);
});



// Format date
const formatDate = (date) => {
  if (date === null) {
    return "N/A";
    
  }
  return new Date(date).toLocaleDateString();
};



function formatTime(time) {
  // Handle undefined or null input
  if (!time) {
    console.error("Invalid time input:", time);
    return "00:00"; // Return a default value or handle the error as needed
  }

  try {
    // Check if the time is in ISO format (e.g., "2023-10-02T10:00:00")
    if (time.includes('T')) {
      const [, timePart] = time.split('T');
      if (timePart.length === 6) return timePart; // Handle cases like "T10:00"
      const [hours, minutes] = timePart.split(':');
      return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
    }

    // Handle plain time strings (e.g., "10:00")
    const [hours, minutes] = time.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
  } catch (error) {
    console.error("Error formatting time:", error);
    return "00:00"; // Return a default value or handle the error as needed
  }
}
// Status options
const statusOptions = ref([
  { value: "0", label: 'Scheduled' },
  { value: "1", label: 'Confirmed' },
  { value: "2", label: 'Canceled' },
  { value: "3", label: 'Pending' },
  { value: "4", label: 'Done' },
]);
// Filter doctors based on selected display mode

const filteredDoctors = (doctors) => {
  if (displayMode.value === 'all') {
    return doctors;
  } else if (displayMode.value === 'holidays') {
    return doctors.filter((doctor) => doctor.isHoliday);
  } else {
    return doctors.filter((doctor) => !doctor.isHoliday);
  }
};
const filteredAppointments = computed(() => {
  return appointments.value.filter(appointment => {
    // Base filtering conditions
    const baseConditions =
      (!filters.value.patientName ||
        `${appointment.patient_first_name} ${appointment.patient_last_name}`
          .toLowerCase()
          .includes(filters.value.patientName.toLowerCase())) &&
      (!filters.value.phone ||
        appointment.phone.includes(filters.value.phone)) &&
      (!filters.value.dateOfBirth ||
        appointment.patient_Date_Of_Birth.includes(filters.value.dateOfBirth)) &&
      (!filters.value.time ||
        appointment.appointment_time.includes(filters.value.time)) &&
      (!filters.value.status ||
        // Convert both values to numbers for comparison
        Number(appointment.status.value) === Number(filters.value.status));
    
    // Additional doctor name filter only if user is not a doctor
    const doctorNameCondition = role.value === 'doctor' ? true :
      (!filters.value.doctorName ||
        appointment.doctor_name.toLowerCase().includes(filters.value.doctorName.toLowerCase()));

    return baseConditions && doctorNameCondition;
  });
});
const generatePdf = async () => {
  try {
    const response = await axios.post('/generate-appointments-pdf', filters.value, {
      responseType: 'blob', // Important for handling binary data
    });
    
    // Create a blob URL for the PDF
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'appointments.pdf'); // Set the file name
    document.body.appendChild(link);
    link.click();

    // Clean up
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error generating PDF:', error);
  }
};
const printTable = () => {
  window.print();
};

// Modified onMounted to ensure role is initialized first
onMounted(async () => {
  await initializeRole(); // Only fetch all appointments if user is not a doctor
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
  initializeRole();
});
</script>
<template>
  <div class="container">
    <div class="card mb-4 shadow-sm rounded-lg">
      <div class="card-header d-flex justify-content-between align-items-center p-3 bg-primary text-white">
        <h4 class="card-title mb-0"></h4>
        <div class="d-flex align-items-center">
          <button @click="previousMonth" class="btn btn-light btn-sm me-2">
            &lt; <!-- Left arrow for previous month -->
          </button>
          <span class="fw-bold mx-3 fs-5">{{ currentMonthYear }}</span>
          <button @click="nextMonth" class="btn btn-light btn-sm ms-2">
            &gt; <!-- Right arrow for next month -->
          </button>
        </div>
      </div>
    <!-- Toggle Buttons -->
  <div class="toggle-buttons mb-2 mt-2">
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'working' }" 
      @click="setDisplayMode('working')">
      Show Working Dates
    </button>
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'holidays' }" 
      @click="setDisplayMode('holidays')">
      Days off
    </button>
   
  </div>

  <!-- Calendar Grid -->
  <div class="calendar-grid">
    <!-- Weekday Headers -->
    <div v-for="day in weekDays" :key="day" class="calendar-header">
      {{ day }}
    </div>
    <!-- Calendar Days -->
    <div v-for="{ date, isCurrentMonth, isToday, doctors } in calendarDays" :key="date.toISOString()"
      class="calendar-day" 
      :class="{
        'current-month': isCurrentMonth,
        'other-month': !isCurrentMonth,
        'today': isToday,
        'selected': isSelectedDate(date)
      }" 
      @click="selectDate(date)">
      <div class="date-number">{{ date.getDate() }}</div>
     
      
      <!-- Display doctors with a vertical stack based on display mode -->
      <div class="doctor-container" v-if="doctors.length > 0">
        <div v-for="doctor in filteredDoctors(doctors)" :key="doctor.name" 
          class="doctor-badge"
          :class="{ 'holiday': doctor.isHoliday }"
          :style="{ borderLeftColor: doctor.color }" 
          @click.stop="filterByDoctor(doctor.isHoliday ? '' : doctor.name.split(':')[0])">
          <span class="doctor-name">{{ doctor.name }}</span>
        </div>
      </div>
    </div>
  </div>
</div>
    

    <div class="selected-date-display mb-3 p-3 bg-light rounded shadow-sm text-center" v-if="selectedDate">
      <h5 class="mb-0">{{ formattedSelectedDate }}</h5>
    </div>

    <div>
      <!-- Error Message -->
      <div v-if="error" class="alert alert-danger my-4">
        {{ error }}
      </div>

      <!-- Filters and Table -->
      <div class="card shadow-sm">
        <div class="mb-2 bg-light d-flex justify-content-between align-items-center ">
          <h5 class="mb-0"></h5>
          <button @click="generatePdf" class="btn btn-primary">
            <i class="fas fa-file-pdf me-2 "></i> Export PDF
          </button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-striped">
              <thead class="table-primary">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">
                    <input v-model="filters.patientName" class="form-control form-control-sm" placeholder="Patient Name" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.phone" class="form-control form-control-sm" placeholder="Phone" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.dateOfBirth" class="form-control form-control-sm" placeholder="Date of Birth" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.date" class="form-control form-control-sm" placeholder="Date" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.time" class="form-control form-control-sm" placeholder="Time" />
                  </th>
                  <th scope="col">
                    <select v-model="filters.status" class="form-select form-select-sm">
                      <option value="">All Statuses</option>
                      <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                  </th>
                  <th scope="col">
                    <input v-model="filters.doctorName" class="form-control form-control-sm" placeholder="Doctor Name" />
                  </th>
                </tr>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Patient Name</th>
                  <th scope="col">Phone</th>
                  <th scope="col">Date Of Birth</th>
                  <th scope="col">Appointment Date</th>
                  <th scope="col">Appointment Time</th>
                  <th scope="col">Status</th>
                  <th scope="col">Doctor Name</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(appointment, index) in filteredAppointments" :key="appointment.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ appointment.patient_first_name }} {{ appointment.patient_last_name }}</td>
                  <td>{{ appointment.phone }}</td>
                  <td>{{ formatDate(appointment.patient_Date_Of_Birth) }}</td>
                  <td>{{ formatDate(appointment.appointment_date) }}</td>
                  <td>{{ formatTime(appointment.appointment_time) }}</td>
                  <td>
                    <span class="badge rounded-pill" :class="`bg-${appointment.status.color}`">
                      {{ appointment.status.name }}
                    </span>
                  </td>
                  <td>{{ appointment.doctor_name }}</td>
                </tr>
                <tr v-if="filteredAppointments.length === 0">
                  <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                      <i class="fas fa-calendar-times fs-4 mb-2"></i>
                      <p>No appointments found.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.toggle-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  justify-content: center;
}

.toggle-btn {
  padding: 8px 16px;
  border: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  background-color: #e9ecef;
}

.toggle-btn.active {
  background-color: #0d6efd;
  color: white;
  border-color: #0d6efd;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: #f0f2f5;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}

.calendar-header {
  background-color: #e9ecef;
  padding: 1rem;
  text-align: center;
  font-weight: bold;
  border-bottom: 1px solid #dee2e6;
}

.calendar-day {
  background-color: white;
  min-height: 200px;
  padding: 1rem;
  position: relative;
  cursor: pointer;
  border: 1px solid #f0f2f5;
  transition: all 0.2s ease;
}

.calendar-day:hover {
  background-color: #f8f9fa;
  transform: scale(0.98);
}

.date-number {
  position: absolute;
  top: 5px;
  right: 5px;
  font-weight: bold;
  background-color: #f8f9fa;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.other-month {
  color: #adb5bd;
  background-color: #f8f9fa;
}

.today {
  background-color: #e3f2fd;
}

.today .date-number {
  background-color: #0d6efd;
  color: white;
}

.selected {
  background-color: #e7f5ff;
  border: 2px solid #0d6efd;
}

.selected .date-number {
  background-color: #0d6efd;
  color: white;
  font-weight: bold;
}

.appointment-indicator {
  position: absolute;
  bottom: 5px;
  right: 5px;
  background-color: #0d6efd;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Doctor container and badges */
.doctor-container {
  position: absolute;
  top: 35px;
  left: 0;
  right: 30px;
  bottom: 30px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 5px;
}

.doctor-badge {
  font-size: 13px;
  padding: 3px 6px;
  border-radius: 4px;
  background-color: #f8f9fa;
  border-left: 3px solid;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  cursor: pointer;
  transition: background-color 0.2s;
}

.doctor-badge:hover {
  background-color: #e9ecef;
}

.doctor-badge.holiday {
  opacity: 0.7;
}

.doctor-name {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 768px) {
  .toggle-buttons {
    flex-wrap: wrap;
  }
  
  .toggle-btn {
    font-size: 12px;
    padding: 6px 12px;
  }
  
  .calendar-day {
    min-height: 100px;
    padding: 0.5rem;
  }
  
  .doctor-badge {
    font-size: 9px;
    padding: 2px 4px;
  }
  
  .doctor-container {
    top: 30px;
    right: 25px;
    bottom: 25px;
  }
}
</style>