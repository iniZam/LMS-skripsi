@extends('layouts.app')

@section('title', auth()->user()->isStudent() ? 'Daftar Kuis & Ujian' : 'Kelola Kuis, UTS & UAS')

@section('content')
    @php $user = auth()->user(); @endphp

    @if ($user->isStudent())
        {{-- ========================================================= --}}
        {{-- TAMPILAN SISWA: KUIS & UJIAN (UTS / UAS)                  --}}
        {{-- ========================================================= --}}

        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-2">
                <a href="{{ route('quizzes.index') }}" class="text-xs px-3.5 py-1.5 rounded-full font-medium {{ !$typeFilter ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">Semua</a>
                <a href="{{ route('quizzes.index', ['type' => 'quiz']) }}" class="text-xs px-3.5 py-1.5 rounded-full font-medium {{ $typeFilter === 'quiz' ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">Kuis Harian</a>
                <a href="{{ route('quizzes.index', ['type' => 'uts']) }}" class="text-xs px-3.5 py-1.5 rounded-full font-medium {{ $typeFilter === 'uts' ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">UTS</a>
                <a href="{{ route('quizzes.index', ['type' => 'uas']) }}" class="text-xs px-3.5 py-1.5 rounded-full font-medium {{ $typeFilter === 'uas' ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">UAS</a>
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <p class="text-xs font-medium text-ink-400">Total Kuis &amp; Ujian</p>
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

        {{-- 1. KUIS & UJIAN BELUM DIKERJAKAN --}}
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    Kuis &amp; Ujian Belum Dikerjakan
                    <span class="ml-2 text-xs px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600 font-medium">
                        {{ $pendingQuizzes->count() }}
                    </span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($pendingQuizzes as $quiz)
                    @php
                        $attempt = $attemptsByQuiz->get($quiz->id);
                        $classRoom = $quiz->classRoom ?? $quiz->meeting?->classRoom;
                        $isPastDeadline = $quiz->deadline && now()->greaterThan($quiz->deadline);
                        $typeBadge = match ($quiz->type) {
                            'uts' => 'bg-blue-100 text-blue-700',
                            'uas' => 'bg-purple-100 text-purple-700',
                            default => 'bg-gold-100 text-gold-600',
                        };
                    @endphp
                    <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="h-2 bg-gradient-to-r {{ $classRoom->banner_gradient ?? 'from-blue-600 to-indigo-700' }}"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $typeBadge }}">
                                            {{ $quiz->shortTypeLabel() }}
                                        </span>
                                        <p class="text-xs font-medium text-ink-400">
                                            {{ $classRoom?->title }}
                                            @if ($quiz->meeting)
                                                &middot; Pertemuan {{ $quiz->meeting->meeting_number }}
                                            @endif
                                        </p>
                                    </div>
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
                            @if ($quiz->meeting_id)
                                <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                    Lihat Materi Pertemuan
                                </a>
                            @elseif ($classRoom)
                                <a href="{{ route('classes.show', $classRoom) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                    Lihat Mata Pelajaran
                                </a>
                            @else
                                <span></span>
                            @endif

                            @if ($attempt)
                                <a href="{{ route('quizzes.take', [$quiz, $attempt]) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                    Lanjutkan {{ $quiz->shortTypeLabel() }} &rarr;
                                </a>
                            @else
                                <a href="{{ route('quizzes.show', $quiz) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                    Kerjakan {{ $quiz->shortTypeLabel() }} &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 bg-white rounded-xl border border-ink-100 p-6 text-center">
                        <p class="text-sm text-ink-500 font-medium">Tidak ada kuis atau ujian yang belum dikerjakan 🎉</p>
                        <p class="text-xs text-ink-400 mt-1">Semua kuis dan ujian pada mata pelajaran Anda telah diselesaikan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 2. KUIS & UJIAN SUDAH DIKERJAKAN --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    Kuis &amp; Ujian Sudah Dikerjakan
                    <span class="ml-2 text-xs px-2.5 py-0.5 rounded-full bg-mint-100 text-mint-700 font-medium">
                        {{ $completedQuizzes->count() }}
                    </span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($completedQuizzes as $quiz)
                    @php
                        $attempt = $attemptsByQuiz->get($quiz->id);
                        $classRoom = $quiz->classRoom ?? $quiz->meeting?->classRoom;
                        $typeBadge = match ($quiz->type) {
                            'uts' => 'bg-blue-100 text-blue-700',
                            'uas' => 'bg-purple-100 text-purple-700',
                            default => 'bg-gold-100 text-gold-600',
                        };
                    @endphp
                    <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="h-2 bg-gradient-to-r {{ $classRoom->banner_gradient ?? 'from-emerald-600 to-teal-700' }}"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $typeBadge }}">
                                            {{ $quiz->shortTypeLabel() }}
                                        </span>
                                        <p class="text-xs font-medium text-ink-400">
                                            {{ $classRoom?->title }}
                                            @if ($quiz->meeting)
                                                &middot; Pertemuan {{ $quiz->meeting->meeting_number }}
                                            @endif
                                        </p>
                                    </div>
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
                            @if ($quiz->meeting_id)
                                <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                    Lihat Pertemuan
                                </a>
                            @elseif ($classRoom)
                                <a href="{{ route('classes.show', $classRoom) }}" class="text-xs text-ink-500 hover:text-ink-900 font-medium">
                                    Lihat Mata Pelajaran
                                </a>
                            @else
                                <span></span>
                            @endif
                            <a href="{{ route('quizzes.result', [$quiz, $attempt]) }}" class="bg-mint-100 hover:bg-mint-200 text-mint-700 text-xs font-medium px-4 py-2 rounded-lg transition">
                                Lihat Detail Nilai
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 bg-white rounded-xl border border-ink-100 p-6 text-center">
                        <p class="text-sm text-ink-400">Belum ada kuis atau ujian yang selesai dikerjakan.</p>
                    </div>
                @endforelse
            </div>
        </div>

    @else
        {{-- ========================================================= --}}
        {{-- TAMPILAN GURU / ADMIN: DAFTAR KUIS, UTS & UAS             --}}
        {{-- ========================================================= --}}

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-display font-semibold text-lg text-ink-900">
                    {{ $user->isAdmin() ? 'Kelola Semua Kuis, UTS & UAS' : 'Daftar Kuis, UTS & UAS yang Anda Buat' }}
                </h2>
                <p class="text-xs text-ink-400 mt-0.5">
                    Buat soal Kuis, UTS, maupun UAS. Khusus UTS &amp; UAS memerlukan persetujuan (ACC) Admin sebelum dirilis ke siswa.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('exams.create', ['type' => 'uts']) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-semibold px-4 py-2.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-sm">
                    + Buat UTS Baru
                </a>
                <a href="{{ route('exams.create', ['type' => 'uas']) }}" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-sm">
                    + Buat UAS Baru
                </a>
            </div>
        </div>

        {{-- Bar Filter --}}
        <div class="bg-white rounded-xl border border-ink-100 p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('quizzes.index', array_filter(['class_id' => $classId, 'approval_status' => $statusFilter])) }}"
                   class="text-xs px-3 py-1.5 rounded-full font-medium {{ !$typeFilter ? 'bg-ink-900 text-white' : 'bg-ink-50 text-ink-600 hover:bg-ink-100' }}">
                    Semua Jenis
                </a>
                <a href="{{ route('quizzes.index', array_filter(['type' => 'quiz', 'class_id' => $classId, 'approval_status' => $statusFilter])) }}"
                   class="text-xs px-3 py-1.5 rounded-full font-medium {{ $typeFilter === 'quiz' ? 'bg-ink-900 text-white' : 'bg-ink-50 text-ink-600 hover:bg-ink-100' }}">
                    Kuis Harian
                </a>
                <a href="{{ route('quizzes.index', array_filter(['type' => 'uts', 'class_id' => $classId, 'approval_status' => $statusFilter])) }}"
                   class="text-xs px-3 py-1.5 rounded-full font-medium {{ $typeFilter === 'uts' ? 'bg-ink-900 text-white' : 'bg-ink-50 text-ink-600 hover:bg-ink-100' }}">
                    UTS
                </a>
                <a href="{{ route('quizzes.index', array_filter(['type' => 'uas', 'class_id' => $classId, 'approval_status' => $statusFilter])) }}"
                   class="text-xs px-3 py-1.5 rounded-full font-medium {{ $typeFilter === 'uas' ? 'bg-ink-900 text-white' : 'bg-ink-50 text-ink-600 hover:bg-ink-100' }}">
                    UAS
                </a>
            </div>

            <form method="GET" action="{{ route('quizzes.index') }}" class="flex flex-wrap items-center gap-2">
                @if ($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                <select name="approval_status" onchange="this.form.submit()" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-xs text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">Semua Status Rilis</option>
                    <option value="approved" @selected($statusFilter === 'approved')>Disetujui (Rilis)</option>
                    <option value="pending" @selected($statusFilter === 'pending')>Menunggu ACC Admin</option>
                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak Admin</option>
                </select>
                <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-xs text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">Semua Mata Pelajaran ({{ $teacherClasses->count() }})</option>
                    @foreach ($teacherClasses as $tc)
                        <option value="{{ $tc->id }}" @selected((string) $classId === (string) $tc->id)>
                            {{ $tc->title }}
                        </option>
                    @endforeach
                </select>
                @if ($classId || $typeFilter || $statusFilter)
                    <a href="{{ route('quizzes.index') }}" class="text-xs text-ink-500 hover:text-ink-900 px-2 py-1">Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
            @forelse ($quizzes as $quiz)
                @php
                    $meeting = $quiz->meeting;
                    $classRoom = $quiz->classRoom ?? $meeting?->classRoom;
                    $typeBadge = match ($quiz->type) {
                        'uts' => 'bg-blue-100 text-blue-700',
                        'uas' => 'bg-purple-100 text-purple-700',
                        default => 'bg-gold-100 text-gold-600',
                    };
                    $approvalBadge = match ($quiz->approval_status) {
                        'approved' => 'bg-mint-100 text-mint-700',
                        'pending' => 'bg-gold-100 text-gold-600',
                        'rejected' => 'bg-rose-100 text-rose-500',
                        default => 'bg-ink-100 text-ink-600',
                    };
                @endphp
                <div class="p-5 flex flex-wrap items-center justify-between gap-4 hover:bg-ink-50/50 transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span class="text-[11px] font-bold uppercase px-2.5 py-0.5 rounded-full {{ $typeBadge }}">
                                {{ $quiz->shortTypeLabel() }}
                            </span>
                            @if ($quiz->isExam())
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full {{ $approvalBadge }}">
                                    {{ $quiz->approvalStatusLabel() }}
                                </span>
                            @endif
                            @if ($classRoom)
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-ink-50 text-ink-700">
                                    {{ $classRoom->title }}
                                </span>
                            @endif
                            @if ($meeting)
                                <span class="text-xs text-ink-400">
                                    Pertemuan {{ $meeting->meeting_number }}: {{ $meeting->title }}
                                </span>
                            @endif
                        </div>

                        <h3 class="font-display font-semibold text-ink-900 text-base">{{ $quiz->title }}</h3>
                        @if ($quiz->description)
                            <p class="text-xs text-ink-500 mt-1 line-clamp-1">{{ $quiz->description }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-3 text-xs text-ink-500">
                            <span>📝 <strong>{{ $quiz->questions_count }}</strong> Soal</span>
                            <span>⏱️ <strong>{{ $quiz->duration_minutes }}</strong> Menit</span>
                            <span>👥 <strong>{{ $quiz->completed_attempts_count }}</strong> Siswa Selesai</span>
                            @if ($user->isAdmin() && $classRoom?->teacher)
                                <span>👨‍🏫 Pengajar: {{ $classRoom->teacher->name }}</span>
                            @endif
                            @if ($quiz->deadline)
                                <span>📅 Tenggat: {{ $quiz->deadline->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if ($user->isAdmin() && $quiz->isExam() && $quiz->approval_status !== 'approved')
                            <form method="POST" action="{{ route('quizzes.approve', $quiz) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="text-xs px-3.5 py-2 rounded-lg bg-mint-500 hover:bg-mint-600 text-white font-semibold transition cursor-pointer">
                                    &#10003; ACC Rilis
                                </button>
                            </form>
                        @endif
                        @if ($meeting)
                            <a href="{{ route('meetings.show', $meeting) }}" class="text-xs px-3.5 py-2 rounded-lg border border-ink-200 text-ink-700 hover:bg-ink-50 font-medium transition">
                                Lihat Pertemuan
                            </a>
                        @elseif ($classRoom)
                            <a href="{{ route('classes.show', $classRoom) }}" class="text-xs px-3.5 py-2 rounded-lg border border-ink-200 text-ink-700 hover:bg-ink-50 font-medium transition">
                                Lihat Kelas
                            </a>
                        @endif
                        <a href="{{ route('quizzes.manage', $quiz) }}" class="text-xs px-4 py-2 rounded-lg bg-ink-900 hover:bg-ink-700 text-white font-medium transition">
                            Kelola Soal
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="text-sm text-ink-500 font-medium">Belum ada kuis, UTS, atau UAS yang dibuat.</p>
                    <p class="text-xs text-ink-400 mt-1 mb-4">
                        Anda dapat membuat <strong>UTS / UAS</strong> melalui tombol di atas, atau membuat kuis pada halaman pertemuan mata pelajaran.
                    </p>
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('exams.create', ['type' => 'uts']) }}" class="inline-block bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-semibold px-4 py-2 rounded-lg">
                            + Buat UTS / UAS
                        </a>
                        <a href="{{ route('classes.index') }}" class="inline-block bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg">
                            Buka Mata Pelajaran
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $quizzes->links() }}
        </div>
    @endif
@endsection
