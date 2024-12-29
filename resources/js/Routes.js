import Dashboard from "./Components/Dashboard.vue";
import ListAppointment from "./Pages/Appointments/ListAppointment.vue";
import DoctorListSchedule from "./Pages/Users/DoctorListSchedule.vue";
import ListUsers from "./Pages/Users/ListUsers.vue";
import ListDoctors from "./Pages/Users/ListDoctors.vue";
import PatientList from "./Pages/Patient/PatientList.vue";
import Profile from "./Pages/Profile/Profile.vue";
import specializationList from "./Pages/Specialization/specializationList.vue";
import settings from "./Pages/Setting/settings.vue";
import DoctorListAppointment from "./Pages/Appointments/DoctorListAppointment.vue";
import SpecializationListAppointment from "./Pages/Appointments/SpecializationListAppointment.vue";
import AppointmentPage from "./Pages/Appointments/AppointmentPage.vue";

export default [
    {
        path: '/admin/dashboard',  // Corrected path
        name: 'admin.dashboard',
        component: Dashboard,
    },
    {
        path: '/admin/appointments/specialization',  // Corrected path
        name: 'admin.appointments.specialization',
        component: SpecializationListAppointment,
    },
    {
        path: '/admin/appointments/doctors/:id',  // Corrected path
        name: 'admin.appointments.doctors',
        component: DoctorListAppointment,
    },
    {
        path: '/admin/appointments/:id',  // Corrected path
        name: 'admin.appointments',
        component: ListAppointment,
    },
    {
        path: '/admin/appointments/create/:id',  // Corrected path
        name: 'admin.appointments.create',
        component: AppointmentPage,
    },
    {
        path: '/admin/patient',  // Corrected path
        name: 'admin.patient',
        component: PatientList,
    },
    {
        path: '/admin/users',  // Corrected path
        name: 'admin.users',
        component: ListUsers,
    },
    {
        path: '/admin/docters',  // Corrected path
        name: 'admin.docters',
        component: ListDoctors,
    },
    {
        path: '/admin/docters/schedule/:id',  // Corrected path
        name: 'admin.docters.schedule',
        component: DoctorListSchedule,
    },
    {
        path: '/admin/settings',  // Corrected path (lowercase "s")
        name: 'admin.settings',
        component: settings,
    },
    {
        path: '/admin/specializations',  // Corrected path (lowercase "s")
        name: 'admin.specialization',
        component: specializationList,
    },
    {
        path: '/admin/profile',  // Corrected path
        name: 'admin.profile',
        component: Profile,
    },
    // If you want a logout route:
    // {
    //     path: '/admin/logout',
    //     name: 'admin.logout',
    //     component: Logout, // Implement the logout component or method
    // },
];
