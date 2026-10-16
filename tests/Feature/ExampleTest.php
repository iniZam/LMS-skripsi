<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_authenticated_user_can_ask_ai(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'message' => ['content' => 'Halo! Ini adalah jawaban dari AI.']
            ], 200),
        ]);

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->postJson('/dashboard/ask-ai', [
            'message' => 'Halo AI!'
        ]);

        $response->assertOk()
            ->assertJsonStructure(['reply'])
            ->assertJson(['reply' => 'Halo! Ini adalah jawaban dari AI.']);
    }

    public function test_teacher_sees_own_quizzes_on_quiz_index(): void
    {
        $teacher = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_TEACHER]);
        $otherTeacher = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_TEACHER]);

        $myClass = \App\Models\ClassRoom::create([
            'code' => 'MTK-7A',
            'title' => 'Matematika - 7A',
            'subject_category' => 'Matematika',
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'academic_year' => '2026/2027',
        ]);

        $otherClass = \App\Models\ClassRoom::create([
            'code' => 'IPA-7A',
            'title' => 'IPA - 7A',
            'subject_category' => 'IPA',
            'teacher_id' => $otherTeacher->id,
            'status' => 'active',
            'academic_year' => '2026/2027',
        ]);

        $myMeeting = \App\Models\Meeting::create([
            'class_id' => $myClass->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1',
            'has_assignment' => false,
        ]);

        $otherMeeting = \App\Models\Meeting::create([
            'class_id' => $otherClass->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1 IPA',
            'has_assignment' => false,
        ]);

        \App\Models\Quiz::create([
            'meeting_id' => $myMeeting->id,
            'title' => 'Kuis Aljabar Guru Saya',
            'duration_minutes' => 30,
        ]);

        \App\Models\Quiz::create([
            'meeting_id' => $otherMeeting->id,
            'title' => 'Kuis Biologi Guru Lain',
            'duration_minutes' => 30,
        ]);

        $response = $this->actingAs($teacher)->get(route('quizzes.index'));

        $response->assertOk()
            ->assertSee('Kuis Aljabar Guru Saya')
            ->assertDontSee('Kuis Biologi Guru Lain');
    }

    public function test_student_sees_pending_and_completed_quizzes(): void
    {
        $teacher = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_TEACHER]);
        $student = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_STUDENT]);

        $classRoom = \App\Models\ClassRoom::create([
            'code' => 'MTK-8A',
            'title' => 'Matematika - 8A',
            'subject_category' => 'Matematika',
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'academic_year' => '2026/2027',
        ]);
        $classRoom->students()->attach($student->id);

        $meeting = \App\Models\Meeting::create([
            'class_id' => $classRoom->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1',
            'has_assignment' => false,
        ]);

        $pendingQuiz = \App\Models\Quiz::create([
            'meeting_id' => $meeting->id,
            'title' => 'Kuis Belum Dikerjakan Siswa',
            'duration_minutes' => 30,
        ]);

        $completedQuiz = \App\Models\Quiz::create([
            'meeting_id' => $meeting->id,
            'title' => 'Kuis Sudah Selesai Siswa',
            'duration_minutes' => 30,
        ]);

        \App\Models\QuizAttempt::create([
            'quiz_id' => $completedQuiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(5),
            'score' => 95,
        ]);

        $response = $this->actingAs($student)->get(route('quizzes.index'));

        $response->assertOk()
            ->assertSee('Kuis Belum Dikerjakan Siswa')
            ->assertSee('Kuis Sudah Selesai Siswa')
            ->assertSee('Nilai: 95');
    }

    public function test_dashboard_displays_logged_in_user_name(): void
    {
        $teacher = \App\Models\User::factory()->create([
            'name' => 'Budi Santoso, S.Pd.',
            'role' => \App\Models\User::ROLE_TEACHER,
        ]);

        $response = $this->actingAs($teacher)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Budi Santoso, S.Pd.');
    }

    public function test_student_dashboard_shows_attendance_percent_and_pending_quizzes(): void
    {
        $teacher = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_TEACHER]);
        $student = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_STUDENT]);

        $classRoom = \App\Models\ClassRoom::create([
            'code' => 'BIN-7A',
            'title' => 'Bahasa Indonesia - 7A',
            'subject_category' => 'Bahasa',
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'academic_year' => '2026/2027',
        ]);
        $classRoom->students()->attach($student->id);

        $meetings = collect([1, 2, 3, 4])->map(fn ($n) => \App\Models\Meeting::create([
            'class_id' => $classRoom->id,
            'meeting_number' => $n,
            'title' => "Pertemuan {$n}",
            'has_assignment' => false,
        ]));

        // 3 hadir, 1 alpa => 75%
        foreach ($meetings as $i => $m) {
            \App\Models\Attendance::create([
                'meeting_id' => $m->id,
                'student_id' => $student->id,
                'status' => $i === 3 ? 'alpa' : 'hadir',
            ]);
        }

        // 3 kuis, 1 sudah dikerjakan => 2 perlu dikerjakan
        $quizzes = $meetings->take(3)->map(fn ($m) => \App\Models\Quiz::create([
            'meeting_id' => $m->id,
            'title' => 'Kuis ' . $m->meeting_number,
            'duration_minutes' => 30,
        ]));
        \App\Models\QuizAttempt::create([
            'quiz_id' => $quizzes->first()->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(5),
            'score' => 70,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('SMP Negeri 1 Lede')
            ->assertSee('Selamat datang,')
            ->assertSee($student->name)
            ->assertSee('75%')
            ->assertSee('Kuis Perlu Dikerjakan')
            ->assertSee('1 dari 3 kuis sudah dikerjakan.')
            ->assertDontSee('Lihat Rapot Saya');
    }

    public function test_teacher_can_view_class_rapot_recap_and_export_csv(): void
    {
        $teacher = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_TEACHER]);
        $student = \App\Models\User::factory()->create([
            'name' => 'Andi Pratama',
            'nip_nis' => '20260001',
            'role' => \App\Models\User::ROLE_STUDENT,
        ]);

        $classRoom = \App\Models\ClassRoom::create([
            'code' => 'FIS-9A',
            'title' => 'Fisika - 9A',
            'subject_category' => 'IPA',
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'academic_year' => '2026/2027',
        ]);
        $classRoom->students()->attach($student->id);

        $meeting1 = \App\Models\Meeting::create([
            'class_id' => $classRoom->id,
            'meeting_number' => 1,
            'title' => 'Hukum Newton',
            'has_assignment' => true,
            'assignment_title' => 'Latihan Hukum Newton',
        ]);

        $meeting2 = \App\Models\Meeting::create([
            'class_id' => $classRoom->id,
            'meeting_number' => 2,
            'title' => 'Usaha dan Energi',
            'has_assignment' => false,
        ]);

        \App\Models\Submission::create([
            'meeting_id' => $meeting1->id,
            'student_id' => $student->id,
            'file_name' => 'jawaban.pdf',
            'file_path' => 'submissions/jawaban.pdf',
            'submitted_at' => now(),
            'grade' => 80,
            'graded_by' => $teacher->id,
            'graded_at' => now(),
        ]);

        $quiz = \App\Models\Quiz::create([
            'meeting_id' => $meeting2->id,
            'title' => 'Kuis Usaha dan Energi',
            'duration_minutes' => 30,
        ]);

        \App\Models\QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(2),
            'score' => 90,
        ]);

        \App\Models\Attendance::create([
            'meeting_id' => $meeting1->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        \App\Models\Attendance::create([
            'meeting_id' => $meeting2->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        // Test Teacher Rapot Page
        $response = $this->actingAs($teacher)->get(route('classes.rapot', $classRoom));
        $response->assertOk()
            ->assertSee('Andi Pratama')
            ->assertSee('20260001')
            ->assertSee('80')
            ->assertSee('90')
            ->assertSee('85'); // Final score average of 80 and 90

        // Test CSV Export
        $exportResponse = $this->actingAs($teacher)->get(route('classes.rapot.export', $classRoom));
        $exportResponse->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Test Student Personal Rapot Page
        $studentResponse = $this->actingAs($student)->get(route('rapot.index'));
        $studentResponse->assertOk()
            ->assertSee('Fisika - 9A')
            ->assertSee('85');
    }
}

