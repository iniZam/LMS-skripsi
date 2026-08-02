@extends('layouts.app')

@section('title', 'Kelas & Wali Kelas')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="font-display font-semibold text-ink-900 mb-4">Daftar Kelas (Rombel)</h2>

            <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
                @forelse ($kelasList as $kelas)
                    <details class="group">
                        <summary class="flex items-center justify-between px-5 py-4 cursor-pointer list-none">
                            <div>
                                <p class="text-sm font-medium text-ink-900">{{ $kelas->label() }}</p>
                                <p class="text-xs text-ink-400 mt-0.5">
                                    Wali Kelas: {{ $kelas->waliKelas?->name ?? '-' }} &middot;
                                    {{ $kelas->siswa_count }} siswa &middot; {{ $kelas->tahun_ajaran }}
                                </p>
                            </div>
                            <span class="text-xs text-ink-400 group-open:hidden">Edit &rarr;</span>
                            <span class="text-xs text-ink-400 hidden group-open:inline">Tutup &uarr;</span>
                        </summary>

                        <div class="px-5 pb-5">
                            <form method="POST" action="{{ route('admin.kelas.update', $kelas) }}" class="grid sm:grid-cols-2 gap-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-medium text-ink-700 mb-1">Tingkat</label>
                                    <select name="tingkat" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                        <option value="7" @selected($kelas->tingkat == 7)>7</option>
                                        <option value="8" @selected($kelas->tingkat == 8)>8</option>
                                        <option value="9" @selected($kelas->tingkat == 9)>9</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-ink-700 mb-1">Nama Rombel</label>
                                    <input type="text" name="nama" value="{{ $kelas->nama }}" required
                                           class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-ink-700 mb-1">Wali Kelas</label>
                                    <select name="wali_kelas_id" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                        <option value="">Belum ditentukan</option>
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" @selected($kelas->wali_kelas_id == $teacher->id)>{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-ink-700 mb-1">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran" value="{{ $kelas->tahun_ajaran }}" required
                                           class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                                </div>
                                <div class="sm:col-span-2 flex items-center justify-between pt-1">
                                    <form method="POST" action="{{ route('admin.kelas.destroy', $kelas) }}" data-confirm="Hapus {{ $kelas->label() }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-500 font-medium hover:underline">Hapus Kelas</button>
                                    </form>
                                    <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </details>
                @empty
                    <p class="px-5 py-6 text-sm text-ink-400">Belum ada kelas. Tambahkan kelas pertama melalui formulir di samping.</p>
                @endforelse
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <h2 class="font-display font-semibold text-ink-900 mb-1">Tambah Kelas Baru</h2>
                <p class="text-xs text-ink-400 mb-4">Contoh: Tingkat 7, Nama Rombel "A" &rarr; Kelas 7A.</p>
                <form method="POST" action="{{ route('admin.kelas.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Tingkat</label>
                        <select name="tingkat" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nama Rombel</label>
                        <input type="text" name="nama" required placeholder="A"
                               class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Wali Kelas</label>
                        <select name="wali_kelas_id" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                            <option value="">Belum ditentukan</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" value="2026/2027" required
                               class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <button class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium rounded-lg py-2.5">Buat Kelas</button>
                </form>
            </div>
        </div>
    </div>
@endsection
