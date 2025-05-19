<template>
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
        <strong>
            Copyright &copy; {{ currentYear }}
            <a href="https://yourwebsite.com">Clinic </a>.
        </strong>
        All rights reserved.
    </footer>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const currentYear = new Date().getFullYear();
</script>

<style scoped>
.main-footer {
    background-color: #fff;
    border-top: 1px solid #dee2e6;
    color: #869099;
    padding: 1rem;
    bottom: 0;
    left: 10px; /* Matches sidebar width */
    right: 0;
    z-index: 1000;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-footer {
        left: 0;
    }
}

/* When sidebar is collapsed */
body.sidebar-collapse .main-footer {
    left: 4.6rem;
}

.float-right {
    float: right !important;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    color: #0056b3;
    text-decoration: underline;
}
</style>