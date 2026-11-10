@extends('layouts.app')

@section('title', 'Buat UTS / UAS Baru')

@section('content')
<div class="max-w-2xl mx-auto mt-4">
    <a href="{{ route('quizzes.index') }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Kelola Kuis &amp; Ujian</a>

    <div class="bg-white rounded-xl border border-ink-100 p-6 shadow-sm">
        <div class="mb-5 border-b border-ink-100 pb-4">
            <h2 class="font-display font-semibold text-xl text-ink-900">Buat Ujian Baru (UTS / UAS)</h2>
            <p class="text-xs text-ink-500 mt-1">
                Ujian Tengah Semester (UTS) dan Ujian Akhir Semester (UAS) memiliki sistem soal pilihan ganda dan timer otomatis seperti kuis, namun memerlukan <strong>persetujuan (ACC) Admin</strong> sebelum dirilis ke siswa.
            </p>
        </div>

        @if (!auth()->user()->isAdmin())
            <div class="mb-5 rounded-xl bg-gold-100/60 border border-gold-400/40 px-4 py-3 text-xs text-ink-800 flex items-start gap-2.5">
                <span class="text-base leading-none">🔒</span>
                <div>
                    <strong class="font-semibold">Informasi Persetujuan Admin:</strong>
                    Setelah Anda membuat UTS/UAS dan menambahkan soal, status ujian akan menjadi <strong>Menunggu ACC Admin</strong>. Siswa baru dapat melihat dan mengerjakan ujian setelah disetujui oleh Admin.
                </div>
            </div>
        @endif

        <form action="{{ route('exams.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-2">Jenis Ujian</label>
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border border-ink-100 hover:bg-ink-50/60 cursor-pointer transition">
                        <input type="radio" name="type" value="uts" @checked(old('type', $defaultType ?? 'uts') === 'uts') class="mt-0.5 text-gold-500 focus:ring-gold-400" required>
                        <div>
                            <span class="block text-sm font-semibold text-ink-900">UTS (Ujian Tengah Semester)</span>
                            <span class="block text-xs text-ink-400 mt-0.5">Evaluasi pertengahan semester (Wajib ACC Admin)</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border border-ink-100 hover:bg-ink-50/60 cursor-pointer transition">
                        <input type="radio" name="type" value="uas" @checked(old('type', $defaultType ?? 'uts') === 'uas') class="mt-0.5 text-gold-500 focus:ring-gold-400">
                        <div>
                            <span class="block text-sm font-semibold text-ink-900">UAS (Ujian Akhir Semester)</span>
                            <span class="block text-xs text-ink-400 mt-0.5">Evaluasi akhir semester (Wajib ACC Admin)</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Mata Pelajaran / Kelas</label>
                <select name="class_id" id="exam-class-select" required class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach ($classes as $cls)
                        <option value="{{ $cls->id }}" data-meetings='@json($cls->meetings->map(fn($m) => ["id" => $m->id, "number" => $m->meeting_number, "title" => $m->title]))' @selected(old('class_id', $selectedClassId) == $cls->id)>
                            {{ $cls->title }} ({{ $cls->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">
                    Tautkan ke Pertemuan <span class="text-ink-400 font-normal">(Opsional)</span>
                </label>
                <select name="meeting_id" id="exam-meeting-select" class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">-- Tidak ditautkan ke pertemuan tertentu (Ujian Kelas) --</option>
                </select>
                <p class="text-[11px] text-ink-400 mt-1">Jika dikosongkan, UTS/UAS akan langsung tampil pada halaman Mata Pelajaran dan menu Kuis &amp; Ujian.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Judul Ujian</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Ujian Tengah Semester (UTS) Ganjil Matematika"
                       class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi / Petunjuk Pengerjaan</label>
                <textarea name="description" rows="3" placeholder="Tuliskan tata tertib atau petunjuk pengerjaan ujian..."
                          class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">{{ old('description') }}</textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Durasi Pengerjaan (Menit)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 90) }}" required min="1"
                           class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Batas Waktu (Opsional)</label>
                    <input type="datetime-local" name="deadline" value="{{ old('deadline') }}"
                           class="w-full rounded-lg border border-ink-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100 flex items-center justify-end gap-3">
                <a href="{{ route('quizzes.index') }}" class="text-sm text-ink-500 hover:text-ink-800 px-4 py-2">Batal</a>
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition cursor-pointer">
                    Simpan &amp; Lanjut Buat Soal Ujian
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const classSelect = document.getElementById('exam-class-select');
        const meetingSelect = document.getElementById('exam-meeting-select');

        function populateMeetings() {
            const selectedOption = classSelect.options[classSelect.selectedIndex];
            meetingSelect.innerHTML = '<option value="">-- Tidak ditautkan ke pertemuan tertentu (Ujian Kelas) --</option>';

            if (!selectedOption || !selectedOption.dataset.meetings) return;

            try {
                const meetings = JSON.parse(selectedOption.dataset.meetings);
                meetings.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = `Pertemuan ${m.number}: ${m.title}`;
                    meetingSelect.appendChild(opt);
                });
            } catch (e) {}
        }

        classSelect.addEventListener('change', populateMeetings);
        populateMeetings();
    });
</script>
@endsection
