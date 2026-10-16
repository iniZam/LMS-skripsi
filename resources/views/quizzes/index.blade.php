@extends('layouts.app')

@section('title', auth()->user()->isStudent() ? 'Daftar Kuis' : 'Kelola Kuis')

@section('content')
    @php $user = auth()->user(); @endphp

    @if ($user->isStudent())
        {{-- ========================================================= --}}
        {{-- TAMPILAN SISWA: KUIS BELUM DIKERJAKAN & SUDAH DIKERJAKAN  --}}
        {{-- ========================================================= --}}

        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <p class="text-xs font-medium text-ink-400">Total Kuis</p>
                <p class="font-display font-semibold text-2xl text-ink-900 mt-1">
                    {{ $pendingQuizzes->count() + $completedQuizzes->count() }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <p class="text-xs font-medium text-gold-600">Belum Dikerjakan</p>
                <p class="font-display font-semibold text-2xl text-ink-900 mt-1">
                    {{ $pendingQuizzes->count() }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <p class="text-xs font-medium text-mint-500">Sudah Dikerjakan</p>
                <p class="font-display font-semibold text-2xl text-ink-900 mt-1">
                    {{ $completedQuizzes->count() }}
                </p>
            </div>
        </div>

        {{-- 1. KUIS BELUM DIKERJAKAN --}}
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    Kuis Belum Dikerjakan
                    <span class="ml-2 text-xs px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600 font-medium">
                        {{ $pendingQuizzes->count() }}
                    </span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($pendingQuizzes as $quiz)
                    @php
                        $attempt = $attemptsByQuiz->get($quiz->id);
                        $classRoom = $quiz->meeting->classRoom;
                        $isPastDeadline = $quiz->deadline && now()->greaterThan($quiz->deadline);
                    @endphp
                    <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="h-2 bg-gradient-to-r {{ $classRoom->banner_gradient ?? 'from-blue-600 to-indigo-700' }}"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <p class="text-xs font-medium text-ink-400">
                                        {{ $classRoom->title }} &middot; Pertemuan {{ $quiz->meeting->meeting_number }}
                                    </p>
                                    @if ($attempt)
                                        <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600 font-medium whitespace-nowrap">
                                            Sedang Dikerjakan
                                        </span>
                                    @else
                                        <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-ink-50 text-ink-600 font-medium whitespace-nowrap">
                                            Belum Dikerjakan
                                        </span>
                                    @endif
                                </div>

                                <h3 class="font-display font-semibold text-ink-900 text-base">{{ $quiz->title }}</h3>
                                @if ($quiz->description)
                                    <p class="text-xs text-ink-500 mt-1 line-clamp-2">{{ $quiz->description }}</p>
                                @endif

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 text-xs text-ink-500">
                                    <span>📝 {{ $quiz->questions_count }} Soal</span>
                                    <span>⏱️ {{ $quiz->duration_minutes }} Menit</span>
                                    @if ($quiz->deadline)
                                        <span class="{{ $isPastDeadline ? 'text-rose-500 font-medium' : 'text-ink-400' }}">
                                            📅 Batas: {{ $quiz->deadline->format('d M Y, H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3.5 bg-ink-50/50 border-t border-ink-100 flex items-center justify-between">
                            <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                Lihat Materi Pertemuan
                            </a>
                            @if ($attempt)
                                <a href="{{ route('quizzes.take', [$quiz, $attempt]) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                    Lanjutkan Kuis &rarr;
                                </a>
                            @else
                                <a href="{{ route('quizzes.show', $quiz) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                    Kerjakan Kuis &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 bg-white rounded-xl border border-ink-100 p-6 text-center">
                        <p class="text-sm text-ink-500 font-medium">Tidak ada kuis yang belum dikerjakan 🎉</p>
                        <p class="text-xs text-ink-400 mt-1">Semua kuis pada mata pelajaran Anda telah diselesaikan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 2. KUIS SUDAH DIKERJAKAN --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    Kuis Sudah Dikerjakan
                    <span class="ml-2 text-xs px-2.5 py-0.5 rounded-full bg-mint-100 text-mint-700 font-medium">
                        {{ $completedQuizzes->count() }}
                    </span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($completedQuizzes as $quiz)
                    @php
                        $attempt = $attemptsByQuiz->get($quiz->id);
                        $classRoom = $quiz->meeting->classRoom;
                    @endphp
                    <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="h-2 bg-gradient-to-r {{ $classRoom->banner_gradient ?? 'from-emerald-600 to-teal-700' }}"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <p class="text-xs font-medium text-ink-400">
                                        {{ $classRoom->title }} &middot; Pertemuan {{ $quiz->meeting->meeting_number }}
                                    </p>
                                    <span class="text-xs px-2.5 py-0.5 rounded-full {{ $attempt->score >= 75 ? 'bg-mint-100 text-mint-700' : 'bg-gold-100 text-gold-600' }} font-bold whitespace-nowrap">
                                        Nilai: {{ number_format($attempt->score, 0) }}
                                    </span>
                                </div>

                                <h3 class="font-display font-semibold text-ink-900 text-base">{{ $quiz->title }}</h3>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 text-xs text-ink-500">
                                    <span>📝 {{ $quiz->questions_count }} Soal</span>
                                    <span>✅ Selesai: {{ $attempt->completed_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3.5 bg-ink-50/50 border-t border-ink-100 flex items-center justify-between">
                            <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                Lihat Pertemuan
                            </a>
                            <a href="{{ route('quizzes.result', [$quiz, $attempt]) }}" class="bg-mint-100 hover:bg-mint-200 text-mint-700 text-xs font-medium px-4 py-2 rounded-lg transition">
                                Lihat Detail Nilai
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 bg-white rounded-xl border border-ink-100 p-6 text-center">
                        <p class="text-sm text-ink-400">Belum ada kuis yang selesai dikerjakan.</p>
                    </div>
                @endforelse
            </div>
        </div>

    @else
        {{-- ========================================================= --}}
        {{-- TAMPILAN GURU / ADMIN: DAFTAR KUIS YANG DIBUAT GURU       --}}
        {{-- ========================================================= --}}

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    {{ $user->isAdmin() ? 'Semua Kuis pada Sistem' : 'Daftar Kuis yang Anda Buat' }}
                </h2>
                <p class="text-xs text-ink-400 mt-0.5">
                    Kelola soal kuis atau pantau jumlah siswa yang telah menyelesaikan kuis di kelas Anda.
                </p>
            </div>

            {{-- Filter Mata Pelajaran --}}
            <form method="GET" action="{{ route('quizzes.index') }}" class="flex items-center gap-2">
                <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-xs text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">Semua Mata Pelajaran ({{ $teacherClasses->count() }})</option>
                    @foreach ($teacherClasses as $tc)
                        <option value="{{ $tc->id }}" @selected((string) $classId === (string) $tc->id)>
                            {{ $tc->title }}
                        </option>
                    @endforeach
                </select>
                @if ($classId)
                    <a href="{{ route('quizzes.index') }}" class="text-xs text-ink-500 hover:text-ink-900 px-2 py-1">Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
            @forelse ($quizzes as $quiz)
                @php
                    $meeting = $quiz->meeting;
                    $classRoom = $meeting->classRoom;
                @endphp
                <div class="p-5 flex flex-wrap items-center justify-between gap-4 hover:bg-ink-50/50 transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600">
                                {{ $classRoom->title }}
                            </span>
                            <span class="text-xs text-ink-400">
                                Pertemuan {{ $meeting->meeting_number }}: {{ $meeting->title }}
                            </span>
                        </div>

                        <h3 class="font-display font-semibold text-ink-900 text-base">{{ $quiz->title }}</h3>
                        @if ($quiz->description)
                            <p class="text-xs text-ink-500 mt-1 line-clamp-1">{{ $quiz->description }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-3 text-xs text-ink-500">
                            <span>📝 <strong>{{ $quiz->questions_count }}</strong> Soal</span>
                            <span>⏱️ <strong>{{ $quiz->duration_minutes }}</strong> Menit</span>
                            <span>👥 <strong>{{ $quiz->completed_attempts_count }}</strong> Siswa Selesai</span>
                            @if ($user->isAdmin() && $classRoom->teacher)
                                <span>👨‍🏫 Pengajar: {{ $classRoom->teacher->name }}</span>
                            @endif
                            @if ($quiz->deadline)
                                <span>📅 Tenggat: {{ $quiz->deadline->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('meetings.show', $meeting) }}" class="text-xs px-3.5 py-2 rounded-lg border border-ink-200 text-ink-700 hover:bg-ink-50 font-medium transition">
                            Lihat Pertemuan
                        </a>
                        <a href="{{ route('quizzes.manage', $quiz) }}" class="text-xs px-4 py-2 rounded-lg bg-ink-900 hover:bg-ink-700 text-white font-medium transition">
                            Kelola Soal
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="text-sm text-ink-500 font-medium">Belum ada kuis yang dibuat.</p>
                    <p class="text-xs text-ink-400 mt-1 mb-4">
                        Untuk membuat kuis baru, buka salah satu pertemuan pada mata pelajaran Anda lalu klik tombol <strong>"+ Buat Kuis Baru"</strong>.
                    </p>
                    <a href="{{ route('classes.index') }}" class="inline-block bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg">
                        Buka Mata Pelajaran
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $quizzes->links() }}
        </div>
    @endif
@endsection

