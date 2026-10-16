@extends('layouts.app')

@section('title', $meeting->title)

@section('content')
    @php $user = auth()->user(); @endphp

    <a href="{{ route('classes.show', $class) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; {{ $class->title }}</a>

    <!-- 1. KOTAK INFO PERTEMUAN & MATERI -->
    <div class="bg-white rounded-xl border border-ink-100 p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-medium text-gold-600 mb-1">Pertemuan {{ $meeting->meeting_number }}</p>
                <h2 class="font-display font-semibold text-xl text-ink-900">{{ $meeting->title }}</h2>
            </div>
            @if ($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id))
                <a href="{{ route('meetings.edit', $meeting) }}" class="text-xs px-3 py-1.5 rounded-full border border-ink-100 text-ink-600 font-medium hover:bg-ink-50">Edit Pertemuan</a>
            @endif
        </div>
        <p class="text-sm text-ink-600 mt-3">{{ $meeting->description }}</p>

        @if ($meeting->youtubeEmbedUrl())
            <div class="mt-5 aspect-video rounded-lg overflow-hidden bg-ink-900">
                <iframe class="w-full h-full" src="{{ $meeting->youtubeEmbedUrl() }}" title="{{ $meeting->title }}" frameborder="0" allowfullscreen></iframe>
            </div>
        @endif

        @if ($meeting->materials->count())
            <div class="mt-5">
                <h3 class="text-sm font-medium text-ink-700 mb-2">Materi Pembelajaran</h3>
                <div class="border border-ink-100 rounded-lg divide-y divide-ink-100">
                    @foreach ($meeting->materials as $material)
                        <a href="{{ route('materials.download', $material) }}" class="flex items-center justify-between px-4 py-2.5 hover:bg-ink-50/60">
                            <span class="text-sm text-ink-700">{{ $material->file_name }}</span>
                            <span class="text-xs text-ink-400">{{ $material->file_size }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- TAMPILAN KUIS (Dipindah ke dalam blok ini agar rapi) -->
        <div class="mt-6 border-t border-ink-100 pt-5 flex items-center justify-between">
            <h3 class="text-sm font-medium text-ink-700">Kuis Interaktif</h3>
            @if ($user->isAdmin() || $user->isTeacher())
                <a href="{{ route('quizzes.create', $meeting) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-medium px-3 py-1.5 rounded-lg inline-flex items-center transition">
                    + Buat Kuis Baru
                </a>
            @endif
        </div>
        
        @if ($meeting->quizzes->count() > 0)
            <div class="mt-3 space-y-3">
                @foreach($meeting->quizzes as $q)
                    <div class="flex items-center justify-between p-4 border border-ink-100 rounded-lg bg-ink-50/50">
                        <div>
                            <h4 class="font-medium text-ink-900">{{ $q->title }}</h4>
                            <p class="text-xs text-ink-500 mt-0.5">{{ $q->duration_minutes }} Menit &middot; {{ $q->questions->count() }} Soal</p>
                        </div>
                        @if($user->isStudent())
                            @php $myAttempt = $q->attempts()->where('student_id', $user->id)->first(); @endphp
                            @if($myAttempt && $myAttempt->completed_at)
                                <a href="{{ route('quizzes.result', [$q, $myAttempt]) }}" class="bg-mint-100 hover:bg-mint-200 text-mint-700 text-xs font-medium px-4 py-2 rounded-lg transition">Lihat Nilai</a>
                            @else
                                <a href="{{ route('quizzes.show', $q) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">Kerjakan Kuis</a>
                            @endif
                        @else
                            <a href="{{ route('quizzes.manage', $q) }}" class="bg-ink-900 text-white hover:bg-ink-700 text-xs font-medium px-4 py-2 rounded-lg transition">Kelola Soal</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            @if($user->isStudent())
                <p class="text-sm text-ink-400 mt-2 italic">Belum ada kuis untuk pertemuan ini.</p>
            @endif
        @endif
    </div>

    <!-- 2. KOTAK ABSENSI (Selalu Muncul untuk Guru/Admin) -->
    @if ($user->isAdmin() || $user->isTeacher())
        <div class="bg-white rounded-xl border border-ink-100 p-6 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h3 class="font-display font-semibold text-ink-900">Absensi Siswa</h3>
                
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- KOTAK FORM IMPOR -->
                    <form action="{{ route('meetings.attendance.import', $meeting) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-ink-50 px-2 py-1 rounded-lg border border-ink-100">
                        @csrf
                        <input type="file" name="file" accept=".csv" required 
                               class="text-xs file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-ink-200 file:text-ink-700 w-48 text-ink-600">
                        <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-3 py-1.5 rounded-md transition">
                            Impor CSV
                        </button>
                    </form>
                    
                    <!-- TOMBOL EKSPOR (UNDUH) -->
                    <a href="{{ route('meetings.attendance.export', $meeting) }}" class="text-xs px-3 py-1.5 rounded-lg bg-mint-100 text-mint-600 font-medium hover:bg-mint-200 transition h-[34px] flex items-center">
                        Unduh CSV
                    </a>
                </div>
            </div>
            
            <form method="POST" action="{{ route('meetings.attendance', $meeting) }}">
                @csrf
                <div class="border border-ink-100 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ink-50 text-ink-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama Siswa</th>
                                <th class="px-4 py-3 font-medium">NISN</th>
                                <th class="px-4 py-3 font-medium">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($class->students as $student)
                                @php
                                    $attRecord = \App\Models\Attendance::where('meeting_id', $meeting->id)
                                                                       ->where('student_id', $student->id)
                                                                       ->first();
                                    $currentStatus = $attRecord ? $attRecord->status : 'hadir';
                                @endphp
                                <tr class="hover:bg-ink-50/50 transition">
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $student->name }}</td>
                                    <td class="px-4 py-3 text-ink-400">{{ $student->nip_nis }}</td>
                                    <td class="px-4 py-3">
                                        <select name="attendances[{{ $student->id }}]" class="text-sm rounded-lg border border-ink-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-gold-400">
                                            <option value="hadir" {{ $currentStatus === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                            <option value="izin" {{ $currentStatus === 'izin' ? 'selected' : '' }}>Izin</option>
                                            <option value="sakit" {{ $currentStatus === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                            <option value="alpa" {{ $currentStatus === 'alpa' ? 'selected' : '' }}>Alpa</option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-ink-400 text-center">Belum ada siswa di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($class->students->count() > 0)
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                            Simpan Absensi
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif

    <!-- 3. KOTAK TUGAS (Hanya Muncul Jika Pertemuan Memiliki Tugas) -->
    @if ($meeting->has_assignment)
        <div class="bg-white rounded-xl border border-ink-100 p-6 mb-6">
            <h3 class="font-display font-semibold text-ink-900 mb-1">{{ $meeting->assignment_title }}</h3>
            <p class="text-sm text-ink-600 mt-2">{{ $meeting->assignment_instructions }}</p>
            
            @if ($meeting->assignment_deadline)
                <p class="text-xs {{ $meeting->isPastDeadline() ? 'text-rose-500' : 'text-ink-400' }} mt-3 font-medium">
                    Batas waktu: {{ $meeting->assignment_deadline->translatedFormat('d M Y, H:i') }} WIB
                </p>
            @endif

            @if ($user->isStudent())
                <!-- Tampilan Siswa: Formulir Pengumpulan Tugas -->
                <div class="border-t border-ink-100 mt-5 pt-5">
                    @if ($mySubmission)
                        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                            <div>
                                <a href="{{ route('submissions.download', $mySubmission) }}" class="text-sm font-medium text-ink-900 hover:underline">{{ $mySubmission->file_name }}</a>
                                <p class="text-xs text-ink-400 mt-0.5">Dikumpulkan {{ $mySubmission->submitted_at->translatedFormat('d M Y, H:i') }} WIB &middot;
                                    <span class="{{ $mySubmission->is_late ? 'text-rose-500' : 'text-mint-500' }}">{{ $mySubmission->statusLabel() }}</span>
                                </p>
                            </div>
                            @if ($mySubmission->isGraded())
                                <div class="text-right">
                                    <span class="text-lg font-display font-semibold text-mint-500">{{ $mySubmission->grade }}/100</span>
                                </div>
                            @else
                                <span class="text-xs px-2.5 py-1 rounded-full bg-ink-50 text-ink-400">Belum dinilai</span>
                            @endif
                        </div>
                        @if ($mySubmission->feedback)
                            <div class="bg-ink-50 rounded-lg px-4 py-3 text-sm text-ink-700 mb-4 border border-ink-100">
                                <span class="font-medium">Catatan pengajar:</span> {{ $mySubmission->feedback }}
                            </div>
                        @endif
                        <p class="text-xs text-ink-400 mb-2">Ingin mengumpulkan ulang? Unggah berkas baru di bawah ini (akan menggantikan berkas sebelumnya dan menghapus nilai lama).</p>
                    @endif

                    <form method="POST" action="{{ route('submissions.store', $meeting) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-ink-700 mb-1">Berkas Tugas</label>
                            <input type="file" name="file" required
                                   class="w-full text-sm border border-ink-100 rounded-lg px-3.5 py-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-ink-50 file:text-ink-700 file:text-xs">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-700 mb-1">Catatan <span class="text-ink-400 font-normal">(opsional)</span></label>
                            <textarea name="student_note" rows="2"
                                      class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400"></textarea>
                        </div>
                        <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                            {{ $mySubmission ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas' }}
                        </button>
                    </form>
                </div>
            @else
                <!-- Tampilan Guru: Daftar Pengumpulan Tugas Siswa -->
                <div class="border-t border-ink-100 mt-5 pt-5">
                    <h4 class="text-sm font-medium text-ink-700 mb-3">Pengumpulan Siswa ({{ $submissions->count() }})</h4>
                    <div class="space-y-4">
                        @forelse ($submissions as $submission)
                            <div class="border border-ink-100 rounded-lg p-4 bg-ink-50/30">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink-900">{{ $submission->student->name }}</p>
                                        <a href="{{ route('submissions.download', $submission) }}" class="text-xs text-ink-600 hover:underline">{{ $submission->file_name }}</a>
                                        <p class="text-xs text-ink-400 mt-0.5">
                                            {{ $submission->submitted_at->translatedFormat('d M Y, H:i') }} WIB &middot;
                                            <span class="{{ $submission->is_late ? 'text-rose-500' : 'text-mint-500' }}">{{ $submission->statusLabel() }}</span>
                                        </p>
                                        @if ($submission->student_note)
                                            <p class="text-xs text-ink-500 mt-1 italic">&ldquo;{{ $submission->student_note }}&rdquo;</p>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('submissions.grade', $submission) }}" class="flex items-end gap-3 mt-3 flex-wrap">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-ink-700 mb-1">Nilai (0-100)</label>
                                        <input type="number" min="0" max="100" name="grade" value="{{ $submission->grade }}" required
                                               class="w-24 rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                    </div>
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-xs font-medium text-ink-700 mb-1">Umpan Balik</label>
                                        <input type="text" name="feedback" value="{{ $submission->feedback }}"
                                               class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                    </div>
                                    <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                                        {{ $submission->isGraded() ? 'Perbarui Nilai' : 'Simpan Nilai' }}
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-ink-400">Belum ada siswa yang mengumpulkan tugas.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- 4. KOTAK WIDGET ASISTEN AI (Untuk Siswa & Guru) -->
    @if ($user->isStudent() || $user->isTeacher())
        <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col h-[400px] shadow-sm mt-8 mb-10">
            
            <div class="bg-ink-900 text-white px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded bg-gold-500 text-ink-900 flex items-center justify-center font-display font-semibold text-xs">AI</span>
                    <div>
                        <h3 class="font-display font-medium text-sm">
                            {{ $user->isTeacher() ? 'Asisten Penilai AI' : 'Asisten Belajar AI' }}
                        </h3>
                        <p class="text-[10px] text-ink-200 mt-0.5">
                            {{ $user->isTeacher() ? 'Bantu evaluasi dan buat umpan balik tugas' : 'Tanyakan materi pertemuan ini' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div id="ai-chat-history" class="flex-1 p-5 overflow-y-auto space-y-4 bg-ink-50/40">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-ink-100 flex items-center justify-center text-ink-600 text-xs font-bold font-display">AI</div>
                    <div class="bg-white border border-ink-100 rounded-2xl rounded-tl-none px-4 py-2.5 text-sm text-ink-700 shadow-sm max-w-[85%]">
                        @if ($user->isTeacher())
                            Halo Guru! Ada tugas siswa yang ingin didiskusikan? Salin jawaban siswa ke sini, dan saya akan membantu menyusun umpan balik (feedback) atau mengevaluasinya berdasarkan materi.
                        @else
                            Halo! Ada bagian dari materi <strong>{{ $meeting->title }}</strong> yang kurang jelas? Tanyakan saja padaku di sini.
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-3 bg-white border-t border-ink-100">
                <form id="ai-chat-form" data-url="{{ route('meetings.ask-ai', $meeting) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" id="ai-chat-input" required 
                           placeholder="{{ $user->isTeacher() ? 'Ketik pertanyaan atau salin jawaban siswa...' : 'Ketik pertanyaanmu...' }}" 
                           autocomplete="off"
                           class="flex-1 rounded-lg border border-ink-100 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <button type="submit" id="ai-chat-submit" class="bg-gold-500 hover:bg-gold-600 text-ink-900 px-4 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap">
                        Kirim
                    </button>
                </form>
            </div>

        </div>
    @endif
@endsection