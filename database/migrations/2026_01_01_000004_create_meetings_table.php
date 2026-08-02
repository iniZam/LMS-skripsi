<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedInteger('meeting_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_url')->nullable();
            $table->boolean('has_assignment')->default(false);
            $table->string('assignment_title')->nullable();
            $table->text('assignment_instructions')->nullable();
            $table->dateTime('assignment_deadline')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'meeting_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
