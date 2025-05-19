<script setup>


// Logout Function
const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login'; // Redirect to login page after logout
    } catch (error) {
        console.error('Error logging out:', error);
    }
};
</script>
<template>

    <nav class="main-header navbar navbar-expand navbar-dark navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">15</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">15 Notifications</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-envelope mr-2"></i> 4 new messages
                        <span class="float-right text-muted text-sm">3 mins</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-users mr-2"></i> 8 friend requests
                        <span class="float-right text-muted text-sm">12 hours</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-file mr-2"></i> 3 new reports
                        <span class="float-right text-muted text-sm">2 days</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>
            <!-- User Dropdown Menu -->
            <!-- User Dropdown Menu -->
             
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="dropdown-divider"></div>
                    <router-link to="/admin/settings" active-class="active" class="nav-link d-flex">
                        <i class="fas fa-cog mr-2 mt-1"></i>
                        <p>Settings</p>
                    </router-link>
                    <div class="dropdown-divider"></div>
                    <form style="display: contents;">
                        <a href="#" @click.prevent="logout" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                    </form>
                </div>
            </li>
           
        </ul>
    </nav>

</template>