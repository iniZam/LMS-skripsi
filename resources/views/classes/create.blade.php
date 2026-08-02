@extends('layouts.app')

@section('title', 'Buat Mata Pelajaran Baru')

@section('content')
    <div class="max-w-2xl bg-white rounded-xl border border-ink-100 p-6">
        <form method="POST" action="{{ route('classes.store') }}" class="space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Kode Mata Pelajaran</label>
                    <input type="text" name="code" value="{{ old('code') }}" required placeholder="MTK-7A"
                           class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Kategori Mata Pelajaran</label>
                    <input type="text" name="subject_category" value="{{ old('subject_category') }}" placeholder="Matematika"
                           class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Nama Mata Pelajaran</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">{{ old('description') }}</textarea>
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Pengajar</label>
                    <select name="teacher_id" required class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="">Pilih pengajar&hellip;</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="registration">Pendaftaran Dibuka</option>
                        <option value="active">Sedang Berlangsung</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Tahun Ajaran</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year', '2026/2027 Ganjil') }}"
                           class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('classes.index') }}" class="px-4 py-2.5 text-sm font-medium text-ink-600">Batal</a>
                <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">Buat Mata Pelajaran</button>
            </div>
        </form>
    </div>
@endsection
