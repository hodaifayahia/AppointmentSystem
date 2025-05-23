<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const props = defineProps({
    user: {
        type: Object,
        required: true,
        default: () => ({})  // Add a default value
    }
})
// Logout Function
const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/dashboard';
    } catch (error) {
        console.error('Error logging out:', error);
    }
};
</script>
<template>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="#" class="brand-link d-flex align-items-center">
            <img :src="'/storage/doctor.png'" class="img-circle elevation-2 me-2 mr-3"
                style="width: 40px; height: 40px;" alt="User Image">

            <span class="brand-text font-weight-light ms-2">Clinic </span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar ">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex justify-content-between">
                <div class="image">
                    <img :src="user.avatar" class="img-circle elevation-2 mr-2" alt="User Image"
                        style="height: 40px; width: 40px; object-fit: cover;">
                </div>
                <div class="info ">
                    <a href="#" class="d-block text-center mr-5 mt-1">{{ user.name }}</a>
                </div>
            </div>


            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Dashboard -->

                    <li class="nav-item">
                        <router-link to="/dashboard" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Dashboard</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/calander" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Calendar</p>
                        </router-link>
                    </li>
                    <!-- Admin-specific links -->
                    <template v-if="user.role === 'admin' || user.role === 'SuperAdmin'">
                        <li class="nav-item">
                            <router-link to="/admin/users" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/specializations" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-briefcase"></i>
                                <p>Specializations</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/admin/excludeDates" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-times"></i>
                                <p>Exclude</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/doctor/forceAppointment" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Manage Forced Appointments</p>
                            </router-link>
                        </li>
                    </template>

                    <!-- Receptionist-specific links -->
                    <!-- @if(Auth::user()->role === 'receptionist' || Auth::user()->role === 'admin') -->
                    <template
                        v-if="user.role === 'admin' || user.role === 'receptionist' || user.role === 'SuperAdmin'">
                        <li class="nav-item">
                            <router-link to="/admin/doctors" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Doctors</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointments/specialization" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Appointment</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/patient" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-injured"></i>
                                <p>Patient</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/admin/pending" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>PendingList</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/admin/Waitlist" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-clock"></i>
                                <p>Waitlist</p>
                            </router-link>
                        </li>
                    </template>

                    <!-- Doctor-specific links -->
                    <!-- @if(Auth::user()->role === 'doctor') -->
                    <template v-if="user.role === 'doctor'">
                        <li class="nav-item">
                            <router-link to="/doctor/appointments" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>Appointments</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/doctor/excludeDates" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-ban"></i>
                                <p>Day Offs</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/doctor/avilability" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-clock"></i>
                                <p>Availability</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/admin/doctors/schedule" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Schedule</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/doctor/users" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Manage Forced Appointments</p>
                            </router-link>
                        </li>

                    </template>
                    <li class="nav-item menu-is-opening menu-open">
                        <a href="#" class="nav-link active">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>
                                Consultation Parameters
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview" style="display: block;">
                            <li class="nav-item">
                                <router-link to="/admin/consultations/placeholders" active-class="active" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sections</p>
                                </router-link>
                            </li>
                            <!-- <li class="nav-item">
                                <router-link to="/admin/consultations/attributes" active-class="active" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Attributes</p>
                                </router-link>
                            </li> -->
                            <li class="nav-item">
                                <router-link to="/admin/consultations/template" active-class="active" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Template</p>
                                </router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/consultations/consulation" active-class="active" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Consultation</p>
                                </router-link>
                            </li>
                        </ul>
                    </li>


                    <!-- @endif -->

                    <!-- Settings and Logout (Common for all roles) -->
                    <li class="nav-item">
                        <router-link to="/admin/settings" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Settings</p>
                        </router-link>
                    </li>
                    <li class="nav-item mr-2">
                        <form style="display: contents;">
                            <a href="#" @click.prevent="logout" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </form>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

</template>