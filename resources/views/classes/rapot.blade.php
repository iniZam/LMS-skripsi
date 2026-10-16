@extends('layouts.app')

@section('title', $class ? 'Rapot & Rekap Nilai - ' . $class->title : 'Rapot & Rekap Nilai')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 print:hidden">
        <div>
            <h2 class="font-display font-semibold text-xl text-ink-900">Rapot &amp; Rekapitulasi Nilai Siswa</h2>
            <p class="text-xs text-ink-400 mt-0.5">
                Rekap nilai tugas, nilai kuis, kehadiran absensi (H/I/S/A), nilai akhir, dan predikat siswa.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Dropdown Pilih Kelas --}}
            @if ($teacherClasses->isNotEmpty())
                <form method="GET" action="{{ route('rapot.index') }}" class="flex items-center gap-2">
                    <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-xs font-medium text-ink-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                        @foreach ($teacherClasses as $tc)
                            <option value="{{ $tc->id }}" @selected($class && $class->id === $tc->id)>
                                {{ $tc->title }} ({{ $tc->code }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if ($class)
                <a href="{{ route('classes.rapot.export', $class) }}" class="bg-mint-500 hover:bg-mint-600 text-white text-xs font-medium px-4 py-2 rounded-lg transition inline-flex items-center gap-1.5">
                    📥 Unduh Excel (CSV)
                </a>
                <button type="button" onclick="window.print()" class="bg-ink-900 hover:bg-ink-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition inline-flex items-center gap-1.5">
                    🖨️ Cetak Rapot
                </button>
            @endif
        </div>
    </div>

    @if (! $class)
        <div class="bg-white rounded-xl border border-ink-100 p-8 text-center">
            <p class="text-sm text-ink-500 font-medium">Belum ada mata pelajaran untuk ditampilkan.</p>
            <p class="text-xs text-ink-400 mt-1">Silakan buat mata pelajaran terlebih dahulu untuk melihat rekap rapot siswa.</p>
        </div>
    @else
        {{-- Kartu Informasi Mata Pelajaran --}}
        <div class="bg-white rounded-xl border border-ink-100 p-6 mb-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-ink-100 pb-4 mb-5">
                <div>
                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-gold-100 text-gold-600">
                        {{ $class->code }} &middot; {{ $class->subject_category }}
                    </span>
                    <h3 class="font-display font-semibold text-lg text-ink-900 mt-1.5">{{ $class->title }}</h3>
                    <p class="text-xs text-ink-400 mt-0.5">
                        Pengajar: <strong class="text-ink-700">{{ $class->teacher->name }}</strong>
                        &middot; Tahun Ajaran: <strong class="text-ink-700">{{ $class->academic_year }}</strong>
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs">
                    <div class="bg-ink-50 px-3.5 py-2 rounded-lg border border-ink-100">
                        <span class="text-ink-400 block">Siswa Terdaftar</span>
                        <span class="font-display font-semibold text-sm text-ink-900">{{ $studentReports->count() }} Siswa</span>
                    </div>
                    <div class="bg-ink-50 px-3.5 py-2 rounded-lg border border-ink-100">
                        <span class="text-ink-400 block">Total Tugas</span>
                        <span class="font-display font-semibold text-sm text-ink-900">{{ $assignmentMeetings->count() }} Tugas</span>
                    </div>
                    <div class="bg-ink-50 px-3.5 py-2 rounded-lg border border-ink-100">
                        <span class="text-ink-400 block">Total Kuis</span>
                        <span class="font-display font-semibold text-sm text-ink-900">{{ $quizzes->count() }} Kuis</span>
                    </div>
                    <div class="bg-ink-50 px-3.5 py-2 rounded-lg border border-ink-100">
                        <span class="text-ink-400 block">Total Pertemuan</span>
                        <span class="font-display font-semibold text-sm text-ink-900">{{ $meetings->count() }} Pertemuan</span>
                    </div>
                </div>
            </div>

            {{-- Tabel Rekap Rapot --}}
            <div class="overflow-x-auto rounded-lg border border-ink-100">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-ink-50 text-ink-700 border-b border-ink-100">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-center w-10">No</th>
                            <th class="px-4 py-3 font-semibold sticky left-0 bg-ink-50 z-10 border-r border-ink-100">Nama Siswa</th>

                            {{-- Kolom Tugas --}}
                            @foreach ($assignmentMeetings as $idx => $m)
                                <th class="px-3 py-3 font-semibold text-center bg-blue-50/40 text-blue-800" title="{{ $m->assignment_title ?: $m->title }}">
                                    T{{ $idx + 1 }} (P{{ $m->meeting_number }})
                                </th>
                            @endforeach
                            <th class="px-3 py-3 font-bold text-center bg-blue-100/60 text-blue-900 border-r border-ink-100">
                                Rata Tugas
                            </th>

                            {{-- Kolom Kuis --}}
                            @foreach ($quizzes as $idx => $q)
                                <th class="px-3 py-3 font-semibold text-center bg-purple-50/40 text-purple-800" title="{{ $q->title }}">
                                    K{{ $idx + 1 }} (P{{ $q->meeting->meeting_number }})
                                </th>
                            @endforeach
                            <th class="px-3 py-3 font-bold text-center bg-purple-100/60 text-purple-900 border-r border-ink-100">
                                Rata Kuis
                            </th>

                            {{-- Kolom Absensi --}}
                            <th class="px-2.5 py-3 font-semibold text-center bg-mint-50 text-mint-700" title="Hadir">H</th>
                            <th class="px-2.5 py-3 font-semibold text-center bg-blue-50 text-blue-700" title="Izin">I</th>
                            <th class="px-2.5 py-3 font-semibold text-center bg-amber-50 text-amber-700" title="Sakit">S</th>
                            <th class="px-2.5 py-3 font-semibold text-center bg-rose-50 text-rose-700" title="Alpa">A</th>
                            <th class="px-3 py-3 font-semibold text-center border-r border-ink-100">% Hadir</th>

                            {{-- Kolom Nilai Akhir & Predikat --}}
                            <th class="px-3.5 py-3 font-bold text-center bg-gold-100/70 text-ink-900">Nilai Akhir</th>
                            <th class="px-3 py-3 font-bold text-center bg-gold-100/70 text-ink-900">Predikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($studentReports as $index => $row)
                            @php $student = $row['student']; @endphp
                            <tr class="hover:bg-ink-50/60">
                                <td class="px-3 py-3 text-center text-ink-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900 sticky left-0 bg-white z-10 border-r border-ink-100">
                                    {{ $student->name }}
                                    <div class="text-[10px] text-ink-400 font-normal">NISN: {{ $student->nip_nis }}</div>
                                </td>

                                {{-- Nilai Tugas per Pertemuan --}}
                                @foreach ($assignmentMeetings as $m)
                                    @php $aScore = $row['assignment_scores'][$m->id] ?? null; @endphp
                                    <td class="px-3 py-3 text-center">
                                        @if ($aScore && $aScore['grade'] !== null)
                                            <span class="font-medium text-ink-900">{{ $aScore['grade'] }}</span>
                                        @elseif ($aScore && $aScore['submitted'])
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gold-100 text-gold-600" title="Sudah dikumpulkan, belum dinilai">BN</span>
                                        @else
                                            <span class="text-ink-300">0</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-center font-bold bg-blue-50/30 text-blue-900 border-r border-ink-100">
                                    {{ number_format($row['avg_assignment'], 1) }}
                                </td>

                                {{-- Nilai Kuis per Kuis --}}
                                @foreach ($quizzes as $q)
                                    @php $qScore = $row['quiz_scores'][$q->id] ?? null; @endphp
                                    <td class="px-3 py-3 text-center">
                                        @if ($qScore && $qScore['score'] !== null)
                                            <span class="font-medium text-ink-900">{{ number_format($qScore['score'], 0) }}</span>
                                        @else
                                            <span class="text-ink-300">0</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-center font-bold bg-purple-50/30 text-purple-900 border-r border-ink-100">
                                    {{ number_format($row['avg_quiz'], 1) }}
                                </td>

                                {{-- Rekap Absensi H/I/S/A --}}
                                <td class="px-2.5 py-3 text-center font-semibold text-mint-700 bg-mint-50/20">{{ $row['attendance']['hadir'] }}</td>
                                <td class="px-2.5 py-3 text-center font-semibold text-blue-700 bg-blue-50/20">{{ $row['attendance']['izin'] }}</td>
                                <td class="px-2.5 py-3 text-center font-semibold text-amber-700 bg-amber-50/20">{{ $row['attendance']['sakit'] }}</td>
                                <td class="px-2.5 py-3 text-center font-semibold text-rose-700 bg-rose-50/20">{{ $row['attendance']['alpa'] }}</td>
                                <td class="px-3 py-3 text-center font-medium text-ink-600 border-r border-ink-100">
                                    {{ $row['attendance_rate'] }}%
                                </td>

                                {{-- Nilai Akhir & Predikat --}}
                                <td class="px-3.5 py-3 text-center font-bold text-ink-900 bg-gold-50/40 text-sm">
                                    {{ number_format($row['final_score'], 1) }}
                                </td>
                                <td class="px-3 py-3 text-center bg-gold-50/40">
                                    @php
                                        $predColor = match($row['predicate']) {
                                            'A' => 'bg-mint-100 text-mint-700',
                                            'B' => 'bg-blue-100 text-blue-700',
                                            'C' => 'bg-gold-100 text-gold-600',
                                            default => 'bg-rose-100 text-rose-600',
                                        };
                                    @endphp
                                    <span class="inline-block w-6 py-0.5 rounded font-bold text-xs {{ $predColor }}">
                                        {{ $row['predicate'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="px-4 py-8 text-center text-ink-400">
                                    Belum ada siswa yang terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Keterangan Legenda --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 text-xs text-ink-500">
                <div class="flex flex-wrap gap-4">
                    <span><strong>T1..Tn</strong>: Nilai Tugas (Pertemuan)</span>
                    <span><strong>K1..Kn</strong>: Nilai Kuis (Pertemuan)</span>
                    <span><strong class="text-gold-600">BN</strong>: Belum Dinilai</span>
                    <span><strong class="text-mint-600">H</strong>: Hadir &middot; <strong class="text-blue-600">I</strong>: Izin &middot; <strong class="text-amber-600">S</strong>: Sakit &middot; <strong class="text-rose-600">A</strong>: Alpa</span>
                </div>
                <div class="text-ink-400">
                    Predikat: <strong>A</strong> (85-100) &middot; <strong>B</strong> (75-84) &middot; <strong>C</strong> (60-74) &middot; <strong>D</strong> (&lt;60)
                </div>
            </div>
        </div>
    @endif
@endsection

