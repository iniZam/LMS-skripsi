<?php

namespace App\Http\Controllers;

use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherAttendanceController extends Controller
{
    private const TIMEZONE = 'Asia/Makassar';

    private function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /**
     * Halaman absensi harian untuk Guru.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isTeacher(), 403);

        $now = $this->now();
        $today = $now->toDateString();

        $todayAttendance = TeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);

        $histories = TeacherAttendance::where('teacher_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderByDesc('date')
            ->get();

        $summary = [
            'hadir' => $histories->where('status', 'hadir')->count(),
            'izin' => $histories->where('status', 'izin')->count(),
            'sakit' => $histories->where('status', 'sakit')->count(),
            'alpa' => $histories->where('status', 'alpa')->count(),
        ];

        return view('teacher_attendance.index', compact(
            'todayAttendance',
            'histories',
            'summary',
            'month',
            'year',
            'now'
        ));
    }

    /**
     * Proses Absen Datang (Masuk) atau pengajuan Izin / Sakit oleh Guru.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isTeacher(), 403);

        $data = $request->validate([
            'status' => ['nullable', 'in:hadir,izin,sakit'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $now = $this->now();
        $today = $now->toDateString();
        $status = $data['status'] ?? 'hadir';

        $existing = TeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return back()->withErrors(['attendance' => 'Anda sudah melakukan absen datang hari ini.']);
        }

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $user->id,
                'date' => $today,
            ],
            [
                'check_in_time' => $status === 'hadir' ? $now->format('H:i:s') : null,
                'status' => $status,
                'notes' => $data['notes'] ?? ($existing?->notes),
            ]
        );

        $msg = $status === 'hadir'
            ? 'Berhasil melakukan Absen Datang pada pukul ' . $now->format('H:i') . ' WITA.'
            : 'Keterangan ' . strtoupper($status) . ' berhasil disimpan untuk hari ini.';

        return back()->with('status', $msg);
    }

    /**
     * Proses Absen Pulang oleh Guru.
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isTeacher(), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $now = $this->now();
        $today = $now->toDateString();

        $attendance = TeacherAttendance::where('teacher_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in_time) {
            return back()->withErrors(['attendance' => 'Anda belum melakukan absen datang hari ini.']);
        }

        if ($attendance->check_out_time) {
            return back()->withErrors(['attendance' => 'Anda sudah melakukan absen pulang hari ini.']);
        }

        $attendance->check_out_time = $now->format('H:i:s');
        if (! empty($data['notes'])) {
            $attendance->notes = $data['notes'];
        }
        $attendance->save();

        return back()->with('status', 'Berhasil melakukan Absen Pulang pada pukul ' . $now->format('H:i') . ' WITA.');
    }

    /**
     * Halaman Admin untuk merekap seluruh absensi guru.
     */
    public function adminRecap(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $now = $this->now();
        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);
        $selectedDate = $request->input('date', $now->toDateString());
        $teacherId = $request->input('teacher_id');

        $teachers = User::where('role', User::ROLE_TEACHER)
            ->orderBy('name')
            ->get();

        // Data absensi seluruh guru pada bulan & tahun terpilih
        $monthlyAttendancesQuery = TeacherAttendance::with('teacher')
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($teacherId) {
            $monthlyAttendancesQuery->where('teacher_id', $teacherId);
        }

        $monthlyAttendances = $monthlyAttendancesQuery
            ->orderByDesc('date')
            ->orderByDesc('check_in_time')
            ->get();

        // Data absensi pada tanggal terpilih (untuk pemantauan harian & input manual admin)
        $dailyAttendances = TeacherAttendance::whereDate('date', $selectedDate)
            ->get()
            ->keyBy('teacher_id');

        // Hitung rekap per guru untuk bulan & tahun terpilih
        $allMonthRecords = TeacherAttendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('teacher_id');

        $teacherSummary = $teachers->map(function (User $teacher) use ($allMonthRecords) {
            $records = $allMonthRecords->get($teacher->id, collect());
            $completedDays = $records->whereNotNull('check_in_time')->whereNotNull('check_out_time')->count();

            return [
                'teacher' => $teacher,
                'hadir' => $records->where('status', 'hadir')->count(),
                'izin' => $records->where('status', 'izin')->count(),
                'sakit' => $records->where('status', 'sakit')->count(),
                'alpa' => $records->where('status', 'alpa')->count(),
                'completed_out' => $completedDays,
                'total_recorded' => $records->count(),
            ];
        });

        return view('teacher_attendance.admin_recap', compact(
            'teachers',
            'monthlyAttendances',
            'dailyAttendances',
            'teacherSummary',
            'month',
            'year',
            'selectedDate',
            'teacherId',
            'now'
        ));
    }

    /**
     * Admin menyimpan atau memperbarui absensi guru secara manual.
     */
    public function adminStoreOrUpdate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:hadir,izin,sakit,alpa'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $teacher = User::where('id', $data['teacher_id'])->where('role', User::ROLE_TEACHER)->firstOrFail();

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'date' => $data['date'],
            ],
            [
                'status' => $data['status'],
                'check_in_time' => ! empty($data['check_in_time']) ? $data['check_in_time'] . ':00' : null,
                'check_out_time' => ! empty($data['check_out_time']) ? $data['check_out_time'] . ':00' : null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return back()->with('status', "Data absensi {$teacher->name} pada tanggal {$data['date']} berhasil diperbarui.");
    }

    /**
     * Ekspor Rekapitulasi Absensi Guru ke CSV/Excel.
     */
    public function exportRecap(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $now = $this->now();
        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthLabel = $monthNames[$month] ?? $month;

        $fileName = "Rekap_Absensi_Guru_{$monthLabel}_{$year}.csv";

        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('name')->get();
        $records = TeacherAttendance::with('teacher')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $groupedByTeacher = $records->groupBy('teacher_id');

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($teachers, $groupedByTeacher, $records, $monthLabel, $year) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Bagian 1: Ringkasan Rekapitulasi Bulanan Per Guru
            fputcsv($file, ["REKAPITULASI ABSENSI GURU - PERIODE " . strtoupper($monthLabel) . " {$year}"]);
            fputcsv($file, []);
            fputcsv($file, ['No', 'Nama Guru', 'NIP', 'Hadir', 'Sudah Absen Pulang', 'Izin', 'Sakit', 'Alpa', 'Total Hari Tercatat']);

            $no = 1;
            foreach ($teachers as $teacher) {
                $tRecords = $groupedByTeacher->get($teacher->id, collect());
                fputcsv($file, [
                    $no++,
                    $teacher->name,
                    $teacher->nip_nis,
                    $tRecords->where('status', 'hadir')->count(),
                    $tRecords->whereNotNull('check_in_time')->whereNotNull('check_out_time')->count(),
                    $tRecords->where('status', 'izin')->count(),
                    $tRecords->where('status', 'sakit')->count(),
                    $tRecords->where('status', 'alpa')->count(),
                    $tRecords->count(),
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['DETAIL LOG HARIAN ABSENSI GURU']);
            fputcsv($file, ['No', 'Tanggal', 'Nama Guru', 'NIP', 'Status', 'Jam Absen Datang', 'Jam Absen Pulang', 'Durasi', 'Keterangan / Catatan']);

            $logNo = 1;
            foreach ($records as $rec) {
                fputcsv($file, [
                    $logNo++,
                    $rec->date->format('Y-m-d'),
                    $rec->teacher?->name ?? '-',
                    $rec->teacher?->nip_nis ?? '-',
                    strtoupper($rec->status),
                    $rec->check_in_time ?? '-',
                    $rec->check_out_time ?? '-',
                    $rec->durationLabel(),
                    $rec->notes ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

