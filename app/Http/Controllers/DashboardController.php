<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Kelas;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
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
            'totalMeetings' => \App\Models\Meeting::count(),
            'totalSubmissions' => Submission::count(),
            'totalGraded' => Submission::whereNotNull('grade')->count(),
        ];

        $classes = ClassRoom::with('teacher')->withCount(['meetings', 'students'])->latest()->take(6)->get();

        return view('dashboard.admin', compact('stats', 'classes'));
    }

    private function teacher(User $user): View
    {
        $classes = $user->taughtClasses()->withCount(['meetings', 'students'])->latest()->get();

        $pendingGrading = Submission::whereNull('grade')
            ->whereHas('meeting.classRoom', fn ($q) => $q->where('teacher_id', $user->id))
            ->count();

        return view('dashboard.teacher', compact('classes', 'pendingGrading'));
    }

    private function student(User $user): View
    {
        $enrolled = $user->enrolledClasses()->withCount('meetings')->get();

        $mySubmissions = $user->submissions()->with('meeting.classRoom')->latest('submitted_at')->take(5)->get();

        return view('dashboard.student', compact('enrolled', 'mySubmissions'));
    }
}
