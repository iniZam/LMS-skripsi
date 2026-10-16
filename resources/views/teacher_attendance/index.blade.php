@extends('layouts.app')

@section('title', 'Absensi Guru')

@section('content')
    @php
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $dayNames = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $todayDayName = $dayNames[$now->format('l')] ?? $now->format('l');
        $todayFormatted = $todayDayName . ', ' . $now->day . ' ' . ($monthNames[$now->month] ?? '') . ' ' . $now->year;
    @endphp

    {{-- Header & Status Kehadiran Hari Ini --}}
    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        {{-- Panel Utama Absen Datang & Absen Pulang --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ink-100 overflow-hidden shadow-sm">
            <div class="bg-gradient-to-r from-ink-900 to-ink-700 text-white px-6 py-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-medium text-gold-400 uppercase tracking-wider">Presensi Harian Pengajar</p>
                    <h2 class="font-display font-semibold text-xl mt-0.5">{{ $todayFormatted }}</h2>
                    <p class="text-xs text-ink-200 mt-1">{{ auth()->user()->name }} &middot; NIP: {{ auth()->user()->nip_nis }}</p>
                </div>
                <div class="text-right bg-ink-800/80 border border-ink-600/50 rounded-xl px-4 py-2.5">
                    <p class="text-[11px] text-ink-200 uppercase tracking-wider">Waktu Sekarang (WITA)</p>
                    <p id="live-clock" class="font-display font-bold text-2xl text-gold-400 tracking-tight">{{ $now->format('H:i:s') }}</p>
                </div>
            </div>

            <div class="p-6">
                {{-- Indikator Status Absen Hari Ini --}}
                <div class="grid sm:grid-cols-2 gap-4 mb-6">
                    <div class="rounded-xl border {{ $todayAttendance && $todayAttendance->check_in_time ? 'border-mint-500/40 bg-mint-100/40' : 'border-ink-100 bg-ink-50/50' }} p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-ink-400 uppercase">Absen Datang (Masuk)</p>
                            <p class="font-display font-semibold text-xl text-ink-900 mt-1">
                                {{ $todayAttendance && $todayAttendance->check_in_time ? substr($todayAttendance->check_in_time, 0, 5) . ' WITA' : 'Belum Absen' }}
                            </p>
                            @if ($todayAttendance && in_array($todayAttendance->status, ['izin', 'sakit', 'alpa']))
                                <span class="inline-block mt-1 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600 uppercase">
                                    Status: {{ $todayAttendance->status }}
                                </span>
                            @endif
                        </div>
                        <div class="w-11 h-11 rounded-full flex items-center justify-center {{ $todayAttendance && $todayAttendance->check_in_time ? 'bg-mint-500 text-white' : 'bg-ink-100 text-ink-400' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-xl border {{ $todayAttendance && $todayAttendance->check_out_time ? 'border-mint-500/40 bg-mint-100/40' : 'border-ink-100 bg-ink-50/50' }} p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-ink-400 uppercase">Absen Pulang (Keluar)</p>
                            <p class="font-display font-semibold text-xl text-ink-900 mt-1">
                                {{ $todayAttendance && $todayAttendance->check_out_time ? substr($todayAttendance->check_out_time, 0, 5) . ' WITA' : 'Belum Absen' }}
                            </p>
                            @if ($todayAttendance && $todayAttendance->check_in_time && $todayAttendance->check_out_time)
                                <p class="text-xs text-mint-500 font-medium mt-0.5">Durasi kerja: {{ $todayAttendance->durationLabel() }}</p>
                            @endif
                        </div>
                        <div class="w-11 h-11 rounded-full flex items-center justify-center {{ $todayAttendance && $todayAttendance->check_out_time ? 'bg-mint-500 text-white' : 'bg-ink-100 text-ink-400' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi Absen Datang & Absen Pulang --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    {{-- Form Absen Datang / Masuk --}}
                    <form method="POST" action="{{ route('teacher.attendance.check-in') }}" class="flex flex-col justify-between rounded-xl border border-ink-100 p-4 bg-ink-50/30">
                        @csrf
                        <input type="hidden" name="status" value="hadir">
                        <div>
                            <h3 class="font-display font-semibold text-sm text-ink-900">1. Absen Datang (Masuk)</h3>
                            <p class="text-xs text-ink-400 mt-1 mb-3">Klik tombol di bawah saat Anda tiba di sekolah untuk mencatat jam masuk.</p>
                            @if (!($todayAttendance && $todayAttendance->check_in_time))
                                <input type="text" name="notes" placeholder="Catatan masuk (opsional)..." value="{{ $todayAttendance?->notes }}"
                                       class="w-full rounded-lg border border-ink-100 bg-white px-3 py-2 text-xs mb-3 focus:outline-none focus:ring-2 focus:ring-gold-400">
                            @endif
                        </div>

                        @if ($todayAttendance && $todayAttendance->check_in_time)
                            <button type="button" disabled class="w-full py-3 px-4 rounded-xl bg-mint-100 text-mint-500 font-display font-semibold text-sm flex items-center justify-center gap-2 cursor-not-allowed">
                                &#10003; Sudah Absen Datang ({{ substr($todayAttendance->check_in_time, 0, 5) }})
                            </button>
                        @else
                            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-mint-500 hover:bg-mint-500/90 text-white font-display font-semibold text-sm shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Tombol Masuk (Absen Datang)
                            </button>
                        @endif
                    </form>

                    {{-- Form Absen Pulang --}}
                    <form method="POST" action="{{ route('teacher.attendance.check-out') }}" class="flex flex-col justify-between rounded-xl border border-ink-100 p-4 bg-ink-50/30">
                        @csrf
                        <div>
                            <h3 class="font-display font-semibold text-sm text-ink-900">2. Absen Pulang (Keluar)</h3>
                            <p class="text-xs text-ink-400 mt-1 mb-3">Klik tombol di bawah saat jam mengajar selesai sebelum meninggalkan sekolah.</p>
                            @if ($todayAttendance && $todayAttendance->check_in_time && !$todayAttendance->check_out_time)
                                <input type="text" name="notes" placeholder="Catatan pulang (opsional)..." value="{{ $todayAttendance?->notes }}"
                                       class="w-full rounded-lg border border-ink-100 bg-white px-3 py-2 text-xs mb-3 focus:outline-none focus:ring-2 focus:ring-gold-400">
                            @endif
                        </div>

                        @if ($todayAttendance && $todayAttendance->check_out_time)
                            <button type="button" disabled class="w-full py-3 px-4 rounded-xl bg-mint-100 text-mint-500 font-display font-semibold text-sm flex items-center justify-center gap-2 cursor-not-allowed">
                                &#10003; Sudah Absen Pulang ({{ substr($todayAttendance->check_out_time, 0, 5) }})
                            </button>
                        @elseif ($todayAttendance && $todayAttendance->check_in_time)
                            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-ink-900 hover:bg-ink-700 text-white font-display font-semibold text-sm shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Absen Pulang Sekarang
                            </button>
                        @else
                            <button type="button" disabled class="w-full py-3 px-4 rounded-xl bg-ink-100 text-ink-400 font-display font-semibold text-sm flex items-center justify-center gap-2 cursor-not-allowed">
                                Absen Pulang (Wajib Absen Datang Dulu)
                            </button>
                        @endif
                    </form>
                </div>

                {{-- Opsi Pengajuan Izin / Sakit (Jika berhalangan hadir) --}}
                @if (!($todayAttendance && $todayAttendance->check_in_time))
                    <details class="mt-5 pt-4 border-t border-ink-100 group">
                        <summary class="text-xs font-medium text-ink-600 hover:text-ink-900 cursor-pointer list-none flex items-center justify-between">
                            <span>Berhalangan hadir hari ini? Ajukan keterangan <strong>Izin / Sakit</strong></span>
                            <span class="text-ink-400 group-open:rotate-180 transition-transform">&#9662;</span>
                        </summary>
                        <form method="POST" action="{{ route('teacher.attendance.check-in') }}" class="mt-3 grid sm:grid-cols-3 gap-3 bg-ink-50/60 p-3.5 rounded-xl border border-ink-100">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-ink-700 mb-1">Status Ketidakhadiran</label>
                                <select name="status" required class="w-full rounded-lg border border-ink-100 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-gold-400">
                                    <option value="izin" @selected($todayAttendance?->status === 'izin')>Izin</option>
                                    <option value="sakit" @selected($todayAttendance?->status === 'sakit')>Sakit</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-ink-700 mb-1">Alasan / Keterangan</label>
                                <input type="text" name="notes" required placeholder="Contoh: Rapat dinas / istirahat dokter" value="{{ $todayAttendance?->notes }}"
                                       class="w-full rounded-lg border border-ink-100 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-gold-400">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-gold-500 hover:bg-gold-600 text-ink-900 font-medium text-xs py-2.5 px-4 rounded-lg transition cursor-pointer">
                                    Simpan Keterangan
                                </button>
                            </div>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        {{-- Ringkasan Statistik Bulan Ini --}}
        <div class="bg-white rounded-2xl border border-ink-100 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-display font-semibold text-ink-900 text-base">Ringkasan Bulan {{ $monthNames[$month] ?? '' }} {{ $year }}</h3>
                <p class="text-xs text-ink-400 mt-0.5 mb-5">Rekap kehadiran Anda pada periode terpilih.</p>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-ink-100 p-3.5 ledger-card" style="border-left-color: #2F855A">
                        <p class="text-xs text-ink-400">Hadir</p>
                        <p class="font-display font-semibold text-2xl text-ink-900 mt-0.5">{{ $summary['hadir'] }}</p>
                        <p class="text-[11px] text-ink-400">hari</p>
                    </div>
                    <div class="rounded-xl border border-ink-100 p-3.5 ledger-card" style="border-left-color: #3B82F6">
                        <p class="text-xs text-ink-400">Izin</p>
                        <p class="font-display font-semibold text-2xl text-ink-900 mt-0.5">{{ $summary['izin'] }}</p>
                        <p class="text-[11px] text-ink-400">hari</p>
                    </div>
                    <div class="rounded-xl border border-ink-100 p-3.5 ledger-card" style="border-left-color: #C9973F">
                        <p class="text-xs text-ink-400">Sakit</p>
                        <p class="font-display font-semibold text-2xl text-ink-900 mt-0.5">{{ $summary['sakit'] }}</p>
                        <p class="text-[11px] text-ink-400">hari</p>
                    </div>
                    <div class="rounded-xl border border-ink-100 p-3.5 ledger-card" style="border-left-color: #C53030">
                        <p class="text-xs text-ink-400">Alpa</p>
                        <p class="font-display font-semibold text-2xl text-ink-900 mt-0.5">{{ $summary['alpa'] }}</p>
                        <p class="text-[11px] text-ink-400">hari</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-ink-100 text-xs text-ink-400 space-y-1">
                <p class="font-medium text-ink-700">Petunjuk Presensi:</p>
                <p>1. Lakukan <strong>Absen Datang</strong> saat tiba di sekolah.</p>
                <p>2. Lakukan <strong>Absen Pulang</strong> sebelum meninggalkan sekolah.</p>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Absensi Guru --}}
    <div class="bg-white rounded-2xl border border-ink-100 p-6 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
            <div>
                <h3 class="font-display font-semibold text-lg text-ink-900">Riwayat Absensi Saya</h3>
                <p class="text-xs text-ink-400 mt-0.5">Daftar catatan jam datang dan jam pulang per hari.</p>
            </div>

            <form method="GET" action="{{ route('teacher.attendance.index') }}" class="flex items-center gap-2">
                <select name="month" class="rounded-lg border border-ink-100 px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    @foreach ($monthNames as $num => $name)
                        <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="rounded-lg border border-ink-100 px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    @for ($y = $now->year - 1; $y <= $now->year + 1; $y++)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-3.5 py-2 rounded-lg transition cursor-pointer">
                    Tampilkan
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-ink-100">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-ink-50 text-ink-700 border-b border-ink-100 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Absen Datang</th>
                        <th class="px-4 py-3 font-medium">Absen Pulang</th>
                        <th class="px-4 py-3 font-medium">Durasi</th>
                        <th class="px-4 py-3 font-medium">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($histories as $item)
                        @php
                            $dName = $dayNames[$item->date->format('l')] ?? $item->date->format('l');
                            $statusBadge = match ($item->status) {
                                'hadir' => 'bg-mint-100 text-mint-500',
                                'izin' => 'bg-blue-100 text-blue-700',
                                'sakit' => 'bg-gold-100 text-gold-600',
                                'alpa' => 'bg-rose-100 text-rose-500',
                                default => 'bg-ink-100 text-ink-600',
                            };
                        @endphp
                        <tr class="hover:bg-ink-50/40">
                            <td class="px-4 py-3 font-medium text-ink-900">
                                {{ $dName }}, {{ $item->date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase {{ $statusBadge }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-ink-700 font-medium">
                                {{ $item->check_in_time ? substr($item->check_in_time, 0, 5) . ' WITA' : '-' }}
                            </td>
                            <td class="px-4 py-3 text-ink-700 font-medium">
                                @if ($item->check_out_time)
                                    {{ substr($item->check_out_time, 0, 5) }} WITA
                                @elseif ($item->check_in_time)
                                    <span class="text-xs text-gold-600 italic">Belum absen pulang</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-600">
                                {{ $item->durationLabel() }}
                            </td>
                            <td class="px-4 py-3 text-ink-400 text-xs">
                                {{ $item->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-ink-400 text-sm">
                                Belum ada catatan absensi pada periode bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const clockEl = document.getElementById('live-clock');
            if (!clockEl) return;

            let parts = clockEl.textContent.trim().split(':').map(Number);
            if (parts.length !== 3) return;

            let totalSeconds = parts[0] * 3600 + parts[1] * 60 + parts[2];

            setInterval(() => {
                totalSeconds = (totalSeconds + 1) % 86400;
                const hrs = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
                const mins = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                const secs = String(totalSeconds % 60).padStart(2, '0');
                clockEl.textContent = `${hrs}:${mins}:${secs}`;
            }, 1000);
        });
    </script>
@endsection

