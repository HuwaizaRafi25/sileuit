<?php

namespace App\Modules\IdentityAccess\Http\Controllers;

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController
{
    public function showStaff(): View
    {
        return view('auth.login-staff', [
            'demoRoles' => config('app.env') === 'local'
                ? array_map(fn (array $account) => $account['label'], $this->demoAccounts())
                : [],
        ]);
    }

    public function loginDemo(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('app.env') === 'local', 404);

        $account = $this->demoAccounts()[$role] ?? null;
        abort_unless($account !== null, 404);

        $user = User::query()->where($account['column'], $account['identifier'])->first();
        abort_unless($user && $user->status === 'AKTIF' && $user->hasRole($role), 404);

        Auth::login($user);
        $request->session()->regenerate();
        $this->setDefaultLocation($user);

        if ($user->hasRole('PETANI')) {
            return redirect('/portal');
        }

        return redirect($user->isGlobal() ? '/admin' : '/app/beranda');
    }

    public function loginStaff(Request $request): RedirectResponse
    {
        $key = 'login-staff:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $menit = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withErrors(['email' => "Akun dikunci {$menit} menit. Hubungi Admin jika perlu bantuan."])->onlyInput('email');
        }

        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $data['email'])->first();

        if (! $user || $user->status !== 'AKTIF' || $user->hasRole('PETANI') || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 900);
            $sisa = max(0, 5 - RateLimiter::attempts($key));

            return back()->withErrors(['email' => "Email atau kata sandi salah. Sisa percobaan: {$sisa}."])->onlyInput('email');
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        $this->setDefaultLocation($user);

        return redirect($user->isGlobal() ? '/admin' : '/app/beranda');
    }

    public function showPetani(): View
    {
        return view('auth.login-petani');
    }

    public function loginPetani(Request $request): RedirectResponse
    {
        $key = 'login-petani:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $menit = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withErrors(['hp' => "Akun dikunci {$menit} menit. Hubungi Admin/Pengadaan jika perlu bantuan."]);
        }

        $data = $request->validate(['hp' => 'required', 'pin' => 'required|digits:6']);
        $hp = $this->normalisasiHp($data['hp']);
        $user = User::where('hp_hash', hash('sha256', $hp))->first();

        if (! $user || $user->status !== 'AKTIF' || ! $user->hasRole('PETANI') || ! $user->pin_hash || ! Hash::check($data['pin'], $user->pin_hash)) {
            RateLimiter::hit($key, 900);
            $sisa = max(0, 5 - RateLimiter::attempts($key));

            return back()->withErrors(['hp' => "Nomor HP atau PIN salah. Sisa percobaan: {$sisa}."]);
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/portal');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function setDefaultLocation(User $user): void
    {
        if (! $user->active_location_id && $user->locationScopes->isNotEmpty()) {
            $user->update(['active_location_id' => $user->locationScopes->first()->id]);
        }
    }

    private function normalisasiHp(string $hp): string
    {
        $digits = preg_replace('/\D/', '', $hp);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return '+'.$digits;
    }

    /**
     * @return array<string, array{label: string, column: string, identifier: string}>
     */
    private function demoAccounts(): array
    {
        return [
            'PEMILIK' => ['label' => 'Pemilik', 'column' => 'email', 'identifier' => 'pemilik-1@sileuit.id'],
            'ADMIN' => ['label' => 'Admin Operasional', 'column' => 'email', 'identifier' => 'adm-1@sileuit.id'],
            'PENGADAAN' => ['label' => 'Staf Pengadaan', 'column' => 'email', 'identifier' => 'pengadaan-1@sileuit.id'],
            'PETUGAS_GUDANG' => ['label' => 'Petugas Gudang (G1)', 'column' => 'email', 'identifier' => 'pg-g1@sileuit.id'],
            'KEPALA_GUDANG' => ['label' => 'Kepala Gudang (G1)', 'column' => 'email', 'identifier' => 'kg-g1@sileuit.id'],
            'KURIR' => ['label' => 'Kurir', 'column' => 'email', 'identifier' => 'kur-1@sileuit.id'],
            'KASIR' => ['label' => 'Kasir (Cabang 1)', 'column' => 'email', 'identifier' => 'ksr-c1@sileuit.id'],
            'KEUANGAN' => ['label' => 'Keuangan', 'column' => 'email', 'identifier' => 'keu-1@sileuit.id'],
            'PETANI' => ['label' => 'Petani Mitra', 'column' => 'hp_hash', 'identifier' => hash('sha256', '+628111111001')],
            'MITRA_B2B' => ['label' => 'Mitra Retail B2B', 'column' => 'email', 'identifier' => 'retail-contoh-satu@mitra.sileuit.id'],
        ];
    }
}
