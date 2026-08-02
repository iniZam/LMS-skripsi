-- =====================================================================
-- EduEduka LMS — Skema + Data Dummy (SMP)
-- =====================================================================
-- Berisi seluruh tabel aplikasi (bukan tabel internal Laravel seperti
-- sessions/cache/jobs) beserta data contoh: 3 kelas (7A, 8A, 9A), 1 admin,
-- 3 guru (masing-masing wali kelas), 6 siswa, 4 mata pelajaran, pertemuan,
-- materi, dan pengumpulan tugas.
--
-- CARA PAKAI
--   mysql -u root -p < edueduka_lms_dummy.sql
-- atau import lewat phpMyAdmin/Adminer ke database KOSONG.
--
-- Skrip ini ditujukan untuk DATABASE BARU/KOSONG (mis. belum pernah
-- dijalankan `php artisan migrate`). Skrip akan DROP lalu membuat ulang
-- ketujuh tabel di bawah ini.
--
-- !! PERINGATAN !! Jangan jalankan ke database yang sudah berisi data
-- sekolah yang sesungguhnya — skrip ini akan MENGHAPUS tabel yang sudah
-- ada dengan nama yang sama.
--
-- Jika proyek Laravel Anda sudah dimigrasikan (`php artisan migrate`),
-- gunakan `php artisan db:seed` (LmsSeeder) sebagai gantinya — isi datanya
-- sama persis dengan file ini.
--
-- Semua akun memakai kata sandi: password
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Opsional: buat & pakai database baru. Sesuaikan nama/hapus baris ini
-- jika database Anda sudah dibuat oleh hosting/DBA.
CREATE DATABASE IF NOT EXISTS lms_edueduka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lms_edueduka;

DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS meetings;
DROP TABLE IF EXISTS class_student;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- Table: users  (admin / guru / siswa)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    role VARCHAR(255) NOT NULL DEFAULT 'student',
    nip_nis VARCHAR(255) NULL DEFAULT NULL,
    kelas_id BIGINT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_role_index (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: kelas  (homeroom / rombongan belajar, mis. "7A")
-- ---------------------------------------------------------------------
CREATE TABLE kelas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tingkat TINYINT UNSIGNED NOT NULL,
    nama VARCHAR(10) NOT NULL,
    wali_kelas_id BIGINT UNSIGNED NULL DEFAULT NULL,
    tahun_ajaran VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY kelas_tingkat_nama_tahun_ajaran_unique (tingkat, nama, tahun_ajaran),
    CONSTRAINT kelas_wali_kelas_id_foreign FOREIGN KEY (wali_kelas_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now that `kelas` exists, attach the FK from users.kelas_id to it.
ALTER TABLE users
    ADD CONSTRAINT users_kelas_id_foreign FOREIGN KEY (kelas_id) REFERENCES kelas (id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- Table: classes  (Mata Pelajaran — subject taught to a homeroom by a guru)
-- ---------------------------------------------------------------------
CREATE TABLE classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    subject_category VARCHAR(255) NULL DEFAULT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'registration',
    academic_year VARCHAR(255) NULL DEFAULT NULL,
    banner_gradient VARCHAR(255) NOT NULL DEFAULT 'from-blue-600 to-indigo-700',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY classes_code_unique (code),
    KEY classes_status_index (status),
    CONSTRAINT classes_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: class_student  (enrollment pivot)
-- ---------------------------------------------------------------------
CREATE TABLE class_student (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY class_student_class_id_student_id_unique (class_id, student_id),
    CONSTRAINT class_student_class_id_foreign FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT class_student_student_id_foreign FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: meetings  (pertemuan)
-- ---------------------------------------------------------------------
CREATE TABLE meetings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    meeting_number INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    youtube_url VARCHAR(255) NULL DEFAULT NULL,
    has_assignment TINYINT(1) NOT NULL DEFAULT 0,
    assignment_title VARCHAR(255) NULL DEFAULT NULL,
    assignment_instructions TEXT NULL,
    assignment_deadline DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY meetings_class_id_meeting_number_unique (class_id, meeting_number),
    CONSTRAINT meetings_class_id_foreign FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: materials  (berkas materi per pertemuan)
-- ---------------------------------------------------------------------
CREATE TABLE materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    meeting_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(255) NULL DEFAULT NULL,
    file_type VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT materials_meeting_id_foreign FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: submissions  (pengumpulan tugas)
-- ---------------------------------------------------------------------
CREATE TABLE submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    meeting_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(255) NULL DEFAULT NULL,
    student_note TEXT NULL,
    submitted_at DATETIME NOT NULL,
    is_late TINYINT(1) NOT NULL DEFAULT 0,
    grade TINYINT UNSIGNED NULL DEFAULT NULL,
    feedback TEXT NULL,
    graded_at DATETIME NULL DEFAULT NULL,
    graded_by BIGINT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY submissions_meeting_id_student_id_unique (meeting_id, student_id),
    CONSTRAINT submissions_meeting_id_foreign FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE CASCADE,
    CONSTRAINT submissions_student_id_foreign FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT submissions_graded_by_foreign FOREIGN KEY (graded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- DATA DUMMY
-- Kata sandi semua akun: password
-- (hash bcrypt di bawah adalah hasil nyata dari password_hash('password'))
-- =====================================================================

-- ---------------------------------------------------------------------
-- users: 1 admin, 3 guru, 6 siswa
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password, role, nip_nis, kelas_id, created_at, updated_at) VALUES
(1, 'Ratna Dewi Kusuma, S.Pd.', 'admin@smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'admin', 'ADM-1001', NULL, NOW(), NOW()),
(2, 'Budi Santoso, S.Pd.', 'budi.santoso@smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'teacher', 'NIP.198504122010121001', NULL, NOW(), NOW()),
(3, 'Siti Rahma, S.Pd.', 'siti.rahma@smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'teacher', 'NIP.199008232015042002', NULL, NOW(), NOW()),
(4, 'Andi Wijaya, S.Pd.', 'andi.wijaya@smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'teacher', 'NIP.198711052012011003', NULL, NOW(), NOW()),
(5, 'Ahmad Rizky Pratama', 'ahmad.rizky@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234561', 1, NOW(), NOW()),
(6, 'Dewi Lestari', 'dewi.lestari@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234562', 1, NOW(), NOW()),
(7, 'Fikri Nurhidayat', 'fikri.nur@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234563', 2, NOW(), NOW()),
(8, 'Putri Ayu Ramadhani', 'putri.ayu@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234564', 2, NOW(), NOW()),
(9, 'Rangga Saputra', 'rangga.saputra@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234565', 3, NOW(), NOW()),
(10, 'Nadia Salsabila', 'nadia.salsabila@siswa.smpcendekiabangsa.sch.id', '$2y$12$UEJOfZw9vCINa389dp1Hy.AyjoE5RSuy.Uvv79k23X/thTQdC8KWy', 'student', '0091234566', 3, NOW(), NOW());

ALTER TABLE users AUTO_INCREMENT = 11;

-- ---------------------------------------------------------------------
-- kelas: 7A, 8A, 9A
-- ---------------------------------------------------------------------
INSERT INTO kelas (id, tingkat, nama, wali_kelas_id, tahun_ajaran, created_at, updated_at) VALUES
(1, 7, 'A', 2, '2026/2027', NOW(), NOW()),
(2, 8, 'A', 3, '2026/2027', NOW(), NOW()),
(3, 9, 'A', 4, '2026/2027', NOW(), NOW());

ALTER TABLE kelas AUTO_INCREMENT = 4;

-- ---------------------------------------------------------------------
-- classes (Mata Pelajaran)
-- ---------------------------------------------------------------------
INSERT INTO classes (id, code, title, description, subject_category, teacher_id, status, academic_year, banner_gradient, created_at, updated_at) VALUES
(1, 'MTK-7A', 'Matematika - Kelas 7A', 'Bilangan bulat, pecahan, dan aritmetika sosial untuk kelas VII.', 'Matematika', 2, 'active', '2026/2027 Ganjil', 'from-blue-600 to-indigo-700', NOW(), NOW()),
(2, 'IPA-7A', 'Ilmu Pengetahuan Alam - Kelas 7A', 'Klasifikasi makhluk hidup dan pengukuran dalam IPA.', 'IPA', 3, 'active', '2026/2027 Ganjil', 'from-emerald-600 to-teal-700', NOW(), NOW()),
(3, 'IPA-8A', 'Ilmu Pengetahuan Alam - Kelas 8A', 'Sistem pencernaan, pernapasan, dan gerak pada manusia.', 'IPA', 3, 'active', '2026/2027 Ganjil', 'from-purple-600 to-pink-700', NOW(), NOW()),
(4, 'BIND-9A', 'Bahasa Indonesia - Kelas 9A', 'Teks deskripsi, teks eksposisi, dan kaidah kebahasaan.', 'Bahasa Indonesia', 4, 'active', '2026/2027 Ganjil', 'from-amber-600 to-orange-700', NOW(), NOW());

ALTER TABLE classes AUTO_INCREMENT = 5;

-- ---------------------------------------------------------------------
-- class_student (enrollment = seluruh siswa homeroom terkait;
-- IPA-7A sengaja dikosongkan untuk mendemonstrasikan tampilan "belum ada siswa")
-- ---------------------------------------------------------------------
INSERT INTO class_student (class_id, student_id, created_at, updated_at) VALUES
(1, 5, NOW(), NOW()),
(1, 6, NOW(), NOW()),
(3, 7, NOW(), NOW()),
(3, 8, NOW(), NOW()),
(4, 9, NOW(), NOW()),
(4, 10, NOW(), NOW());

-- ---------------------------------------------------------------------
-- meetings (pertemuan) — assignment_deadline dihitung relatif terhadap
-- waktu import (NOW()) supaya selalu terlihat wajar/segar
-- ---------------------------------------------------------------------
INSERT INTO meetings (id, class_id, meeting_number, title, description, youtube_url, has_assignment, assignment_title, assignment_instructions, assignment_deadline, created_at, updated_at) VALUES
(1, 1, 1, 'Pertemuan 1: Operasi Hitung Bilangan Bulat', 'Penjumlahan, pengurangan, perkalian, dan pembagian bilangan bulat beserta sifat-sifatnya.', 'https://www.youtube.com/watch?v=DCdGyoyeih8', 0, NULL, NULL, NULL, NOW(), NOW()),
(2, 1, 2, 'Pertemuan 2: Aritmetika Sosial', 'Konsep untung, rugi, diskon, bunga tunggal, dan penerapannya dalam jual beli.', 'https://www.youtube.com/watch?v=8UVfXaFwWsY', 1, 'Tugas: Latihan Soal Aritmetika Sosial', 'Kerjakan 10 soal cerita tentang untung, rugi, dan diskon pada modul halaman 24. Foto atau scan jawaban tulis tangan, lalu unggah dalam satu berkas PDF.', DATE_ADD(NOW(), INTERVAL 3 DAY), NOW(), NOW()),
(3, 3, 1, 'Pertemuan 1: Sistem Pencernaan Manusia', 'Organ-organ pencernaan dan proses pencernaan mekanik serta kimiawi.', 'https://www.youtube.com/watch?v=RFXPKzKm8Bw', 1, 'Tugas: Laporan Pengamatan Sistem Pencernaan', 'Buatlah diagram alur sistem pencernaan manusia beserta fungsi tiap organ, ditulis tangan pada kertas folio, lalu foto dan unggah.', DATE_ADD(NOW(), INTERVAL 5 DAY), NOW(), NOW()),
(4, 4, 1, 'Pertemuan 1: Menulis Teks Deskripsi', 'Struktur dan ciri kebahasaan teks deskripsi, dilengkapi contoh teks.', 'https://www.youtube.com/watch?v=k4Ac2j2X2Qw', 0, NULL, NULL, NULL, NOW(), NOW());

ALTER TABLE meetings AUTO_INCREMENT = 5;

-- ---------------------------------------------------------------------
-- materials (berkas contoh — lihat README/paket zip untuk berkas
-- placeholder fisiknya di folder storage-seed-files/)
-- ---------------------------------------------------------------------
INSERT INTO materials (id, meeting_id, file_name, file_path, file_size, file_type, created_at, updated_at) VALUES
(1, 1, 'Modul_01_Bilangan_Bulat.pdf', 'materials/1/Modul_01_Bilangan_Bulat.pdf', '1.0 MB', 'application/pdf', NOW(), NOW()),
(2, 2, 'Modul_02_Aritmetika_Sosial.pdf', 'materials/2/Modul_02_Aritmetika_Sosial.pdf', '1.0 MB', 'application/pdf', NOW(), NOW()),
(3, 3, 'Modul_IPA_Sistem_Pencernaan.pdf', 'materials/3/Modul_IPA_Sistem_Pencernaan.pdf', '1.0 MB', 'application/pdf', NOW(), NOW()),
(4, 4, 'Contoh_Teks_Deskripsi.pdf', 'materials/4/Contoh_Teks_Deskripsi.pdf', '1.0 MB', 'application/pdf', NOW(), NOW());

ALTER TABLE materials AUTO_INCREMENT = 5;

-- ---------------------------------------------------------------------
-- submissions (pengumpulan tugas untuk Pertemuan 2 - Matematika 7A)
-- ---------------------------------------------------------------------
INSERT INTO submissions (id, meeting_id, student_id, file_name, file_path, file_size, student_note, submitted_at, is_late, grade, feedback, graded_at, graded_by, created_at, updated_at) VALUES
(1, 2, 5, 'Ahmad_Rizky_Tugas_Aritmetika.pdf', 'submissions/2/5_Ahmad_Rizky_Tugas_Aritmetika.pdf', '850 KB', 'Bu, mohon dikoreksi soal nomor 7, saya kurang yakin dengan jawaban saya.', DATE_SUB(NOW(), INTERVAL 2 DAY), 0, 88, 'Bagus! Nomor 7 sudah benar, hanya perlu ditulis satuannya (Rp) secara konsisten.', DATE_SUB(NOW(), INTERVAL 1 DAY), 2, NOW(), NOW()),
(2, 2, 6, 'Dewi_Lestari_Tugas_Aritmetika.pdf', 'submissions/2/6_Dewi_Lestari_Tugas_Aritmetika.pdf', '780 KB', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR), 1, 75, 'Jawaban sudah tepat, tapi lain kali tolong dikumpulkan sebelum batas waktu ya.', DATE_SUB(NOW(), INTERVAL 2 HOUR), 2, NOW(), NOW());

ALTER TABLE submissions AUTO_INCREMENT = 3;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Selesai. Login dengan salah satu email di atas + kata sandi: password
-- =====================================================================
