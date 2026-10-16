<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Kelas;
use App\Models\Material;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClassMeetingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan direktori penyimpanan berkas tersedia di storage public
        Storage::disk('public')->makeDirectory('materials');
        Storage::disk('public')->makeDirectory('submissions');

        // 2. Hubungkan siswa ke setiap mata pelajaran berdasarkan rombel/kelasnya
        $this->enrollStudentsToClasses();

        // 3. Bank Soal & Video berdasarkan kategori mata pelajaran
        $questionBanks = $this->getQuestionBanks();
        $videoBanks = $this->getVideoBanks();

        // 4. Ambil semua kelas (ClassRoom) yang ada di sistem
        $classes = ClassRoom::with(['teacher', 'students'])->get();

        foreach ($classes as $class) {
            // Bersihkan pertemuan yang sudah ada sebelumnya agar tidak terjadi duplikasi
            $class->meetings()->each(function ($meeting) {
                $meeting->delete();
            });

            $subject = $class->subject_category ?: 'Umum';
            $normalizedSubject = $this->normalizeSubjectName($subject);

            $videos = $videoBanks[$normalizedSubject] ?? $videoBanks['Default'];
            $questions = $questionBanks[$normalizedSubject] ?? $questionBanks['Default'];

            // =========================================================================
            // PERTEMUAN 1: Pengenalan & Konsep Dasar (Materi Pembelajaran Saja)
            // =========================================================================
            $meeting1 = Meeting::create([
                'class_id' => $class->id,
                'meeting_number' => 1,
                'title' => 'Pertemuan 1: Pengenalan & Konsep Dasar ' . $subject,
                'description' => 'Membahas pengenalan konsep dasar, tujuan pembelajaran, dan kompetensi awal mata pelajaran ' . $subject . '.',
                'youtube_url' => $videos[0] ?? 'https://www.youtube.com/watch?v=DCdGyoyeih8',
                'has_assignment' => false,
            ]);
            $this->seedPlaceholderMaterial($meeting1, "Modul_01_Dasar_{$this->cleanFileName($subject)}.pdf");

            // =========================================================================
            // PERTEMUAN 2: Pendalaman Materi & TUGAS MANDIRI (Ada Tugas)
            // =========================================================================
            $meeting2 = Meeting::create([
                'class_id' => $class->id,
                'meeting_number' => 2,
                'title' => 'Pertemuan 2: Pendalaman Materi & Latihan Mandiri',
                'description' => 'Pendalaman materi, pembahasan studi kasus terstruktur, serta instruksi pengerjaan lembar kerja siswa (LKS).',
                'youtube_url' => $videos[1] ?? 'https://www.youtube.com/watch?v=8UVfXaFwWsY',
                'has_assignment' => true,
                'assignment_title' => 'Tugas 1: Lembar Kerja Siswa (LKS) ' . $subject,
                'assignment_instructions' => 'Pelajari modul pertemuan 2 dengan seksama. Kerjakan 5 soal latihan dan analisis kasus yang tercantum pada modul. Tuliskan jawaban secara rapi, lalu unggah berkas dalam format PDF sebelum tenggat waktu.',
                'assignment_deadline' => now()->addDays(7),
            ]);
            $this->seedPlaceholderMaterial($meeting2, "LKS_Pertemuan_02_{$this->cleanFileName($subject)}.pdf");

            // Buat sampel pengumpulan tugas (submission) dari siswa terdaftar
            $this->seedSampleSubmissions($class, $meeting2, $subject);

            // =========================================================================
            // PERTEMUAN 3: Evaluasi Pembelajaran & KUIS INTERAKTIF (Ada Kuis)
            // =========================================================================
            $meeting3 = Meeting::create([
                'class_id' => $class->id,
                'meeting_number' => 3,
                'title' => 'Pertemuan 3: Evaluasi Bab & Kuis Pemahaman',
                'description' => 'Sesi evaluasi capaian pemahaman belajar siswa pada materi ' . $subject . ' melalui kuis interaktif pilihan ganda.',
                'youtube_url' => $videos[2] ?? 'https://www.youtube.com/watch?v=k4Ac2j2X2Qw',
                'has_assignment' => false,
            ]);
            $this->seedPlaceholderMaterial($meeting3, "Rangkuman_Evaluasi_Bab_{$this->cleanFileName($subject)}.pdf");

            // Buat Kuis Interaktif pada Pertemuan 3
            $quiz = Quiz::create([
                'meeting_id' => $meeting3->id,
                'title' => 'Kuis Harian 1: Pemahaman ' . $subject,
                'description' => 'Kuis pilihan ganda evaluasi materi pertemuan 1 dan 2. Waktu pengerjaan 30 menit. Pilih satu jawaban yang paling tepat.',
                'duration_minutes' => 30,
                'deadline' => now()->addDays(10),
            ]);

            // Buat Soal-soal Kuis beserta Pilihan Ganda (A, B, C, D)
            foreach ($questions as $qData) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $qData['question'],
                    'points' => $qData['points'] ?? 25,
                ]);

                foreach ($qData['options'] as $idx => $optText) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optText,
                        'is_correct' => ($idx === $qData['correct_index']),
                    ]);
                }
            }

            // Buat 1 sampel pengerjaan kuis dari siswa pertama agar guru bisa melihat contoh rekap nilai
            $this->seedSampleQuizAttempt($class, $quiz);
        }
    }

    /**
     * Daftarkan (enroll) siswa ke setiap ClassRoom berdasarkan rombel / kelasnya.
     */
    private function enrollStudentsToClasses(): void
    {
        $tingkatMap = [
            '7' => 7, '8' => 8, '9' => 9,
            'VII' => 7, 'VIII' => 8, 'IX' => 9,
        ];

        $classes = ClassRoom::all();

        foreach ($classes as $class) {
            if (preg_match('/(?:Kelas\s*)?([789]|VII|VIII|IX)\s*[-_ ]?\s*([A-C])/i', $class->title, $matches)) {
                $tingkat = $tingkatMap[strtoupper($matches[1])] ?? null;
                $rombel = strtoupper($matches[2]);

                if ($tingkat) {
                    $kelasIds = Kelas::where('tingkat', $tingkat)
                        ->where('nama', $rombel)
                        ->pluck('id');

                    $studentIds = User::where('role', User::ROLE_STUDENT)
                        ->whereIn('kelas_id', $kelasIds)
                        ->pluck('id');

                    if ($studentIds->isNotEmpty()) {
                        $class->students()->syncWithoutDetaching($studentIds);
                    }
                }
            }
        }
    }

    /**
     * Buat berkas placeholder materi pada storage publik.
     */
    private function seedPlaceholderMaterial(Meeting $meeting, string $fileName): Material
    {
        $path = "materials/{$meeting->id}/{$fileName}";
        $content = "DOKUMEN MATERI PEMBELAJARAN\n"
            . "Pertemuan: {$meeting->title}\n"
            . "Berkas: {$fileName}\n"
            . "Kurikulum: Merdeka Belajar (LMS EduEduka)\n"
            . "Catatan: Ini adalah berkas modul placeholder untuk kebutuhan pengujian sistem LMS.";

        Storage::disk('public')->put($path, $content);

        return $meeting->materials()->create([
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => '1.2 MB',
            'file_type' => 'application/pdf',
        ]);
    }

    /**
     * Buat 2 contoh submission siswa: 1 sudah dinilai guru, 1 masih menunggu penilaian (pending).
     */
    private function seedSampleSubmissions(ClassRoom $class, Meeting $meeting, string $subject): void
    {
        $students = $class->students()->take(2)->get();

        if ($students->isNotEmpty()) {
            // Siswa 1: Tugas sudah dinilai oleh guru
            $student1 = $students->first();
            $fileName1 = "{$this->cleanFileName($student1->name)}_Tugas_{$this->cleanFileName($subject)}.pdf";
            $path1 = "submissions/{$meeting->id}/{$student1->id}_{$fileName1}";
            Storage::disk('public')->put($path1, "Lembar Jawaban Tugas oleh {$student1->name}");

            Submission::create([
                'meeting_id' => $meeting2Id = $meeting->id,
                'student_id' => $student1->id,
                'file_name' => $fileName1,
                'file_path' => $path1,
                'file_size' => '850 KB',
                'student_note' => 'Tugas sudah saya selesaikan sesuai dengan modul dan petunjuk pertemuan 2. Terima kasih.',
                'submitted_at' => now()->subDays(2),
                'is_late' => false,
                'grade' => 90,
                'feedback' => 'Kerja bagus! Pembahasan jawaban sangat runtut dan tepat.',
                'graded_at' => now()->subDay(),
                'graded_by' => $class->teacher_id,
            ]);

            // Siswa 2: Tugas baru dikumpulkan, belum dinilai (pending)
            if ($students->count() > 1) {
                $student2 = $students->skip(1)->first();
                $fileName2 = "{$this->cleanFileName($student2->name)}_Tugas_{$this->cleanFileName($subject)}.pdf";
                $path2 = "submissions/{$meeting->id}/{$student2->id}_{$fileName2}";
                Storage::disk('public')->put($path2, "Lembar Jawaban Tugas oleh {$student2->name}");

                Submission::create([
                    'meeting_id' => $meeting->id,
                    'student_id' => $student2->id,
                    'file_name' => $fileName2,
                    'file_path' => $path2,
                    'file_size' => '720 KB',
                    'student_note' => 'Mohon koreksi dan arahannya untuk bagian soal nomor 4 Pak/Bu.',
                    'submitted_at' => now()->subHours(3),
                    'is_late' => false,
                    'grade' => null,
                    'feedback' => null,
                    'graded_at' => null,
                    'graded_by' => null,
                ]);
            }
        }
    }

    /**
     * Buat 1 contoh pengerjaan kuis siswa agar tampilan hasil kuis dapat langsung diverifikasi.
     */
    private function seedSampleQuizAttempt(ClassRoom $class, Quiz $quiz): void
    {
        $student = $class->students()->first();
        if (! $student) {
            return;
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(1)->addMinutes(35),
            'score' => 100.00,
        ]);

        foreach ($quiz->questions as $question) {
            $correctOption = $question->options()->where('is_correct', true)->first();

            StudentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'option_id' => $correctOption?->id,
                'is_correct' => true,
            ]);
        }
    }

    /**
     * Normalisasi nama kategori mata pelajaran.
     */
    private function normalizeSubjectName(string $subject): string
    {
        return match (trim($subject)) {
            'Bhs. Indo', 'Bahasa Indonesia' => 'Bahasa Indonesia',
            'Bhs. Inggris', 'Bahasa Inggris' => 'Bahasa Inggris',
            'Pend. Agama', 'Pendidikan Agama' => 'Pend. Agama',
            'IPA', 'Ilmu Pengetahuan Alam' => 'IPA',
            'IPS', 'Ilmu Pengetahuan Sosial' => 'IPS',
            'Matematika' => 'Matematika',
            'PKn', 'Pendidikan Kewarganegaraan' => 'PKn',
            'PJOK', 'Penjas' => 'PJOK',
            'Prakarya' => 'Prakarya',
            'Informatika' => 'Informatika',
            default => 'Default',
        };
    }

    private function cleanFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    /**
     * Bank Video YouTube per kategori mapel.
     */
    private function getVideoBanks(): array
    {
        return [
            'Matematika' => [
                'https://www.youtube.com/watch?v=DCdGyoyeih8',
                'https://www.youtube.com/watch?v=8UVfXaFwWsY',
                'https://www.youtube.com/watch?v=0h94h93-x6U',
            ],
            'IPA' => [
                'https://www.youtube.com/watch?v=RFXPKzKm8Bw',
                'https://www.youtube.com/watch?v=7X3uK5_yTbg',
                'https://www.youtube.com/watch?v=kR2gO5L2tGQ',
            ],
            'Bahasa Indonesia' => [
                'https://www.youtube.com/watch?v=k4Ac2j2X2Qw',
                'https://www.youtube.com/watch?v=GjY4PZ_oO0s',
                'https://www.youtube.com/watch?v=mHn_2aP_92U',
            ],
            'Bahasa Inggris' => [
                'https://www.youtube.com/watch?v=jul2hu6IIaI',
                'https://www.youtube.com/watch?v=83nC4M9F4Xg',
                'https://www.youtube.com/watch?v=X2h2yqK7vGg',
            ],
            'PKn' => [
                'https://www.youtube.com/watch?v=6rL0q2gYp7U',
                'https://www.youtube.com/watch?v=qT_V1-f2rQw',
                'https://www.youtube.com/watch?v=Vl82s3d0_dE',
            ],
            'Pend. Agama' => [
                'https://www.youtube.com/watch?v=qT_V1-f2rQw',
                'https://www.youtube.com/watch?v=6rL0q2gYp7U',
                'https://www.youtube.com/watch?v=9g2_81L1a7A',
            ],
            'IPS' => [
                'https://www.youtube.com/watch?v=rT7Qf59-Z7k',
                'https://www.youtube.com/watch?v=7X3uK5_yTbg',
                'https://www.youtube.com/watch?v=Vl82s3d0_dE',
            ],
            'PJOK' => [
                'https://www.youtube.com/watch?v=7PptB_m2g_0',
                'https://www.youtube.com/watch?v=DCdGyoyeih8',
                'https://www.youtube.com/watch?v=RFXPKzKm8Bw',
            ],
            'Prakarya' => [
                'https://www.youtube.com/watch?v=Bv08eFm-F3o',
                'https://www.youtube.com/watch?v=8UVfXaFwWsY',
                'https://www.youtube.com/watch?v=k4Ac2j2X2Qw',
            ],
            'Informatika' => [
                'https://www.youtube.com/watch?v=mCq8-xTH7jA',
                'https://www.youtube.com/watch?v=jul2hu6IIaI',
                'https://www.youtube.com/watch?v=83nC4M9F4Xg',
            ],
            'Default' => [
                'https://www.youtube.com/watch?v=DCdGyoyeih8',
                'https://www.youtube.com/watch?v=8UVfXaFwWsY',
                'https://www.youtube.com/watch?v=k4Ac2j2X2Qw',
            ],
        ];
    }

    /**
     * Bank Soal Kuis Pilihan Ganda (4 Soal per kategori mapel, masing-masing bobot 25 poin).
     */
    private function getQuestionBanks(): array
    {
        return [
            'Matematika' => [
                [
                    'question' => 'Berapakah hasil dari -15 + 28 - (-7)?',
                    'points' => 25,
                    'options' => ['13', '20', '40', '-6'],
                    'correct_index' => 2, // 40
                ],
                [
                    'question' => 'Sebuah barang dibeli seharga Rp 100.000 dan dijual seharga Rp 125.000. Berapakah persentase keuntungannya?',
                    'points' => 25,
                    'options' => ['15%', '20%', '25%', '30%'],
                    'correct_index' => 2, // 25%
                ],
                [
                    'question' => 'Nilai x yang memenuhi persamaan linear 3x - 5 = 16 adalah...',
                    'points' => 25,
                    'options' => ['5', '7', '9', '11'],
                    'correct_index' => 1, // 7
                ],
                [
                    'question' => 'Sebuah persegi panjang memiliki panjang 12 cm dan lebar 8 cm. Keliling persegi panjang tersebut adalah...',
                    'points' => 25,
                    'options' => ['40 cm', '48 cm', '96 cm', '20 cm'],
                    'correct_index' => 0, // 40 cm
                ],
            ],

            'IPA' => [
                [
                    'question' => 'Bagian sel tumbuhan yang berfungsi sebagai tempat berlangsungnya fotosintesis adalah...',
                    'points' => 25,
                    'options' => ['Vakuola', 'Kloroplas', 'Mitokondria', 'Ribosom'],
                    'correct_index' => 1, // Kloroplas
                ],
                [
                    'question' => 'Enzim di lambung manusia yang berfungsi mengubah protein menjadi pepton adalah...',
                    'points' => 25,
                    'options' => ['Ptialin', 'Lipase', 'Pepsin', 'Amilase'],
                    'correct_index' => 2, // Pepsin
                ],
                [
                    'question' => 'Perubahan wujud benda dari zat padat langsung menjadi gas tanpa mencair terlebih dahulu disebut...',
                    'points' => 25,
                    'options' => ['Menyublim', 'Mengembun', 'Mengkristal', 'Mencair'],
                    'correct_index' => 0, // Menyublim
                ],
                [
                    'question' => 'Satuan internasional (SI) untuk besaran gaya adalah...',
                    'points' => 25,
                    'options' => ['Joule', 'Watt', 'Newton', 'Pascal'],
                    'correct_index' => 2, // Newton
                ],
            ],

            'Bahasa Indonesia' => [
                [
                    'question' => 'Teks yang menggambarkan objek secara rinci sehingga pembaca seolah-olah melihat dan merasakan sendiri disebut teks...',
                    'points' => 25,
                    'options' => ['Eksplanasi', 'Narasi', 'Deskripsi', 'Prosedur'],
                    'correct_index' => 2, // Deskripsi
                ],
                [
                    'question' => 'Gagasan atau ide pokok suatu paragraf biasanya terletak pada...',
                    'points' => 25,
                    'options' => ['Kalimat penjelas', 'Kalimat utama', 'Kalimat simpulan saja', 'Judul teks'],
                    'correct_index' => 1, // Kalimat utama
                ],
                [
                    'question' => 'Kalimat yang menggunakan konjungsi kausalitas (sebab-akibat) yang tepat adalah...',
                    'points' => 25,
                    'options' => [
                        'Ibu pergi ke pasar sedangkan ayah mencuci motor.',
                        'Siswa tidak masuk sekolah karena sedang sakit.',
                        'Andi belajar setelah menyelesaikan makan siang.',
                        'Kami makan bersama di rumah makan baru.'
                    ],
                    'correct_index' => 1, // Siswa tidak masuk...
                ],
                [
                    'question' => 'Penulisan kata serapan yang baku menurut kaidah EYD terbaru adalah...',
                    'points' => 25,
                    'options' => ['Praktik', 'Praktek', 'Sistim', 'Apotik'],
                    'correct_index' => 0, // Praktik
                ],
            ],

            'Bahasa Inggris' => [
                [
                    'question' => 'She ... to school by bicycle every morning. The correct verb form is...',
                    'points' => 25,
                    'options' => ['go', 'goes', 'going', 'went'],
                    'correct_index' => 1, // goes
                ],
                [
                    'question' => 'Which of the following is an expression of congratulations?',
                    'points' => 25,
                    'options' => [
                        'I am sorry to hear that.',
                        'Congratulations on your graduation!',
                        'Excuse me, where is the library?',
                        'Thank you very much.'
                    ],
                    'correct_index' => 1, // Congratulations...
                ],
                [
                    'question' => 'What is the opposite meaning (antonym) of the word "generous"?',
                    'points' => 25,
                    'options' => ['Kind', 'Stingy', 'Brave', 'Honest'],
                    'correct_index' => 1, // Stingy
                ],
                [
                    'question' => 'Look at the dark clouds! It ... rain soon.',
                    'points' => 25,
                    'options' => ['is going to', 'will was', 'did', 'have'],
                    'correct_index' => 0, // is going to
                ],
            ],

            'PKn' => [
                [
                    'question' => 'Lambang negara Indonesia adalah Garuda Pancasila dengan semboyan nasional...',
                    'points' => 25,
                    'options' => ['Tut Wuri Handayani', 'Bhinneka Tunggal Ika', 'Bersatu Kita Teguh', 'Indonesia Raya'],
                    'correct_index' => 1, // Bhinneka Tunggal Ika
                ],
                [
                    'question' => 'Sumber dari segala sumber hukum di Negara Kesatuan Republik Indonesia adalah...',
                    'points' => 25,
                    'options' => ['Ketetapan MPR', 'Pancasila', 'Peraturan Presiden', 'Undang-Undang'],
                    'correct_index' => 1, // Pancasila
                ],
                [
                    'question' => 'Sikap yang mencerminkan pengamalan sila ketiga Pancasila adalah...',
                    'points' => 25,
                    'options' => [
                        'Gemar menabung dan hidup hemat',
                        'Beribadah tepat waktu sesuai agama',
                        'Mengutamakan kepentingan bangsa di atas kepentingan pribadi',
                        'Melakukan musyawarah untuk mencapai mufakat'
                    ],
                    'correct_index' => 2, // Mengutamakan...
                ],
                [
                    'question' => 'Jaminan Hak Asasi Manusia diatur secara komprehensif dalam UUD 1945 pasal...',
                    'points' => 25,
                    'options' => ['28A sampai 28J', 'Pasal 33', 'Pasal 34', 'Pasal 36'],
                    'correct_index' => 0, // 28A-28J
                ],
            ],

            'Pend. Agama' => [
                [
                    'question' => 'Rukun iman yang ketiga dalam ajaran agama Islam adalah iman kepada...',
                    'points' => 25,
                    'options' => ['Malaikat-malaikat Allah', 'Kitab-kitab Allah', 'Rasul-rasul Allah', 'Hari Akhir'],
                    'correct_index' => 1, // Kitab-kitab Allah
                ],
                [
                    'question' => 'Perilaku terpuji seperti jujur, amanah, dan dapat dipercaya dinamakan akhlak...',
                    'points' => 25,
                    'options' => ['Mazmumah', 'Mahmudah', 'Sayyiah', 'Qabihah'],
                    'correct_index' => 1, // Mahmudah
                ],
                [
                    'question' => 'Shalat fardhu yang dikerjakan secara berjamaah memiliki keutamaan derajat pahala sebanyak...',
                    'points' => 25,
                    'options' => ['7 derajat', '17 derajat', '27 derajat', '37 derajat'],
                    'correct_index' => 2, // 27 derajat
                ],
                [
                    'question' => 'Sikap saling menghormati dan menghargai perbedaan keyakinan beragama disebut...',
                    'points' => 25,
                    'options' => ['Tasamuh (Toleransi)', 'Tawadhu', 'Taawun', 'Tabayyun'],
                    'correct_index' => 0, // Tasamuh
                ],
            ],

            'IPS' => [
                [
                    'question' => 'Secara astronomis, wilayah Indonesia terletak pada koordinat...',
                    'points' => 25,
                    'options' => [
                        '6° LU - 11° LS dan 95° BT - 141° BT',
                        '11° LU - 6° LS dan 90° BT - 140° BT',
                        '0° - 15° LU dan 100° - 150° BT',
                        '5° LU - 10° LS dan 95° BT - 135° BT'
                    ],
                    'correct_index' => 0, // 6 LU - 11 LS
                ],
                [
                    'question' => 'Kegiatan menyalurkan barang dan jasa dari produsen kepada konsumen disebut...',
                    'points' => 25,
                    'options' => ['Produksi', 'Distribusi', 'Konsumsi', 'Investasi'],
                    'correct_index' => 1, // Distribusi
                ],
                [
                    'question' => 'Benua terluas di dunia yang juga memiliki populasi terbesar adalah Benua...',
                    'points' => 25,
                    'options' => ['Afrika', 'Amerika', 'Asia', 'Eropa'],
                    'correct_index' => 2, // Asia
                ],
                [
                    'question' => 'Faktor utama yang mendorong terjadinya perdagangan antarpulau atau antarwilayah adalah...',
                    'points' => 25,
                    'options' => [
                        'Kesamaan sumber daya alam',
                        'Perbedaan potensi sumber daya dan komoditas',
                        'Tingkat isolasi geografis',
                        'Jarak yang sangat berjauhan'
                    ],
                    'correct_index' => 1, // Perbedaan potensi...
                ],
            ],

            'PJOK' => [
                [
                    'question' => 'Dalam permainan sepak bola, menendang bola dengan kaki bagian dalam umumnya digunakan untuk...',
                    'points' => 25,
                    'options' => [
                        'Menembak keras ke arah gawang lawan',
                        'Mengoper bola jarak pendek secara akurat',
                        'Menghentikan bola yang melambung tinggi',
                        'Menghalau sundulan pemain lawan'
                    ],
                    'correct_index' => 1, // Mengoper bola...
                ],
                [
                    'question' => 'Pukulan keras dan menukik tajam ke area pertahanan lawan dalam bola voli disebut...',
                    'points' => 25,
                    'options' => ['Passing bawah', 'Servis atas', 'Smash (Spike)', 'Blocking'],
                    'correct_index' => 2, // Smash
                ],
                [
                    'question' => 'Kemampuan tubuh untuk melakukan aktivitas fisik sehari-hari tanpa kelelahan berlebih disebut...',
                    'points' => 25,
                    'options' => ['Daya tahan otot', 'Kelenturan sendi', 'Kebugaran jasmani', 'Kelincahan gerak'],
                    'correct_index' => 2, // Kebugaran jasmani
                ],
                [
                    'question' => 'Start yang digunakan pada perlombaan lari jarak pendek (sprint) adalah start...',
                    'points' => 25,
                    'options' => ['Berdiri', 'Melayang', 'Jongkok', 'Bebas'],
                    'correct_index' => 2, // Jongkok
                ],
            ],

            'Prakarya' => [
                [
                    'question' => 'Bahan lunak alami yang paling sering digunakan untuk membuat produk kerajinan keramik adalah...',
                    'points' => 25,
                    'options' => ['Plastisin', 'Tanah liat', 'Gips lunak', 'Lilin parafin'],
                    'correct_index' => 1, // Tanah liat
                ],
                [
                    'question' => 'Teknik menyilangkan benang atau serat untuk membuat lembaran kain tenun disebut teknik...',
                    'points' => 25,
                    'options' => ['Menganyam', 'Menenun', 'Menjahit', 'Membatik'],
                    'correct_index' => 1, // Menenun
                ],
                [
                    'question' => 'Metode pengawetan bahan makanan dengan cara mengurangi kadar air secara alami atau buatan disebut...',
                    'points' => 25,
                    'options' => ['Fermentasi', 'Pengasapan', 'Pengeringan', 'Pendinginan'],
                    'correct_index' => 2, // Pengeringan
                ],
                [
                    'question' => 'Kemasan produk yang langsung bersentuhan dengan bahan makanan disebut kemasan...',
                    'points' => 25,
                    'options' => ['Primer', 'Sekunder', 'Tersier', 'Kuarter'],
                    'correct_index' => 0, // Primer
                ],
            ],

            'Informatika' => [
                [
                    'question' => 'Perangkat keras komputer yang bertindak sebagai otak utama pengolah instruksi data adalah...',
                    'points' => 25,
                    'options' => ['Hard Disk Drive (HDD)', 'Random Access Memory (RAM)', 'Central Processing Unit (CPU)', 'Power Supply Unit'],
                    'correct_index' => 2, // CPU
                ],
                [
                    'question' => 'Urutan langkah-langkah logis dan sistematis untuk menyelesaikan suatu masalah komputasi dinamakan...',
                    'points' => 25,
                    'options' => ['Sintaks kode', 'Algoritma', 'Pseudocode', 'Kompilator'],
                    'correct_index' => 1, // Algoritma
                ],
                [
                    'question' => 'Jaringan komputer global yang menghubungkan milyaran perangkat komputasi di seluruh dunia adalah...',
                    'points' => 25,
                    'options' => ['Local Area Network (LAN)', 'Metropolitan Area Network (MAN)', 'Internet', 'Intranet Sekolah'],
                    'correct_index' => 2, // Internet
                ],
                [
                    'question' => 'Format file dokumen standar yang menjaga tata letak tetap konsisten di berbagai perangkat digital adalah...',
                    'points' => 25,
                    'options' => ['.docx', '.pdf', '.txt', '.xlsx'],
                    'correct_index' => 1, // .pdf
                ],
            ],

            'Default' => [
                [
                    'question' => 'Tujuan utama dari pembelajaran modul pada bab ini adalah untuk memahami...',
                    'points' => 25,
                    'options' => ['Konsep dasar dan aplikasinya', 'Hanya menghafal teori', 'Menyalin materi modul', 'Mengerjakan tugas akhir saja'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Langkah pertama yang tepat dalam memecahkan masalah kontekstual adalah...',
                    'points' => 25,
                    'options' => ['Langsung membuat kesimpulan', 'Mengidentifikasi dan memahami permasalahan', 'Mengabaikan petunjuk tugas', 'Menebak hasil'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Sikap ilmiah yang harus dijunjung tinggi dalam proses evaluasi belajar adalah...',
                    'points' => 25,
                    'options' => ['Menyontek pekerjaan teman', 'Kejujuran dan ketelitian', 'Terburu-buru mengumpulkan', 'Mengabaikan evaluasi'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Manfaat melakukan refleksi belajar di akhir pertemuan adalah...',
                    'points' => 25,
                    'options' => ['Mengetahui sejauh mana pemahaman yang telah dicapai', 'Menambah beban belajar', 'Menghabiskan waktu jam pelajaran', 'Formalitas semata'],
                    'correct_index' => 0,
                ],
            ],
        ];
    }
}

