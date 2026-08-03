@extends('layouts.app')

@section('title', auth()->user()->isStudent() ? 'Mata Pelajaran' : 'Kelola Mata Pelajaran')

@section('content')
    @if (auth()->user()->isStudent())
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-semibold text-ink-900">Mata Pelajaran Saya</h2>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @forelse ($myClasses as $class)
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
    @else
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-semibold text-ink-900">{{ auth()->user()->isAdmin() ? 'Semua Mata Pelajaran' : 'Mata Pelajaran Saya' }}</h2>
            <a href="{{ route('classes.create') }}" class="text-sm bg-ink-900 hover:bg-ink-700 text-white px-4 py-2 rounded-lg font-medium">+ Buat Mata Pelajaran</a>
        </div>

        <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
            @forelse ($classes as $class)
                <a href="{{ route('classes.show', $class) }}" class="flex items-center justify-between px-5 py-4 hover:bg-ink-50/60">
                    <div>
                        <p class="font-medium text-ink-900 text-sm">{{ $class->title }}</p>
                        <p class="text-xs text-ink-400 mt-0.5">
                            {{ $class->code }}
                            @if(auth()->user()->isAdmin()) &middot; {{ $class->teacher->name }} @endif
                            &middot; {{ $class->meetings_count }} pertemuan &middot; {{ $class->students_count }} siswa
                        </p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-ink-50 text-ink-600 font-medium">{{ $class->statusLabel() }}</span>
                </a>
            @empty
                <p class="px-5 py-6 text-sm text-ink-400">Belum ada kelas.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $classes->links() }}</div>
    @endif
@endsection
