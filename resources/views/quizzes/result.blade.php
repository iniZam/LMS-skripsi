@extends('layouts.app')
@section('title', 'Nilai Kuis: ' . $quiz->title)
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <a href="{{ route('meetings.show', $quiz->meeting_id) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Pertemuan</a>
    
    <div class="bg-white rounded-xl border border-ink-100 p-8 shadow-sm text-center">
        <h2 class="font-display font-semibold text-xl text-ink-900 mb-6">Hasil Pengerjaan Kuis</h2>
        
        <div class="w-32 h-32 mx-auto rounded-full border-4 flex items-center justify-center mb-4 {{ $attempt->score >= 70 ? 'border-mint-500 text-mint-500' : 'border-rose-500 text-rose-500' }}">
            <span class="font-display font-bold text-4xl">{{ round($attempt->score) }}</span>
        </div>
        
        <h3 class="font-bold text-ink-900 text-lg mb-1">{{ $attempt->student->name }}</h3>
        <p class="text-sm text-ink-500 mb-6">Diselesaikan pada {{ $attempt->completed_at->translatedFormat('d M Y, H:i') }}</p>
        
        @if ($attempt->score >= 70)
            <p class="text-mint-700 font-medium bg-mint-50 py-2 rounded-lg">Luar Biasa! Anda telah menguasai materi ini.</p>
        @else
            <p class="text-rose-700 font-medium bg-rose-50 py-2 rounded-lg">Tetap Semangat! Coba pelajari ulang materi ini.</p>
        @endif
    </div>
</div>
@endsection