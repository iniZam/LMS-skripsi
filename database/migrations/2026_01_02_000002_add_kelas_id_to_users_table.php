<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assigns each student to a homeroom (kelas). Nullable because admin/teacher
     * accounts don't belong to a homeroom, and a newly created student could
     * briefly be unassigned before the admin picks a class.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('nip_nis')->constrained('kelas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};
