<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ClassRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeachingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            'Pend. Agama' => [
                'Yuyun, S.Pd' => ['VIII A', 'VIII B', 'VIII C', 'IX A', 'IX B', 'IX C'],
                'Resti, S.Pd' => ['VII A', 'VII B'],
            ],
            'PKn' => [
                'Rayana La Yni, S.Pd' => ['VII A', 'VII B'],
                'Sinta Bayau, S.Pd' => ['VIII A', 'VIII B', 'VIII C'],
                'Ishak Barunggu, S.Pd' => ['IX A', 'IX B', 'IX C'],
            ],
            'Bhs. Indo' => [
                'Wa Ruwiati, S.Pd' => ['IX A', 'IX B'],
                'Sariani, S.Pd' => ['VII A', 'VII B'],
                'Rina Yasri, S.Pd' => ['VIII A', 'VIII B'],
                'Sukaesi, S.Pd' => ['VIII C', 'IX C'],
            ],
            'Bhs. Inggris' => [
                'Siskawati Ilyas, S.Pd' => ['IX A', 'IX B', 'IX C'],
                'Rusnawati Koboo, S.Pd' => ['VIII A', 'VIII B', 'VIII C'],
                'Nursani Rasyid, S.Pd' => ['VII A', 'VII B'],
            ],
            'Matematika' => [
                'Marly, S.Pd' => ['IX A', 'IX B', 'IX C'],
                'Luxfiyanti Hidusu, S.Pd' => ['VIII A', 'VIII B', 'VIII C'],
                'Nilam D. Jamna, S.Pd' => ['VII A', 'VII B'],
            ],
            'IPA' => [
                'Rusmin Gailea, S.Pd' => ['IX A', 'IX B', 'IX C'],
                'Wa Hariani, S.Pd' => ['VIII A', 'VIII B', 'VIII C'],
                'Nurmaani, S.Pd' => ['VII A', 'VII B'],
            ],
            'IPS' => [
                'Wa Ode Hasmita, S.Pd' => ['IX A', 'IX B', 'IX C'],
                'Marishack, S.Pd' => ['VII B', 'VIII A', 'VIII B', 'VIII C'],
                'Resti, S.Pd' => ['VII A'],
            ],
            'PJOK' => [
                'Supitno, S.Pd' => ['VII A', 'VII B', 'IX A', 'IX B', 'IX C'],
                'Muh. Sarvin Saparua, S.Pd' => ['VIII A', 'VIII B', 'VIII C'],
            ],
            'Prakarya' => [
                'Adilas, S.Pd' => ['IX A', 'IX B', 'IX C'],
                'Hamsa Abusaru, S.Pi' => ['VIII A', 'VIII B', 'VIII C'],
                'Nani Mahmud, S.P' => ['VII A', 'VII B'],
            ],
            'Informatika' => [
                'Venty Noesa, S.Pd' => ['VII A', 'VII B', 'IX A', 'IX B', 'IX C'],
                'Nurnia, S.P' => ['VIII A', 'VIII B', 'VIII C'],
            ],
        ];

        $gradients = [
            'from-blue-600 to-indigo-700',
            'from-emerald-600 to-teal-700',
            'from-purple-600 to-pink-700',
            'from-amber-600 to-orange-700',
        ];

        foreach ($schedules as $subject => $teachers) {
            foreach ($teachers as $teacherName => $classes) {
                
                // 1. Buat atau temukan akun Guru
                // Username dibersihkan dari gelar untuk email (misal: yuyun@sekolah.com)
                $cleanName = Str::slug(explode(',', $teacherName)[0]); 
                
                $teacher = User::firstOrCreate(
                    ['name' => $teacherName],
                    [
                        'email' => $cleanName . '@sekolah.com',
                        'password' => bcrypt('password'),
                        'role' => User::ROLE_TEACHER,
                        'nip_nis' => '19' . rand(100000000000, 999999999999) // Dummy NIP
                    ]
                );

                // 2. Buat Mata Pelajaran (ClassRoom) untuk setiap kelas
                foreach ($classes as $className) {
                    $classCode = strtoupper(Str::slug($subject . '-' . $className));
                    
                    ClassRoom::firstOrCreate(
                        ['code' => $classCode],
                        [
                            'title' => $subject . ' - ' . $className,
                            'description' => "Mata pelajaran $subject untuk kelas $className yang diampu oleh $teacherName.",
                            'subject_category' => $subject,
                            'teacher_id' => $teacher->id,
                            'status' => 'active',
                            'academic_year' => '2026/2027 Ganjil',
                            'banner_gradient' => $gradients[array_rand($gradients)],
                        ]
                    );
                }
            }
        }
    }
}