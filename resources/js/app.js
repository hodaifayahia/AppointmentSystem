import './bootstrap';

import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import Routes from '../js/Routes';
import Login from '../js/auth/Login.vue';
import App from '../js/Pages/App.vue';


// ✅ Initialize Pinia first
const pinia = createPinia();

// ✅ Create app instance
const app = createApp(App);
app.use(pinia); // Register Pinia before using the store


const router = createRouter({
    history: createWebHistory(),
    routes: Routes,
});

app.use(router);

if (window.location.pathname === '/login') {
    const currentApp = createApp(Login);
    currentApp.mount('#login');
} else {
    app.mount('#app');
}


// app.mount('#app');
