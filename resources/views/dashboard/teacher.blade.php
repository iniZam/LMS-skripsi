@extends('layouts.app')

@section('title', 'Dashboard Pengajar')

@section('content')
    {{-- Kotak ucapan selamat datang --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-ink-900 to-ink-700 text-white p-6 mb-6">
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-gold-500/20"></div>
        <div class="absolute right-16 -bottom-12 w-32 h-32 rounded-full bg-gold-500/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-gold-500 uppercase tracking-wider">LMS SMP Negeri 1 Lede</p>
                <h2 class="font-display font-semibold text-2xl mt-1">Selamat datang, {{ auth()->user()->name }}! 👋</h2>
                <p class="text-sm text-white/70 mt-1">Pengajar &middot; NIP: {{ auth()->user()->nip_nis }}</p>
                <p class="text-sm text-white/80 mt-3 max-w-2xl">Terima kasih telah membimbing siswa-siswi SMP Negeri 1 Lede. Kelola materi, tugas, kuis, dan absensi kelas Anda dengan mudah di sini.</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl p-4 min-w-[230px]">
                <p class="text-xs text-gold-400 font-medium uppercase">Presensi Hari Ini</p>
                <p class="text-sm text-white font-medium mt-1">
                    @if (isset($todayAttendance) && $todayAttendance->check_in_time)
                        Datang: {{ substr($todayAttendance->check_in_time, 0, 5) }}
                        &middot;
                        Pulang: {{ $todayAttendance->check_out_time ? substr($todayAttendance->check_out_time, 0, 5) : '-' }}
                    @elseif (isset($todayAttendance))
                        Status: {{ strtoupper($todayAttendance->status) }}
                    @else
                        Belum Absen Datang
                    @endif
                </p>
                <a href="{{ route('teacher.attendance.index') }}" class="mt-3 inline-flex items-center justify-center w-full bg-gold-500 hover:bg-gold-600 text-ink-900 font-display font-semibold text-xs px-3.5 py-2 rounded-lg transition">
                    Buka Halaman Absensi Guru &rarr;
                </a>
            </div>
        </div>
    </div>

    @php
        $attColor = $attendancePercent === null ? '#A0AEC0' : ($attendancePercent >= 80 ? '#2F855A' : ($attendancePercent >= 60 ? '#C9973F' : '#C53030'));
    @endphp
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: {{ $attColor }}">
            <p class="text-xs text-ink-400">Kehadiran Siswa</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">
                {{ $attendancePercent === null ? '-' : rtrim(rtrim(number_format($attendancePercent, 1, ',', ''), '0'), ',') . '%' }}
            </p>
            <div class="w-full h-1.5 bg-ink-50 rounded-full mt-2 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $attendancePercent ?? 0 }}%; background-color: {{ $attColor }}"></div>
            </div>
            <p class="text-xs text-ink-400 mt-2">Rata-rata di kelas yang Anda ajar</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C9973F">
            <p class="text-xs text-ink-400">Kuis Dibuat</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $totalQuizzes }}</p>
            <p class="text-xs text-ink-400 mt-2">Total kuis di seluruh kelas Anda</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #2F855A">
            <p class="text-xs text-ink-400">Mata Pelajaran</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $classes->count() }}</p>
            <p class="text-xs text-ink-400 mt-2">{{ $classes->sum('meetings_count') }} pertemuan</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C53030">
            <p class="text-xs text-ink-400">Tugas Menunggu Dinilai</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $pendingGrading }}</p>
            <p class="text-xs text-ink-400 mt-2">Pengumpulan belum diberi nilai</p>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-semibold text-ink-900">Mata Pelajaran Saya</h2>
        <a href="{{ route('classes.create') }}" class="text-sm bg-ink-900 hover:bg-ink-700 text-white px-4 py-2 rounded-lg font-medium">+ Buat Mata Pelajaran</a>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        @forelse ($classes as $class)
            <a href="{{ route('classes.show', $class) }}" class="block bg-white rounded-xl border border-ink-100 overflow-hidden hover:shadow-md transition">
                <div class="h-2 bg-gradient-to-r {{ $class->banner_gradient }}"></div>
                <div class="p-5">
                    <p class="font-medium text-ink-900">{{ $class->title }}</p>
                    <p class="text-xs text-ink-400 mt-1">{{ $class->code }} &middot; {{ $class->academic_year }}</p>
                    <div class="flex items-center gap-3 mt-3 text-xs text-ink-400">
                        <span>{{ $class->meetings_count }} pertemuan</span>
                        <span>&middot;</span>
                        <span>{{ $class->students_count }} siswa</span>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-sm text-ink-400">Anda belum membuat mata pelajaran. Klik "Buat Mata Pelajaran" untuk memulai.</p>
        @endforelse
    </div>
@endsection
