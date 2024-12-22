<script setup>
import axios from 'axios';
import { reactive, onMounted, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '@/Components/toster';
import { Form } from 'vee-validate';
import flatpickr from "flatpickr";
import 'flatpickr/dist/themes/light.css';

const router = useRouter();
const route = useRoute();
const toastr = useToastr();


const form = reactive({
    first_name: '',
    last_name: '',
    description: '',
    phone: '',
    doctor_id: 0,
    start_time: '',
    appointment_time: '',
    status: 0
});



const doctors = ref([]);
const selectedDoctor = ref(null);
const availableSlots = ref([]);

const getDoctors = async (page = 1) => {
    try {
        const response = await axios.get(`/api/doctors?page=${page}`);
        doctors.value = response.data.data; // Immediately update the list
    } catch (error) {
        toaster.error('Failed to fetch doctors');
        console.error('Error fetching doctors:', error);
    }
};


const handleSubmit = (values, actions) => {
    if (editMode.value) {
        editAppointment(values, actions);
    } else {
        createAppointment(values, actions);
    }
};

const createAppointment = (values, actions) => {
    axios.post('/api/appointment', form)
        .then(() => {
            router.push('/admin/appointments');
            toastr.success('Appointment created successfully!');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        });
};

const editAppointment = (values, actions) => {
    axios.put(`/api/appointments/${route.params.id}/edit`, form)
        .then(() => {
            router.push('/admin/appointments');
            toastr.success('Appointment updated successfully!');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        });
};
const selectSlot = (time) => {
    form.appointment_time = time;
};
const Patient = ref([]);
const getPatient = () => {
    axios.get('/api/Patient')
        .then((response) => {
            Patient.value = response.data;
        });
};

// const getAppointment = () => {
//     axios.get(`/api/appointments/${route.params.id}/edit`)
//         .then(({ data }) => {
//             form.client_id = data.client_id;
//             form.start_time = data.formatted_start_time;
//             form.end_time = data.formatted_end_time;
//             form.description = data.description;
//             form.patient_first_name = data.patient_first_name;
//             form.patient_last_name = data.patient_last_name;
//             form.phone = data.phone;
//             form.doctor_name = data.doctor_name;
//             form.appointment_date = data.appointment_date;
//             form.appointment_time = data.appointment_time;
//             form.status = data.status;
//         });
// };

// New function to calculate slots based on date and doctor's schedule
const calculateSlots = (doctor, date) => {
    if (!doctor || !doctor.start_time || !doctor.end_time || !date) return [];

    const slots = [];
    const startTime = new Date(`${date}T${doctor.start_time}`);
    const endTime = new Date(`${date}T${doctor.end_time}`);

    if (doctor.time_slots) {
        // If 'time_slots' is provided, use it as the duration between slots
        const slotDuration = parseInt(doctor.time_slots);
        let currentTime = startTime;
        while (currentTime < endTime) {
            slots.push({
                time: currentTime.toTimeString().slice(0, 5),
                available: true
            });
            currentTime = new Date(currentTime.getTime() + slotDuration * 60000); // Add minutes
        }
    } else if (doctor.number_of_patients_per_day) {
        // If 'number_of_patients_per_day' is provided, calculate slots based on this
        const totalMinutes = (endTime - startTime) / 60000; // Convert to minutes
        const slotDuration = Math.floor(totalMinutes / doctor.number_of_patients_per_day);

        let currentTime = startTime;
        for (let i = 0; i < doctor.number_of_patients_per_day; i++) {
            slots.push({
                time: currentTime.toTimeString().slice(0, 5),
                available: true
            });
            currentTime = new Date(currentTime.getTime() + slotDuration * 60000); // Add minutes
        }
    }

    return slots;
};
// Watch for changes in both doctor_id and start_date
watch([() => form.doctor_id, () => form.start_date], ([newDoctorId, newDate]) => {
    const doctor = doctors.value.find(d => d.id === newDoctorId);
    if (doctor && newDate) {
        selectedDoctor.value = doctor;
        availableSlots.value = calculateSlots(doctor, newDate);
    } else {
        selectedDoctor.value = null;
        availableSlots.value = [];
    }
});

const editMode = ref(false);

onMounted(() => {
    if (route.name === 'admin.appointments.edit') {
        editMode.value = true;
        getAppointment();
    }

    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d", // Only date, no time
    });

    getPatient();
    getDoctors();
});

</script>
<template>
    <div class="appointment-page">
        <div class="content-header text-black">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">
                            <span v-if="editMode">Edit</span>
                            <span v-else>Create</span>
                            Appointment
                        </h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <ol class="breadcrumb float-sm-right breadcrumb-dark">
                            <li class="breadcrumb-item"><router-link to="/admin/dashboard"
                                    class="text-white">Home</router-link></li>
                            <li class="breadcrumb-item"><router-link to="/admin/appointments"
                                    class="text-white">Appointments</router-link></li>
                            <li class="breadcrumb-item active">
                                <span v-if="editMode">Edit</span>
                                <span v-else>Create</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="card ">
                            <div class="card-body">
                                <Form @submit="handleSubmit" v-slot:default="{ errors }">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label for="patient-first-name" class="text-muted">Patient First
                                                    Name</label>
                                                <input v-model="form.first_name" type="text"
                                                    class="form-control form-control-sm rounded-pill"
                                                    :class="{ 'is-invalid': errors.patient_first_name }"
                                                    id="patient-first-name" placeholder="Enter Patient First Name">
                                                <span class="invalid-feedback">{{ errors.patient_first_name }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label for="patient-last-name" class="text-muted">Patient Last
                                                    Name</label>
                                                <input v-model="form.last_name" type="text"
                                                    class="form-control form-control-sm rounded-pill"
                                                    :class="{ 'is-invalid': errors.patient_last_name }"
                                                    id="patient-last-name" placeholder="Enter Patient Last Name">
                                                <span class="invalid-feedback">{{ errors.patient_last_name }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label for="phone" class="text-muted">Phone</label>
                                                <input v-model="form.phone" type="text"
                                                    class="form-control form-control-sm rounded-pill"
                                                    :class="{ 'is-invalid': errors.phone }" id="phone"
                                                    placeholder="Enter Phone">
                                                <span class="invalid-feedback">{{ errors.phone }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">

                                            <div class="form-group mb-4">
                                                <label for="doctor" class="text-muted">Doctor</label>
                                                <select id="doctor" v-model="form.doctor_id"
                                                    class="form-control form-select-sm rounded-pill"
                                                    :class="{ 'is-invalid': errors.doctor_id }">
                                                    <option value="">Select Doctor</option>
                                                    <option v-for="doctor in doctors" :key="doctor.id"
                                                        :value="doctor.id">
                                                        {{ doctor.name }} - {{ doctor.specialization }}

                                                    </option>
                                                </select>
                                                <span class="invalid-feedback">{{ errors.doctor_id }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label for="status" class="text-muted ">Status</label>
                                                <select v-model="form.status" id="status"
                                                    class="form-control form-select-sm rounded-pill"
                                                    :class="{ 'is-invalid': errors.status }">
                                                    <option :value=-1>Select Status</option>
                                                    <option :value=0>SCHEDULED</option>
                                                    <option :value=1>Confirmed</option>
                                                    <option :value=2>Cancelled</option>
                                                    <option :value=3>Pending</option>
                                                </select>
                                                {{ form.status }}
                                                <span class="invalid-feedback">{{ errors.status }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label for="start-date" class="text-muted">Date</label>
                                                <input v-model="form.start_date" type="text"
                                                    class="form-control form-control-sm flatpickr rounded-pill"
                                                    :class="{ 'is-invalid': errors.start_date }" id="start-date"
                                                    placeholder="Select Date">
                                                <span class="invalid-feedback">{{ errors.start_date }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" v-if="selectedDoctor && form.start_date">
                                        <div class="col-md-12">
                                            <div class="form-group mb-4">
                                                <label class="text-muted">Available Time Slots</label>
                                                <div class="slot-container">
                                                    <div v-if="availableSlots.length > 0">
                                                        <span class="badge bg-primary mb-2">Number of slots: {{
                                                            availableSlots.length }}</span>
                                                        <div class="d-flex flex-wrap" style="gap: 10px;">
                                                            <button type="button" v-for="slot in availableSlots"
                                                                :key="slot.time"
                                                                class="btn btn-outline-primary btn-sm slot-btn" :class="{
                                                                    'btn-primary': form.appointment_time === slot.time
                                                                }" @click="selectSlot(slot.time)">
                                                                {{ slot.time }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-muted">No slots available for this date and
                                                        doctor.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="description" class="text-muted">Description</label>
                                        <textarea v-model="form.description"
                                            class="form-control form-control-lg rounded"
                                            :class="{ 'is-invalid': errors.description }" id="description" rows="3"
                                            placeholder="Enter Description"></textarea>
                                        <span class="invalid-feedback">{{ errors.description }}</span>
                                    </div>

                                    <button type="submit" class=" btn btn-md  btn-primary  rounded-pill">Submit</button>
                                </Form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.appointment-page {
    background-color: #f8f9fa;
}

.content-header {
    border-radius: 0 0 1rem 1rem;
}

.breadcrumb-dark {
    background: transparent;
}

.card {
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0;
}

.form-group label {
    font-weight: 500;
}

.slot-container {
    max-height: 200px;
    overflow-y: auto;
}

.slot-btn {
    width: 100px;
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .slot-btn {
        width: 100%;
    }
}
</style>