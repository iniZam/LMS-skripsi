<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Student: only the Mata Pelajaran they've been enrolled into by an admin.
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
    
    public function attendanceRecap(Request $request, ClassRoom $class)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        // Ambil semua pertemuan yang diurutkan berdasarkan nomor pertemuan
        $meetings = $class->meetings()->orderBy('meeting_number')->get();
        
        // Ambil semua siswa yang terdaftar di kelas ini
        $students = $class->students()->orderBy('name')->get();
        
        // Ambil seluruh data absensi untuk pertemuan-pertemuan di kelas ini
        $attendances = Attendance::whereIn('meeting_id', $meetings->pluck('id'))->get();

        return view('classes.attendance_recap', compact('class', 'meetings', 'students', 'attendances'));
    }

    public function exportAttendanceRecap(Request $request, ClassRoom $class)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        $fileName = "Rekap_Absensi_{$class->code}.csv";
        $meetings = $class->meetings()->orderBy('meeting_number')->get();
        $students = $class->students()->orderBy('name')->get();
        $attendances = Attendance::whereIn('meeting_id', $meetings->pluck('id'))->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($students, $meetings, $attendances) {
            $file = fopen('php://output', 'w');
            
            // Susun Header Kolom
            $headerRow = ['No', 'Nama Siswa', 'NISN'];
            foreach ($meetings as $meeting) {
                $headerRow[] = "P" . $meeting->meeting_number;
            }
            $headerRow = array_merge($headerRow, ['Total Hadir', 'Total Izin', 'Total Sakit', 'Total Alpa']);
            fputcsv($file, $headerRow);

            // Susun Data Baris per Siswa
            $rowNum = 1;
            foreach ($students as $student) {
                $studentRow = [$rowNum++, $student->name, $student->nip_nis];
                $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];

                foreach ($meetings as $meeting) {
                    $att = $attendances->where('meeting_id', $meeting->id)->where('student_id', $student->id)->first();
                    $status = $att ? $att->status : '-';
                    $studentRow[] = strtoupper($status);
                    
                    if ($att) {
                        $rekap[$att->status]++;
                    }
                }
                
                $studentRow[] = $rekap['hadir'];
                $studentRow[] = $rekap['izin'];
                $studentRow[] = $rekap['sakit'];
                $studentRow[] = $rekap['alpa'];

                fputcsv($file, $studentRow);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    // ==========================================
    // FITUR RAPOT / REKAP NILAI & ABSENSI
    // ==========================================

    public function rapotIndex(Request $request)
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $enrolledClasses = $user->enrolledClasses()->with('teacher')->orderBy('title')->get();
            $subjectReports = [];

            foreach ($enrolledClasses as $class) {
                $data = $this->buildClassRapotData($class, $user->id);
                $studentRow = $data['studentReports']->first();
                if ($studentRow) {
                    $subjectReports[] = [
                        'class' => $class,
                        'report' => $studentRow,
                        'totalAssignments' => $data['assignmentMeetings']->count(),
                        'totalQuizzes' => $data['quizzes']->count(),
                        'totalMeetings' => $data['meetings']->count(),
                    ];
                }
            }

            return view('classes.rapot_student', compact('subjectReports'));
        }

        $teacherClasses = $user->isTeacher()
            ? $user->taughtClasses()->orderBy('title')->get()
            : ClassRoom::with('teacher')->orderBy('title')->get();

        $selectedClassId = $request->query('class_id');
        $selectedClass = $selectedClassId
            ? $teacherClasses->firstWhere('id', (int) $selectedClassId)
            : $teacherClasses->first();

        if (! $selectedClass) {
            return view('classes.rapot', [
                'class' => null,
                'teacherClasses' => $teacherClasses,
                'meetings' => collect(),
                'assignmentMeetings' => collect(),
                'quizzes' => collect(),
                'studentReports' => collect(),
            ]);
        }

        $rapotData = $this->buildClassRapotData($selectedClass);
        $rapotData['teacherClasses'] = $teacherClasses;

        return view('classes.rapot', $rapotData);
    }

    public function rapot(Request $request, ClassRoom $class): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        $teacherClasses = $user->isTeacher()
            ? $user->taughtClasses()->orderBy('title')->get()
            : ClassRoom::with('teacher')->orderBy('title')->get();

        $rapotData = $this->buildClassRapotData($class);
        $rapotData['teacherClasses'] = $teacherClasses;

        return view('classes.rapot', $rapotData);
    }

    public function exportRapot(Request $request, ClassRoom $class): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        $fileName = "Rapot_Rekap_Nilai_{$class->code}.csv";
        $data = $this->buildClassRapotData($class);
        $assignmentMeetings = $data['assignmentMeetings'];
        $quizzes = $data['quizzes'];
        $studentReports = $data['studentReports'];

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($assignmentMeetings, $quizzes, $studentReports) {
            $file = fopen('php://output', 'w');

            $headerRow = ['No', 'Nama Siswa', 'NISN'];
            foreach ($assignmentMeetings as $idx => $m) {
                $headerRow[] = 'Tugas ' . ($idx + 1) . ' (P' . $m->meeting_number . ')';
            }
            $headerRow[] = 'Rata-rata Tugas';

            foreach ($quizzes as $idx => $q) {
                $headerRow[] = 'Kuis ' . ($idx + 1) . ' (P' . $q->meeting->meeting_number . ')';
            }
            $headerRow[] = 'Rata-rata Kuis';

            $headerRow = array_merge($headerRow, [
                'Hadir (H)',
                'Izin (I)',
                'Sakit (S)',
                'Alpa (A)',
                '% Kehadiran',
                'Nilai Akhir',
                'Predikat',
            ]);
            fputcsv($file, $headerRow);

            $rowNum = 1;
            foreach ($studentReports as $row) {
                $student = $row['student'];
                $csvRow = [$rowNum++, $student->name, $student->nip_nis];

                foreach ($assignmentMeetings as $m) {
                    $scoreInfo = $row['assignment_scores'][$m->id] ?? null;
                    $csvRow[] = $scoreInfo && $scoreInfo['grade'] !== null ? $scoreInfo['grade'] : 0;
                }
                $csvRow[] = $row['avg_assignment'];

                foreach ($quizzes as $q) {
                    $qInfo = $row['quiz_scores'][$q->id] ?? null;
                    $csvRow[] = $qInfo && $qInfo['score'] !== null ? round($qInfo['score'], 1) : 0;
                }
                $csvRow[] = $row['avg_quiz'];

                $csvRow[] = $row['attendance']['hadir'];
                $csvRow[] = $row['attendance']['izin'];
                $csvRow[] = $row['attendance']['sakit'];
                $csvRow[] = $row['attendance']['alpa'];
                $csvRow[] = $row['attendance_rate'] . '%';
                $csvRow[] = $row['final_score'];
                $csvRow[] = $row['predicate'];

                fputcsv($file, $csvRow);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function buildClassRapotData(ClassRoom $class, ?int $onlyStudentId = null): array
    {
        $meetings = $class->meetings()->orderBy('meeting_number')->get();
        $assignmentMeetings = $meetings->where('has_assignment', true)->values();
        $quizzes = Quiz::with('meeting')
            ->whereIn('meeting_id', $meetings->pluck('id'))
            ->orderBy('meeting_id')
            ->get();

        $studentsQuery = $class->students()->orderBy('name');
        if ($onlyStudentId) {
            $studentsQuery->where('users.id', $onlyStudentId);
        }
        $students = $studentsQuery->get();

        $submissions = Submission::whereIn('meeting_id', $assignmentMeetings->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->whereNotNull('completed_at')
            ->get();

        $attendances = Attendance::whereIn('meeting_id', $meetings->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $studentReports = $students->map(function (User $student) use (
            $meetings,
            $assignmentMeetings,
            $quizzes,
            $submissions,
            $quizAttempts,
            $attendances
        ) {
            // 1. Rekap Nilai Tugas
            $assignmentScores = [];
            $totalAssignmentScore = 0;
            foreach ($assignmentMeetings as $m) {
                $sub = $submissions->where('meeting_id', $m->id)->where('student_id', $student->id)->first();
                $grade = $sub ? $sub->grade : null;
                $assignmentScores[$m->id] = [
                    'submitted' => (bool) $sub,
                    'grade' => $grade,
                ];
                $totalAssignmentScore += ($grade ?? 0);
            }
            $avgAssignment = $assignmentMeetings->count() > 0
                ? round($totalAssignmentScore / $assignmentMeetings->count(), 1)
                : 0;

            // 2. Rekap Nilai Kuis
            $quizScores = [];
            $totalQuizScore = 0;
            foreach ($quizzes as $q) {
                $att = $quizAttempts->where('quiz_id', $q->id)->where('student_id', $student->id)->first();
                $score = $att ? (float) $att->score : null;
                $quizScores[$q->id] = [
                    'completed' => (bool) $att,
                    'score' => $score,
                ];
                $totalQuizScore += ($score ?? 0);
            }
            $avgQuiz = $quizzes->count() > 0
                ? round($totalQuizScore / $quizzes->count(), 1)
                : 0;

            // 3. Rekap Absensi
            $rekapAbsen = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
            foreach ($meetings as $m) {
                $att = $attendances->where('meeting_id', $m->id)->where('student_id', $student->id)->first();
                if ($att && isset($rekapAbsen[$att->status])) {
                    $rekapAbsen[$att->status]++;
                }
            }
            $attendanceRate = $meetings->count() > 0
                ? round(($rekapAbsen['hadir'] / $meetings->count()) * 100, 1)
                : 0;

            // 4. Nilai Akhir & Predikat
            if ($assignmentMeetings->count() > 0 && $quizzes->count() > 0) {
                $finalScore = round(($avgAssignment + $avgQuiz) / 2, 1);
            } elseif ($assignmentMeetings->count() > 0) {
                $finalScore = $avgAssignment;
            } elseif ($quizzes->count() > 0) {
                $finalScore = $avgQuiz;
            } else {
                $finalScore = 0;
            }

            $predicate = match (true) {
                $finalScore >= 85 => 'A',
                $finalScore >= 75 => 'B',
                $finalScore >= 60 => 'C',
                default => 'D',
            };

            return [
                'student' => $student,
                'assignment_scores' => $assignmentScores,
                'avg_assignment' => $avgAssignment,
                'quiz_scores' => $quizScores,
                'avg_quiz' => $avgQuiz,
                'attendance' => $rekapAbsen,
                'attendance_rate' => $attendanceRate,
                'final_score' => $finalScore,
                'predicate' => $predicate,
            ];
        });

        return [
            'class' => $class,
            'meetings' => $meetings,
            'assignmentMeetings' => $assignmentMeetings,
            'quizzes' => $quizzes,
            'studentReports' => $studentReports,
        ];
    }
}