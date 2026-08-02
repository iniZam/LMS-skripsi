<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    private array $gradients = [
        'from-blue-600 to-indigo-700',
        'from-emerald-600 to-teal-700',
        'from-purple-600 to-pink-700',
        'from-amber-600 to-orange-700',
        'from-cyan-600 to-blue-700',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $classes = ClassRoom::with('teacher')->withCount(['meetings', 'students'])->latest()->paginate(10);

            return view('classes.index', ['classes' => $classes]);
        }

        if ($user->isTeacher()) {
            $classes = $user->taughtClasses()->withCount(['meetings', 'students'])->latest()->paginate(10);

            return view('classes.index', ['classes' => $classes]);
        }

        // Student: split into "my classes" and classes open for registration.
        $myClasses = $user->enrolledClasses()->with('teacher')->withCount('meetings')->get();
        $availableClasses = ClassRoom::where('status', ClassRoom::STATUS_REGISTRATION)
            ->whereDoesntHave('students', fn ($q) => $q->where('student_id', $user->id))
            ->with('teacher')
            ->withCount('meetings')
            ->get();

        return view('classes.index', [
            'classes' => null,
            'myClasses' => $myClasses,
            'availableClasses' => $availableClasses,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isTeacher(), 403);

        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('name')->get();

        return view('classes.create', compact('teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:classes,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subject_category' => ['nullable', 'string', 'max:255'],
            'teacher_id' => [$user->isAdmin() ? 'required' : 'nullable', 'exists:users,id'],
            'status' => ['required', 'in:registration,active,completed'],
            'academic_year' => ['nullable', 'string', 'max:50'],
        ]);

        $teacherId = $user->isTeacher() ? $user->id : (int) $data['teacher_id'];

        $class = ClassRoom::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'subject_category' => $data['subject_category'] ?? 'Umum',
            'teacher_id' => $teacherId,
            'status' => $data['status'],
            'academic_year' => $data['academic_year'] ?: '2026/2027 Ganjil',
            'banner_gradient' => $this->gradients[array_rand($this->gradients)],
        ]);

        return redirect()->route('classes.show', $class)->with('status', 'Kelas baru berhasil dibuat.');
    }

    public function show(Request $request, ClassRoom $class): View
    {
        $user = $request->user();
        $isEnrolled = false;

        if ($user->isStudent()) {
            $isEnrolled = $class->students()->where('student_id', $user->id)->exists();
            // Students may preview an open-for-registration class before joining;
            // otherwise the class must already be joined to view it.
            abort_unless($isEnrolled || $class->status === ClassRoom::STATUS_REGISTRATION, 403, 'Anda belum terdaftar di kelas ini.');
        } elseif ($user->isTeacher()) {
            abort_unless($class->teacher_id === $user->id, 403);
        }

        $class->load(['teacher', 'students', 'meetings.materials', 'meetings.submissions']);

        return view('classes.show', compact('class', 'isEnrolled'));
    }

    public function edit(Request $request, ClassRoom $class): View
    {
        $this->authorizeManage($request, $class);

        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('name')->get();

        return view('classes.edit', compact('class', 'teachers'));
    }

    public function update(Request $request, ClassRoom $class): RedirectResponse
    {
        $this->authorizeManage($request, $class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subject_category' => ['nullable', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:registration,active,completed'],
            'academic_year' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->user()->isAdmin() && $data['teacher_id']) {
            $class->teacher_id = $data['teacher_id'];
        }

        $class->title = $data['title'];
        $class->description = $data['description'] ?? $class->description;
        $class->subject_category = $data['subject_category'] ?? $class->subject_category;
        $class->status = $data['status'];
        $class->academic_year = $data['academic_year'] ?? $class->academic_year;
        $class->save();

        return redirect()->route('classes.show', $class)->with('status', 'Data kelas berhasil diperbarui.');
    }

    public function enroll(Request $request, ClassRoom $class): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        if ($class->status === ClassRoom::STATUS_COMPLETED) {
            return back()->withErrors(['enroll' => 'Kelas ini sudah selesai dan tidak menerima pendaftaran.']);
        }

        if ($class->students()->where('student_id', $user->id)->exists()) {
            return back()->withErrors(['enroll' => 'Anda sudah terdaftar di kelas ini.']);
        }

        $class->students()->attach($user->id);

        return redirect()->route('classes.show', $class)->with('status', "Berhasil mendaftar ke kelas {$class->title}!");
    }

    private function authorizeManage(Request $request, ClassRoom $class): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);
    }
}
