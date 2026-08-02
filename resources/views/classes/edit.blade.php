@extends('layouts.app')

@section('title', 'Edit Mata Pelajaran')

@section('content')
    <div class="max-w-2xl bg-white rounded-xl border border-ink-100 p-6">
        <form method="POST" action="{{ route('classes.update', $class) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Nama Mata Pelajaran</label>
                <input type="text" name="title" value="{{ old('title', $class->title) }}" required
                       class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Kategori Mata Pelajaran</label>
                <input type="text" name="subject_category" value="{{ old('subject_category', $class->subject_category) }}"
                       class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">{{ old('description', $class->description) }}</textarea>
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Pengajar</label>
                    <select name="teacher_id" class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($class->teacher_id == $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="registration" @selected($class->status === 'registration')>Pendaftaran Dibuka</option>
                        <option value="active" @selected($class->status === 'active')>Sedang Berlangsung</option>
                        <option value="completed" @selected($class->status === 'completed')>Selesai</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1">Tahun Ajaran</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year', $class->academic_year) }}"
                           class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('classes.show', $class) }}" class="px-4 py-2.5 text-sm font-medium text-ink-600">Batal</a>
                <button class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
