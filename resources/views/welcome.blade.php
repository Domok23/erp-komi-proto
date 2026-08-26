<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ERP Komi') }} - Enterprise Management Portal</title>

        <!-- Google Fonts: Plus Jakarta Sans & Fira Code -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950 relative overflow-x-hidden transition-colors duration-200">
        <!-- Background Glow FX -->
        <div class="fixed inset-0 pointer-events-none z-0">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/10 dark:bg-amber-500/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 w-96 h-96 bg-sky-500/10 dark:bg-sky-500/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
        </div>

        <!-- Navigation Bar -->
        <header class="relative z-10 w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 bg-linear-to-tr from-amber-500 to-amber-400 flex items-center justify-center shadow-lg shadow-amber-500/20 font-black text-slate-950 text-xl tracking-tighter">
                    EK
                </div>
                <div>
                    <span class="font-extrabold text-lg text-slate-900 dark:text-white tracking-tight">ERP KOMI</span>
                    <span class="text-xs px-2 py-0.5 ml-2 bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded-full font-mono">PROTO v1.0</span>
                </div>
            </div>

            <!-- Theme Toggle Button (Icon Only) -->
            <button onclick="toggleTheme()" type="button" aria-label="Toggle Theme" title="Toggle Theme" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-500 dark:hover:text-amber-400 shadow-xs transition cursor-pointer flex items-center justify-center">
                <svg class="w-5 h-5 hidden dark:block text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-5 h-5 block dark:hidden text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
        </header>

        <!-- Main Hero Content -->
        <main class="relative z-10 w-full max-w-7xl mx-auto px-6 py-12 flex-1 flex flex-col justify-center">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Hero Text -->
                <div class="lg:col-span-7 space-y-6 text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 backdrop-blur-md shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Enterprise Operations &amp; Resource Management</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                        Integrated ERP <br>
                        <span class="text-amber-500 dark:text-amber-400 bg-clip-text text-transparent bg-linear-to-r from-amber-500 via-amber-600 to-emerald-600 dark:from-amber-400 dark:via-amber-300 dark:to-emerald-400">Precision &amp; Control</span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl font-normal leading-relaxed">
                        Integrated enterprise management system for manufacturing operations, material inventory, Job Order tracking, Profit &amp; Loss reporting, and employee leave requests.
                    </p>

                    <!-- Quick Navigation Badges -->
                    @php
                        $defaultCompanyCode = \App\Models\Company::value('code') ?? 'KEI';
                    @endphp
                    <div class="pt-4 flex flex-wrap gap-4">
                        @auth
                            <a href="{{ url('/') }}" class="px-6 py-3.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-base rounded-xl transition shadow-xl shadow-amber-500/20 flex items-center gap-3">
                                <span>Open Dashboard</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @else
                            <a href="{{ url('/login') }}" class="px-6 py-3.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-base rounded-xl transition shadow-xl shadow-amber-500/20 flex items-center gap-3">
                                <span>Admin Login</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @endauth

                        <a href="{{ route('leave-request.lookup', $defaultCompanyCode) }}" class="px-6 py-3.5 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-800 font-semibold text-base rounded-xl transition flex items-center gap-3 shadow-xs">
                            <span>Employee Leave Portal</span>
                            <svg class="w-5 h-5 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Right Feature Cards Grid -->
                <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Feature Card 1 -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 backdrop-blur-xl hover:border-amber-500/40 transition group shadow-xs">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base mb-1">Inventory &amp; PO Management</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">Raw material requirements preview and auto-generated Purchase Orders.</p>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 backdrop-blur-xl hover:border-emerald-500/40 transition group shadow-xs">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base mb-1">Profit &amp; Loss Reports</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">Real-time Profit and Loss financial statements with automated cost breakdowns.</p>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 backdrop-blur-xl hover:border-sky-500/40 transition group shadow-xs">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base mb-1">Job Orders &amp; Workforce</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">Labor allocation and production workflow management (R&amp;D / Production).</p>
                    </div>

                    <!-- Feature Card 4 -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 backdrop-blur-xl hover:border-purple-500/40 transition group shadow-xs">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base mb-1">Self-Service Leave Portal</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">Real-time quota check and leave application via Employee Number.</p>
                    </div>

                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 w-full max-w-7xl mx-auto px-6 py-6 border-t border-slate-200 dark:border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
            <div>
                &copy; {{ date('Y') }} ERP Komi System. All rights reserved.
            </div>
            <div class="flex items-center gap-6">
                <span>Powering Enterprise Performance</span>
            </div>
        </footer>
    </body>
</html>

