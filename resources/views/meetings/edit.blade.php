@extends('layouts.app')

@section('title', 'Edit Pertemuan')

@section('content')
    <div class="max-w-2xl bg-white rounded-xl border border-ink-100 p-6">
        <p class="text-xs font-medium text-gold-600 mb-4">{{ $class->title }} &middot; Pertemuan {{ $meeting->meeting_number }}</p>

        @if ($meeting->materials->count())
            <div class="mb-5 border border-ink-100 rounded-lg divide-y divide-ink-100">
                @foreach ($meeting->materials as $material)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <span class="text-sm text-ink-700 truncate">{{ $material->file_name }}</span>
                        <form method="POST" action="{{ route('materials.destroy', $material) }}" data-confirm="Hapus berkas {{ $material->file_name }}?">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-rose-500 hover:underline font-medium">Hapus</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('meetings.update', $meeting) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Judul Pertemuan</label>
                <input type="text" name="title" value="{{ old('title', $meeting->title) }}" required
                       class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">{{ old('description', $meeting->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">URL Video YouTube</label>
                <input type="url" name="youtube_url" value="{{ old('youtube_url', $meeting->youtube_url) }}"
                       class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Tambah Materi (Berkas) <span class="text-ink-400 font-normal">(opsional)</span></label>
                <input type="file" name="materials[]" multiple
                       class="w-full text-sm border border-ink-100 rounded-lg px-3.5 py-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-ink-50 file:text-ink-700 file:text-xs">
            </div>

            <div class="border-t border-ink-100 pt-4">
                <label class="flex items-center gap-2 text-sm font-medium text-ink-700">
                    <input type="checkbox" id="has_assignment" name="has_assignment" value="1" @checked(old('has_assignment', $meeting->has_assignment))
                           class="rounded border-ink-100 text-gold-500 focus:ring-gold-400">
                    Pertemuan ini memiliki tugas
                </label>

                <div id="assignment-fields" class="space-y-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-700 mb-1">Judul Tugas</label>
                        <input type="text" name="assignment_title" value="{{ old('assignment_title', $meeting->assignment_title) }}"
                               class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-700 mb-1">Instruksi Tugas</label>
                        <textarea name="assignment_instructions" rows="3"
                                  class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">{{ old('assignment_instructions', $meeting->assignment_instructions) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-700 mb-1">Batas Waktu Pengumpulan</label>
                        <input type="datetime-local" name="assignment_deadline"
                               value="{{ old('assignment_deadline', optional($meeting->assignment_deadline)->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-2">
                <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" data-confirm="Hapus pertemuan ini beserta seluruh materi dan tugas terkait?">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-rose-500 font-medium hover:underline">Hapus Pertemuan</button>
                </form>
                <div class="flex gap-3">
                    <a href="{{ route('classes.show', $class) }}" class="px-4 py-2.5 text-sm font-medium text-ink-600">Batal</a>
                    <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
@endsection
