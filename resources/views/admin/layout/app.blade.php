
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AdminLTE 3 | Starter</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hold-transition sidebar-mini text-sm">
  <div class="wrapper" id="app">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
          <!-- Left navbar links -->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="index3.html" class="nav-link">Home</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="#" class="nav-link">Contact</a>
            </li>
          </ul>
    
          <!-- Right navbar links -->
          <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
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
            <li class="nav-item dropdown">
              <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="#" class="dropdown-item">
                  <i class="fas fa-user-circle mr-2"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </div>
            </li>
          </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
          <!-- Brand Logo -->
          <a href="index3.html" class="brand-link">
            <span class="brand-text font-weight-light">Appointment System</span>
          </a>

          <!-- Sidebar -->
          <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
              <div class="image">
              </div>
              <div class="info">
                <a href="#" class="d-block text-center">{{ Auth::user()->name }} </a>
                <img src="{{Auth::user()->avatar }}" alt="">
              </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
              <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                @if(Auth::user()->role === 'receptionist' || Auth::user()->role === 'admin')

                <li class="nav-item">
                  <router-link to="/admin/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                  </router-link>
                </li>
            @endif
                <!-- Admin-specific links -->
                @if(Auth::user()->role === 'admin')
                  <li class="nav-item">
                    <router-link to="/admin/users" active-class="active" class="nav-link">
                      <i class="nav-icon fas fa-users"></i>
                      <p>Admins</p>
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
                 
                @endif
            
                <!-- Receptionist-specific links -->
                @if(Auth::user()->role === 'receptionist' || Auth::user()->role === 'admin')
                <li class="nav-item">
                  <router-link to="/admin/docters" active-class="active" class="nav-link">
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
                    <router-link to="/admin/Waitlist" active-class="active" class="nav-link">
                      <i class="nav-icon fas fa-clock"></i>
                      <p>Waitlist</p>
                    </router-link>
                  </li>
                @endif
            
                <!-- Doctor-specific links -->
                @if(Auth::user()->role === 'doctor')
                  <li class="nav-item">
                    <router-link to="/doctor/appointments" active-class="active" class="nav-link">
                      <i class="nav-icon fas fa-calendar-alt"></i>
                      <p>Appointment</p>
                    </router-link>
                  </li>

                  <li class="nav-item">
                    <router-link to="/doctor/excludeDates" active-class="active" class="nav-link">
                      <i class="nav-icon fas fa-calendar-times"></i>
                      <p>Day Offs</p>
                    </router-link>
                  </li>
                @endif
            
                <!-- Settings and Logout (Common for all roles) -->
                <li class="nav-item">
                  <router-link to="/admin/settings" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                  </router-link>
                </li>
                <li class="nav-item mr-2">
                  <form action="{{ route('logout') }}" method="POST" style="display: contents;">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); this.closest('form').submit();" class="nav-link" style="padding: 2; text-decoration: none;">
                      <i class="nav-icon fas fa-sign-out-alt" style="margin-right: 5px;"></i>
                      <p style="display: inline; margin: 0;">Logout</p>
                    </a>
                  </form>
                </li>
              </ul>
            </nav>
            <!-- /.sidebar-menu -->
          </div>
          <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
          <router-view>
            
          </router-view>
        </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.querySelector('body');
    const sidebarToggle = document.querySelector('[data-widget="pushmenu"]');

    // Load saved state on page load
    const savedState = localStorage.getItem('sidebarState');
    
    if (savedState === 'collapsed') {
        body.classList.add('sidebar-collapse');
    }

    // Handle sidebar toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle the class
            body.classList.toggle('sidebar-collapse');
            
            // Save the new state
            const isCollapsed = body.classList.contains('sidebar-collapse');
            localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
            
        });
    }
});
</script>
</body>


</html>
