<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leave Request — ERP Komi</title>
    <!-- Google Fonts: Inter (Filament Default Font) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        summary::-webkit-details-marker,
        summary::marker {
            display: none !important;
            content: "";
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen py-8 sm:py-12 px-4">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 sm:p-8">
            @yield('content')
        </div>

        <div class="text-center mt-6 text-xs text-gray-400 font-medium">
            &copy; {{ date('Y') }} HR Self-Service System
        </div>
    </div>
</body>
</html>
