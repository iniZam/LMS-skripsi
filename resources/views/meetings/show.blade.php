@extends('layouts.app')

@section('title', $meeting->title)

@section('content')
    @php $user = auth()->user(); @endphp

    <a href="{{ route('classes.show', $class) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; {{ $class->title }}</a>

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
    </div>

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
                            <div class="bg-ink-50 rounded-lg px-4 py-3 text-sm text-ink-700 mb-4">
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
                        <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">
                            {{ $mySubmission ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas' }}
                        </button>
                    </form>
                </div>
            @else
                <div class="border-t border-ink-100 mt-5 pt-5">
                    <h4 class="text-sm font-medium text-ink-700 mb-3">Pengumpulan Siswa ({{ $submissions->count() }})</h4>
                    <div class="space-y-4">
                        @forelse ($submissions as $submission)
                            <div class="border border-ink-100 rounded-lg p-4">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink-900">{{ $submission->student->name }}</p>
                                        <a href="{{ route('submissions.download', $submission) }}" class="text-xs text-ink-600 hover:underline">{{ $submission->file_name }}</a>
                                        <p class="text-xs text-ink-400 mt-0.5">
                                            {{ $submission->submitted_at->translatedFormat('d M Y, H:i') }} WIB &middot;
                                            <span class="{{ $submission->is_late ? 'text-rose-500' : 'text-mint-500' }}">{{ $submission->statusLabel() }}</span>
                                        </p>
                                        @if ($submission->student_note)
                                            <p class="text-xs text-ink-400 mt-1 italic">&ldquo;{{ $submission->student_note }}&rdquo;</p>
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
                                    <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
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

    @if ($user->isStudent())
        <!-- Widget Asisten AI (Tutor Chatbot) -->
        <div class="bg-white rounded-xl border border-ink-100 overflow-hidden flex flex-col h-[400px] shadow-sm">
            
            <!-- Bagian Header (Kepala) -->
            <div class="bg-ink-900 text-white px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded bg-gold-500 text-ink-900 flex items-center justify-center font-display font-semibold text-xs">AI</span>
                    <div>
                        <h3 class="font-display font-medium text-sm">Asisten Belajar AI</h3>
                        <p class="text-[10px] text-ink-200 mt-0.5">Tanyakan materi pertemuan ini</p>
                    </div>
                </div>
            </div>
            
            <!-- Area Riwayat Obrolan -->
            <div id="ai-chat-history" class="flex-1 p-5 overflow-y-auto space-y-4 bg-ink-50/40">
                <!-- Pesan Pembuka dari AI -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-ink-100 flex items-center justify-center text-ink-600 text-xs font-bold font-display">AI</div>
                    <div class="bg-white border border-ink-100 rounded-2xl rounded-tl-none px-4 py-2.5 text-sm text-ink-700 shadow-sm max-w-[85%]">
                        Halo! Ada bagian dari materi <strong>{{ $meeting->title }}</strong> yang kurang jelas? Tanyakan saja padaku di sini.
                    </div>
                </div>
            </div>

            <!-- Area Input Pesan -->
            <div class="p-3 bg-white border-t border-ink-100">
                <form id="ai-chat-form" data-url="{{ route('meetings.ask-ai', $meeting) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" id="ai-chat-input" required placeholder="Ketik pertanyaanmu..." autocomplete="off"
                           class="flex-1 rounded-lg border border-ink-100 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <button type="submit" id="ai-chat-submit" class="bg-gold-500 hover:bg-gold-600 text-ink-900 px-4 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap">
                        Kirim
                    </button>
                </form>
            </div>

        </div>
    @endif
@endsection