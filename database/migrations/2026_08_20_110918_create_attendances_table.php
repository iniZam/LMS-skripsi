<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
        $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
        $table->string('status')->default('hadir'); // hadir, izin, sakit, alpa
        $table->timestamps();
        $table->unique(['meeting_id', 'student_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
