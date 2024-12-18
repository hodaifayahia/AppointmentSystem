import Dashboard from "./Components/Dashboard.vue";
import ListAppointment from "./Pages/Appointments/ListAppointment.vue";
import ListUsers from "./Pages/Users/ListUsers.vue";
import ListDoctors from "./Pages/Users/ListDoctors.vue";
import Profile from "./Pages/Profile/Profile.vue";
import settings from "./Pages/Setting/settings.vue";

export default [
    {
        path: '/admin/dashboard',  // Corrected path
        name: 'admin.dashboard',
        component: Dashboard,
    },
    {
        path: '/admin/appointments',  // Corrected path
        name: 'admin.appointments',
        component: ListAppointment,
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
        path: '/admin/settings',  // Corrected path (lowercase "s")
        name: 'admin.settings',
        component: settings,
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
