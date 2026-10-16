@extends('layouts.app')

@section('title', 'Rekap Absensi Guru')

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

        $totalTeachersCount = $teachers->count();
        $todayHadirCount = $dailyAttendances->where('status', 'hadir')->count();
        $todayPulangCount = $dailyAttendances->whereNotNull('check_in_time')->whereNotNull('check_out_time')->count();
        $todayIzinSakitCount = $dailyAttendances->whereIn('status', ['izin', 'sakit'])->count();
        $todayBelumAbsenCount = max(0, $totalTeachersCount - $dailyAttendances->count());
    @endphp

    {{-- Statistik Ringkas Hari Terpilih --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #2F855A">
            <p class="text-xs text-ink-400">Hadir ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $todayHadirCount }} <span class="text-sm font-normal text-ink-400">/ {{ $totalTeachersCount }} guru</span></p>
            <p class="text-xs text-ink-400 mt-1">Sudah melakukan absen datang</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #1E2A4A">
            <p class="text-xs text-ink-400">Sudah Absen Pulang</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $todayPulangCount }} <span class="text-sm font-normal text-ink-400">guru</span></p>
            <p class="text-xs text-ink-400 mt-1">Tercatat jam masuk &amp; pulang</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C9973F">
            <p class="text-xs text-ink-400">Izin / Sakit</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $todayIzinSakitCount }} <span class="text-sm font-normal text-ink-400">guru</span></p>
            <p class="text-xs text-ink-400 mt-1">Keterangan tidak hadir</p>
        </div>
        <div class="bg-white rounded-xl border border-ink-100 p-5 ledger-card" style="border-left-color: #C53030">
            <p class="text-xs text-ink-400">Belum Absen</p>
            <p class="text-2xl font-display font-semibold text-ink-900 mt-1">{{ $todayBelumAbsenCount }} <span class="text-sm font-normal text-ink-400">guru</span></p>
            <p class="text-xs text-ink-400 mt-1">Pada tanggal {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Filter & Tombol Unduh Rekap --}}
    <div class="bg-white rounded-2xl border border-ink-100 p-5 mb-6 shadow-sm">
        <form method="GET" action="{{ route('admin.teacher-attendance.index') }}" class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Bulan Rekap</label>
                    <select name="month" class="rounded-lg border border-ink-100 px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                        @foreach ($monthNames as $num => $name)
                            <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Tahun</label>
                    <select name="year" class="rounded-lg border border-ink-100 px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                        @for ($y = $now->year - 1; $y <= $now->year + 1; $y++)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Tanggal Pantauan Harian</label>
                    <input type="date" name="date" value="{{ $selectedDate }}"
                           class="rounded-lg border border-ink-100 px-3 py-1.5 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Filter Guru (Log)</label>
                    <select name="teacher_id" class="rounded-lg border border-ink-100 px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="">Semua Guru</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected($teacherId == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition cursor-pointer">
                    Terapkan Filter
                </button>
            </div>

            <a href="{{ route('admin.teacher-attendance.export', ['month' => $month, 'year' => $year]) }}"
               class="bg-mint-500 hover:bg-mint-500/90 text-white text-xs font-medium px-4 py-2.5 rounded-lg transition inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Unduh Rekap Excel (CSV)
            </a>
        </form>
    </div>

    {{-- BAGIAN 1: Tabel Rekapitulasi Bulanan Seluruh Guru --}}
    <div class="bg-white rounded-2xl border border-ink-100 p-6 mb-8 shadow-sm">
        <div class="mb-4">
            <h2 class="font-display font-semibold text-lg text-ink-900">
                Rekapitulasi Kehadiran Seluruh Guru &mdash; {{ $monthNames[$month] ?? '' }} {{ $year }}
            </h2>
            <p class="text-xs text-ink-400 mt-0.5">Total akumulasi kehadiran, absen pulang, izin, sakit, dan alpa setiap guru.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-ink-100">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-ink-50 text-ink-700 border-b border-ink-100 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium w-10">No</th>
                        <th class="px-4 py-3 font-medium">Nama Guru</th>
                        <th class="px-4 py-3 font-medium">NIP</th>
                        <th class="px-3 py-3 font-medium text-center bg-mint-100/50 text-mint-500">Hadir (Datang)</th>
                        <th class="px-3 py-3 font-medium text-center bg-ink-100/60 text-ink-700">Absen Pulang</th>
                        <th class="px-3 py-3 font-medium text-center bg-blue-50 text-blue-700">Izin</th>
                        <th class="px-3 py-3 font-medium text-center bg-gold-100/60 text-gold-600">Sakit</th>
                        <th class="px-3 py-3 font-medium text-center bg-rose-100/50 text-rose-500">Alpa</th>
                        <th class="px-3 py-3 font-medium text-center">Total Tercatat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($teacherSummary as $idx => $row)
                        <tr class="hover:bg-ink-50/40">
                            <td class="px-4 py-3 text-ink-400">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $row['teacher']->name }}</td>
                            <td class="px-4 py-3 text-ink-400 text-xs">{{ $row['teacher']->nip_nis }}</td>
                            <td class="px-3 py-3 text-center font-bold text-mint-500 bg-mint-100/20">{{ $row['hadir'] }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-ink-700 bg-ink-50/40">{{ $row['completed_out'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-blue-700 bg-blue-50/30">{{ $row['izin'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-gold-600 bg-gold-100/20">{{ $row['sakit'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-rose-500 bg-rose-100/20">{{ $row['alpa'] }}</td>
                            <td class="px-3 py-3 text-center font-medium text-ink-700">{{ $row['total_recorded'] }} hari</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-ink-400">Belum ada data pengajar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- BAGIAN 2: Pantauan Kehadiran Harian & Input/Koreksi Manual Admin --}}
    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ink-100 p-6 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                <div>
                    <h3 class="font-display font-semibold text-base text-ink-900">
                        Status Kehadiran Guru Hari Ini ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                    </h3>
                    <p class="text-xs text-ink-400 mt-0.5">Pantau siapa saja guru yang sudah absen datang dan absen pulang pada tanggal terpilih.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-ink-100 max-h-[420px] overflow-y-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-ink-50 text-ink-700 border-b border-ink-100 text-xs uppercase sticky top-0">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama Guru</th>
                            <th class="px-3 py-3 font-medium text-center">Status</th>
                            <th class="px-3 py-3 font-medium text-center">Jam Datang</th>
                            <th class="px-3 py-3 font-medium text-center">Jam Pulang</th>
                            <th class="px-4 py-3 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($teachers as $teacher)
                            @php
                                $att = $dailyAttendances->get($teacher->id);
                                $badge = match ($att?->status) {
                                    'hadir' => 'bg-mint-100 text-mint-500',
                                    'izin' => 'bg-blue-100 text-blue-700',
                                    'sakit' => 'bg-gold-100 text-gold-600',
                                    'alpa' => 'bg-rose-100 text-rose-500',
                                    default => 'bg-ink-100 text-ink-400',
                                };
                            @endphp
                            <tr class="hover:bg-ink-50/40">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-ink-900 text-sm">{{ $teacher->name }}</p>
                                    <p class="text-[11px] text-ink-400">{{ $teacher->nip_nis }}</p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full uppercase {{ $badge }}">
                                        {{ $att ? $att->status : 'Belum Absen' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium text-ink-700">
                                    {{ $att && $att->check_in_time ? substr($att->check_in_time, 0, 5) : '-' }}
                                </td>
                                <td class="px-3 py-3 text-center font-medium text-ink-700">
                                    {{ $att && $att->check_out_time ? substr($att->check_out_time, 0, 5) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-ink-400">
                                    {{ $att?->notes ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Form Input / Koreksi Absensi Guru oleh Admin --}}
        <div class="bg-white rounded-2xl border border-ink-100 p-6 shadow-sm">
            <h3 class="font-display font-semibold text-base text-ink-900 mb-1">Input / Koreksi Absensi</h3>
            <p class="text-xs text-ink-400 mb-4">Admin dapat mencatat atau memperbarui absen datang/pulang guru secara manual.</p>

            <form method="POST" action="{{ route('admin.teacher-attendance.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Pilih Guru</label>
                    <select name="teacher_id" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="">-- Pilih Pengajar --</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->nip_nis }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" required
                           class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Status Kehadiran</label>
                    <select name="status" required class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        <option value="hadir">Hadir</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpa">Alpa</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Jam Datang</label>
                        <input type="time" name="check_in_time" value="07:30"
                               class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Jam Pulang</label>
                        <input type="time" name="check_out_time" value="14:00"
                               class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Catatan <span class="text-ink-400 font-normal">(opsional)</span></label>
                    <input type="text" name="notes" placeholder="Keterangan tambahan..."
                           class="w-full rounded-lg border border-ink-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>
                <button type="submit" class="w-full bg-ink-900 hover:bg-ink-700 text-white text-sm font-medium rounded-lg py-2.5 transition cursor-pointer">
                    Simpan Data Absensi
                </button>
            </form>
        </div>
    </div>

    {{-- BAGIAN 3: Detail Log Riwayat Absensi Guru Bulanan --}}
    <div class="bg-white rounded-2xl border border-ink-100 p-6 shadow-sm">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-base text-ink-900">
                Log Detail Absensi Datang &amp; Pulang ({{ $monthNames[$month] ?? '' }} {{ $year }})
            </h3>
            <p class="text-xs text-ink-400 mt-0.5">Daftar lengkap riwayat waktu absen datang dan absen pulang guru.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-ink-100">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-ink-50 text-ink-700 border-b border-ink-100 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Nama Guru</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Absen Datang</th>
                        <th class="px-4 py-3 font-medium">Absen Pulang</th>
                        <th class="px-4 py-3 font-medium">Durasi</th>
                        <th class="px-4 py-3 font-medium">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($monthlyAttendances as $log)
                        @php
                            $dName = $dayNames[$log->date->format('l')] ?? $log->date->format('l');
                            $statusBadge = match ($log->status) {
                                'hadir' => 'bg-mint-100 text-mint-500',
                                'izin' => 'bg-blue-100 text-blue-700',
                                'sakit' => 'bg-gold-100 text-gold-600',
                                'alpa' => 'bg-rose-100 text-rose-500',
                                default => 'bg-ink-100 text-ink-600',
                            };
                        @endphp
                        <tr class="hover:bg-ink-50/40">
                            <td class="px-4 py-3 text-ink-700">
                                {{ $dName }}, {{ $log->date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink-900">{{ $log->teacher?->name ?? '-' }}</p>
                                <p class="text-[11px] text-ink-400">{{ $log->teacher?->nip_nis }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase {{ $statusBadge }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-ink-700">
                                {{ $log->check_in_time ? substr($log->check_in_time, 0, 5) . ' WITA' : '-' }}
                            </td>
                            <td class="px-4 py-3 font-medium text-ink-700">
                                @if ($log->check_out_time)
                                    {{ substr($log->check_out_time, 0, 5) }} WITA
                                @elseif ($log->check_in_time)
                                    <span class="text-xs text-gold-600 italic">Belum absen pulang</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-600">
                                {{ $log->durationLabel() }}
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-400">
                                {{ $log->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-ink-400">
                                Belum ada catatan absensi guru pada periode bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

