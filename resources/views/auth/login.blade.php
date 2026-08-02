@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h2 class="font-display font-semibold text-xl text-ink-900 mb-1">Masuk ke akun Anda</h2>
    <p class="text-ink-400 text-sm mb-6">Gunakan email dan kata sandi yang terdaftar.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-ink-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-ink-700 mb-1">Kata Sandi</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-ink-100 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-400">
            <input type="checkbox" name="remember" class="rounded border-ink-100 text-gold-500 focus:ring-gold-400">
            Ingat saya
        </label>
        <button type="submit" class="w-full bg-ink-900 hover:bg-ink-700 text-white font-medium rounded-lg py-2.5 text-sm transition">
            Masuk
        </button>
    </form>

    <p class="text-center text-xs text-ink-400 mt-6">
        Belum punya akun? Hubungi admin sekolah untuk didaftarkan.
    </p>
@endsection
