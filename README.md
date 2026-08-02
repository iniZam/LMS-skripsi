# EduEduka LMS — Laravel + MySQL (Edisi SMP)

LMS untuk jenjang SMP (Sekolah Menengah Pertama) dibangun di atas **Laravel 13
+ MySQL**: tiga peran (Admin, Pengajar, Siswa), kelas/rombel dengan wali
kelas, mata pelajaran, pertemuan dengan video & materi, tugas, pengumpulan,
dan penilaian.

## Perubahan pada revisi ini (adaptasi SMP)

1. **Atribut kelas (rombel) pada siswa.** Ditambahkan entitas baru `Kelas`
   (homeroom, mis. "Kelas 7A") yang terpisah dari `ClassRoom` (mata
   pelajaran) yang sudah ada sebelumnya — keduanya sama-sama disebut "kelas"
   dalam bahasa sehari-hari, jadi label UI untuk `ClassRoom` diganti menjadi
   **"Mata Pelajaran"** supaya tidak rancu. Setiap siswa memiliki kolom
   `kelas_id` yang menunjuk ke rombelnya (7A/8A/9A/dst.), dan setiap Kelas
   punya wali kelas (guru).
2. **Siswa hanya bisa dibuat oleh admin.** Rute dan halaman pendaftaran
   mandiri (`/register`) sudah dihapus sepenuhnya. Admin membuat akun siswa
   (sekaligus memilih kelasnya) dan akun pengajar dari halaman **Manajemen
   Pengguna** — persis seperti alur pembuatan akun pengajar sebelumnya.
3. **Halaman baru: Kelas & Wali Kelas** (admin) — untuk membuat/mengedit/
   menghapus rombel dan menugaskan wali kelasnya.
4. **File SQL data dummy** (`database/edueduka_lms_dummy.sql`) — lihat bagian
   di bawah.

## Cara pakai

**Kebutuhan:** PHP 8.3+, Composer, MySQL 8+ (atau MariaDB), Node.js.

1. **Buat project Laravel 13 baru** (pilih "None" saat ditanya starter kit):
   ```bash
   composer create-project laravel/laravel:^13.0 edueduka-lms
   cd edueduka-lms
   ```

2. **Salin isi paket ini** ke dalamnya (timpa berkas yang sama):
   ```bash
   # dari dalam edueduka-lms/, dengan paket ini di-unzip ke ../laravel-lms-app
   cp -r ../laravel-lms-app/app/Models/*.php app/Models/
   cp -r ../laravel-lms-app/app/Http/Controllers app/Http/
   cp -r ../laravel-lms-app/app/Http/Middleware/EnsureUserHasRole.php app/Http/Middleware/
   cp ../laravel-lms-app/bootstrap/app.php bootstrap/app.php
   cp -r ../laravel-lms-app/database/migrations/*.php database/migrations/
   cp -r ../laravel-lms-app/database/seeders/*.php database/seeders/
   cp -r ../laravel-lms-app/resources/views resources/
   cp -r ../laravel-lms-app/resources/css resources/
   cp -r ../laravel-lms-app/resources/js/app.js resources/js/app.js
   cp ../laravel-lms-app/routes/web.php routes/web.php
   cp ../laravel-lms-app/vite.config.js vite.config.js
   cp ../laravel-lms-app/package.json package.json
   ```
   `app/Models/User.php` di paket ini *menggantikan* berkas bawaan Laravel
   (isinya sama, ditambah helper peran & relasi LMS), jadi timpa langsung saja.

3. **Konfigurasi database.** Salin blok DB dari `.env.example` paket ini ke
   `.env` proyek Anda, lalu buat databasenya:
   ```sql
   CREATE DATABASE lms_edueduka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Isi data.** Pilih salah satu cara berikut (hasilnya identik):

   **Cara A — lewat Artisan (disarankan jika Anda sudah punya Composer/PHP):**
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```

   **Cara B — import SQL langsung (cocok jika Anda ingin mengisi database
   lebih dulu tanpa menjalankan Artisan, mis. lewat phpMyAdmin/Adminer):**
   ```bash
   mysql -u root -p < database/edueduka_lms_dummy.sql
   ```
   File ini membuat sendiri ketujuh tabel aplikasi (bukan tabel internal
   Laravel seperti `sessions`/`cache`, yang tetap perlu `php artisan migrate`)
   beserta datanya — jalankan pada **database kosong**, karena skrip ini akan
   men-DROP tabel dengan nama yang sama jika sudah ada. Sudah diuji langsung
   (import bersih, tanpa foreign key yatim, aman diimpor ulang) sebelum
   dimasukkan ke paket ini.
   Setelah import, salin folder `storage-seed-files/` paket ini ke
   `storage/app/public/` di proyek Laravel Anda (berkas placeholder untuk
   materi & tugas contoh yang direferensikan oleh data SQL-nya), lalu:
   ```bash
   php artisan storage:link
   ```

5. **Build asset front-end:**
   ```bash
   npm install
   npm run build   # atau `npm run dev` saat pengembangan
   ```

6. **Jalankan:**
   ```bash
   php artisan serve
   ```
   Buka `http://localhost:8000`.

> Jika unggah berkas gagal untuk file besar, naikkan `upload_max_filesize`
> dan `post_max_size` di `php.ini` — aplikasi memvalidasi hingga 20 MB per
> berkas, sementara default PHP sering lebih kecil (2M/8M).

## Akun contoh (kata sandi semua akun: `password`)

| Peran     | Email                                              | Kelas / Wali Kelas |
|-----------|------------------------------------------------------|--------------------|
| Admin     | admin@smpcendekiabangsa.sch.id                        | —                  |
| Pengajar  | budi.santoso@smpcendekiabangsa.sch.id                 | Wali Kelas 7A      |
| Pengajar  | siti.rahma@smpcendekiabangsa.sch.id                   | Wali Kelas 8A      |
| Pengajar  | andi.wijaya@smpcendekiabangsa.sch.id                  | Wali Kelas 9A      |
| Siswa     | ahmad.rizky@siswa.smpcendekiabangsa.sch.id            | Kelas 7A           |
| Siswa     | dewi.lestari@siswa.smpcendekiabangsa.sch.id           | Kelas 7A           |
| Siswa     | fikri.nur@siswa.smpcendekiabangsa.sch.id              | Kelas 8A           |
| Siswa     | putri.ayu@siswa.smpcendekiabangsa.sch.id              | Kelas 8A           |
| Siswa     | rangga.saputra@siswa.smpcendekiabangsa.sch.id         | Kelas 9A           |
| Siswa     | nadia.salsabila@siswa.smpcendekiabangsa.sch.id        | Kelas 9A           |

4 mata pelajaran contoh: Matematika 7A, IPA 7A (sengaja dikosongkan tanpa
pertemuan, untuk menunjukkan tampilan "belum ada data"), IPA 8A, dan Bahasa
Indonesia 9A.

## Isi paket ini

```
app/Http/Controllers/    Auth, Dashboard, ClassRoom, Meeting, Material, Submission, User, Kelas
app/Http/Middleware/      EnsureUserHasRole ("role:admin,teacher" dst. di routes)
app/Models/               User, Kelas, ClassRoom, Meeting, Material, Submission
database/migrations/      users (role/nip_nis/kelas_id), kelas, classes, class_student, meetings, materials, submissions
database/seeders/         LmsSeeder — data sama persis dengan file SQL, lewat Artisan
database/edueduka_lms_dummy.sql   Skema + data lengkap, siap diimpor langsung ke MySQL
storage-seed-files/       Berkas placeholder untuk materi/tugas contoh (dipakai bersama file SQL)
resources/views/          Blade (Tailwind v4, dirender di server)
routes/web.php            Seluruh route table (tanpa /register)
bootstrap/app.php         Mendaftarkan middleware alias `role`
```

## Ringkasan struktur data

- **Kelas** (homeroom/rombel, mis. "Kelas 7A"): `tingkat` (7/8/9), `nama`
  (label rombel, mis. "A"), `wali_kelas_id` (guru), `tahun_ajaran`. Dikelola
  admin di halaman **Kelas & Wali Kelas**.
- **User** (siswa) kini punya `kelas_id` — wajib diisi saat admin membuat
  akun siswa, ditampilkan di sidebar, daftar pengguna, dan bisa difilter.
- **ClassRoom** (`classes` table, ditampilkan sebagai **"Mata Pelajaran"**)
  tidak berubah strukturnya — masih punya `teacher_id`, status, dan daftar
  siswa lewat pivot `class_student`. Ini yang menampung pertemuan, materi,
  dan tugas per mata pelajaran.

## Kemungkinan langkah lanjutan (belum diimplementasikan, agar tetap fokus)

- **Pendaftaran otomatis per rombel:** saat ini pengajar/admin masih memilih
  siswa satu per satu untuk sebuah Mata Pelajaran (tombol "Daftar Mata
  Pelajaran"). Untuk SMP, biasanya lebih pas jika sebuah Mata Pelajaran
  ditautkan ke satu Kelas dan seluruh siswa rombel itu otomatis
  terdaftar — bisa ditambahkan sebagai tombol "Daftarkan Seluruh Kelas Ini".
- **Dasbor wali kelas:** halaman ringkasan nilai seluruh siswa di kelas yang
  diwalikan seorang guru.
- Reset kata sandi lewat email (mailer masih `log`).
- Rapor/e-rapor (rekap nilai per siswa lintas mata pelajaran).
