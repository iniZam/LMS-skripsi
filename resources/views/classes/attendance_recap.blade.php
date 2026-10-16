@extends('layouts.app')

@section('title', 'Rekap Absensi - ' . $class->title)

@section('content')
    <a href="{{ route('classes.show', $class) }}" class="text-sm text-ink-400 hover:text-ink-700 mb-4 inline-block">&larr; Kembali ke Kelas</a>

    <div class="bg-white rounded-xl border border-ink-100 p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
            <div>
                <h2 class="font-display font-semibold text-xl text-ink-900">Rekapitulasi Absensi</h2>
                <p class="text-sm text-ink-500 mt-1">{{ $class->code }} - {{ $class->title }} ({{ $class->academic_year }})</p>
            </div>
            <a href="{{ route('classes.attendance-recap.export', $class) }}" class="bg-mint-500 hover:bg-mint-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Unduh Rekap Excel
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-ink-100">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-ink-50 text-ink-700 border-b border-ink-100">
                    <tr>
                        <th class="px-4 py-3 font-medium sticky left-0 bg-ink-50 z-10 border-r border-ink-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">Nama Siswa</th>
                        @foreach($meetings as $meeting)
                            <th class="px-3 py-3 font-medium text-center" title="{{ $meeting->title }}">
                                P{{ $meeting->meeting_number }}
                            </th>
                        @endforeach
                        <th class="px-3 py-3 font-medium text-center border-l border-ink-100 bg-mint-50 text-mint-700">H</th>
                        <th class="px-3 py-3 font-medium text-center bg-blue-50 text-blue-700">I</th>
                        <th class="px-3 py-3 font-medium text-center bg-amber-50 text-amber-700">S</th>
                        <th class="px-3 py-3 font-medium text-center bg-rose-50 text-rose-700">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse($students as $student)
                        @php
                            $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
                        @endphp
                        <tr class="hover:bg-ink-50/50">
                            <td class="px-4 py-3 font-medium text-ink-900 sticky left-0 bg-white z-10 border-r border-ink-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                {{ $student->name }}
                                <div class="text-[10px] text-ink-400 font-normal">{{ $student->nip_nis }}</div>
                            </td>
                            
                            @foreach($meetings as $meeting)
                                @php
                                    $att = $attendances->where('meeting_id', $meeting->id)->where('student_id', $student->id)->first();
                                    $status = $att ? $att->status : null;
                                    
                                    if ($status) $rekap[$status]++;
                                    
                                    $badgeClass = 'text-ink-300'; // Default jika kosong
                                    if ($status === 'hadir') $badgeClass = 'text-mint-600 font-bold';
                                    if ($status === 'izin') $badgeClass = 'text-blue-600 font-bold';
                                    if ($status === 'sakit') $badgeClass = 'text-amber-600 font-bold';
                                    if ($status === 'alpa') $badgeClass = 'text-rose-600 font-bold';
                                @endphp
                                <td class="px-3 py-3 text-center {{ $badgeClass }}">
                                    {{ $status ? strtoupper(substr($status, 0, 1)) : '-' }}
                                </td>
                            @endforeach
                            
                            <td class="px-3 py-3 text-center font-bold border-l border-ink-100 bg-mint-50/30 text-mint-700">{{ $rekap['hadir'] }}</td>
                            <td class="px-3 py-3 text-center font-bold bg-blue-50/30 text-blue-700">{{ $rekap['izin'] }}</td>
                            <td class="px-3 py-3 text-center font-bold bg-amber-50/30 text-amber-700">{{ $rekap['sakit'] }}</td>
                            <td class="px-3 py-3 text-center font-bold bg-rose-50/30 text-rose-700">{{ $rekap['alpa'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $meetings->count() + 5 }}" class="px-4 py-6 text-center text-ink-400">Belum ada siswa di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex gap-4 text-xs text-ink-500">
            <span class="font-medium text-ink-700">Keterangan:</span>
            <span><strong class="text-mint-600">H</strong>: Hadir</span>
            <span><strong class="text-blue-600">I</strong>: Izin</span>
            <span><strong class="text-amber-600">S</strong>: Sakit</span>
            <span><strong class="text-rose-600">A</strong>: Alpa</span>
            <span><strong>-</strong>: Belum diisi</span>
        </div>
    </div>
@endsection