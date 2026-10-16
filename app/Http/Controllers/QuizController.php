<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;

class QuizController extends Controller
{
    // ==========================================
    // DAFTAR KUIS (Guru & Siswa)
    // ==========================================

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $enrolledClassIds = $user->enrolledClasses()->pluck('classes.id');

            $allQuizzes = Quiz::with(['meeting.classRoom.teacher'])
                ->withCount('questions')
                ->whereHas('meeting', function ($q) use ($enrolledClassIds) {
                    $q->whereIn('class_id', $enrolledClassIds);
                })
                ->latest()
                ->get();

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
                'attemptsByQuiz'
            ));
        }

        // Untuk Guru (menampilkan kuis pada kelas yang diampu/dibuat guru) & Admin
        $classId = $request->query('class_id');

        $query = Quiz::with(['meeting.classRoom.teacher'])
            ->withCount([
                'questions',
                'attempts as completed_attempts_count' => function ($q) {
                    $q->whereNotNull('completed_at');
                },
            ]);

        if ($user->isTeacher()) {
            $query->whereHas('meeting.classRoom', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
            $teacherClasses = $user->taughtClasses()->orderBy('title')->get();
        } else {
            $teacherClasses = \App\Models\ClassRoom::orderBy('title')->get();
        }

        if ($classId) {
            $query->whereHas('meeting', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $quizzes = $query->latest()->paginate(10)->withQueryString();

        return view('quizzes.index', compact('quizzes', 'teacherClasses', 'classId'));
    }

    // ==========================================
    // MANAJEMEN KUIS (Membuat & Mengedit Info Kuis)
    // ==========================================

    public function create(Meeting $meeting)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);
        return view('quizzes.create', compact('meeting'));
    }

    public function store(Request $request, Meeting $meeting)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'deadline' => 'nullable|date',
        ]);

        $quiz = $meeting->quizzes()->create($validated);

        return redirect()->route('quizzes.manage', $quiz)->with('status', 'Kuis berhasil dibuat. Silakan tambahkan soal.');
    }

    // ==========================================
    // MANAJEMEN SOAL (Menambahkan Soal & Pilihan Ganda)
    // ==========================================

    public function manage(Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isTeacher(), 403);
        
        $quiz->load('questions.options');
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
            'correct_option' => 'required|integer|min:0', // Indeks array pilihan yang benar
        ]);

        // Simpan Pertanyaan
        $question = $quiz->questions()->create([
            'question_text' => $request->question_text,
            'points' => $request->points,
        ]);

        // Simpan Pilihan Ganda
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
    // PENGERJAAN KUIS (Siswa)
    // ==========================================

    public function show(Quiz $quiz)
    {
        $user = auth()->user();
        // Cek apakah siswa sudah pernah mengerjakan kuis ini
        $attempt = $quiz->attempts()->where('student_id', $user->id)->first();
        
        return view('quizzes.show', compact('quiz', 'attempt'));
    }

    public function start(Quiz $quiz)
    {
        $user = auth()->user();
        abort_unless($user->isStudent(), 403);

        // Jika sudah ada percobaan, langsung arahkan ke hasil
        $existingAttempt = $quiz->attempts()->where('student_id', $user->id)->first();
        if ($existingAttempt) {
            return redirect()->route('quizzes.result', [$quiz, $existingAttempt]);
        }

        // Mulai waktu pengerjaan
        $attempt = $quiz->attempts()->create([
            'student_id' => $user->id,
            'started_at' => now(),
        ]);

        return redirect()->route('quizzes.take', [$quiz, $attempt]);
    }

    public function take(Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless(auth()->id() === $attempt->student_id, 403);
        abort_if($attempt->completed_at, 403, 'Anda sudah menyelesaikan kuis ini.');

        // Acak urutan pilihan ganda agar tidak mudah dicontek
        $quiz->load(['questions.options' => function($query) {
            $query->inRandomOrder();
        }]);

        return view('quizzes.take', compact('quiz', 'attempt'));
    }

    public function submit(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless(auth()->id() === $attempt->student_id, 403);
        abort_if($attempt->completed_at, 403, 'Anda sudah menyelesaikan kuis ini.');

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

        // Kalkulasi nilai skala 100
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