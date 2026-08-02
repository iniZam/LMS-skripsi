<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only management of homeroom classes (Kelas / rombongan belajar),
 * e.g. "7A", "8B", "9C", each with an assigned wali kelas (homeroom teacher).
 */
class KelasController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $kelasList = Kelas::with('waliKelas')->withCount('siswa')
            ->orderBy('tingkat')->orderBy('nama')->get();

        $teachers = User::where('role', User::ROLE_TEACHER)->orderBy('name')->get();

        return view('kelas.index', compact('kelasList', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'tingkat' => ['required', 'integer', 'in:7,8,9'],
            'nama' => ['required', 'string', 'max:10'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
        ]);

        $kelas = Kelas::create($data);

        return back()->with('status', "{$kelas->label()} berhasil dibuat.");
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'tingkat' => ['required', 'integer', 'in:7,8,9'],
            'nama' => ['required', 'string', 'max:10'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
        ]);

        $kelas->update($data);

        return back()->with('status', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, Kelas $kelas): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($kelas->siswa()->exists()) {
            return back()->withErrors(['kelas' => "Tidak dapat menghapus {$kelas->label()} karena masih memiliki siswa. Pindahkan siswa ke kelas lain terlebih dahulu."]);
        }

        $kelas->delete();

        return back()->with('status', 'Kelas berhasil dihapus.');
    }
}
