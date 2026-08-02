<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsFileSize;
use App\Models\Meeting;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    use FormatsFileSize;

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);
        abort_unless($meeting->has_assignment, 400, 'Pertemuan ini tidak memiliki tugas.');

        $class = $meeting->classRoom;
        abort_unless($class->students()->where('student_id', $user->id)->exists(), 403, 'Anda belum terdaftar di kelas ini.');

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'student_note' => ['nullable', 'string'],
        ]);

        $path = $request->file('file')->store('submissions/'.$meeting->id, 'public');
        $now = now();
        $isLate = $meeting->assignment_deadline !== null && $now->greaterThan($meeting->assignment_deadline);

        Submission::updateOrCreate(
            ['meeting_id' => $meeting->id, 'student_id' => $user->id],
            [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $this->formatBytes($request->file('file')->getSize()),
                'student_note' => $data['student_note'] ?? null,
                'submitted_at' => $now,
                'is_late' => $isLate,
                // A resubmission is graded fresh, rather than silently keeping
                // a grade that was given for a different file.
                'grade' => null,
                'feedback' => null,
                'graded_at' => null,
                'graded_by' => null,
            ]
        );

        $message = $isLate
            ? 'Tugas berhasil dikumpulkan! Catatan: pengumpulan melewati batas waktu (Terlambat).'
            : 'Tugas berhasil dikumpulkan tepat waktu!';

        return back()->with('status', $message);
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        $user = $request->user();
        $class = $submission->meeting->classRoom;
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        $data = $request->validate([
            'grade' => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
        ]);

        $submission->update([
            'grade' => $data['grade'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $user->id,
        ]);

        return back()->with('status', 'Nilai dan catatan berhasil disimpan.');
    }

    public function download(Request $request, Submission $submission): StreamedResponse
    {
        $user = $request->user();
        $class = $submission->meeting->classRoom;

        if ($user->isStudent()) {
            abort_unless($submission->student_id === $user->id, 403);
        } elseif ($user->isTeacher()) {
            abort_unless($class->teacher_id === $user->id, 403);
        }

        abort_unless(Storage::disk('public')->exists($submission->file_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('public')->download($submission->file_path, $submission->file_name);
    }
}
