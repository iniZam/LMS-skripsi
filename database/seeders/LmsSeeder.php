<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Kelas;
use App\Models\Material;
use App\Models\Meeting;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data for an SMP (junior high school): 3 homerooms (7A, 8A, 9A) each
 * with a wali kelas, students assigned to their homeroom via kelas_id, and
 * a handful of Mata Pelajaran (subject classes) with meetings/assignments.
 *
 * Every seeded account uses the password: password
 */
class LmsSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('materials');

        $admin = User::create([
            'name' => 'Ratna Dewi Kusuma, S.Pd.',
            'email' => 'admin@smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'nip_nis' => 'ADM-1001',
        ]);

        $guruBudi = User::create([
            'name' => 'Budi Santoso, S.Pd.',
            'email' => 'budi.santoso@smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_TEACHER,
            'nip_nis' => 'NIP.198504122010121001',
        ]);

        $guruSiti = User::create([
            'name' => 'Siti Rahma, S.Pd.',
            'email' => 'siti.rahma@smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_TEACHER,
            'nip_nis' => 'NIP.199008232015042002',
        ]);

        $guruAndi = User::create([
            'name' => 'Andi Wijaya, S.Pd.',
            'email' => 'andi.wijaya@smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_TEACHER,
            'nip_nis' => 'NIP.198711052012011003',
        ]);

        // --- Homeroom classes (Kelas / Rombel) ---
        $kelas7A = Kelas::create([
            'tingkat' => 7,
            'nama' => 'A',
            'wali_kelas_id' => $guruBudi->id,
            'tahun_ajaran' => '2026/2027',
        ]);

        $kelas8A = Kelas::create([
            'tingkat' => 8,
            'nama' => 'A',
            'wali_kelas_id' => $guruSiti->id,
            'tahun_ajaran' => '2026/2027',
        ]);

        $kelas9A = Kelas::create([
            'tingkat' => 9,
            'nama' => 'A',
            'wali_kelas_id' => $guruAndi->id,
            'tahun_ajaran' => '2026/2027',
        ]);

        // --- Students, each assigned to a homeroom ---
        $siswaAhmad = User::create([
            'name' => 'Ahmad Rizky Pratama',
            'email' => 'ahmad.rizky@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234561',
            'kelas_id' => $kelas7A->id,
        ]);

        $siswaDewi = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi.lestari@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234562',
            'kelas_id' => $kelas7A->id,
        ]);

        $siswaFikri = User::create([
            'name' => 'Fikri Nurhidayat',
            'email' => 'fikri.nur@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234563',
            'kelas_id' => $kelas8A->id,
        ]);

        $siswaPutri = User::create([
            'name' => 'Putri Ayu Ramadhani',
            'email' => 'putri.ayu@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234564',
            'kelas_id' => $kelas8A->id,
        ]);

        $siswaRangga = User::create([
            'name' => 'Rangga Saputra',
            'email' => 'rangga.saputra@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234565',
            'kelas_id' => $kelas9A->id,
        ]);

        $siswaNadia = User::create([
            'name' => 'Nadia Salsabila',
            'email' => 'nadia.salsabila@siswa.smpcendekiabangsa.sch.id',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'nip_nis' => '0091234566',
            'kelas_id' => $kelas9A->id,
        ]);

        // --- Mata Pelajaran (subject classes), tied to a homeroom & taught by a guru ---
        $mtk7A = ClassRoom::create([
            'code' => 'MTK-7A',
            'title' => 'Matematika - Kelas 7A',
            'description' => 'Bilangan bulat, pecahan, dan aritmetika sosial untuk kelas VII.',
            'subject_category' => 'Matematika',
            'teacher_id' => $guruBudi->id,
            'status' => ClassRoom::STATUS_ACTIVE,
            'academic_year' => '2026/2027 Ganjil',
            'banner_gradient' => 'from-blue-600 to-indigo-700',
        ]);
        $mtk7A->students()->attach([$siswaAhmad->id, $siswaDewi->id]);

        $ipa7A = ClassRoom::create([
            'code' => 'IPA-7A',
            'title' => 'Ilmu Pengetahuan Alam - Kelas 7A',
            'description' => 'Klasifikasi makhluk hidup dan pengukuran dalam IPA.',
            'subject_category' => 'IPA',
            'teacher_id' => $guruSiti->id,
            'status' => ClassRoom::STATUS_ACTIVE,
            'academic_year' => '2026/2027 Ganjil',
            'banner_gradient' => 'from-emerald-600 to-teal-700',
        ]);
        // Intentionally left with no meetings yet — demonstrates the empty state.

        $ipa8A = ClassRoom::create([
            'code' => 'IPA-8A',
            'title' => 'Ilmu Pengetahuan Alam - Kelas 8A',
            'description' => 'Sistem pencernaan, pernapasan, dan gerak pada manusia.',
            'subject_category' => 'IPA',
            'teacher_id' => $guruSiti->id,
            'status' => ClassRoom::STATUS_ACTIVE,
            'academic_year' => '2026/2027 Ganjil',
            'banner_gradient' => 'from-purple-600 to-pink-700',
        ]);
        $ipa8A->students()->attach([$siswaFikri->id, $siswaPutri->id]);

        $bindo9A = ClassRoom::create([
            'code' => 'BIND-9A',
            'title' => 'Bahasa Indonesia - Kelas 9A',
            'description' => 'Teks deskripsi, teks eksposisi, dan kaidah kebahasaan.',
            'subject_category' => 'Bahasa Indonesia',
            'teacher_id' => $guruAndi->id,
            'status' => ClassRoom::STATUS_ACTIVE,
            'academic_year' => '2026/2027 Ganjil',
            'banner_gradient' => 'from-amber-600 to-orange-700',
        ]);
        $bindo9A->students()->attach([$siswaRangga->id, $siswaNadia->id]);

        // --- Meetings, materials, assignments & submissions ---
        $meeting1 = Meeting::create([
            'class_id' => $mtk7A->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1: Operasi Hitung Bilangan Bulat',
            'description' => 'Penjumlahan, pengurangan, perkalian, dan pembagian bilangan bulat beserta sifat-sifatnya.',
            'youtube_url' => 'https://www.youtube.com/watch?v=DCdGyoyeih8',
            'has_assignment' => false,
        ]);
        $this->seedPlaceholderMaterial($meeting1, 'Modul_01_Bilangan_Bulat.pdf', 'application/pdf');

        $meeting2 = Meeting::create([
            'class_id' => $mtk7A->id,
            'meeting_number' => 2,
            'title' => 'Pertemuan 2: Aritmetika Sosial',
            'description' => 'Konsep untung, rugi, diskon, bunga tunggal, dan penerapannya dalam jual beli.',
            'youtube_url' => 'https://www.youtube.com/watch?v=8UVfXaFwWsY',
            'has_assignment' => true,
            'assignment_title' => 'Tugas: Latihan Soal Aritmetika Sosial',
            'assignment_instructions' => 'Kerjakan 10 soal cerita tentang untung, rugi, dan diskon pada modul halaman 24. Foto atau scan jawaban tulis tangan, lalu unggah dalam satu berkas PDF.',
            'assignment_deadline' => now()->addDays(3),
        ]);
        $this->seedPlaceholderMaterial($meeting2, 'Modul_02_Aritmetika_Sosial.pdf', 'application/pdf');

        Submission::create([
            'meeting_id' => $meeting2->id,
            'student_id' => $siswaAhmad->id,
            'file_name' => 'Ahmad_Rizky_Tugas_Aritmetika.pdf',
            'file_path' => $this->seedPlaceholderSubmissionFile($meeting2, $siswaAhmad, 'Ahmad_Rizky_Tugas_Aritmetika.pdf'),
            'file_size' => '850 KB',
            'student_note' => 'Bu, mohon dikoreksi soal nomor 7, saya kurang yakin dengan jawaban saya.',
            'submitted_at' => now()->subDays(2),
            'is_late' => false,
            'grade' => 88,
            'feedback' => 'Bagus! Nomor 7 sudah benar, hanya perlu ditulis satuannya (Rp) secara konsisten.',
            'graded_at' => now()->subDay(),
            'graded_by' => $guruBudi->id,
        ]);

        Submission::create([
            'meeting_id' => $meeting2->id,
            'student_id' => $siswaDewi->id,
            'file_name' => 'Dewi_Lestari_Tugas_Aritmetika.pdf',
            'file_path' => $this->seedPlaceholderSubmissionFile($meeting2, $siswaDewi, 'Dewi_Lestari_Tugas_Aritmetika.pdf'),
            'file_size' => '780 KB',
            'student_note' => null,
            'submitted_at' => now()->subHours(5),
            'is_late' => true,
            'grade' => 75,
            'feedback' => 'Jawaban sudah tepat, tapi lain kali tolong dikumpulkan sebelum batas waktu ya.',
            'graded_at' => now()->subHours(2),
            'graded_by' => $guruBudi->id,
        ]);

        $meeting3 = Meeting::create([
            'class_id' => $ipa8A->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1: Sistem Pencernaan Manusia',
            'description' => 'Organ-organ pencernaan dan proses pencernaan mekanik serta kimiawi.',
            'youtube_url' => 'https://www.youtube.com/watch?v=RFXPKzKm8Bw',
            'has_assignment' => true,
            'assignment_title' => 'Tugas: Laporan Pengamatan Sistem Pencernaan',
            'assignment_instructions' => 'Buatlah diagram alur sistem pencernaan manusia beserta fungsi tiap organ, ditulis tangan pada kertas folio, lalu foto dan unggah.',
            'assignment_deadline' => now()->addDays(5),
        ]);
        $this->seedPlaceholderMaterial($meeting3, 'Modul_IPA_Sistem_Pencernaan.pdf', 'application/pdf');

        $meeting4 = Meeting::create([
            'class_id' => $bindo9A->id,
            'meeting_number' => 1,
            'title' => 'Pertemuan 1: Menulis Teks Deskripsi',
            'description' => 'Struktur dan ciri kebahasaan teks deskripsi, dilengkapi contoh teks.',
            'youtube_url' => 'https://www.youtube.com/watch?v=k4Ac2j2X2Qw',
            'has_assignment' => false,
        ]);
        $this->seedPlaceholderMaterial($meeting4, 'Contoh_Teks_Deskripsi.pdf', 'application/pdf');
    }

    private function seedPlaceholderMaterial(Meeting $meeting, string $fileName, string $mime): Material
    {
        $path = "materials/{$meeting->id}/{$fileName}";
        Storage::disk('public')->put($path, "Berkas contoh untuk: {$fileName}\nUnggah ulang berkas asli melalui halaman edit pertemuan.");

        return $meeting->materials()->create([
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => '1.0 MB',
            'file_type' => $mime,
        ]);
    }

    private function seedPlaceholderSubmissionFile(Meeting $meeting, User $student, string $fileName): string
    {
        $path = "submissions/{$meeting->id}/{$student->id}_{$fileName}";
        Storage::disk('public')->put($path, "Berkas contoh pengumpulan tugas: {$fileName}");

        return $path;
    }
}
