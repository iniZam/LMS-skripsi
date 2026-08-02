<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · EduEduka LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lexend:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ink-50/40 text-ink-900">
<div class="min-h-screen flex">

    {{-- Sidebar --}}
    @php $role = auth()->user()->role; @endphp
    <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-ink-900 text-ink-100 flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-200">
        <div class="h-16 flex items-center gap-2 px-5 border-b border-ink-700/60">
            <span class="w-8 h-8 rounded-lg bg-gold-500 text-ink-900 flex items-center justify-center font-display font-semibold text-sm">EL</span>
            <span class="font-display font-semibold text-white">EduEduka LMS</span>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1 text-sm">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Dashboard
            </a>
            <a href="{{ route('classes.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('classes.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                {{ $role === 'student' ? 'Mata Pelajaran' : 'Kelola Mata Pelajaran' }}
            </a>
            @if ($role === 'admin')
                <a href="{{ route('admin.kelas.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.kelas.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Kelas &amp; Wali Kelas
                </a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Manajemen Pengguna
                </a>
            @endif
        </nav>

        <div class="px-3 py-4 border-t border-ink-700/60">
            <div class="px-3 py-2 mb-2">
                <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-ink-200 text-xs">
                    {{ auth()->user()->roleLabel() }} &middot; {{ auth()->user()->nip_nis }}
                    @if (auth()->user()->isStudent() && auth()->user()->kelas)
                        &middot; {{ auth()->user()->kelas->label() }}
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2.5 rounded-lg text-ink-200 hover:bg-rose-500/20 hover:text-white text-sm">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 md:ml-64 min-w-0">
        <header class="h-16 bg-white border-b border-ink-100 flex items-center justify-between px-5 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="md:hidden w-9 h-9 rounded-lg border border-ink-100 flex items-center justify-center text-ink-700">
                    &#9776;
                </button>
                <h1 class="font-display font-semibold text-lg text-ink-900">@yield('title', 'Dashboard')</h1>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full bg-gold-100 text-gold-600 font-medium">
                {{ auth()->user()->roleLabel() }}
            </span>
        </header>

        <main class="p-5 md:p-8 max-w-6xl">
            @if (session('status'))
                <div class="mb-5 rounded-lg bg-mint-100 text-mint-500 text-sm px-4 py-3" data-flash>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-rose-100 text-rose-500 text-sm px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
