<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin-only management of Teacher (Pengajar) and Student (Siswa) accounts.
 * Both roles are provisioned exclusively by an admin — there is no
 * self-registration in this system.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $role = $request->query('role');
        $kelasId = $request->query('kelas_id');

        $query = User::query()->where('role', '!=', User::ROLE_ADMIN)->with('kelas');
        if ($role) {
            $query->where('role', $role);
        }
        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama')->get();

        return view('users.index', compact('users', 'role', 'kelasId', 'kelasList'));
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip_nis' => ['nullable', 'string', 'max:50'],
        ]);

        $tempPassword = "password";

        $teacher = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $tempPassword,
            'role' => User::ROLE_TEACHER,
            'nip_nis' => $data['nip_nis'] ?: 'NIP.'.random_int(100000000, 999999999),
        ]);

        return back()->with('status', "Akun pengajar {$teacher->name} berhasil dibuat. Kata sandi sementara: {$tempPassword} — mohon segera sampaikan ke pengajar yang bersangkutan untuk diganti setelah masuk pertama kali.");
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip_nis' => ['nullable', 'string', 'max:50'],
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);

        $tempPassword = "password";

        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $tempPassword,
            'role' => User::ROLE_STUDENT,
            'nip_nis' => $data['nip_nis'] ?: 'NISN.'.random_int(1000000000, 9999999999),
            'kelas_id' => $data['kelas_id'],
        ]);

        $kelasLabel = $student->kelas->label();

        return back()->with('status', "Akun siswa {$student->name} berhasil dibuat di {$kelasLabel}. Kata sandi sementara: {$tempPassword} — mohon segera sampaikan ke siswa yang bersangkutan untuk diganti setelah masuk pertama kali.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if($user->id === $request->user()->id, 403, 'Tidak dapat menghapus akun sendiri.');
        abort_if($user->isAdmin(), 403, 'Tidak dapat menghapus akun admin lain melalui halaman ini.');

        $user->delete();

        return back()->with('status', 'Pengguna berhasil dihapus.');
    }
}
