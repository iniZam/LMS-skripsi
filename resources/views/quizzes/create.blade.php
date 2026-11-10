@extends('layouts.app')

@section('title', 'Buat Kuis / Ujian Baru')

@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Pertemuan</a>

    <div class="bg-white rounded-xl border border-ink-100 p-6 shadow-sm">
        <h2 class="font-display font-semibold text-xl text-ink-900 mb-1">Buat Kuis / Ujian (UTS &amp; UAS)</h2>
        <p class="text-xs text-ink-400 mb-5">
            {{ $meeting->classRoom->title }} &middot; Pertemuan {{ $meeting->meeting_number }}: {{ $meeting->title }}
        </p>

        <form action="{{ route('quizzes.store', $meeting) }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-2">Kategori Evaluasi</label>
                <div class="grid sm:grid-cols-3 gap-3">
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-ink-100 hover:bg-ink-50 cursor-pointer">
                        <input type="radio" name="type" value="quiz" @checked(old('type', $defaultType ?? 'quiz') === 'quiz') class="mt-0.5 text-gold-500 focus:ring-gold-400">
                        <div>
                            <span class="block text-sm font-semibold text-ink-900">Kuis Harian</span>
                            <span class="block text-[11px] text-ink-400">Langsung rilis ke siswa</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-ink-100 hover:bg-ink-50 cursor-pointer">
                        <input type="radio" name="type" value="uts" @checked(old('type', $defaultType ?? 'quiz') === 'uts') class="mt-0.5 text-gold-500 focus:ring-gold-400">
                        <div>
                            <span class="block text-sm font-semibold text-ink-900">UTS</span>
                            <span class="block text-[11px] text-gold-600 font-medium">Perlu ACC Admin</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-ink-100 hover:bg-ink-50 cursor-pointer">
                        <input type="radio" name="type" value="uas" @checked(old('type', $defaultType ?? 'quiz') === 'uas') class="mt-0.5 text-gold-500 focus:ring-gold-400">
                        <div>
                            <span class="block text-sm font-semibold text-ink-900">UAS</span>
                            <span class="block text-[11px] text-gold-600 font-medium">Perlu ACC Admin</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Judul Kuis / Ujian</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Kuis Bab 1 / UTS Semester Ganjil"
                       class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi / Petunjuk</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" required min="1" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Batas Waktu (Opsional)</label>
                    <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100 flex justify-end">
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg cursor-pointer">
                    Simpan &amp; Lanjut Buat Soal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
