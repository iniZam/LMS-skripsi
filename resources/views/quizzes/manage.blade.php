@extends('layouts.app')

@section('title', 'Kelola Soal: ' . $quiz->title)

@section('content')
@php
    $user = auth()->user();
    $classRoom = $quiz->classRoom ?? $quiz->meeting?->classRoom;
    $typeBadge = match ($quiz->type) {
        'uts' => 'bg-blue-100 text-blue-700',
        'uas' => 'bg-purple-100 text-purple-700',
        default => 'bg-gold-100 text-gold-600',
    };
    $approvalBadge = match ($quiz->approval_status) {
        'approved' => 'bg-mint-100 text-mint-700',
        'pending' => 'bg-gold-100 text-gold-600',
        'rejected' => 'bg-rose-100 text-rose-500',
        default => 'bg-ink-100 text-ink-600',
    };
@endphp
<div class="max-w-4xl mx-auto mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase {{ $typeBadge }}">
                    {{ $quiz->shortTypeLabel() }}
                </span>
                @if ($quiz->isExam())
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $approvalBadge }}">
                        {{ $quiz->approvalStatusLabel() }}
                    </span>
                @endif
                @if ($classRoom)
                    <span class="text-xs text-ink-400">
                        {{ $classRoom->title }}
                        @if ($quiz->meeting)
                            &middot; Pertemuan {{ $quiz->meeting->meeting_number }}
                        @endif
                    </span>
                @endif
            </div>
            <h2 class="font-display font-semibold text-2xl text-ink-900">{{ $quiz->title }}</h2>
            <p class="text-sm text-ink-500 mt-0.5">
                Durasi: {{ $quiz->duration_minutes }} menit &middot; Total Soal: {{ $quiz->questions->count() }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($user->isAdmin() && $quiz->isExam() && $quiz->approval_status !== 'approved')
                <form method="POST" action="{{ route('quizzes.approve', $quiz) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="bg-mint-500 hover:bg-mint-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition cursor-pointer">
                        &#10003; ACC &amp; Rilis Ujian
                    </button>
                </form>
            @endif
            @if ($quiz->meeting_id)
                <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="bg-ink-100 text-ink-700 hover:bg-ink-200 text-xs font-medium px-3.5 py-2 rounded-lg">
                    Lihat Pertemuan
                </a>
            @elseif ($quiz->class_id)
                <a href="{{ route('classes.show', $quiz->class_id) }}" class="bg-ink-100 text-ink-700 hover:bg-ink-200 text-xs font-medium px-3.5 py-2 rounded-lg">
                    Lihat Kelas
                </a>
            @endif
            <a href="{{ route('quizzes.index') }}" class="bg-mint-100 text-mint-700 hover:bg-mint-200 text-xs font-semibold px-4 py-2 rounded-lg">
                Selesai &amp; Kembali ke Daftar
            </a>
        </div>
    </div>

    @if ($quiz->isExam() && $quiz->approval_status === 'pending')
        <div class="mb-6 rounded-xl bg-gold-100/60 border border-gold-400/50 px-4 py-3 text-xs text-ink-800 flex items-center justify-between gap-3">
            <div>
                <strong>Status Ujian: Menunggu Persetujuan (ACC) Admin.</strong>
                Ujian {{ $quiz->shortTypeLabel() }} ini belum tampil di halaman siswa sampai disetujui oleh Admin. Anda tetap dapat menambah atau mengelola soal ujian sekarang.
            </div>
        </div>
    @elseif ($quiz->isExam() && $quiz->approval_status === 'rejected')
        <div class="mb-6 rounded-xl bg-rose-100/70 border border-rose-300 px-4 py-3 text-xs text-rose-700">
            <strong>Status Ujian: Ditolak Admin.</strong> Silakan perbaiki soal atau detail ujian dan hubungi Admin untuk persetujuan ulang.
        </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Tambah Soal -->
        <div class="md:col-span-1 bg-white rounded-xl border border-ink-100 p-5 shadow-sm h-fit sticky top-20">
            <h3 class="font-medium text-ink-900 mb-4 border-b border-ink-100 pb-2">Tambah Soal Baru</h3>
            
            <form action="{{ route('questions.store', $quiz) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Pertanyaan</label>
                    <textarea name="question_text" rows="3" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-400"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Bobot Nilai</label>
                    <input type="number" name="points" value="10" required min="1" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-400">
                </div>

                <div class="space-y-3 pt-2">
                    <label class="block text-xs font-medium text-ink-700">Pilihan Jawaban (Pilih radio button untuk jawaban benar)</label>
                    @for($i = 0; $i < 4; $i++)
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_option" value="{{ $i }}" {{ $i==0 ? 'required' : '' }} class="text-gold-500 focus:ring-gold-400">
                        <input type="text" name="options[]" placeholder="Pilihan {{ ['A','B','C','D'][$i] }}" required class="flex-1 rounded-lg border border-ink-100 px-3 py-1.5 text-sm focus:ring-2 focus:ring-gold-400">
                    </div>
                    @endfor
                </div>

                <button type="submit" class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg mt-4 cursor-pointer">
                    Simpan Soal
                </button>
            </form>
        </div>

        <!-- Kolom Kanan: Daftar Soal yang Sudah Dibuat -->
        <div class="md:col-span-2 space-y-4">
            @forelse ($quiz->questions as $index => $question)
                <div class="bg-white rounded-xl border border-ink-100 p-5 shadow-sm">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <h4 class="text-sm font-medium text-ink-900">
                            <span class="text-gold-600 mr-1">{{ $index + 1 }}.</span> {{ $question->question_text }}
                        </h4>
                        <span class="text-xs font-medium bg-ink-50 text-ink-500 px-2 py-1 rounded">{{ $question->points }} Poin</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm pl-5">
                        @foreach ($question->options as $optIndex => $option)
                            <div class="p-2 rounded border {{ $option->is_correct ? 'bg-mint-50 border-mint-200 text-mint-800 font-medium' : 'bg-white border-ink-100 text-ink-600' }}">
                                {{ ['A','B','C','D'][$optIndex] }}. {{ $option->option_text }}
                                @if($option->is_correct) <span class="float-right">✓</span> @endif
                            </div>
                        @endforeach
                    </div>

                    <form action="{{ route('questions.destroy', $question) }}" method="POST" class="mt-4 text-right border-t border-ink-100 pt-3">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Hapus soal ini?')" class="text-xs text-rose-500 hover:text-rose-700 font-medium cursor-pointer">Hapus Soal</button>
                    </form>
                </div>
            @empty
                <div class="bg-ink-50 border border-ink-100 rounded-xl p-8 text-center">
                    <p class="text-ink-500 text-sm">Belum ada soal untuk {{ strtolower($quiz->shortTypeLabel()) }} ini. Silakan buat soal di panel sebelah kiri.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
