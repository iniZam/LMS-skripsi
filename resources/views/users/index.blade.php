@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-2 mb-4 flex-wrap">
                <a href="{{ route('admin.users.index') }}" class="text-xs px-3 py-1.5 rounded-full font-medium {{ !$role ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">Semua</a>
                <a href="{{ route('admin.users.index', ['role' => 'teacher']) }}" class="text-xs px-3 py-1.5 rounded-full font-medium {{ $role === 'teacher' ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">Pengajar</a>
                <a href="{{ route('admin.users.index', ['role' => 'student']) }}" class="text-xs px-3 py-1.5 rounded-full font-medium {{ $role === 'student' ? 'bg-ink-900 text-white' : 'bg-white border border-ink-100 text-ink-600' }}">Siswa</a>

                <form method="GET" action="{{ route('admin.users.index') }}" class="ml-auto">
                    @if ($role)
                        <input type="hidden" name="role" value="{{ $role }}">
                    @endif
                    <select name="kelas_id" onchange="this.form.submit()" class="text-xs rounded-full border border-ink-100 px-3 py-1.5 text-ink-600 font-medium focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}" @selected($kelasId == $k->id)>{{ $k->label() }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-ink-100 divide-y divide-ink-100">
                @forelse ($users as $user)
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $user->name }}</p>
                            <p class="text-xs text-ink-400 mt-0.5">{{ $user->email }} &middot; {{ $user->nip_nis }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($user->isStudent())
                                <span class="text-xs px-2.5 py-1 rounded-full bg-gold-100 text-gold-600 font-medium">
                                    {{ $user->kelas?->label() ?? 'Belum ada kelas' }}
                                </span>
                            @endif
                            <span class="text-xs px-2.5 py-1 rounded-full bg-ink-50 text-ink-600 font-medium">{{ $user->roleLabel() }}</span>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Hapus pengguna {{ $user->name }}? Tindakan ini tidak bisa dibatalkan.">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-rose-500 hover:underline font-medium">Hapus</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-ink-400">Belum ada pengguna pada kategori ini.</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <h2 class="font-display font-semibold text-ink-900 mb-1">Tambah Akun Siswa</h2>
                <p class="text-xs text-ink-400 mb-4">Setiap siswa wajib ditempatkan pada satu kelas (rombel).</p>
                <form method="POST" action="{{ route('admin.users.storeStudent') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Kelas</label>
                        <select name="kelas_id" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                            <option value="">Pilih kelas&hellip;</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k->id }}">{{ $k->label() }}</option>
                            @endforeach
                        </select>
                        @if ($kelasList->isEmpty())
                            <p class="text-xs text-rose-500 mt-1">Belum ada kelas. Buat kelas terlebih dahulu di halaman <a href="{{ route('admin.kelas.index') }}" class="underline">Kelas &amp; Wali Kelas</a>.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">NISN <span class="text-ink-400 font-normal">(opsional)</span></label>
                        <input type="text" name="nip_nis" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <button class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium rounded-lg py-2.5">Buat Akun Siswa</button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-ink-100 p-5">
                <h2 class="font-display font-semibold text-ink-900 mb-1">Tambah Akun Pengajar</h2>
                <p class="text-xs text-ink-400 mb-4">Pengajar dapat ditugaskan sebagai wali kelas di halaman Kelas &amp; Wali Kelas.</p>
                <form method="POST" action="{{ route('admin.users.storeTeacher') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">NIP <span class="text-ink-400 font-normal">(opsional)</span></label>
                        <input type="text" name="nip_nis" class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <button class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium rounded-lg py-2.5">Buat Akun Pengajar</button>
                </form>
            </div>
        </div>
    </div>
@endsection
