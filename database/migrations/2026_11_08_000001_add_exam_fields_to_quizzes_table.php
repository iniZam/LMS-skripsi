<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('id')->constrained('classes')->cascadeOnDelete();
            $table->string('type')->default('quiz')->after('meeting_id'); // quiz, uts, uas
            $table->string('approval_status')->default('approved')->after('type'); // approved, pending, rejected
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('meeting_id')->nullable()->change();
        });

        // Backfill class_id from meeting_id for existing quizzes
        $quizzes = DB::table('quizzes')->whereNotNull('meeting_id')->get();
        foreach ($quizzes as $quiz) {
            $meeting = DB::table('meetings')->where('id', $quiz->meeting_id)->first();
            if ($meeting) {
                DB::table('quizzes')->where('id', $quiz->id)->update(['class_id' => $meeting->class_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['class_id', 'type', 'approval_status', 'approved_by', 'approved_at']);
        });
    }
};
