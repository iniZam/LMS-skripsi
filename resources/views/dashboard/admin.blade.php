@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
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
