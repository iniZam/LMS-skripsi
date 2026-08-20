<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// ---------------------------------------------------------------------
// Guest-only routes.
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ---------------------------------------------------------------------
// Authenticated routes
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // === Rute AI & Absensi (Pindahan) ===
    Route::post('/meetings/{meeting}/ask-ai', [App\Http\Controllers\MeetingController::class, 'askAiTutor'])->name('meetings.ask-ai');
    
    // Pastikan ini ditambahkan middleware role:admin,teacher agar guru bisa akses
    Route::post('/classes/{class}/ask-ai-designer', [App\Http\Controllers\MeetingController::class, 'askAiDesigner'])
        ->middleware('role:admin,teacher')->name('classes.ask-ai-designer');
        
    Route::post('/meetings/{meeting}/attendance', [App\Http\Controllers\MeetingController::class, 'saveAttendance'])
        ->middleware('role:admin,teacher')->name('meetings.attendance');
        
    Route::get('/meetings/{meeting}/attendance/export', [App\Http\Controllers\MeetingController::class, 'exportAttendance'])
        ->middleware('role:admin,teacher')->name('meetings.attendance.export');
    // ===================================

    // Classes (Mata Pelajaran)
    Route::get('/classes', [ClassRoomController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassRoomController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassRoomController::class, 'store'])->name('classes.store');
    Route::get('/classes/{class}', [ClassRoomController::class, 'show'])->name('classes.show');
    Route::get('/classes/{class}/edit', [ClassRoomController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{class}', [ClassRoomController::class, 'update'])->name('classes.update');
    Route::post('/classes/{class}/students', [ClassRoomController::class, 'addStudent'])
        ->middleware('role:admin')->name('classes.addStudent');
    Route::delete('/classes/{class}/students/{student}', [ClassRoomController::class, 'removeStudent'])
        ->middleware('role:admin')->name('classes.removeStudent');

    // Meetings
    Route::get('/classes/{class}/meetings/create', [MeetingController::class, 'create'])
        ->middleware('role:admin,teacher')->name('meetings.create');
    Route::post('/classes/{class}/meetings', [MeetingController::class, 'store'])
        ->middleware('role:admin,teacher')->name('meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])
        ->middleware('role:admin,teacher')->name('meetings.edit');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])
        ->middleware('role:admin,teacher')->name('meetings.update');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])
        ->middleware('role:admin,teacher')->name('meetings.destroy');

    // Materials
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])
        ->middleware('role:admin,teacher')->name('materials.destroy');

    // Submissions
    Route::post('/meetings/{meeting}/submissions', [SubmissionController::class, 'store'])
        ->middleware('role:student')->name('submissions.store');
    Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])
        ->middleware('role:admin,teacher')->name('submissions.grade');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // User management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/teacher', [UserController::class, 'storeTeacher'])->name('users.storeTeacher');
        Route::post('/users/student', [UserController::class, 'storeStudent'])->name('users.storeStudent');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Homeroom (Kelas) management
        Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
        Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');
    });
});