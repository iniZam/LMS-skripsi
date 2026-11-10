<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Meeting;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    // ==========================================
    // DAFTAR KUIS, UTS & UAS (Guru, Admin & Siswa)
    // ==========================================

    public function index(Request $request)
    {
        $user = $request->user();
        $typeFilter = $request->query('type'); // quiz, uts, uas

        if ($user->isStudent()) {
            $enrolledClassIds = $user->enrolledClasses()->pluck('classes.id');

            $allQuizzesQuery = Quiz::with(['meeting.classRoom.teacher', 'classRoom.teacher'])
                ->withCount('questions')
                ->where('approval_status', Quiz::STATUS_APPROVED)
                ->where(function ($q) use ($enrolledClassIds) {
                    $q->whereIn('class_id', $enrolledClassIds)
                      ->orWhereHas('meeting', function ($mq) use ($enrolledClassIds) {
                          $mq->whereIn('class_id', $enrolledClassIds);
                      });
                });

            if (in_array($typeFilter, [Quiz::TYPE_QUIZ, Quiz::TYPE_UTS, Quiz::TYPE_UAS], true)) {
                $allQuizzesQuery->where('type', $typeFilter);
            }

            $allQuizzes = $allQuizzesQuery->latest()->get();

            $attemptsByQuiz = QuizAttempt::where('student_id', $user->id)
                ->whereIn('quiz_id', $allQuizzes->pluck('id'))
                ->get()
                ->keyBy('quiz_id');

            [$completedQuizzes, $pendingQuizzes] = $allQuizzes->partition(function (Quiz $quiz) use ($attemptsByQuiz) {
                $attempt = $attemptsByQuiz->get($quiz->id);
                return $attempt && $attempt->completed_at !== null;
            });

            return view('quizzes.index', compact(
                'pendingQuizzes',
                'completedQuizzes',
                'attemptsByQuiz',
                'typeFilter'
            ));
        }

        // Untuk Guru & Admin
        $classId = $request->query('class_id');
        $statusFilter = $request->query('approval_status');

        $query = Quiz::with(['meeting.classRoom.teacher', 'classRoom.teacher'])
            ->withCount([
                'questions',
                'attempts as completed_attempts_count' => function ($q) {
                    $q->whereNotNull('completed_at');
                },
            ]);

        if ($user->isTeacher()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('classRoom', fn ($cq) => $cq->where('teacher_id', $user->id))
                  ->orWhereHas('meeting.classRoom', fn ($mq) => $mq->where('teacher_id', $user->id));
            });
            $teacherClasses = $user->taughtClasses()->orderBy('title')->get();
        } else {
            $teacherClasses = ClassRoom::orderBy('title')->get();
        }

        if ($classId) {
            $query->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                  ->orWhereHas('meeting', fn ($mq) => $mq->where('class_id', $classId));
            });
        }

        if (in_array($typeFilter, [Quiz::TYPE_QUIZ, Quiz::TYPE_UTS, Quiz::TYPE_UAS], true)) {
            $query->where('type', $typeFilter);
        }

        if (in_array($statusFilter, [Quiz::STATUS_APPROVED, Quiz::STATUS_PENDING, Quiz::STATUS_REJECTED], true)) {
            $query->where('approval_status', $statusFilter);
        }

        $quizzes = $query->latest()->paginate(10)->withQueryString();

        return view('quizzes.index', compact('quizzes', 'teacherClasses', 'classId', 'typeFilter', 'statusFilter'));
    }

    // ==========================================
    // MANAJEMEN KUIS / UTS / UAS (Dari Pertemuan)
    // ==========================================

    public function create(Request $request, Meeting $meeting)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);
        $defaultType = in_array($request->query('type'), [Quiz::TYPE_QUIZ, Quiz::TYPE_UTS, Quiz::TYPE_UAS], true)
            ? $request->query('type')
            : Quiz::TYPE_QUIZ;

        return view('quizzes.create', compact('meeting', 'defaultType'));
    }

    public function store(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $validated = $request->validate([
            'type' => 'required|in:quiz,uts,uas',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'deadline' => 'nullable|date',
        ]);

        $isExam = in_array($validated['type'], [Quiz::TYPE_UTS, Quiz::TYPE_UAS], true);
        // UTS & UAS wajib mendapat ACC Admin untuk bisa rilis ke siswa
        $approvalStatus = ($isExam && ! $user->isAdmin())
            ? Quiz::STATUS_PENDING
            : Quiz::STATUS_APPROVED;

        $quiz = $meeting->quizzes()->create([
            'class_id' => $meeting->class_id,
            'type' => $validated['type'],
            'approval_status' => $approvalStatus,
            'approved_by' => $approvalStatus === Quiz::STATUS_APPROVED ? $user->id : null,
            'approved_at' => $approvalStatus === Quiz::STATUS_APPROVED ? now() : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'deadline' => $validated['deadline'] ?? null,
        ]);

        $msg = $isExam && $approvalStatus === Quiz::STATUS_PENDING
            ? strtoupper($validated['type']) . ' berhasil dibuat (Status: Menunggu ACC Admin untuk rilis). Silakan tambahkan soal ujian.'
            : 'Berhasil dibuat! Silakan tambahkan soal.';

        return redirect()->route('quizzes.manage', $quiz)->with('status', $msg);
    }

    // ==========================================
    // PEMBUATAN UTS / UAS LANGSUNG DARI MATA PELAJARAN / MENU UJIAN
    // ==========================================

    public function createExam(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $classes = $user->isTeacher()
            ? $user->taughtClasses()->with('meetings')->orderBy('title')->get()
            : ClassRoom::with('meetings')->orderBy('title')->get();

        $selectedClassId = $request->query('class_id');
        $defaultType = in_array($request->query('type'), [Quiz::TYPE_UTS, Quiz::TYPE_UAS], true)
            ? $request->query('type')
            : Quiz::TYPE_UTS;

        return view('quizzes.create_exam', compact('classes', 'selectedClassId', 'defaultType'));
    }

    public function storeExam(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'meeting_id' => 'nullable|exists:meetings,id',
            'type' => 'required|in:uts,uas',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'deadline' => 'nullable|date',
        ]);

        $classRoom = ClassRoom::findOrFail($validated['class_id']);
        if ($user->isTeacher()) {
            abort_unless($classRoom->teacher_id === $user->id, 403);
        }

        // Validasi meeting_id jika diisi harus milik class_id tersebut
        $meetingId = $validated['meeting_id'] ?? null;
        if ($meetingId) {
            $meetingExists = Meeting::where('id', $meetingId)->where('class_id', $classRoom->id)->exists();
            if (! $meetingExists) {
                $meetingId = null;
            }
        }

        // UTS & UAS yang dibuat guru berstatus pending (menunggu ACC Admin)
        $approvalStatus = $user->isAdmin() ? Quiz::STATUS_APPROVED : Quiz::STATUS_PENDING;

        $quiz = Quiz::create([
            'class_id' => $classRoom->id,
            'meeting_id' => $meetingId,
            'type' => $validated['type'],
            'approval_status' => $approvalStatus,
            'approved_by' => $user->isAdmin() ? $user->id : null,
            'approved_at' => $user->isAdmin() ? now() : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'deadline' => $validated['deadline'] ?? null,
        ]);

        $label = strtoupper($validated['type']);
        $msg = $approvalStatus === Quiz::STATUS_PENDING
            ? "Ujian {$label} berhasil dibuat dengan status Menunggu ACC Admin. Silakan lengkapi daftar soal di bawah ini."
            : "Ujian {$label} berhasil dibuat dan disetujui. Silakan tambahkan soal.";

        return redirect()->route('quizzes.manage', $quiz)->with('status', $msg);
    }

    // ==========================================
    // PERSETUJUAN (ACC) ADMIN UNTUK UTS & UAS
    // ==========================================

    public function approve(Request $request, Quiz $quiz)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $quiz->update([
            'approval_status' => Quiz::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', "{$quiz->shortTypeLabel()} \"{$quiz->title}\" telah di-ACC dan dirilis untuk siswa.");
    }

    public function reject(Request $request, Quiz $quiz)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $quiz->update([
            'approval_status' => Quiz::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('status', "{$quiz->shortTypeLabel()} \"{$quiz->title}\" ditolak dan belum dirilis ke siswa.");
    }

    // ==========================================
    // MANAJEMEN SOAL (Menambahkan Soal & Pilihan Ganda)
    // ==========================================

    public function manage(Quiz $quiz)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $quiz->load(['questions.options', 'classRoom', 'meeting.classRoom']);
        return view('quizzes.manage', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);

        $request->validate([
            'question_text' => 'required|string',
            'points' => 'required|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string',
            'correct_option' => 'required|integer|min:0',
        ]);

        $question = $quiz->questions()->create([
            'question_text' => $request->question_text,
            'points' => $request->points,
        ]);

        foreach ($request->options as $index => $optionText) {
            $question->options()->create([
                'option_text' => $optionText,
                'is_correct' => ((int) $request->correct_option === $index),
            ]);
        }

        return back()->with('status', 'Soal berhasil ditambahkan!');
    }

    public function destroyQuestion(Question $question)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);
        $question->delete();
        return back()->with('status', 'Soal berhasil dihapus.');
    }

    // ==========================================
    // PENGERJAAN KUIS / UTS / UAS (Siswa)
    // ==========================================

    public function show(Quiz $quiz)
    {
        $user = auth()->user();
        if ($user->isStudent()) {
            abort_unless($quiz->isApproved(), 403, 'Ujian ini masih menunggu persetujuan (ACC) Admin untuk dirilis.');
        }

        $attempt = $quiz->attempts()->where('student_id', $user->id)->first();
        
        return view('quizzes.show', compact('quiz', 'attempt'));
    }

    public function start(Quiz $quiz)
    {
        $user = auth()->user();
        abort_unless($user->isStudent(), 403);
        abort_unless($quiz->isApproved(), 403, 'Ujian ini masih menunggu persetujuan (ACC) Admin untuk dirilis.');

        $existingAttempt = $quiz->attempts()->where('student_id', $user->id)->first();
        if ($existingAttempt) {
            return redirect()->route('quizzes.result', [$quiz, $existingAttempt]);
        }

        $attempt = $quiz->attempts()->create([
            'student_id' => $user->id,
            'started_at' => now(),
        ]);

        return redirect()->route('quizzes.take', [$quiz, $attempt]);
    }

    public function take(Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless(auth()->id() === $attempt->student_id, 403);
        abort_unless($quiz->isApproved(), 403, 'Ujian ini belum dirilis.');
        abort_if($attempt->completed_at, 403, 'Anda sudah menyelesaikan pengerjaan ini.');

        $quiz->load(['questions.options' => function ($query) {
            $query->inRandomOrder();
        }]);

        return view('quizzes.take', compact('quiz', 'attempt'));
    }

    public function submit(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless(auth()->id() === $attempt->student_id, 403);
        abort_if($attempt->completed_at, 403, 'Anda sudah menyelesaikan pengerjaan ini.');

        $answers = $request->input('answers', []);
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($quiz->questions as $question) {
            $totalPoints += $question->points;
            
            $selectedOptionId = $answers[$question->id] ?? null;
            $isCorrect = false;

            if ($selectedOptionId) {
                $option = QuestionOption::find($selectedOptionId);
                if ($option && $option->is_correct) {
                    $isCorrect = true;
                    $earnedPoints += $question->points;
                }
            }

            StudentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'option_id' => $selectedOptionId,
                'is_correct' => $isCorrect,
            ]);
        }

        $finalScore = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        $attempt->update([
            'completed_at' => now(),
            'score' => $finalScore,
        ]);

        return redirect()->route('quizzes.result', [$quiz, $attempt]);
    }

    public function result(Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless(auth()->id() === $attempt->student_id || auth()->user()->isTeacher() || auth()->user()->isAdmin(), 403);
        
        return view('quizzes.result', compact('quiz', 'attempt'));
    }
}
