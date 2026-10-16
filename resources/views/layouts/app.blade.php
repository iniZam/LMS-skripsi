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
            <a href="{{ route('quizzes.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('quizzes.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                {{ $role === 'student' ? 'Kuis' : 'Kelola Kuis' }}
            </a>
            <a href="{{ route('rapot.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('rapot.*') || request()->routeIs('classes.rapot*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                {{ $role === 'student' ? 'Rapot Saya' : 'Rapot & Rekap Nilai' }}
            </a>
            @if ($role === 'teacher')
                <a href="{{ route('teacher.attendance.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('teacher.attendance.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Absensi Guru
                </a>
            @endif
            @if ($role === 'admin')
                <a href="{{ route('admin.teacher-attendance.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.teacher-attendance.*') ? 'bg-ink-700 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Rekap Absensi Guru
                </a>
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
            <a href="{{ route('password.edit') }}" class="w-full text-left flex items-center gap-2 px-3 py-2.5 rounded-lg text-ink-200 hover:bg-rose-500/20 hover:text-white text-sm">
                Ubah Kata Sandi
            </a>
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
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-ink-900 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-ink-400 leading-tight">{{ auth()->user()->nip_nis }}</p>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full bg-gold-100 text-gold-600 font-medium">
                    {{ auth()->user()->roleLabel() }}
                </span>
            </div>
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
<!-- TOMBOL MENGAMBANG (FLOATING CHAT ICON) -->
    <button id="ai-floating-btn" class="fixed bottom-6 right-6 bg-gold-500 hover:bg-gold-600 text-ink-900 p-4 rounded-full shadow-xl transition-transform transform hover:scale-110 z-50 flex items-center justify-center">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
    </button>

    <!-- KOTAK POP-UP CHAT (Tersembunyi secara default) -->
    <div id="ai-chat-popup" class="fixed bottom-24 right-6 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-ink-100 flex flex-col h-[450px] z-50 hidden transition-opacity origin-bottom-right">
        
        <!-- Header Pop-up -->
        <div class="bg-ink-900 text-white px-4 py-3 rounded-t-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-gold-500 text-ink-900 flex items-center justify-center font-bold text-xs">AI</span>
                <h3 class="font-medium text-sm">EduEduka AI Assistant</h3>
            </div>
            <button id="ai-close-btn" class="text-ink-300 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Ruang Obrolan -->
        <div id="popup-chat-history" class="flex-1 p-4 overflow-y-auto space-y-4 bg-ink-50/50">
            <div class="flex items-start gap-2">
                <div class="w-7 h-7 shrink-0 rounded-full bg-ink-200 flex items-center justify-center text-ink-700 text-[10px] font-bold">AI</div>
                <div class="bg-white border border-ink-100 rounded-xl rounded-tl-none px-3 py-2 text-sm text-ink-700 shadow-sm max-w-[85%]">
                    Halo {{ auth()->check() ? auth()->user()->name : 'Sobat' }}! Ada yang bisa saya bantu hari ini?
                </div>
            </div>
        </div>

        <!-- Kolom Input -->
        <div class="p-3 bg-white border-t border-ink-100 rounded-b-xl">
            <form id="popup-chat-form" class="flex items-center gap-2">
                <input type="text" id="popup-chat-input" required autocomplete="off" placeholder="Tanya AI di sini..." 
                       class="flex-1 rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                <button type="submit" class="bg-gold-500 hover:bg-gold-600 text-ink-900 p-2 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- LOGIKA JAVASCRIPT POP-UP AI -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const aiBtn = document.getElementById('ai-floating-btn');
            const aiPopup = document.getElementById('ai-chat-popup');
            const aiCloseBtn = document.getElementById('ai-close-btn');
            const chatForm = document.getElementById('popup-chat-form');
            const chatInput = document.getElementById('popup-chat-input');
            const chatHistory = document.getElementById('popup-chat-history');

            if (!aiBtn) return; // Mencegah error jika layout tidak memuat elemen ini

            // Buka / Tutup Pop-up
            aiBtn.addEventListener('click', () => {
                aiPopup.classList.toggle('hidden');
                if(!aiPopup.classList.contains('hidden')) chatInput.focus();
            });

            aiCloseBtn.addEventListener('click', () => {
                aiPopup.classList.add('hidden');
            });

            // Kirim Pesan
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const message = chatInput.value.trim();
                if(!message) return;

                // 1. Tampilkan pesan pengguna secara aman (mencegah XSS)
                const userRow = document.createElement('div');
                userRow.className = 'flex items-start justify-end gap-2';
                const userBubble = document.createElement('div');
                userBubble.className = 'bg-mint-100 border border-mint-200 rounded-xl rounded-tr-none px-3 py-2 text-sm text-mint-900 shadow-sm max-w-[85%] whitespace-pre-wrap';
                userBubble.textContent = message;
                userRow.appendChild(userBubble);
                chatHistory.appendChild(userRow);
                
                chatInput.value = '';
                chatHistory.scrollTop = chatHistory.scrollHeight;

                // 2. Tampilkan indikator mengetik
                const loadingRow = document.createElement('div');
                loadingRow.className = 'flex items-start gap-2';
                loadingRow.innerHTML = `
                    <div class="w-7 h-7 shrink-0 rounded-full bg-ink-200 flex items-center justify-center text-ink-700 text-[10px] font-bold">AI</div>
                    <div class="bg-white border border-ink-100 rounded-xl rounded-tl-none px-3 py-2 text-sm text-ink-400 shadow-sm animate-pulse">
                        Sedang berpikir...
                    </div>`;
                chatHistory.appendChild(loadingRow);
                chatHistory.scrollTop = chatHistory.scrollHeight;

                // 3. Kirim ke Server via Fetch API
                try {
                    const response = await fetch('{{ route("dashboard.ask-ai") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message })
                    });

                    if (!response.ok) {
                        const errData = await response.json().catch(() => null);
                        throw new Error((errData && errData.message) ? errData.message : 'Gagal menghubungi server (Status: ' + response.status + ')');
                    }

                    const data = await response.json();
                    
                    // 4. Hapus loading dan tampilkan jawaban AI secara aman
                    loadingRow.remove();

                    const aiRow = document.createElement('div');
                    aiRow.className = 'flex items-start gap-2';
                    const aiAvatar = document.createElement('div');
                    aiAvatar.className = 'w-7 h-7 shrink-0 rounded-full bg-ink-200 flex items-center justify-center text-ink-700 text-[10px] font-bold';
                    aiAvatar.textContent = 'AI';
                    const aiBubble = document.createElement('div');
                    aiBubble.className = 'bg-white border border-ink-100 rounded-xl rounded-tl-none px-3 py-2 text-sm text-ink-700 shadow-sm max-w-[85%] whitespace-pre-wrap';
                    aiBubble.textContent = data.reply;
                    aiRow.appendChild(aiAvatar);
                    aiRow.appendChild(aiBubble);
                    chatHistory.appendChild(aiRow);

                    chatHistory.scrollTop = chatHistory.scrollHeight;
                } catch (error) {
                    loadingRow.remove();

                    const errRow = document.createElement('div');
                    errRow.className = 'flex items-start gap-2';
                    const errAvatar = document.createElement('div');
                    errAvatar.className = 'w-7 h-7 shrink-0 rounded-full bg-rose-200 flex items-center justify-center text-rose-700 text-[10px] font-bold';
                    errAvatar.textContent = '!';
                    const errBubble = document.createElement('div');
                    errBubble.className = 'bg-rose-50 border border-rose-200 rounded-xl rounded-tl-none px-3 py-2 text-sm text-rose-700 shadow-sm max-w-[85%]';
                    errBubble.textContent = error.message || 'Terjadi kesalahan jaringan. Gagal menghubungi server.';
                    errRow.appendChild(errAvatar);
                    errRow.appendChild(errBubble);
                    chatHistory.appendChild(errRow);

                    chatHistory.scrollTop = chatHistory.scrollHeight;
                }
            });
        });
    </script>
</body>
</html>
