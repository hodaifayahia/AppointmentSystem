
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AdminLTE 3 | Starter</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="icon" type="image/png" href="{{ asset('login.png') }}">
</head>
<body class="hold-transition sidebar-mini text-sm">
  <div  id="app">
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
