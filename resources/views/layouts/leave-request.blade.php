<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Self-Service Leave Portal</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Fira Code -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flatpickr Date Picker CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amber: {
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
                        sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['Fira Code', 'monospace'],
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            if (!('theme' in localStorage)) {
                localStorage.theme = 'light';
            }
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        summary::-webkit-details-marker,
        summary::marker {
            display: none !important;
            content: "";
        }
        /* Custom Flatpickr Amber Theme Styling */
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        .flatpickr-day.inRange {
            background: #fef3c7 !important;
            border-color: #fef3c7 !important;
            box-shadow: -5px 0 0 #fef3c7, 5px 0 0 #fef3c7 !important;
            color: #78350f !important;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased min-h-screen py-8 sm:py-12 px-4 relative overflow-x-hidden selection:bg-amber-500 selection:text-slate-950 transition-colors duration-200">
    <!-- Ambient Background Lighting -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-amber-500/10 blur-[120px] rounded-full"></div>
        <div class="absolute top-1/2 left-10 w-72 h-72 bg-sky-500/5 blur-[100px] rounded-full"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-30"></div>
    </div>

    <div class="max-w-2xl mx-auto relative z-10">
        <!-- Floating Theme Toggle Header (Icon Only) -->
        <div class="flex items-center justify-end mb-4">
            <button onclick="toggleTheme()" type="button" aria-label="Toggle Theme" title="Toggle Theme" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-500 dark:hover:text-amber-400 shadow-xs transition cursor-pointer flex items-center justify-center">
                <svg class="w-5 h-5 hidden dark:block text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-5 h-5 block dark:hidden text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
        </div>

        <!-- Main Card Container -->
        <div class="bg-white dark:bg-slate-900/90 backdrop-blur-2xl rounded-3xl shadow-xl dark:shadow-2xl dark:shadow-slate-950/80 border border-slate-200 dark:border-slate-800 p-6 sm:p-9">
            @yield('content')
        </div>

        <div class="text-center mt-8 text-xs text-slate-500 font-medium flex items-center justify-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Employee Self-Service Leave Portal &copy; {{ date('Y') }} ERP Komi</span>
        </div>
    </div>
</body>
</html>


