<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function download(Request $request, Material $material): StreamedResponse
    {
        $user = $request->user();
        $class = $material->meeting->classRoom;

        if ($user->isStudent()) {
            abort_unless($class->students()->where('student_id', $user->id)->exists(), 403);
        } elseif ($user->isTeacher()) {
            abort_unless($class->teacher_id === $user->id, 403);
        }

        abort_unless(Storage::disk('public')->exists($material->file_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $user = $request->user();
        $meeting = $material->meeting;
        $class = $meeting->classRoom;
        abort_unless($user->isAdmin() || ($user->isTeacher() && $class->teacher_id === $user->id), 403);

        Storage::disk('public')->delete($material->file_path);
        $material->delete();

        return back()->with('status', 'Berkas materi berhasil dihapus.');
    }
}
