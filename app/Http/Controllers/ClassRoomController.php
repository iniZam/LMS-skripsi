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

        // Student: only the Mata Pelajaran they've been enrolled into by an
        // admin. There is no self-service enrollment — see addStudent().
        $myClasses = $user->enrolledClasses()->with('teacher')->withCount('meetings')->get();

        return view('classes.index', [
            'classes' => null,
            'myClasses' => $myClasses,
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

        if ($user->isStudent()) {
            // Students only ever see a Mata Pelajaran they've already been
            // enrolled into by an admin — no self-service preview/enroll.
            abort_unless($class->students()->where('student_id', $user->id)->exists(), 403, 'Anda belum terdaftar di kelas ini.');
        } elseif ($user->isTeacher()) {
            abort_unless($class->teacher_id === $user->id, 403);
        }

        $class->load(['teacher', 'students', 'meetings.materials', 'meetings.submissions']);

        $availableStudents = null;
        if ($user->isAdmin()) {
            $enrolledIds = $class->students->pluck('id');
            $availableStudents = User::where('role', User::ROLE_STUDENT)
                ->whereNotIn('id', $enrolledIds)
                ->with('kelas')
                ->orderBy('name')
                ->get();
        }

        return view('classes.show', compact('class', 'availableStudents'));
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

    /**
     * Admin-only: add a student to this Mata Pelajaran's roster.
     * There is no student-facing self-enrollment anymore.
     */
    public function addStudent(Request $request, ClassRoom $class): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($class->status === ClassRoom::STATUS_COMPLETED) {
            return back()->withErrors(['student' => 'Tidak dapat menambahkan siswa ke kelas yang sudah selesai.']);
        }

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ]);

        $student = User::where('id', $data['student_id'])->where('role', User::ROLE_STUDENT)->first();
        abort_unless($student, 404);

        if ($class->students()->where('student_id', $student->id)->exists()) {
            return back()->withErrors(['student' => "{$student->name} sudah terdaftar di kelas ini."]);
        }

        $class->students()->attach($student->id);

        return redirect()->route('classes.show', $class)->with('status', "{$student->name} berhasil ditambahkan ke {$class->title}.");
    }

    /**
     * Admin-only: remove a student from this Mata Pelajaran's roster.
     */
    public function removeStudent(Request $request, ClassRoom $class, User $student): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $class->students()->detach($student->id);

        return back()->with('status', "{$student->name} dikeluarkan dari {$class->title}.");
    }

    private function authorizeManage(Request $request, ClassRoom $class): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);
    }
}
