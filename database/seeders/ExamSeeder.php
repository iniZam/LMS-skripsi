<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bersihkan data dummy UTS dan UAS sebelumnya agar idempotent
        Quiz::whereIn('type', [Quiz::TYPE_UTS, Quiz::TYPE_UAS])->delete();

        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $adminId = $admin ? $admin->id : 1;

        $classes = ClassRoom::with(['teacher', 'students'])->get();
        if ($classes->isEmpty()) {
            return;
        }

        $utsQuestionBank = $this->getUtsQuestionBanks();
        $uasQuestionBank = $this->getUasQuestionBanks();

        foreach ($classes as $index => $class) {
            $normalizedSubject = $this->normalizeSubjectName($class->subject_category ?: $class->title);
            $utsQuestions = $utsQuestionBank[$normalizedSubject] ?? $utsQuestionBank['Default'];
            $uasQuestions = $uasQuestionBank[$normalizedSubject] ?? $uasQuestionBank['Default'];

            // Penentuan status approval agar ada variasi untuk demo pengujian:
            // Kelas 1-5 (contoh Kelas 7A, 8A, 9A utama):
            // - UTS: approved (dengan attempt siswa sehingga rapot terisi)
            // - UAS: pending (untuk uji coba tombol "ACC & Rilis" oleh Admin)
            if ($index < 10) {
                $utsStatus = Quiz::STATUS_APPROVED;
                $uasStatus = ($index % 2 === 0) ? Quiz::STATUS_PENDING : Quiz::STATUS_APPROVED;
            } elseif ($index < 50) {
                $utsStatus = Quiz::STATUS_APPROVED;
                $uasStatus = Quiz::STATUS_APPROVED;
            } elseif ($index < 75) {
                $utsStatus = Quiz::STATUS_APPROVED;
                $uasStatus = Quiz::STATUS_PENDING;
            } else {
                $utsStatus = Quiz::STATUS_PENDING;
                $uasStatus = ($index % 2 === 0) ? Quiz::STATUS_REJECTED : Quiz::STATUS_PENDING;
            }

            // -------------------------------------------------------------
            // 1. BUAT DATA DUMMY UTS (Ujian Tengah Semester)
            // -------------------------------------------------------------
            $uts = Quiz::create([
                'class_id' => $class->id,
                'meeting_id' => null,
                'type' => Quiz::TYPE_UTS,
                'approval_status' => $utsStatus,
                'approved_by' => ($utsStatus === Quiz::STATUS_APPROVED) ? $adminId : null,
                'approved_at' => ($utsStatus === Quiz::STATUS_APPROVED) ? now()->subDays(10) : null,
                'title' => 'UTS Semester Ganjil - ' . ($class->subject_category ?: 'Mata Pelajaran') . ' (' . $class->title . ')',
                'description' => 'Ujian Tengah Semester (UTS) Ganjil Tahun Ajaran ' . $class->academic_year . '. Bacalah soal dengan teliti dan pilih jawaban yang paling tepat.',
                'duration_minutes' => 60,
                'deadline' => now()->addDays(14),
            ]);

            $this->seedQuestionsAndOptions($uts, $utsQuestions);

            if ($utsStatus === Quiz::STATUS_APPROVED) {
                $this->seedSampleAttempts($class, $uts, 3);
            }

            // -------------------------------------------------------------
            // 2. BUAT DATA DUMMY UAS (Ujian Akhir Semester)
            // -------------------------------------------------------------
            $uas = Quiz::create([
                'class_id' => $class->id,
                'meeting_id' => null,
                'type' => Quiz::TYPE_UAS,
                'approval_status' => $uasStatus,
                'approved_by' => ($uasStatus === Quiz::STATUS_APPROVED) ? $adminId : null,
                'approved_at' => ($uasStatus === Quiz::STATUS_APPROVED) ? now()->subDays(3) : null,
                'title' => 'UAS Semester Ganjil - ' . ($class->subject_category ?: 'Mata Pelajaran') . ' (' . $class->title . ')',
                'description' => 'Ujian Akhir Semester (UAS) Ganjil Tahun Ajaran ' . $class->academic_year . '. Evaluasi menyeluruh capaian kompetensi semester ganjil.',
                'duration_minutes' => 90,
                'deadline' => now()->addDays(28),
            ]);

            $this->seedQuestionsAndOptions($uas, $uasQuestions);

            if ($uasStatus === Quiz::STATUS_APPROVED) {
                $this->seedSampleAttempts($class, $uas, 2);
            }
        }
    }

    /**
     * Membuat butir pertanyaan dan pilihan ganda.
     */
    private function seedQuestionsAndOptions(Quiz $quiz, array $questions): void
    {
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
    }

    /**
     * Membuat sampel pengerjaan siswa yang realistis.
     */
    private function seedSampleAttempts(ClassRoom $class, Quiz $quiz, int $studentCount = 2): void
    {
        $students = $class->students()->take($studentCount)->get();
        if ($students->isEmpty()) {
            return;
        }

        $questions = $quiz->questions()->with('options')->get();
        if ($questions->isEmpty()) {
            return;
        }

        foreach ($students as $sIdx => $student) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'started_at' => now()->subDays(2)->addMinutes($sIdx * 15),
                'completed_at' => now()->subDays(2)->addMinutes($sIdx * 15 + 40),
                'score' => 0,
            ]);

            $totalPoints = 0;
            $earnedPoints = 0;

            foreach ($questions as $qIdx => $question) {
                $totalPoints += $question->points;
                $options = $question->options;
                $correctOption = $options->firstWhere('is_correct', true);

                // Variasikan jawaban: siswa pertama selalu benar 100%, siswa berikutnya ada 1 kesalahan
                $pickCorrect = ($sIdx === 0) || ($qIdx !== 1);

                if ($pickCorrect && $correctOption) {
                    $selectedOption = $correctOption;
                    $isCorrect = true;
                    $earnedPoints += $question->points;
                } else {
                    $selectedOption = $options->firstWhere('is_correct', false) ?? $options->first();
                    $isCorrect = false;
                }

                StudentAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => $selectedOption?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $finalScore = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
            $attempt->update(['score' => $finalScore]);
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

    /**
     * Bank Soal UTS (Materi Mid-Semester).
     */
    private function getUtsQuestionBanks(): array
    {
        return [
            'Matematika' => [
                [
                    'question' => 'Suhu udara di dalam ruangan pendingin mula-mula adalah -4°C. Jika suhu dinaikkan 7°C, maka suhu sekarang adalah...',
                    'points' => 25,
                    'options' => ['-11°C', '3°C', '11°C', '-3°C'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Bentuk paling sederhana dari pecahan 36/84 adalah...',
                    'points' => 25,
                    'options' => ['3/7', '2/7', '4/9', '3/8'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Hasil dari (-12) × 4 + 72 : (-6) adalah...',
                    'points' => 25,
                    'options' => ['-60', '-36', '36', '60'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Jika a = 3 dan b = -2, maka nilai dari 2a² - 3b adalah...',
                    'points' => 25,
                    'options' => ['12', '24', '18', '20'],
                    'correct_index' => 1,
                ],
            ],

            'IPA' => [
                [
                    'question' => 'Organel sel yang dijuluki sebagai "the powerhouse of cell" karena menghasilkan energi bagi sel adalah...',
                    'points' => 25,
                    'options' => ['Ribosom', 'Mitokondria', 'Badan Golgi', 'Lisosom'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Hubungan antara lebah madu dengan bunga yang saling menguntungkan merupakan contoh simbiosis...',
                    'points' => 25,
                    'options' => ['Komensalisme', 'Parasitisme', 'Mutualisme', 'Amensalisme'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Zat yang memiliki bentuk berubah-ubah mengikuti wadahnya namun volumenya tetap adalah zat berbentuk...',
                    'points' => 25,
                    'options' => ['Padat', 'Cair', 'Gas', 'Plasma'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Peristiwa perpindahan kalor tanpa disertai perpindahan partikel zat perantaranya disebut...',
                    'points' => 25,
                    'options' => ['Konduksi', 'Konveksi', 'Radiasi', 'Evaporasi'],
                    'correct_index' => 0,
                ],
            ],

            'Bahasa Indonesia' => [
                [
                    'question' => 'Bagian teks deskripsi yang berisi nama objek yang dideskripsikan, lokasi, sejarah lahirnya, atau makna nama objek disebut...',
                    'points' => 25,
                    'options' => ['Deskripsi bagian', 'Identifikasi / Deskripsi umum', 'Simpulan', 'Penutup'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Kalimat berikut yang menggunakan majas personifikasi adalah...',
                    'points' => 25,
                    'options' => [
                        'Suaranya menggelegar membelah angkasa.',
                        'Angin malam membelai rambutnya dengan lembut.',
                        'Wajahnya bersinar laksana rembulan.',
                        'Ayah memeras keringat demi keluarga.'
                    ],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Cerita fiksi yang tokoh-tokohnya diperankan oleh hewan yang berperilaku menyerupai manusia dinamakan...',
                    'points' => 25,
                    'options' => ['Mite', 'Legenda', 'Fabel', 'Sage'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Unsur intrinsik cerpen yang berkaitan dengan tempat, waktu, dan suasana terjadinya peristiwa adalah...',
                    'points' => 25,
                    'options' => ['Alur', 'Latar (Setting)', 'Tema', 'Sudut pandang'],
                    'correct_index' => 1,
                ],
            ],

            'Bahasa Inggris' => [
                [
                    'question' => 'Rina: "Good morning, Sir. How are you today?" - Teacher: "Good morning, Rina. ... , thank you."',
                    'points' => 25,
                    'options' => ['I am fine', 'Nice to meet you', 'Goodbye', 'You are welcome'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'My father ... a cup of black coffee every morning.',
                    'points' => 25,
                    'options' => ['drink', 'drinks', 'drinking', 'drank'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Look at the cat! ... fur is very soft and white.',
                    'points' => 25,
                    'options' => ['It', 'It\'s', 'Its', 'They'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'The antonym (opposite meaning) of the word "CLEVER" is...',
                    'points' => 25,
                    'options' => ['Smart', 'Stupid', 'Diligent', 'Polite'],
                    'correct_index' => 1,
                ],
            ],

            'IPS' => [
                [
                    'question' => 'Secara astronomis, letak wilayah Indonesia berada di antara...',
                    'points' => 25,
                    'options' => [
                        '6° LU - 11° LS dan 95° BT - 141° BT',
                        '6° LS - 11° LU dan 95° BB - 141° BT',
                        '11° LU - 6° LS dan 90° BT - 140° BT',
                        '7° LU - 12° LS dan 94° BT - 142° BT'
                    ],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Perbandingan jarak pada peta dengan jarak sebenarnya di permukaan bumi disebut...',
                    'points' => 25,
                    'options' => ['Legenda', 'Orientasi', 'Skala', 'Inset'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Kegiatan menyalurkan barang atau jasa dari produsen kepada konsumen disebut kegiatan...',
                    'points' => 25,
                    'options' => ['Produksi', 'Distribusi', 'Konsumsi', 'Investasi'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Lembaga sosial pertama dan utama yang memberikan sosialisasi nilai dan norma kepada anak adalah lembaga...',
                    'points' => 25,
                    'options' => ['Pendidikan', 'Agama', 'Keluarga', 'Ekonomi'],
                    'correct_index' => 2,
                ],
            ],

            'PKn' => [
                [
                    'question' => 'Rumusan Pancasila sebagai dasar negara Indonesia secara resmi tercantum dalam Pembukaan UUD 1945 alinea ke-...',
                    'points' => 25,
                    'options' => ['Pertama', 'Kedua', 'Ketiga', 'Keempat'],
                    'correct_index' => 3,
                ],
                [
                    'question' => 'Norma yang bersumber dari hati nurani manusia mengenai baik dan buruknya perbuatan dinamakan norma...',
                    'points' => 25,
                    'options' => ['Agama', 'Kesusilaan', 'Kesopanan', 'Hukum'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Sikap saling menghormati dan menghargai perbedaan suku, agama, dan ras antar sesama warga negara disebut...',
                    'points' => 25,
                    'options' => ['Chauvinisme', 'Toleransi', 'Etnosentrisme', 'Individualisme'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Tokoh pendiri bangsa yang menyampaikan usulan dasar negara pada tanggal 1 Juni 1945 adalah...',
                    'points' => 25,
                    'options' => ['Moh. Hatta', 'Mr. Soepomo', 'Ir. Soekarno', 'Moh. Yamin'],
                    'correct_index' => 2,
                ],
            ],

            'Pend. Agama' => [
                [
                    'question' => 'Sikap senantiasa berkata dan berbuat sesuai dengan kenyataan yang sebenarnya disebut sifat...',
                    'points' => 25,
                    'options' => ['Amanah', 'Jujur (Siddiq)', 'Fathanah', 'Tabligh'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Rukun iman yang kedua dalam ajaran Islam adalah beriman kepada...',
                    'points' => 25,
                    'options' => ['Allah SWT', 'Malaikat-malaikat Allah', 'Kitab-kitab Allah', 'Hari Akhir'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Membersihkan diri dari hadas kecil dilakukan dengan cara...',
                    'points' => 25,
                    'options' => ['Mandi wajib', 'Berwudhu atau tayamum', 'Mengusap pakaian', 'Istighfar'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Menghormati orang tua dan guru dalam ajaran agama merupakan bentuk perilaku...',
                    'points' => 25,
                    'options' => ['Birrul walidain', 'Suudzon', 'Riya', 'Ujub'],
                    'correct_index' => 0,
                ],
            ],

            'PJOK' => [
                [
                    'question' => 'Kemampuan tubuh untuk melakukan aktivitas fisik dalam jangka waktu lama tanpa mengalami kelelahan yang berlebihan disebut...',
                    'points' => 25,
                    'options' => ['Kekuatan', 'Kebugaran jasmani', 'Kelenturan', 'Kecepatan'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Teknik mengoper bola kepada teman satu tim dalam permainan sepak bola disebut...',
                    'points' => 25,
                    'options' => ['Dribbling', 'Shooting', 'Passing', 'Heading'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Jumlah pemain dalam satu tim pada pertandingan bola voli resmi di lapangan adalah...',
                    'points' => 25,
                    'options' => ['5 orang', '6 orang', '7 orang', '11 orang'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Gerakan senam lantai berupa berguling ke depan di atas matras dinamakan...',
                    'points' => 25,
                    'options' => ['Forward roll', 'Back roll', 'Cartwheel', 'Handstand'],
                    'correct_index' => 0,
                ],
            ],

            'Prakarya' => [
                [
                    'question' => 'Bahan alam yang lunak dan mudah dibentuk untuk pembuatan produk kerajinan gerabah adalah...',
                    'points' => 25,
                    'options' => ['Kayu jati', 'Tanah liat', 'Bambu', 'Rotan'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Teknik pengolahan makanan dengan cara memasak bahan makanan di dalam cairan yang sedang mendidih disebut...',
                    'points' => 25,
                    'options' => ['Boiling (Merebus)', 'Steaming (Mengukus)', 'Baking (Memanggang)', 'Frying (Menggoreng)'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Serat alam yang dihasilkan dari kepompong ulat sutra dimanfaatkan untuk pembuatan kain...',
                    'points' => 25,
                    'options' => ['Katun', 'Sutra', 'Wol', 'Linen'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Prinsip 3R dalam pengelolaan limbah lingkungan terdiri dari...',
                    'points' => 25,
                    'options' => ['Reduce, Reuse, Recycle', 'Repair, Repeat, Return', 'Remove, Refuse, Replace', 'Rebuild, Refresh, Restart'],
                    'correct_index' => 0,
                ],
            ],

            'Informatika' => [
                [
                    'question' => 'Komponen fisik dari sistem komputer yang dapat dilihat dan disentuh secara langsung disebut...',
                    'points' => 25,
                    'options' => ['Software', 'Hardware', 'Brainware', 'Malware'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Perangkat keras yang berfungsi sebagai otak utama dalam memproses instruksi pada komputer adalah...',
                    'points' => 25,
                    'options' => ['Harddisk', 'RAM', 'CPU (Processor)', 'Power Supply'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Fondasi berpikir komputasional yang memecah masalah besar menjadi bagian-bagian yang lebih kecil disebut...',
                    'points' => 25,
                    'options' => ['Abstraksi', 'Dekomposisi', 'Pengenalan pola', 'Perancangan algoritma'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Kombinasi tombol keyboard (shortcut) standar untuk menyalin (copy) teks pada sistem operasi komputer adalah...',
                    'points' => 25,
                    'options' => ['Ctrl + V', 'Ctrl + C', 'Ctrl + X', 'Ctrl + Z'],
                    'correct_index' => 1,
                ],
            ],

            'Default' => [
                [
                    'question' => 'Sikap yang mencerminkan integritas tinggi dalam mengikuti kegiatan evaluasi belajar adalah...',
                    'points' => 25,
                    'options' => ['Bekerja sama saat ujian', 'Mengerjakan sendiri dengan jujur', 'Melihat catatan kecil', 'Menanyakan jawaban ke teman'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Tujuan utama dari penyelenggaraan Ujian Tengah Semester (UTS) adalah...',
                    'points' => 25,
                    'options' => ['Menghukum siswa', 'Mengukur capaian pemahaman belajar', 'Mengurangi waktu bermain', 'Membandingkan kecerdasan antar siswa'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Manfaat menjaga kedisiplinan dan hadir tepat waktu dalam proses pembelajaran adalah...',
                    'points' => 25,
                    'options' => ['Mendapatkan pujian saja', 'Membangun karakter bertanggung jawab', 'Menghindari hukuman guru', 'Membuat teman iri'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Langkah pertama yang sebaiknya dilakukan saat menghadapi soal ujian yang sulit adalah...',
                    'points' => 25,
                    'options' => ['Langsung menyerah', 'Membaca soal dengan teliti dan tenang', 'Menebak sembarangan', 'Keluar dari ruangan ujian'],
                    'correct_index' => 1,
                ],
            ],
        ];
    }

    /**
     * Bank Soal UAS (Materi Komprehensif Semester Ganjil).
     */
    private function getUasQuestionBanks(): array
    {
        return [
            'Matematika' => [
                [
                    'question' => 'Sebuah peta berskala 1 : 500.000. Jika jarak dua kota pada peta adalah 8 cm, maka jarak sebenarnya kedua kota tersebut adalah...',
                    'points' => 25,
                    'options' => ['4 km', '40 km', '400 km', '40.000 m'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Seorang pedagang membeli 1 kodi baju dengan harga Rp 800.000. Jika baju tersebut dijual dengan harga Rp 50.000 per lembar, maka pedagang tersebut mengalami...',
                    'points' => 25,
                    'options' => ['Untung Rp 200.000', 'Rugi Rp 200.000', 'Untung Rp 100.000', 'Rugi Rp 100.000'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Penyelesaian dari pertidaksamaan 2x - 4 < 10 dengan x bilangan bulat positif adalah...',
                    'points' => 25,
                    'options' => ['x < 7', 'x > 7', 'x < 3', 'x > 3'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Sebuah taman berbentuk lingkaran memiliki diameter 28 meter. Luas taman tersebut adalah... (gunakan π = 22/7)',
                    'points' => 25,
                    'options' => ['616 m²', '154 m²', '1.232 m²', '88 m²'],
                    'correct_index' => 0,
                ],
            ],

            'IPA' => [
                [
                    'question' => 'Urutan tingkatan organisasi kehidupan dari yang paling sederhana ke yang paling kompleks adalah...',
                    'points' => 25,
                    'options' => [
                        'Sel -> Jaringan -> Organ -> Sistem Organ -> Organisme',
                        'Jaringan -> Sel -> Organ -> Organisme -> Sistem Organ',
                        'Organ -> Sistem Organ -> Sel -> Jaringan -> Organisme',
                        'Sel -> Organ -> Jaringan -> Organisme -> Sistem Organ'
                    ],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Sebuah mobil bergerak dengan kecepatan tetap 20 m/s selama 15 detik. Jarak yang ditempuh mobil tersebut adalah...',
                    'points' => 25,
                    'options' => ['150 meter', '300 meter', '450 meter', '600 meter'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Contoh pemisahan campuran yang memanfaatkan perbedaan titik didih antar zat cair adalah...',
                    'points' => 25,
                    'options' => ['Filtrasi', 'Distilasi (Penyulingan)', 'Kristalisasi', 'Kromatografi'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Gas rumah kaca yang paling dominan menyebabkan terjadinya pemanasan global (global warming) adalah...',
                    'points' => 25,
                    'options' => ['Oksigen (O₂)', 'Nitrogen (N₂)', 'Karbon dioksida (CO₂)', 'Helium (He)'],
                    'correct_index' => 2,
                ],
            ],

            'Bahasa Indonesia' => [
                [
                    'question' => 'Teks yang berisi langkah-langkah atau tahapan yang harus ditempuh untuk mencapai suatu tujuan disebut teks...',
                    'points' => 25,
                    'options' => ['Laporan Hasil Observasi', 'Prosedur', 'Eksposisi', 'Negosiasi'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Kalimat perintah atau imperatif yang tepat dalam teks prosedur membuat teh manis adalah...',
                    'points' => 25,
                    'options' => [
                        'Teh manis sangat nikmat diminum saat hangat.',
                        'Masukkan dua sendok gula pasir ke dalam cangkir!',
                        'Gula pasir adalah pemanis alami.',
                        'Siapakah yang membuat teh manis ini?'
                    ],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Bagian surat dinas yang berisi logo lembaga, nama instansi, alamat, dan nomor telepon instansi disebut...',
                    'points' => 25,
                    'options' => ['Kop / Kepala surat', 'Nomor surat', 'Perihal surat', 'Alamat tujuan'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Buku yang memuat informasi berdasarkan fakta, data empiris, dan riset ilmiah disebut buku...',
                    'points' => 25,
                    'options' => ['Fiksi', 'Nonfiksi', 'Novel', 'Komik'],
                    'correct_index' => 1,
                ],
            ],

            'Bahasa Inggris' => [
                [
                    'question' => 'Dayu: "What time do you usually wake up?" - Siti: "I usually wake up ... five o\'clock."',
                    'points' => 25,
                    'options' => ['in', 'on', 'at', 'by'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'The classroom is very dark. Please ... the lamp.',
                    'points' => 25,
                    'options' => ['turn off', 'turn on', 'clean', 'open'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'There are many ... playing in the school yard during break time.',
                    'points' => 25,
                    'options' => ['child', 'childs', 'children', 'childrens'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Which of the following sentences expresses asking for attention?',
                    'points' => 25,
                    'options' => [
                        'Excuse me, may I have your attention, please?',
                        'I don\'t understand this lesson.',
                        'What a beautiful painting!',
                        'Thank you very much.'
                    ],
                    'correct_index' => 0,
                ],
            ],

            'IPS' => [
                [
                    'question' => 'Dampak positif letak geologis Indonesia yang berada pada jalur pertemuan tiga lempeng tektonik adalah...',
                    'points' => 25,
                    'options' => [
                        'Tanah vulkanik menjadi sangat subur',
                        'Sering terjadi gempa bumi',
                        'Iklim tropis sepanjang tahun',
                        'Jalur pelayaran internasional sepi'
                    ],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Proses interaksi sosial yang mengarah pada persatuan dan kesatuan masyarakat dinamakan proses...',
                    'points' => 25,
                    'options' => ['Disosiatif', 'Asosiatif', 'Kompetisi', 'Konflik'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Pasar tempat bertemunya penjual dan pembeli yang barang dagangannya tidak dapat dilihat secara langsung (transaksi daring) disebut...',
                    'points' => 25,
                    'options' => ['Pasar konkret', 'Pasar abstrak', 'Pasar harian', 'Pasar daerah'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Kerajaan maritim bercorak Buddha terbesar di Nusantara yang pernah menjadi pusat pembelajaran agama Buddha di Asia Tenggara adalah...',
                    'points' => 25,
                    'options' => ['Majapahit', 'Sriwijaya', 'Kutai', 'Tarumanegara'],
                    'correct_index' => 1,
                ],
            ],

            'PKn' => [
                [
                    'question' => 'Pancasila berfungsi sebagai pedoman dan petunjuk arah dalam kehidupan bermasyarakat, berbangsa, dan bernegara. Hal ini menunjukkan kedudukan Pancasila sebagai...',
                    'points' => 25,
                    'options' => ['Dasar negara', 'Pandangan hidup bangsa', 'Perjanjian luhur', 'Sumber hukum formal'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Semboyan "Bhinneka Tunggal Ika" yang menjadi semboyan persatuan bangsa Indonesia diambil dari kitab...',
                    'points' => 25,
                    'options' => ['Negarakertagama', 'Sutasoma', 'Arjunawiwaha', 'Pararaton'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Hak dan kewajiban setiap warga negara untuk ikut serta dalam upaya pembelaan negara dijamin dalam UUD 1945 pasal...',
                    'points' => 25,
                    'options' => ['27 ayat 3', '29 ayat 2', '31 ayat 1', '34 ayat 1'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Lembaga negara yang berwenang mengadili pada tingkat pertama dan terakhir yang putusannya bersifat final untuk menguji undang-undang terhadap UUD adalah...',
                    'points' => 25,
                    'options' => ['Mahkamah Agung', 'Mahkamah Konstitusi', 'Komisi Yudisial', 'DPR RI'],
                    'correct_index' => 1,
                ],
            ],

            'Pend. Agama' => [
                [
                    'question' => 'Kitab suci Al-Qur\'an diturunkan Allah SWT kepada Nabi Muhammad SAW melalui perantaraan Malaikat...',
                    'points' => 25,
                    'options' => ['Mikail', 'Jibril', 'Israfil', 'Izrail'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Shalat fardhu berjamaah memiliki keutamaan derajat pahala sebanyak...',
                    'points' => 25,
                    'options' => ['17 derajat', '27 derajat', '37 derajat', '70 derajat'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Sikap berserah diri kepada kehendak Allah SWT setelah berikhtiar dan berusaha secara maksimal disebut...',
                    'points' => 25,
                    'options' => ['Qanaah', 'Tawakal', 'Sabar', 'Syukur'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Menjauhi perbuatan ghibah (membicarakan aib orang lain) merupakan cerminan pengamalan ajaran akhlak...',
                    'points' => 25,
                    'options' => ['Mazmumah', 'Mahmudah', 'Tadabbur', 'Takabur'],
                    'correct_index' => 1,
                ],
            ],

            'PJOK' => [
                [
                    'question' => 'Posisi awal tubuh saat melakukan start jongkok (crouch start) dalam lari jarak pendek (sprint) pada aba-aba "BERSEDIA" adalah...',
                    'points' => 25,
                    'options' => [
                        'Berdiri tegak di belakang garis start',
                        'Berlutut dengan satu kaki di belakang garis start',
                        'Melompat ke depan',
                        'Berlari pelan di tempat'
                    ],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Pukulan pertama yang menandai dimulainya permainan dalam bulu tangkis atau bola voli dinamakan pukulan...',
                    'points' => 25,
                    'options' => ['Smash', 'Service', 'Lob', 'Drop shot'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Gaya renang yang gerakannya menyerupai gerakan katak berenang di air disebut renang gaya...',
                    'points' => 25,
                    'options' => ['Bebas (Crawl)', 'Dada (Breaststroke)', 'Punggung (Backstroke)', 'Kupu-kupu (Butterfly)'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Pola makan seimbang yang mengandung karbohidrat, protein, lemak, vitamin, dan mineral sesuai kebutuhan tubuh dikenal dengan pedoman...',
                    'points' => 25,
                    'options' => ['4 Sehat 5 Sempurna', 'Isi Piringku / Gizi Seimbang', 'Fast food sehat', 'Diet ketat'],
                    'correct_index' => 1,
                ],
            ],

            'Prakarya' => [
                [
                    'question' => 'Kemasan produk kerajinan yang baik harus memenuhi kriteria utama, yaitu...',
                    'points' => 25,
                    'options' => [
                        'Melindungi produk dan menambah daya tarik estetika',
                        'Berbiaya paling mahal',
                        'Berukuran sebesar mungkin',
                        'Hanya terbuat dari plastik'
                    ],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Tanaman obat keluarga (TOGA) seperti jahe, kunyit, dan temulawak berkembang biak secara vegetatif alami menggunakan...',
                    'points' => 25,
                    'options' => ['Umbi lapis', 'Rizoma (Rimpang)', 'Geragih (Stolon)', 'Spora'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Teknik penyambungan dua potongan kayu menggunakan pasak kayu atau paku tanpa perekat kimia merupakan keterampilan bidang...',
                    'points' => 25,
                    'options' => ['Kerajinan', 'Rekayasa konstruksi', 'Budidaya', 'Pengolahan'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Tahapan awal dalam proses pembuatan karya kerajinan sebelum masuk tahap produksi adalah...',
                    'points' => 25,
                    'options' => ['Pengemasan', 'Perencanaan dan pembuatan sketsa ide', 'Pemasaran produk', 'Evaluasi hasil'],
                    'correct_index' => 1,
                ],
            ],

            'Informatika' => [
                [
                    'question' => 'Jaringan komputer global yang menghubungkan jutaan perangkat komputer di seluruh dunia menggunakan protokol TCP/IP disebut...',
                    'points' => 25,
                    'options' => ['Intranet', 'LAN', 'Internet', 'Bluetooth'],
                    'correct_index' => 2,
                ],
                [
                    'question' => 'Aplikasi peramban web (web browser) yang umum digunakan untuk mengakses informasi di internet adalah...',
                    'points' => 25,
                    'options' => ['Microsoft Excel', 'Google Chrome', 'Adobe Photoshop', 'VLC Media Player'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Tindakan kejahatan siber berupa pencurian data pribadi melalui email palsu yang menyerupai institusi resmi disebut...',
                    'points' => 25,
                    'options' => ['Phishing', 'Hacking', 'Cracking', 'Spamming'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Ekstensi berkas standar yang dihasilkan oleh program pengolah kata Microsoft Word versi terbaru adalah...',
                    'points' => 25,
                    'options' => ['.xlsx', '.docx', '.pptx', '.pdf'],
                    'correct_index' => 1,
                ],
            ],

            'Default' => [
                [
                    'question' => 'Evaluasi menyeluruh pada akhir semester (UAS) bertujuan untuk...',
                    'points' => 25,
                    'options' => ['Menentukan kelulusan kompetensi semester', 'Menghukum siswa malas', 'Mengisi waktu luang sekolah', 'Membuat siswa cemas'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Kunci utama meraih prestasi belajar yang memuaskan dan berkesinambungan adalah...',
                    'points' => 25,
                    'options' => ['Belajar hanya saat malam sebelum ujian', 'Disiplin, konsisten, dan tekun berdoa', 'Menghafal tanpa memahami konsep', 'Mengandalkan bantuan teman'],
                    'correct_index' => 1,
                ],
                [
                    'question' => 'Sikap sportivitas dan kejujuran yang ditanamkan selama mengikuti ujian akan membentuk profil pelajar...',
                    'points' => 25,
                    'options' => ['Pancasila yang berintegritas', 'Materialistis', 'Egois', 'Konsumtif'],
                    'correct_index' => 0,
                ],
                [
                    'question' => 'Setelah menyelesaikan seluruh soal ujian sebelum waktu berakhir, langkah terbaik yang dilakukan adalah...',
                    'points' => 25,
                    'options' => ['Langsung mengumpulkan dan keluar ruangan', 'Memeriksa kembali jawaban secara teliti', 'Mengobrol dengan peserta lain', 'Tidur di meja ujian'],
                    'correct_index' => 1,
                ],
            ],
        ];
    }
}
