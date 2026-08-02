<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->text('student_note')->nullable();
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One submission record per student per meeting (resubmission overwrites it).
            $table->unique(['meeting_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
