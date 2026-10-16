<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsFileSize;
use App\Models\ClassRoom;
use App\Models\Meeting;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            if (! $file) continue;

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

    // ==========================================
    // FITUR ABSENSI
    // ==========================================

    public function saveAttendance(Request $request, Meeting $meeting)
    {
        abort_unless($request->user()->isTeacher() || $request->user()->isAdmin(), 403);
        
        $attendances = $request->input('attendances', []);
        foreach ($attendances as $studentId => $status) {
            Attendance::updateOrCreate(
                ['meeting_id' => $meeting->id, 'student_id' => $studentId],
                ['status' => $status]
            );
        }
        return back()->with('status', 'Data absensi berhasil disimpan.');
    }

    public function exportAttendance(Meeting $meeting)
    {
        abort_unless(auth()->user()->isTeacher() || auth()->user()->isAdmin(), 403);

        $fileName = "Absensi_Pertemuan_{$meeting->meeting_number}_{$meeting->classRoom->code}.csv";
        $attendances = Attendance::with('student')->where('meeting_id', $meeting->id)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($attendances) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Nama Siswa', 'NISN', 'Status Kehadiran']);

            $row = 1;
            foreach ($attendances as $att) {
                fputcsv($file, [
                    $row++,
                    $att->student->name,
                    $att->student->nip_nis,
                    strtoupper($att->status)
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
    public function importAttendance(Request $request, Meeting $meeting)
    {
        abort_unless($request->user()->isTeacher() || $request->user()->isAdmin(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), "r");
        
        $isHeader = true;
        $validStatuses = ['hadir', 'izin', 'sakit', 'alpa'];
        $importedCount = 0;

        // Membaca file CSV baris per baris
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue; // Lewati baris pertama (header: No, Nama Siswa, NISN, Status)
            }

            // Pastikan format kolom sesuai dengan hasil export (Indeks 2 = NISN, Indeks 3 = Status)
            $nisn = trim($data[2] ?? '');
            $status = strtolower(trim($data[3] ?? ''));

            // Lewati jika NISN kosong atau status tidak valid
            if (!$nisn || !in_array($status, $validStatuses)) {
                continue; 
            }

            // Cari siswa berdasarkan NISN yang terdaftar di kelas ini
            $student = $meeting->classRoom->students()->where('nip_nis', $nisn)->first();

            if ($student) {
                Attendance::updateOrCreate(
                    ['meeting_id' => $meeting->id, 'student_id' => $student->id],
                    ['status' => $status]
                );
                $importedCount++;
            }
        }
        fclose($handle);

        return back()->with('status', "Berhasil mengimpor $importedCount data absensi.");
    }

    // ==========================================
    // FITUR AI CHATBOT (Tutor & Designer)
    // ==========================================

    public function askAiDesigner(Request $request, ClassRoom $class)
    {
        abort_unless($request->user()->isTeacher(), 403);
        $request->validate(['question' => 'required|string|max:500']);

        $ollamaModel = 'deepseek-coder:6.7b'; 
        $systemPrompt = "Anda adalah Asisten AI Perancang Kurikulum untuk EduEduka LMS. "
            . "Tugas Anda adalah membantu guru merancang judul, deskripsi materi, atau ide tugas "
            . "untuk mata pelajaran '{$class->subject_category}' di kelas '{$class->title}'. "
            . "Gunakan bahasa Indonesia yang profesional dan berikan ide yang terstruktur.";

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:11434/api/chat', [
                'model' => $ollamaModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $request->question],
                ],
                'stream' => false,
            ]);

            if ($response->successful()) {
                return response()->json(['answer' => $response->json()['message']['content'] ?? 'Maaf, gagal membuat respons.']);
            }
            return response()->json(['answer' => 'Gangguan teknis pada AI.'], 500);
        } catch (\Exception $e) {
            return response()->json(['answer' => 'AI sedang tidak bisa dihubungi.'], 500);
        }
    }

    // Fungsi askAiTutor yang sempat terhapus dikembalikan di sini
    public function askAiTutor(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        abort_unless($user->isStudent() || $user->isTeacher(), 403);
        $request->validate(['question' => ['required', 'string', 'max:500']]);

        $contextMateri = "Materi Utama: {$meeting->title}.\nDeskripsi: {$meeting->description}\n\n";

        // Mengekstrak Teks dari PDF (Materi yang Terlampir)
        $materials = $meeting->materials; 
        if ($materials->count() > 0) {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                $pdfParser = new \Smalot\PdfParser\Parser();
                $contextMateri .= "Berikut adalah isi dari dokumen/materi pendukung:\n";
                foreach ($materials as $material) {
                    if (strtolower(pathinfo($material->file_name, PATHINFO_EXTENSION)) === 'pdf') {
                        $filePath = Storage::disk('public')->path($material->file_path);
                        if (file_exists($filePath)) {
                            try {
                                $pdf = $pdfParser->parseFile($filePath);
                                $pdfText = substr($pdf->getText(), 0, 15000); 
                                $contextMateri .= "--- Dokumen: {$material->file_name} ---\n" . $pdfText . "\n\n";
                            } catch (\Exception $e) {
                                Log::warning("Gagal membaca PDF untuk AI: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        $pertanyaan = $request->question;
        $ollamaModel = 'deepseek-coder:6.7b'; 

        if ($user->isTeacher()) {
            $systemPrompt = "Anda adalah Asisten Penilai AI untuk guru di EduEduka LMS. "
                . "Tugas Anda adalah membantu guru mengevaluasi tugas, memberikan ide rentang nilai, "
                . "atau menyusun kalimat umpan balik (feedback) yang konstruktif untuk siswa.\n\n"
                . "Instruksi Tugas yang diberikan ke siswa: " . ($meeting->assignment_instructions ?? 'Tidak ada instruksi spesifik.') . "\n\n"
                . "Konteks Materi Pelajaran:\n" . $contextMateri . "\n\n"
                . "Gunakan bahasa Indonesia yang profesional, ramah, dan membantu.";
        } else {
            $systemPrompt = "Anda adalah asisten tutor AI yang pintar untuk EduEduka LMS. "
                . "Tugas Anda adalah menjawab pertanyaan siswa HANYA berdasarkan konteks materi berikut:\n\n"
                . $contextMateri . "\n"
                . "Gunakan bahasa Indonesia yang ramah, mudah dipahami, dan edukatif. "
                . "Jika pertanyaan di luar konteks materi, tolak dengan sopan dan arahkan siswa kembali ke pelajaran.";
        }

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:11434/api/chat', [
                'model' => $ollamaModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $pertanyaan],
                ],
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $jawabanAi = $data['message']['content'] ?? 'Maaf, saya tidak bisa memberikan jawaban saat ini.';
                return response()->json(['answer' => $jawabanAi]);
            }

            return response()->json(['answer' => 'Maaf, otak AI sedang mengalami gangguan teknis.'], 500);
        } catch (\Exception $e) {
            return response()->json(['answer' => 'Maaf, asisten AI sedang tidak bisa dihubungi saat ini.'], 500);
        }
    }
}