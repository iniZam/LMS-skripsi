<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Kelas" here means the SMP homeroom / rombongan belajar (e.g. "7A", "8B", "9C") —
     * distinct from the `classes` table, which represents a Mata Pelajaran (subject)
     * taught to a homeroom by a guru. Every student belongs to exactly one homeroom.
     */
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('tingkat'); // 7, 8, or 9
            $table->string('nama', 10); // section label, e.g. "A", "B", "Unggulan"
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tahun_ajaran');
            $table->timestamps();

            $table->unique(['tingkat', 'nama', 'tahun_ajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
