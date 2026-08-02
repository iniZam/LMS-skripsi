<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Masuk') · EduEduka LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lexend:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-ink-900 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 text-gold-400 font-display font-semibold text-2xl">
                <span class="w-9 h-9 rounded-lg bg-gold-500 text-ink-900 flex items-center justify-center text-base">EL</span>
                EduEduka LMS
            </div>
            <p class="text-ink-200 text-sm mt-2">Sistem Manajemen Pembelajaran</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-ink-900/30 p-8">
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
        </div>
    </div>
</body>
</html>
