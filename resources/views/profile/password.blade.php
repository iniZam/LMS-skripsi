@extends('layouts.app')

@section('title', 'Ubah Kata Sandi')

@section('content')
<div class="max-w-xl mx-auto mt-8">
    <div class="bg-white rounded-xl border border-ink-100 p-6 md:p-8 shadow-sm">
        <h2 class="font-display font-semibold text-2xl text-ink-900 mb-2">Ubah Kata Sandi</h2>
        <p class="text-sm text-ink-500 mb-6">Ganti kata sandi bawaan Anda dengan kata sandi baru yang lebih aman.</p>

        @if (session('status'))
            <div class="bg-mint-50 border border-mint-200 text-mint-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Kata Sandi Lama -->
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required 
                       class="w-full rounded-lg border border-ink-100 px-4 py-2.5 text-sm focus:ring-2 focus:ring-gold-400 focus:outline-none">
                @error('current_password')
                    <p class="text-xs text-rose-500 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-ink-100">

            <!-- Kata Sandi Baru -->
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Kata Sandi Baru</label>
                <input type="password" name="password" required 
                       class="w-full rounded-lg border border-ink-100 px-4 py-2.5 text-sm focus:ring-2 focus:ring-gold-400 focus:outline-none">
                @error('password')
                    <p class="text-xs text-rose-500 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="password_confirmation" required 
                       class="w-full rounded-lg border border-ink-100 px-4 py-2.5 text-sm focus:ring-2 focus:ring-gold-400 focus:outline-none">
            </div>

            <div class="pt-3">
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium px-5 py-3 rounded-lg w-full transition">
                    Simpan Kata Sandi Baru
                </button>
            </div>
        </form>
    </div>
</div>
@endsection