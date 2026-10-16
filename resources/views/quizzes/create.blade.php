@extends('layouts.app')

@section('title', 'Buat Kuis Baru')

@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Pertemuan</a>

    <div class="bg-white rounded-xl border border-ink-100 p-6 shadow-sm">
        <h2 class="font-display font-semibold text-xl text-ink-900 mb-4">Buat Kuis Baru</h2>

        <form action="{{ route('quizzes.store', $meeting) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Judul Kuis</label>
                <input type="text" name="title" required class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi / Petunjuk</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" value="60" required min="1" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Batas Waktu (Opsional)</label>
                    <input type="datetime-local" name="deadline" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100 flex justify-end">
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">
                    Simpan & Lanjut Buat Soal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection