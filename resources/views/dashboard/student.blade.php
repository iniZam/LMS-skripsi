@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-semibold text-ink-900">Mata Pelajaran Saya</h2>
        <a href="{{ route('classes.index') }}" class="text-sm text-gold-600 font-medium hover:underline">Jelajahi kelas &rarr;</a>
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
            <p class="text-sm text-ink-400">Anda belum terdaftar di kelas manapun.</p>
        @endforelse
    </div>

    @if ($availableClasses->count())
        <h2 class="font-display font-semibold text-ink-900 mb-4">Mata Pelajaran Terbuka untuk Pendaftaran</h2>
        <div class="grid sm:grid-cols-2 gap-4 mb-8">
            @foreach ($availableClasses as $class)
                <div class="bg-white rounded-xl border border-ink-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-ink-900 text-sm">{{ $class->title }}</p>
                        <p class="text-xs text-ink-400 mt-1">{{ $class->code }} &middot; {{ $class->teacher->name }}</p>
                    </div>
                    <form method="POST" action="{{ route('classes.enroll', $class) }}">
                        @csrf
                        <button class="text-xs bg-gold-500 hover:bg-gold-600 text-ink-900 font-semibold px-3.5 py-2 rounded-lg">Daftar</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

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
