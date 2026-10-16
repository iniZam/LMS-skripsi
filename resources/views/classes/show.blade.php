@extends('layouts.app')

@section('title', $class->code)

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="bg-white rounded-xl border border-ink-100 overflow-hidden mb-6">
        <div class="h-3 bg-gradient-to-r {{ $class->banner_gradient }}"></div>
        <div class="p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-xs font-medium text-ink-400 mb-1">{{ $class->code }} &middot; {{ $class->subject_category }}</p>
                    <h2 class="font-display font-semibold text-xl text-ink-900">{{ $class->title }}</h2>
                    <p class="text-sm text-ink-400 mt-2 max-w-2xl">{{ $class->description }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-3 py-1.5 rounded-full bg-ink-50 text-ink-600 font-medium">{{ $class->statusLabel() }}</span>
                    @if ($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id))
                        <a href="{{ route('classes.edit', $class) }}" class="text-xs px-3 py-1.5 rounded-full border border-ink-100 text-ink-600 font-medium hover:bg-ink-50">Edit Mata Pelajaran</a>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-5 text-sm text-ink-600">
                <span><span class="text-ink-400">Pengajar:</span> {{ $class->teacher->name }}</span>
                <span><span class="text-ink-400">Tahun Ajaran:</span> {{ $class->academic_year }}</span>
                <span><span class="text-ink-400">Siswa Terdaftar:</span> {{ $class->students->count() }}</span>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display font-semibold text-ink-900">Pertemuan</h3>
                @if ($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id))
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('meetings.create', $class) }}" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                + Pertemuan Baru
            </a>
            
            <!-- Tambahkan baris tombol rekap absensi & rapot ini -->
            <a href="{{ route('classes.attendance-recap', $class) }}" class="bg-white hover:bg-ink-50 text-ink-700 border border-ink-200 text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-2">
                📋 Lihat Rekap Absensi
            </a>
            <a href="{{ route('classes.rapot', $class) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-2">
                📊 Lihat Rapot Nilai
            </a>
        </div>
    @endif
            </div>

            <div class="space-y-3">
                @forelse ($class->meetings as $meeting)
                    <a href="{{ route('meetings.show', $meeting) }}" class="block bg-white rounded-xl border border-ink-100 p-5 hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-medium text-gold-600 mb-1">Pertemuan {{ $meeting->meeting_number }}</p>
                                <p class="font-medium text-ink-900 text-sm">{{ $meeting->title }}</p>
                                <p class="text-xs text-ink-400 mt-1">{{ $meeting->materials->count() }} berkas materi</p>
                            </div>
                            @if ($meeting->has_assignment)
                                <span class="text-xs px-2.5 py-1 rounded-full {{ $meeting->isPastDeadline() ? 'bg-rose-100 text-rose-500' : 'bg-mint-100 text-mint-500' }} font-medium whitespace-nowrap">
                                    Ada Tugas
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-ink-400 bg-white rounded-xl border border-ink-100 p-6">Belum ada pertemuan pada kelas ini.</p>
                @endforelse
            </div>
        </div>

        @if ($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id))
            <div>
                <h3 class="font-display font-semibold text-ink-900 mb-4">Siswa Terdaftar</h3>

                @if ($user->isAdmin() && $errors->has('student'))
                    <div class="mb-3 rounded-lg bg-rose-100 text-rose-500 text-xs px-3 py-2.5">
                        {{ $errors->first('student') }}
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
                    @forelse ($class->students as $student)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-ink-900">{{ $student->name }}</p>
                                <p class="text-xs text-ink-400">{{ $student->nip_nis }}</p>
                            </div>
                            @if ($user->isAdmin())
                                <form method="POST" action="{{ route('classes.removeStudent', [$class, $student]) }}" data-confirm="Keluarkan {{ $student->name }} dari mata pelajaran ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-rose-500 hover:underline font-medium">Keluarkan</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-5 text-sm text-ink-400">Belum ada siswa yang terdaftar.</p>
                    @endforelse
                </div>

                @if ($user->isAdmin())
                    <div class="bg-white rounded-xl border border-ink-100 p-4 mt-4">
                        <p class="text-xs font-medium text-ink-700 mb-2">Tambah Siswa ke Mata Pelajaran Ini</p>
                        <form method="POST" action="{{ route('classes.addStudent', $class) }}" class="flex gap-2">
                            @csrf
                            <select name="student_id" required class="flex-1 rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                <option value="">Pilih siswa&hellip;</option>
                                @foreach ($availableStudents as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }} @if ($student->kelas)({{ $student->kelas->label() }})@endif</option>
                                @endforeach
                            </select>
                            <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg whitespace-nowrap">Tambah</button>
                        </form>
                        @if ($availableStudents->isEmpty())
                            <p class="text-xs text-ink-400 mt-2">Semua siswa sudah terdaftar di mata pelajaran ini.</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
