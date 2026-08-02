<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the default Laravel `users` table with the fields the LMS needs:
     * a role (admin/teacher/student) and the NIP (staff number) or NISN
     * (student number) identifier used throughout the original prototype.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');
            $table->string('nip_nis')->nullable()->after('role');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'nip_nis']);
        });
    }
};
