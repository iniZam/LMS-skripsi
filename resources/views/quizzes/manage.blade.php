@extends('layouts.app')

@section('title', 'Kelola Soal: ' . $quiz->title)

@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-display font-semibold text-2xl text-ink-900">{{ $quiz->title }}</h2>
            <p class="text-sm text-ink-500">Durasi: {{ $quiz->duration_minutes }} menit &middot; Total Soal: {{ $quiz->questions->count() }}</p>
        </div>
        <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="bg-mint-100 text-mint-700 hover:bg-mint-200 text-sm font-medium px-4 py-2 rounded-lg">Selesai & Kembali</a>
    </div>

    @if (session('status'))
        <div class="bg-mint-50 text-mint-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium border border-mint-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Tambah Soal -->
        <div class="md:col-span-1 bg-white rounded-xl border border-ink-100 p-5 shadow-sm h-fit sticky top-4">
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
                    <!-- Looping 4 Pilihan -->
                    @for($i = 0; $i < 4; $i++)
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_option" value="{{ $i }}" {{ $i==0 ? 'required' : '' }} class="text-gold-500 focus:ring-gold-400">
                        <input type="text" name="options[]" placeholder="Pilihan {{ ['A','B','C','D'][$i] }}" required class="flex-1 rounded-lg border border-ink-100 px-3 py-1.5 text-sm focus:ring-2 focus:ring-gold-400">
                    </div>
                    @endfor
                </div>

                <button type="submit" class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-4 py-2 rounded-lg mt-4">
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
                        <button type="submit" onclick="return confirm('Hapus soal ini?')" class="text-xs text-rose-500 hover:text-rose-700 font-medium">Hapus Soal</button>
                    </form>
                </div>
            @empty
                <div class="bg-ink-50 border border-ink-100 rounded-xl p-8 text-center">
                    <p class="text-ink-500 text-sm">Belum ada soal untuk kuis ini. Silakan buat soal di panel sebelah kiri.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection