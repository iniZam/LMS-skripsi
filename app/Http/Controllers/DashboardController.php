<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Kelas;
use App\Models\Submission;
use App\Models\User;
use App\Models\Meeting; // 1. Menambahkan import Meeting
use App\Models\Attendance;
use App\Models\Quiz;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            User::ROLE_ADMIN => $this->admin(),
            User::ROLE_TEACHER => $this->teacher($user),
            default => $this->student($user),
        };
    }

    private function admin(): View
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalTeachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'totalKelas' => Kelas::count(),
            'totalClasses' => ClassRoom::count(),
            'totalMeetings' => Meeting::count(), // 1. Diperbarui agar lebih rapi
            'totalSubmissions' => Submission::count(),
            'totalGraded' => Submission::whereNotNull('grade')->count(),
        ];

        $classes = ClassRoom::with('teacher')->withCount(['meetings', 'students'])->latest()->take(6)->get();

        // Persentase kehadiran seluruh siswa di sekolah
        $attendancePercent = $this->attendancePercent(Attendance::query());
        $totalQuizzes = Quiz::count();

        $today = Carbon::now('Asia/Makassar')->toDateString();
        $teacherTodayHadir = TeacherAttendance::whereDate('date', $today)->where('status', 'hadir')->count();

        return view('dashboard.admin', compact('stats', 'classes', 'attendancePercent', 'totalQuizzes', 'teacherTodayHadir'));
    }

    private function teacher(User $user): View
    {
        $classes = $user->taughtClasses()->withCount(['meetings', 'students'])->latest()->get();

        $pendingGrading = Submission::whereNull('grade')
            ->whereHas('meeting.classRoom', fn ($q) => $q->where('teacher_id', $user->id))
            ->count();

        // Persentase kehadiran siswa pada kelas-kelas yang diampu guru
        $meetingIds = Meeting::whereIn('class_id', $classes->pluck('id'))->pluck('id');
        $attendancePercent = $this->attendancePercent(Attendance::whereIn('meeting_id', $meetingIds));

        $totalQuizzes = Quiz::whereIn('meeting_id', $meetingIds)->count();

        $today = Carbon::now('Asia/Makassar')->toDateString();
        $todayAttendance = TeacherAttendance::where('teacher_id', $user->id)->whereDate('date', $today)->first();

        return view('dashboard.teacher', compact('classes', 'pendingGrading', 'attendancePercent', 'totalQuizzes', 'todayAttendance'));
    }

    private function student(User $user): View
    {
        $enrolled = $user->enrolledClasses()->withCount('meetings')->get();

        $mySubmissions = $user->submissions()->with('meeting.classRoom')->latest('submitted_at')->take(5)->get();

        // Persentase kehadiran siswa yang sedang login
        $attendancePercent = $this->attendancePercent(Attendance::where('student_id', $user->id));

        // Kuis pada kelas yang diikuti dan belum diselesaikan oleh siswa
        $meetingIds = Meeting::whereIn('class_id', $enrolled->pluck('id'))->pluck('id');
        $quizzesInClasses = Quiz::whereIn('meeting_id', $meetingIds);
        $totalQuizzes = (clone $quizzesInClasses)->count();
        $pendingQuizzes = (clone $quizzesInClasses)
            ->whereDoesntHave('attempts', fn ($q) => $q->where('student_id', $user->id)->whereNotNull('completed_at'))
            ->count();

        return view('dashboard.student', compact('enrolled', 'mySubmissions', 'attendancePercent', 'totalQuizzes', 'pendingQuizzes'));
    }

    /**
     * Hitung persentase status "hadir" dari kumpulan data absensi.
     * Mengembalikan null jika belum ada data absensi sama sekali.
     */
    private function attendancePercent($query): ?float
    {
        $total = (clone $query)->count();
        if ($total === 0) {
            return null;
        }
        $hadir = (clone $query)->where('status', 'hadir')->count();

        return round($hadir / $total * 100, 1);
    }

    public function askAi(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $userMessage = $request->message;
        $user = $request->user();
        $userName = $user ? $user->name : 'Pengguna';
        $userRole = $user ? $user->roleLabel() : 'Pengguna';

        $ollamaHost = env('OLLAMA_HOST', 'http://127.0.0.1:11434');
        $ollamaModel = env('OLLAMA_MODEL');

        // Otomatis deteksi model yang tersedia di Ollama jika belum diset di .env
        if (!$ollamaModel) {
            try {
                $tags = Http::timeout(3)->get("{$ollamaHost}/api/tags");
                if ($tags->successful() && !empty($tags->json()['models'])) {
                    $ollamaModel = $tags->json()['models'][0]['name'];
                }
            } catch (\Exception $e) {
                // Abaikan jika pengecekan tags gagal
            }
            $ollamaModel = $ollamaModel ?: 'deepseek-coder:6.7b';
        }

        $systemPrompt = "Anda adalah Asisten AI untuk platform EduEduka LMS. "
            . "Anda sedang berbicara dengan {$userName} ({$userRole}). "
            . "Tugas Anda adalah membantu pengguna dalam proses pembelajaran, manajemen kelas, materi, dan tugas. "
            . "Berikan jawaban yang jelas, ramah, edukatif, dan ringkas dalam bahasa Indonesia.";

        try {
            $response = Http::timeout(120)->post("{$ollamaHost}/api/chat", [
                'model' => $ollamaModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['message']['content'] ?? 'Maaf, AI tidak memberikan respons yang sesuai.';
            } else {
                Log::warning('Ollama API error: ' . $response->body());
                $aiResponse = 'Maaf, layanan AI sedang mengalami kendala (HTTP ' . $response->status() . ').';
            }
        } catch (\Exception $e) {
            Log::warning('Gagal menghubungi Ollama: ' . $e->getMessage());
            $aiResponse = 'Maaf, asisten AI sedang tidak bisa dihubungi saat ini. Pastikan server Ollama sudah aktif.';
        }

        return response()->json([
            'reply' => $aiResponse
        ]);
    }
}