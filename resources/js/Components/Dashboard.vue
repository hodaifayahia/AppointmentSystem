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

const filters = ref({
  patientName: '',
  phone: '',
  dateOfBirth: '',
  time: '',
  status: '',
  doctorName: '',
});

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Fetch appointments
const getAppointments = async (status = null, filter = null, date = null) => {
  try {
    loading.value = true;
    error.value = null;

    currentFilter.value = status || 'ALL';

    const params = {
      status: status === 'ALL' ? null : status,
      filter: filter,
      date: date || filters.value.date, // Apply the selected date
      doctorName: filters.value.doctorName, // Apply the selected doctor name
    };

    const response = await axios.get(`/api/appointments`, { params });
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

    const response = await axios.get('/api/doctors/WorkingDates', {
      params: { month },
    });

    if (response.data) {
      // Assign a unique color to each doctor
      const colors = ['#FF6B6B', '#4ECDC4', '#FFD166', '#06D6A0', '#118AB2', '#073B4C', '#EF476F'];
      doctors.value = response.data.data.map((doctor, index) => ({
        ...doctor,
        working_dates: doctor.working_dates.map(date => formatLocalDate(new Date(date))),
        color: colors[index % colors.length], // Assign a color from the palette
      }));
    } else {
      throw new Error('Failed to fetch doctors');
    }
  } catch (err) {
    console.error('Error fetching doctors:', err);
    error.value = err.message || 'Failed to load doctors';
  } finally {
    loading.value = false;
  }
};
const hasAppointments = (date) => {
  const dateStr = formatLocalDate(date);
  return appointments.value.some(apt => {
    const aptDate = formatLocalDate(new Date(apt.date));
    return aptDate === dateStr;
  });
};

const getAppointmentCount = (date) => {
  const dateStr = formatLocalDate(date);
  return appointments.value.filter(apt => {
    const aptDate = formatLocalDate(new Date(apt.date));
    return aptDate === dateStr;
  }).length;
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
const getDoctorsWorkingOnDate = (date) => {
  const dateStr = formatLocalDate(date);
  console.log('Checking date:', dateStr);

  // Filter doctors whose working_dates include the formatted date
  const workingDoctors = doctors.value
    .filter(doctor => {
      return doctor.working_dates.some(workingDate => {
        const formattedWorkingDate = formatLocalDate(new Date(workingDate));
        return formattedWorkingDate === dateStr;
      });
    })
    .map(doctor => ({
      name: doctor.name,
      color: doctor.color,
    }));

  console.log('Working Doctors on', dateStr, ':', workingDoctors);
  return workingDoctors;
};
// Formatted selected date
const formattedSelectedDate = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(selectedDate.value);
});


// Clear all filters
const clearFilters = () => {
  filters.value = {
    patientName: '',
    phone: '',
    dateOfBirth: '',
    time: '',
    date: '',
    status: '',
    doctorName: '',
  };
};

// Format date
const formatDate = (date) => {
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
  { value: 0, label: 'Scheduled' },
  { value: 1, label: 'Confirmed' },
  { value: 2, label: 'Canceled' },
  { value: 3, label: 'Pending' },
  { value: 4, label: 'Done' },
]);

// Filtered appointments
const filteredAppointments = computed(() => {
  return appointments.value.filter(appointment => {
    return (
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
        appointment.status.value === filters.value.status) &&
      (!filters.value.doctorName ||
        appointment.doctor_name.toLowerCase().includes(filters.value.doctorName.toLowerCase()))
    );
  });
});

// Fetch data on mount
onMounted(() => {
  getAppointments();
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
});
</script>
<template>
  <div class="container">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center p-3">
        <h4 class="card-title mb-0">Appointment Calendar</h4>
        <div class="d-flex align-items-center">
          <button @click="previousMonth" class="btn btn-outline-primary btn-sm me-2">
            &lt; <!-- Left arrow for previous month -->
          </button>
          <span class="fw-bold mx-2">{{ currentMonthYear }}</span>
          <button @click="nextMonth" class="btn btn-outline-primary btn-sm ms-2">
            &gt; <!-- Right arrow for next month -->
          </button>
        </div>
      </div>
      <div class="">
        <!-- Calendar Grid -->
        <div class="calendar-grid">
          <!-- Weekday Headers -->
          <div v-for="day in weekDays" :key="day" class="calendar-header">
            {{ day }}
          </div>
          <!-- Calendar Days -->
          <div
            v-for="{ date, isCurrentMonth, isToday, doctors } in calendarDays"
            :key="date.toISOString()"
            class="calendar-day"
            :class="{
              'current-month': isCurrentMonth,
              'other-month': !isCurrentMonth,
              'today': isToday,
              'selected': isSelectedDate(date)
            }"
            @click="selectDate(date)"
          >
            <span class="date-number">{{ date.getDate() }}</span>
            <div class="appointment-indicator" v-if="hasAppointments(date)">
              {{ getAppointmentCount(date) }}
            </div>
            <!-- Display doctors working on this day -->
            <div class="doctor-names" v-if="doctors.length > 0">
  <div
    v-for="doctor in doctors"
    :key="doctor.name"
    class="doctor-name"
    :style="{ backgroundColor: doctor.color }"
    @click.stop="filterByDoctor(doctor.name)"
  >
    {{ doctor.name }}
  </div>
</div>
          </div>
        </div>
      </div>
    </div>

    <div>

      <!-- Error Message -->
      <div v-if="error" class="alert alert-danger my-4">
        {{ error }}
      </div>

      <!-- Filters and Table -->
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered ">
              <thead class="table-light">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">
                    <input v-model="filters.patientName" class="form-control" placeholder="Filter by Patient Name" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.phone" class="form-control" placeholder="Filter by Phone" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.dateOfBirth" class="form-control" placeholder="Filter by Date of Birth" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.date" class="form-control" placeholder="Filter by date" />
                  </th>
                  <th scope="col">
                    <input v-model="filters.time" class="form-control" placeholder="Filter by Time" />
                  </th>
                  <th scope="col">
                    <select v-model="filters.status" class="form-control">
                      <option value="">All Statuses</option>
                      <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                  </th>
                  <th scope="col">
                    <input v-model="filters.doctorName" class="form-control" placeholder="Filter by Doctor Name" />
                  </th>
                </tr>
                <tr class="table-primary">
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
                    <span class="status-indicator" :class="appointment.status.color"></span>
                    <span :class="`text-${appointment.status.color}`">
                      <i :class="[`text-${appointment.status.color}`, appointment.status.icon]" class="fa-lg ml-1"></i>
                      {{ appointment.status.name }}
                    </span>
                  </td>
                  <td>{{ appointment.doctor_name }}</td>
                </tr>
                <tr v-if="filteredAppointments.length === 0">
                  <td colspan="8" class="text-center text-muted">No appointments found.</td>
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
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: #dee2e6;
  border: 1px solid #dee2e6;
}

.calendar-header {
  background-color: #f8f9fa;
  padding: 1rem;
  text-align: center;
  font-weight: bold;
}

.calendar-day {
  background-color: white;
  min-height: 120px;
  padding: 0.6rem;
  position: relative;
  cursor: pointer;
}

.calendar-day:hover {
  background-color: #f8f9fa;
}

.date-number {
  position: absolute;
  top: 5px;
  right: 5px;
  font-weight: bold;
}

.other-month {
  color: #6c757d;
  background-color: #f8f9fa;
}

.today {
  background-color: #e3f2fd;
}

.selected {
  background-color: #cfe2ff;
  border: 2px solid #0d6efd;
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
}

.doctor-names {
  position: absolute;
  bottom: 10px;
  left: 5px;
  right: 5px;
  font-size: 0.7rem;
  color: #333;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.doctor-names {
  position: absolute;
  bottom: 10px;
  left: 5px;
  right: 5px;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.doctor-name {
  font-size: 13px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 12px;
  color: white;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.doctor-name:hover {
  opacity: 0.8;
}
</style>