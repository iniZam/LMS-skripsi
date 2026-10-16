@extends('layouts.app')

@section('title', 'Rapot Saya')

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="font-display font-semibold text-xl text-ink-900">Rapot &amp; Rekap Nilai Saya</h2>
            <p class="text-xs text-ink-400 mt-0.5">
                Nama Siswa: <strong class="text-ink-700">{{ $user->name }}</strong>
                &middot; NISN: <strong class="text-ink-700">{{ $user->nip_nis }}</strong>
                @if ($user->kelas)
                    &middot; Kelas: <strong class="text-ink-700">{{ $user->kelas->label() }}</strong>
                @endif
            </p>
        </div>
        <button type="button" onclick="window.print()" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition print:hidden">
            🖨️ Cetak Rapot
        </button>
    </div>

    <div class="bg-white rounded-xl border border-ink-100 p-6 shadow-sm">
        <div class="overflow-x-auto rounded-lg border border-ink-100">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-ink-50 text-ink-700 border-b border-ink-100">
                    <tr>
                        <th class="px-3 py-3 font-semibold text-center w-10">No</th>
                        <th class="px-4 py-3 font-semibold">Mata Pelajaran</th>
                        <th class="px-4 py-3 font-semibold">Guru Pengajar</th>
                        <th class="px-3 py-3 font-semibold text-center bg-blue-50/40 text-blue-900">Rata-rata Tugas</th>
                        <th class="px-3 py-3 font-semibold text-center bg-purple-50/40 text-purple-900">Rata-rata Kuis</th>
                        <th class="px-2.5 py-3 font-semibold text-center bg-mint-50 text-mint-700">H</th>
                        <th class="px-2.5 py-3 font-semibold text-center bg-blue-50 text-blue-700">I</th>
                        <th class="px-2.5 py-3 font-semibold text-center bg-amber-50 text-amber-700">S</th>
                        <th class="px-2.5 py-3 font-semibold text-center bg-rose-50 text-rose-700">A</th>
                        <th class="px-3 py-3 font-semibold text-center">% Hadir</th>
                        <th class="px-3.5 py-3 font-bold text-center bg-gold-100/70 text-ink-900">Nilai Akhir</th>
                        <th class="px-3 py-3 font-bold text-center bg-gold-100/70 text-ink-900">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($subjectReports as $idx => $item)
                        @php
                            $class = $item['class'];
                            $row = $item['report'];
                            $predColor = match($row['predicate']) {
                                'A' => 'bg-mint-100 text-mint-700',
                                'B' => 'bg-blue-100 text-blue-700',
                                'C' => 'bg-gold-100 text-gold-600',
                                default => 'bg-rose-100 text-rose-600',
                            };
                        @endphp
                        <tr class="hover:bg-ink-50/60">
                            <td class="px-3 py-3 text-center text-ink-400">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900">
                                <a href="{{ route('classes.show', $class) }}" class="hover:underline">{{ $class->title }}</a>
                                <div class="text-[10px] text-ink-400">{{ $class->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-ink-600">{{ $class->teacher->name }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-blue-900 bg-blue-50/20">
                                {{ number_format($row['avg_assignment'], 1) }}
                            </td>
                            <td class="px-3 py-3 text-center font-semibold text-purple-900 bg-purple-50/20">
                                {{ number_format($row['avg_quiz'], 1) }}
                            </td>
                            <td class="px-2.5 py-3 text-center font-semibold text-mint-700">{{ $row['attendance']['hadir'] }}</td>
                            <td class="px-2.5 py-3 text-center font-semibold text-blue-700">{{ $row['attendance']['izin'] }}</td>
                            <td class="px-2.5 py-3 text-center font-semibold text-amber-700">{{ $row['attendance']['sakit'] }}</td>
                            <td class="px-2.5 py-3 text-center font-semibold text-rose-700">{{ $row['attendance']['alpa'] }}</td>
                            <td class="px-3 py-3 text-center text-ink-600">{{ $row['attendance_rate'] }}%</td>
                            <td class="px-3.5 py-3 text-center font-bold text-ink-900 bg-gold-50/40 text-sm">
                                {{ number_format($row['final_score'], 1) }}
                            </td>
                            <td class="px-3 py-3 text-center bg-gold-50/40">
                                <span class="inline-block w-6 py-0.5 rounded font-bold text-xs {{ $predColor }}">
                                    {{ $row['predicate'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-ink-400">
                                Anda belum terdaftar di mata pelajaran manapun.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

