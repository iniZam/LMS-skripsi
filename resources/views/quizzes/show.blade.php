@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Pertemuan</a>
    <div class="bg-white rounded-xl border border-ink-100 p-8 shadow-sm text-center">
        <h2 class="font-display font-semibold text-2xl text-ink-900 mb-2">{{ $quiz->title }}</h2>
        <p class="text-sm text-ink-600 mb-6">{{ $quiz->description }}</p>
        
        <div class="flex justify-center gap-6 mb-8 text-sm text-ink-700 bg-ink-50 py-3 rounded-lg w-fit mx-auto px-6">
            <div><span class="font-bold">{{ $quiz->questions->count() }}</span> Soal</div>
            <div>&middot;</div>
            <div>Waktu: <span class="font-bold">{{ $quiz->duration_minutes }}</span> Menit</div>
        </div>

        @if ($attempt && $attempt->completed_at)
            <div class="bg-mint-50 border border-mint-200 text-mint-800 p-4 rounded-lg mb-4">
                Anda sudah menyelesaikan kuis ini.
            </div>
            <a href="{{ route('quizzes.result', [$quiz, $attempt]) }}" class="bg-ink-900 hover:bg-ink-700 text-white font-medium px-6 py-3 rounded-lg inline-block">Lihat Nilai</a>
        @else
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs p-3 rounded-lg mb-6 text-left">
                <strong>Peringatan:</strong> Waktu akan mulai berjalan saat Anda menekan tombol di bawah. Jika keluar sebelum selesai, nilai akan tersimpan otomatis saat waktu habis.
            </div>
            <form action="{{ route('quizzes.start', $quiz) }}" method="POST">
                @csrf
                <button type="submit" class="bg-gold-500 hover:bg-gold-600 text-ink-900 font-bold px-8 py-3 rounded-lg text-lg shadow-sm">
                    Mulai Kerjakan Kuis
                </button>
            </form>
        @endif
    </div>
</div>
@endsection