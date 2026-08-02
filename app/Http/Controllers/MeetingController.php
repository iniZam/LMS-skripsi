<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsFileSize;
use App\Models\ClassRoom;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MeetingController extends Controller
{
    use FormatsFileSize;

    public function create(Request $request, ClassRoom $class): View
    {
        $this->authorizeManage($request, $class);

        $nextNumber = (int) $class->meetings()->max('meeting_number') + 1;

        return view('meetings.create', compact('class', 'nextNumber'));
    }

    public function store(Request $request, ClassRoom $class): RedirectResponse
    {
        $this->authorizeManage($request, $class);

        $data = $this->validateMeeting($request);

        $meetingNumber = (int) $class->meetings()->max('meeting_number') + 1;

        $meeting = $class->meetings()->create([
            'meeting_number' => $meetingNumber,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'youtube_url' => $data['youtube_url'] ?? null,
            'has_assignment' => $request->boolean('has_assignment'),
            'assignment_title' => $data['assignment_title'] ?? null,
            'assignment_instructions' => $data['assignment_instructions'] ?? null,
            'assignment_deadline' => $data['assignment_deadline'] ?? null,
        ]);

        $this->storeMaterials($request, $meeting);

        return redirect()->route('classes.show', $class)->with('status', 'Pertemuan baru berhasil ditambahkan.');
    }

    public function show(Request $request, Meeting $meeting): View
    {
        $user = $request->user();
        $meeting->load(['classRoom', 'materials']);
        $class = $meeting->classRoom;

        if ($user->isStudent()) {
            abort_unless($class->students()->where('student_id', $user->id)->exists(), 403, 'Anda belum terdaftar di kelas ini.');
            $mySubmission = $meeting->submissions()->where('student_id', $user->id)->first();

            return view('meetings.show', compact('meeting', 'class', 'mySubmission'));
        }

        abort_unless($user->isAdmin() || $class->teacher_id === $user->id, 403);
        $submissions = $meeting->submissions()->with('student')->latest('submitted_at')->get();

        return view('meetings.show', compact('meeting', 'class', 'submissions'));
    }

    public function edit(Request $request, Meeting $meeting): View
    {
        $class = $meeting->classRoom;
        $this->authorizeManage($request, $class);

        return view('meetings.edit', compact('meeting', 'class'));
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $class = $meeting->classRoom;
        $this->authorizeManage($request, $class);

        $data = $this->validateMeeting($request);

        $meeting->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'youtube_url' => $data['youtube_url'] ?? null,
            'has_assignment' => $request->boolean('has_assignment'),
            'assignment_title' => $data['assignment_title'] ?? null,
            'assignment_instructions' => $data['assignment_instructions'] ?? null,
            'assignment_deadline' => $data['assignment_deadline'] ?? null,
        ]);

        $this->storeMaterials($request, $meeting);

        return redirect()->route('classes.show', $class)->with('status', 'Data pertemuan berhasil diperbarui.');
    }

    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        $class = $meeting->classRoom;
        $this->authorizeManage($request, $class);

        foreach ($meeting->materials as $material) {
            Storage::disk('public')->delete($material->file_path);
        }
        foreach ($meeting->submissions as $submission) {
            Storage::disk('public')->delete($submission->file_path);
        }

        $meeting->delete();

        return redirect()->route('classes.show', $class)->with('status', 'Pertemuan berhasil dihapus.');
    }

    private function validateMeeting(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'has_assignment' => ['nullable', 'boolean'],
            'assignment_title' => ['nullable', 'string', 'max:255', 'required_if:has_assignment,1'],
            'assignment_instructions' => ['nullable', 'string'],
            'assignment_deadline' => ['nullable', 'date'],
            'materials.*' => ['nullable', 'file', 'max:20480'],
        ]);
    }

    private function storeMaterials(Request $request, Meeting $meeting): void
    {
        if (! $request->hasFile('materials')) {
            return;
        }

        foreach ($request->file('materials') as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('materials/'.$meeting->id, 'public');

            $meeting->materials()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $this->formatBytes($file->getSize()),
                'file_type' => $file->getClientMimeType(),
            ]);
        }
    }

    private function authorizeManage(Request $request, ClassRoom $class): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);
    }
}
