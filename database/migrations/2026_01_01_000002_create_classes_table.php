<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('subject_category')->nullable();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('registration'); // registration | active | completed
            $table->string('academic_year')->nullable();
            $table->string('banner_gradient')->default('from-blue-600 to-indigo-700');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
