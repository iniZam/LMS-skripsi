@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
    {{-- Kotak ucapan selamat datang --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-ink-900 to-ink-700 text-white p-6 mb-6">
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-gold-500/20"></div>
        <div class="absolute right-16 -bottom-12 w-32 h-32 rounded-full bg-gold-500/10"></div>
        <div class="relative">
            <p class="text-xs font-medium text-gold-500 uppercase tracking-wider">LMS SMP Negeri 4 Lede</p>
            <h2 class="font-display font-semibold text-2xl mt-1">Selamat datang, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-sm text-white/70 mt-1">
                NISN: {{ auth()->user()->nip_nis }}
                @if (auth()->user()->kelas)
                    &middot; {{ auth()->user()->kelas->label() }}
                @endif
            </p>
            <p class="text-sm text-white/80 mt-3 max-w-2xl">Semangat belajar hari ini! Jangan lupa hadir di setiap pertemuan dan kerjakan kuis serta tugas tepat waktu.</p>
        </div>
    </div>

    {{-- Kotak informasi absensi & kuis --}}
    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        @php
            $attColor = $attendancePercent === null ? '#A0AEC0' : ($attendancePercent >= 80 ? '#2F855A' : ($attendancePercent >= 60 ? '#C9973F' : '#C53030'));
        @endphp
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: {{ $attColor }}">
            <p class="text-xs text-ink-400">Kehadiran Saya</p>
            <p class="text-3xl font-display font-semibold text-ink-900 mt-1">
                {{ $attendancePercent === null ? '-' : rtrim(rtrim(number_format($attendancePercent, 1, ',', ''), '0'), ',') . '%' }}
            </p>
            <div class="w-full h-2 bg-ink-50 rounded-full mt-3 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $attendancePercent ?? 0 }}%; background-color: {{ $attColor }}"></div>
            </div>
            <p class="text-xs text-ink-400 mt-2">
                {{ $attendancePercent === null ? 'Belum ada data absensi.' : 'Persentase kehadiran dari seluruh pertemuan yang tercatat.' }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: {{ $pendingQuizzes > 0 ? '#C53030' : '#2F855A' }}">
            <p class="text-xs text-ink-400">Kuis Perlu Dikerjakan</p>
            <p class="text-3xl font-display font-semibold text-ink-900 mt-1">{{ $pendingQuizzes }}</p>
            <p class="text-xs text-ink-400 mt-3">
                @if ($totalQuizzes === 0)
                    Belum ada kuis di mata pelajaran Anda.
                @elseif ($pendingQuizzes === 0)
                    Hebat! Semua {{ $totalQuizzes }} kuis sudah selesai dikerjakan. 🎉
                @else
                    {{ $totalQuizzes - $pendingQuizzes }} dari {{ $totalQuizzes }} kuis sudah dikerjakan.
                @endif
            </p>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-semibold text-ink-900">Mata Pelajaran Saya</h2>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        @forelse ($enrolled as $class)
            <a href="{{ route('classes.show', $class) }}" class="block bg-white rounded-xl border border-ink-100 overflow-hidden hover:shadow-md transition">
                <div class="h-2 bg-gradient-to-r {{ $class->banner_gradient }}"></div>
                <div class="p-5">
                    <p class="font-medium text-ink-900">{{ $class->title }}</p>
                    <p class="text-xs text-ink-400 mt-1">{{ $class->code }} &middot; {{ $class->teacher->name }}</p>
                    <p class="text-xs text-ink-400 mt-2">{{ $class->meetings_count }} pertemuan</p>
                </div>
            </a>
        @empty
            <p class="text-sm text-ink-400">Anda belum didaftarkan ke mata pelajaran manapun. Hubungi admin sekolah.</p>
        @endforelse
    </div>

    <h2 class="font-display font-semibold text-ink-900 mb-4">Riwayat Pengumpulan Tugas Terbaru</h2>
    <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
        @forelse ($mySubmissions as $submission)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="text-sm font-medium text-ink-900">{{ $submission->meeting->assignment_title }}</p>
                    <p class="text-xs text-ink-400 mt-0.5">{{ $submission->meeting->classRoom->title }} &middot; dikumpulkan {{ $submission->submitted_at->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    @if ($submission->isGraded())
                        <span class="text-sm font-semibold text-mint-500">{{ $submission->grade }}/100</span>
                    @else
                        <span class="text-xs px-2.5 py-1 rounded-full bg-ink-50 text-ink-400">Belum dinilai</span>
                    @endif
                    <p class="text-xs {{ $submission->is_late ? 'text-rose-500' : 'text-ink-400' }} mt-0.5">{{ $submission->statusLabel() }}</p>
                </div>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-ink-400">Belum ada tugas yang dikumpulkan.</p>
        @endforelse
    </div>
@endsection
