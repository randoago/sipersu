<?php

namespace App\Livewire\Auth;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
class Login extends Component
{
    #[Validate('required|string', message: 'NPM/NIDN atau username wajib diisi.')]
    public string $nomor_induk = '';

    #[Validate('required|string', message: 'Kata sandi wajib diisi.')]
    public string $password = '';

    public bool $ingat = false;

    public function masuk()
    {
        $this->validate();

        $kunci = Str::lower($this->nomor_induk).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);
            throw ValidationException::withMessages([
                'nomor_induk' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        // Masuk dengan NPM/NIDN atau username (mis. "TU", "superadmin"; huruf besar/kecil tidak dibedakan).
        $identitas = trim($this->nomor_induk);
        $akun = User::where('nomor_induk', $identitas)->orWhereRaw('lower(username) = ?', [Str::lower($identitas)])->first();

        $cocok = Auth::attempt(
            ['nomor_induk' => $akun?->nomor_induk ?? $identitas, 'password' => $this->password, 'aktif' => true],
            $this->ingat,
        );

        if (! $cocok) {
            RateLimiter::hit($kunci, 60);
            LogAktivitas::catat('login_gagal', 'Percobaan masuk gagal', null, ['nomor_induk' => $this->nomor_induk], null);
            $this->reset('password');
            throw ValidationException::withMessages(['nomor_induk' => 'NPM/NIDN/username atau kata sandi salah, atau akun tidak aktif.']);
        }

        RateLimiter::clear($kunci);
        session()->regenerate();
        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['login_terakhir' => now()])->saveQuietly();
        LogAktivitas::catat('login', 'Berhasil masuk', $user);

        return $this->redirectIntended(route('dasbor'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login', ['tahunAkademik' => \App\Support\TahunAkademik::saatIni()]);
    }
}
