@extends('layouts.app')
@section('title', 'Mengerjakan: ' . $quiz->title)
@section('content')
<div class="max-w-4xl mx-auto mt-4">
    <!-- Header Mengambang untuk Timer -->
    <div class="sticky top-0 z-50 bg-white border border-ink-200 shadow-md rounded-xl p-4 mb-6 flex justify-between items-center">
        <div>
            <h3 class="font-bold text-ink-900">{{ $quiz->title }}</h3>
            <p class="text-xs text-ink-500">{{ auth()->user()->name }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-ink-500 font-medium mb-0.5">Sisa Waktu</p>
            <div id="timer-display" class="font-display font-bold text-xl text-rose-600 font-mono tracking-wider">
                --:--
            </div>
        </div>
    </div>

    <!-- Lembar Soal -->
    <form id="quiz-form" action="{{ route('quizzes.submit', [$quiz, $attempt]) }}" method="POST" class="space-y-6">
        @csrf
        @foreach ($quiz->questions as $index => $question)
            <div class="bg-white rounded-xl border border-ink-100 p-6 shadow-sm">
                <h4 class="font-medium text-ink-900 mb-4">
                    {{ $index + 1 }}. {{ $question->question_text }}
                </h4>
                <div class="space-y-3 pl-4">
                    @foreach ($question->options as $option)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-ink-100 hover:bg-ink-50 cursor-pointer transition">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="w-4 h-4 text-gold-500 focus:ring-gold-400">
                            <span class="text-sm text-ink-700">{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="bg-ink-900 rounded-xl p-6 text-center shadow-md">
            <p class="text-ink-200 text-sm mb-4">Pastikan semua jawaban telah terisi sebelum mengumpulkan.</p>
            <button type="submit" onclick="return confirm('Yakin ingin mengumpulkan jawaban sekarang?')" class="bg-gold-500 hover:bg-gold-600 text-ink-900 font-bold px-8 py-3 rounded-lg">
                Kumpulkan Jawaban
            </button>
        </div>
    </form>
</div>

<!-- Logika Hitung Mundur Javascript -->
<script>
    const durationMinutes = {{ $quiz->duration_minutes }};
    const startTime = new Date("{{ $attempt->started_at->toIso8601String() }}").getTime();
    const endTime = startTime + (durationMinutes * 60 * 1000);

    const timerInterval = setInterval(function() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance <= 0) {
            clearInterval(timerInterval);
            document.getElementById('timer-display').innerHTML = "WAKTU HABIS";
            alert('Waktu Anda telah habis! Jawaban akan dikumpulkan otomatis.');
            document.getElementById('quiz-form').submit(); 
        } else {
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            document.getElementById('timer-display').innerHTML = minutes + "m " + seconds + "s";
            
            if(minutes < 5) {
                document.getElementById('timer-display').classList.add('animate-pulse');
            }
        }
    }, 1000);
</script>
@endsection