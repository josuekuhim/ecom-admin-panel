<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Painel Administrativo</title>

    <!-- Fonts - Inter for modern UI -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/css/admin-theme.css', 'resources/js/app.js'])
    
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body data-theme="dark">
    <div class="d-flex">
        @auth
            @include('partials.admin_sidebar')
        @endauth

        <main class="w-100 p-4" style="margin-left: 280px; min-height: 100vh;">
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')
    
    {{-- Global Admin Scripts --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load notification counts
        function loadNotificationCounts() {
            fetch('{{ route("admin.notifications.counts") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notification-badge');
                    if (data.total > 0) {
                        badge.textContent = data.total;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.log('Error loading notifications:', error));
        }

    // Load notifications on page load
        loadNotificationCounts();
        
        // Refresh notifications every 30 seconds
        setInterval(loadNotificationCounts, 30000);
    });
    </script>
</body>
</html>
