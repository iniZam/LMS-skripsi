@extends('layouts.app')

@section('title', 'Dashboard Pengajar')

@section('content')
    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C9973F">
            <p class="text-2xl font-display font-semibold text-ink-900">{{ $classes->count() }}</p>
            <p class="text-xs text-ink-400 mt-1">Mata pelajaran yang Anda ajar</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #2F855A">
            <p class="text-2xl font-display font-semibold text-ink-900">{{ $classes->sum('meetings_count') }}</p>
            <p class="text-xs text-ink-400 mt-1">Total pertemuan</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C53030">
            <p class="text-2xl font-display font-semibold text-ink-900">{{ $pendingGrading }}</p>
            <p class="text-xs text-ink-400 mt-1">Tugas menunggu dinilai</p>
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
