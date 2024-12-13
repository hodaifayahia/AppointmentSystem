import './bootstrap';

import "admin-lte/plugins/jquery/jquery.min.js";
import "admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js";
import "admin-lte/dist/js/adminlte.min.js";

import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createRouter, createWebHistory } from 'vue-router';
import Router from './Routes.js';  // Ensure the correct path for Routes.js

const app = createApp({});

const router = createRouter({
    history: createWebHistory(), // Call createWebHistory here
    routes: Router,              // Use the Router imported from Routes.js
});

app.use(router);
app.mount('#app');
