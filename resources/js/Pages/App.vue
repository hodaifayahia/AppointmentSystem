<template>
    <div v-if="!isLoading" class="wrapper" id="app">
        <Navbar />
        <Sidebar :user="authStore.user" />
        <div class="content-wrapper">
            <router-view></router-view>
        </div>
        <Footer />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import Navbar from './Main/Navbar.vue';
import Sidebar from './Main/Sidebar.vue';
import Footer from './Main/Footer.vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

const authStore = useAuthStore();
const { user, isLoading } = storeToRefs(authStore);

onMounted(async () => {
    await authStore.getUser();
});
</script>

<style>
.wrapper {
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

.content-wrapper {
    min-height: calc(100vh - 4rem); /* Adjust for footer height */
    padding-bottom: 4rem; /* Ensure content doesn't get hidden behind footer */
    margin-left: 250px; /* Matches sidebar width */
    padding-top: 1rem;
    padding-right: 1rem;
    padding-left: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}

/* When sidebar is collapsed */
body.sidebar-collapse .content-wrapper {
    margin-left: 4.6rem;
}
</style>