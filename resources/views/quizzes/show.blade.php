@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    @if ($quiz->meeting_id)
        <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Pertemuan</a>
    @elseif ($quiz->class_id)
        <a href="{{ route('classes.show', $quiz->class_id) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Mata Pelajaran</a>
    @else
        <a href="{{ route('quizzes.index') }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Daftar Kuis &amp; Ujian</a>
    @endif
    <div class="bg-white rounded-xl border border-ink-100 p-8 shadow-sm text-center">
        <span class="inline-block text-xs font-bold uppercase px-3 py-1 rounded-full bg-gold-100 text-gold-600 mb-2">
            {{ $quiz->typeLabel() }}
        </span>
        <h2 class="font-display font-semibold text-2xl text-ink-900 mb-2">{{ $quiz->title }}</h2>
        <p class="text-sm text-ink-600 mb-6">{{ $quiz->description }}</p>
        
        <div class="flex justify-center gap-6 mb-8 text-sm text-ink-700 bg-ink-50 py-3 rounded-lg w-fit mx-auto px-6">
            <div><span class="font-bold">{{ $quiz->questions->count() }}</span> Soal</div>
            <div>&middot;</div>
            <div>Waktu: <span class="font-bold">{{ $quiz->duration_minutes }}</span> Menit</div>
        </div>

        @if ($attempt && $attempt->completed_at)
            <div class="bg-mint-50 border border-mint-200 text-mint-800 p-4 rounded-lg mb-4">
                Anda sudah menyelesaikan {{ strtolower($quiz->shortTypeLabel()) }} ini.
            </div>
            <a href="{{ route('quizzes.result', [$quiz, $attempt]) }}" class="bg-ink-900 hover:bg-ink-700 text-white font-medium px-6 py-3 rounded-lg inline-block">Lihat Nilai</a>
        @elseif ($attempt && !$attempt->completed_at)
            <div class="bg-gold-50 border border-gold-200 text-gold-800 p-4 rounded-lg mb-4 text-left">
                <p class="font-bold text-sm">Pengerjaan sedang berlangsung!</p>
                <p class="text-xs text-gold-700 mt-1">Anda sudah memulai {{ strtolower($quiz->shortTypeLabel()) }} ini sebelumnya. Silakan klik tombol di bawah untuk melanjutkan pengerjaan.</p>
            </div>
            <a href="{{ route('quizzes.take', [$quiz, $attempt]) }}" class="bg-gold-500 hover:bg-gold-600 text-ink-900 font-bold px-8 py-3 rounded-lg text-lg shadow-sm inline-block">
                Lanjutkan Kerjakan {{ $quiz->shortTypeLabel() }} &rarr;
            </a>
        @else
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs p-3 rounded-lg mb-6 text-left">
                <strong>Peringatan:</strong> Waktu akan mulai berjalan saat Anda menekan tombol di bawah. Jika keluar sebelum selesai, nilai akan tersimpan otomatis saat waktu habis.
            </div>
            <form action="{{ route('quizzes.start', $quiz) }}" method="POST">
                @csrf
                <button type="submit" class="bg-gold-500 hover:bg-gold-600 text-ink-900 font-bold px-8 py-3 rounded-lg text-lg shadow-sm cursor-pointer">
                    Mulai Kerjakan {{ $quiz->shortTypeLabel() }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection