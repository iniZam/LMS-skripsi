@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    {{-- Kotak ucapan selamat datang --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-ink-900 to-ink-700 text-white p-6 mb-6">
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-gold-500/20"></div>
        <div class="absolute right-16 -bottom-12 w-32 h-32 rounded-full bg-gold-500/10"></div>
        <div class="relative">
            <p class="text-xs font-medium text-gold-500 uppercase tracking-wider">LMS SMP Negeri 4 Lede</p>
            <h2 class="font-display font-semibold text-2xl mt-1">Selamat datang, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-sm text-white/70 mt-1">Administrator Sistem</p>
            <p class="text-sm text-white/80 mt-3 max-w-2xl">Pantau seluruh aktivitas pembelajaran, pengguna, dan kelas di SMP Negeri 4 Lede.</p>
        </div>
    </div>

    @php
        $attColor = $attendancePercent === null ? '#A0AEC0' : ($attendancePercent >= 80 ? '#2F855A' : ($attendancePercent >= 60 ? '#C9973F' : '#C53030'));
    @endphp
    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: {{ $attColor }}">
            <p class="text-xs text-ink-400">Kehadiran Siswa (Seluruh Sekolah)</p>
            <p class="text-3xl font-display font-semibold text-ink-900 mt-1">
                {{ $attendancePercent === null ? '-' : rtrim(rtrim(number_format($attendancePercent, 1, ',', ''), '0'), ',') . '%' }}
            </p>
            <div class="w-full h-2 bg-ink-50 rounded-full mt-3 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $attendancePercent ?? 0 }}%; background-color: {{ $attColor }}"></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #2F855A">
            <div class="flex items-center justify-between">
                <p class="text-xs text-ink-400">Kehadiran Guru Hari Ini</p>
                <a href="{{ route('admin.teacher-attendance.index') }}" class="text-xs text-gold-600 font-medium hover:underline">Rekap &rarr;</a>
            </div>
            <p class="text-3xl font-display font-semibold text-ink-900 mt-1">{{ $teacherTodayHadir ?? 0 }} <span class="text-sm font-normal text-ink-400">/ {{ $stats['totalTeachers'] }} guru</span></p>
            <p class="text-xs text-ink-400 mt-3">Sudah melakukan absen datang hari ini</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C9973F">
            <p class="text-xs text-ink-400">Total Kuis</p>
            <p class="text-3xl font-display font-semibold text-ink-900 mt-1">{{ $totalQuizzes }}</p>
            <p class="text-xs text-ink-400 mt-3">Kuis yang telah dibuat oleh seluruh pengajar</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'Total Pengguna', 'value' => $stats['totalUsers']],
                ['label' => 'Pengajar', 'value' => $stats['totalTeachers']],
                ['label' => 'Siswa', 'value' => $stats['totalStudents']],
                ['label' => 'Kelas (Rombel)', 'value' => $stats['totalKelas']],
                ['label' => 'Mata Pelajaran', 'value' => $stats['totalClasses']],
                ['label' => 'Pertemuan', 'value' => $stats['totalMeetings']],
                ['label' => 'Pengumpulan Tugas', 'value' => $stats['totalSubmissions']],
                ['label' => 'Sudah Dinilai', 'value' => $stats['totalGraded']],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C9973F">
                <p class="text-2xl font-display font-semibold text-ink-900">{{ $card['value'] }}</p>
                <p class="text-xs text-ink-400 mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-semibold text-ink-900">Mata Pelajaran Terbaru</h2>
        <a href="{{ route('classes.index') }}" class="text-sm text-gold-600 font-medium hover:underline">Lihat semua &rarr;</a>
    </div>

    <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
        @forelse ($classes as $class)
            <a href="{{ route('classes.show', $class) }}" class="flex items-center justify-between px-5 py-4 hover:bg-ink-50/60">
                <div>
                    <p class="font-medium text-ink-900 text-sm">{{ $class->title }}</p>
                    <p class="text-xs text-ink-400 mt-0.5">{{ $class->code }} &middot; {{ $class->teacher->name }} &middot; {{ $class->meetings_count }} pertemuan &middot; {{ $class->students_count }} siswa</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-ink-50 text-ink-600 font-medium">{{ $class->statusLabel() }}</span>
            </a>
        @empty
            <p class="px-5 py-6 text-sm text-ink-400">Belum ada kelas.</p>
        @endforelse
    </div>
@endsection
