<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\LogAktivitas;
use App\Services\PenyusunSurat;
use App\Support\MasterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    private function def(string $entitas): array
    {
        return MasterData::semua()[$entitas] ?? abort(404);
    }

    public function daftar(Request $request, string $entitas)
    {
        $d = $this->def($entitas);
        $q = trim((string) $request->query('q', ''));
        $kelompok = $entitas === 'pengguna' && $request->query('kelompok') === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        $daftar = $d['model']::with($d['with'])
            ->when($entitas === 'pengguna', fn ($w) => $kelompok === 'mahasiswa' ? $w->mahasiswa() : $w->bukanMahasiswa())
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => collect($d['cari'])->each(fn ($c) => $x->orWhere($c, 'like', "%$q%"))))
            ->orderBy($entitas === 'jenis-surat' ? 'urutan' : ($d['cari'][0]))
            ->paginate(15)->withQueryString();

        return view('master.daftar', ['entitas' => $entitas, 'd' => $d, 'daftar' => $daftar, 'q' => $q, 'kelompok' => $kelompok, 'semua' => MasterData::semua()]);
    }

    public function buat(string $entitas)
    {
        return view('master.form', ['entitas' => $entitas, 'd' => $this->def($entitas), 'm' => null, 'semua' => MasterData::semua(), 'peranBisa' => $this->peranBisa(), 'nilai' => $this->nilaiAwal($this->def($entitas), null)]);
    }

    public function ubah(string $entitas, int $id)
    {
        $d = $this->def($entitas);
        $m = $d['model']::findOrFail($id);
        $this->izinkanUbah($entitas, $m);

        return view('master.form', ['entitas' => $entitas, 'd' => $d, 'm' => $m, 'semua' => MasterData::semua(), 'peranBisa' => $this->peranBisa(), 'nilai' => $this->nilaiAwal($d, $m)]);
    }

    public function simpan(Request $request, string $entitas, ?int $id = null)
    {
        $d = $this->def($entitas);
        $m = $id ? $d['model']::findOrFail($id) : null;
        if ($m) {
            $this->izinkanUbah($entitas, $m);
        }

        $aturan = [];
        foreach ($d['field'] as $f) {
            $r = array_map(fn ($x) => is_string($x) ? str_replace('{id}', (string) ($id ?? 'NULL'), $x) : $x, $f[3]);
            $r = array_values(array_filter($r, fn ($x) => $x !== '{wajib-buat}'));
            if ($f[0] === 'password' && ! $id) {
                array_unshift($r, 'required');
                $r = array_values(array_diff($r, ['nullable']));
            }
            $aturan[$f[0]] = $r;
        }
        $data = $request->validate($aturan);

        $data = $this->olah($entitas, $d, $data, $request);
        $peran = $data['__peran'] ?? null;
        unset($data['__peran']);

        foreach ($d['field'] as $f) {
            if ($f[2] === 'ya_tidak') {
                $data[$f[0]] = $request->boolean($f[0]);
            }
        }

        if ($entitas === 'pengguna' && blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $m = $m ?? new $d['model'];
        $m->fill(array_map(fn ($v) => $v === '' ? null : $v, $data))->save();

        if ($entitas === 'pengguna' && $peran !== null) {
            $m->syncRoles($peran);
        }
        LogAktivitas::catat($id ? 'master_ubah' : 'master_buat', ($id ? 'Mengubah ' : 'Menambah ').$d['judul'].': '.($m->nama ?? $m->kode ?? $m->id), $m);

        $arah = $entitas === 'pengguna' ? ['entitas' => $entitas, 'kelompok' => $m->adalahMahasiswa() ? 'mahasiswa' : 'dosen'] : $entitas;

        return redirect()->route('master.daftar', $arah)->with('sukses', $d['judul'].' berhasil disimpan.');
    }

    public function aktif(string $entitas, int $id)
    {
        $d = $this->def($entitas);
        $m = $d['model']::findOrFail($id);
        $this->izinkanUbah($entitas, $m);
        abort_if($entitas === 'pengguna' && $m->id === auth()->id(), 422, 'Anda tidak dapat menonaktifkan akun sendiri.');
        $m->update(['aktif' => ! $m->aktif]);
        LogAktivitas::catat('master_aktif', ($m->aktif ? 'Mengaktifkan ' : 'Menonaktifkan ').$d['judul'].': '.($m->nama ?? $m->kode), $m);

        return back()->with('sukses', 'Status diperbarui.');
    }

    // ---- khusus -----------------------------------------------------------------------------------------

    private function peranBisa(): array
    {
        return array_values(array_filter(Peran::cases(), fn ($p) => $p !== Peran::SuperAdmin || auth()->user()->hasRole(Peran::SuperAdmin->value)));
    }

    /** Admin TU tidak boleh mengubah akun Super Admin. */
    private function izinkanUbah(string $entitas, $m): void
    {
        if ($entitas === 'pengguna' && $m->hasRole(Peran::SuperAdmin->value) && ! auth()->user()->hasRole(Peran::SuperAdmin->value)) {
            abort(403, 'Hanya Super Admin yang dapat mengubah akun Super Admin.');
        }
    }

    private function nilaiAwal(array $d, $m): array
    {
        $n = [];
        foreach ($d['field'] as $f) {
            $v = $m?->{$f[0]};
            if ($f[0] === 'peran') {
                $v = $m ? $m->getRoleNames()->all() : [];
            } elseif (in_array($f[2], ['json'], true)) {
                $v = $m ? json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]';
            } elseif ($f[2] === 'tanggal' && $v) {
                $v = $v->format('Y-m-d');
            } elseif ($f[2] === 'ya_tidak') {
                $v = $m ? (bool) $v : true;
            } elseif ($f[2] === 'sandi') {
                $v = '';
            }
            $n[$f[0]] = $v;
        }

        return $n;
    }

    private function olah(string $entitas, array $d, array $data, Request $request): array
    {
        if ($entitas === 'pengguna') {
            $diminta = array_values(array_intersect($data['peran'], array_map(fn ($p) => $p->value, $this->peranBisa())));
            if (! $diminta) {
                throw ValidationException::withMessages(['peran' => 'Pilih minimal satu peran yang diizinkan.']);
            }
            // Super Admin tidak boleh kehilangan peran miliknya melalui admin TU (sudah diblokir di izinkanUbah)
            $data['__peran'] = $diminta;
            unset($data['peran']);
        }

        if ($entitas === 'jenis-surat') {
            $data['field_formulir'] = $this->jsonTerstruktur($request->input('field_formulir'), 'field_formulir', fn ($x) => is_array($x)
                && preg_match('/^[a-z][a-z0-9_]*$/', (string) ($x['nama'] ?? ''))
                && filled($x['label'] ?? null)
                && in_array($x['tipe'] ?? '', ['teks', 'area', 'tanggal', 'angka', 'pilihan'], true)
                && (($x['tipe'] ?? '') !== 'pilihan' || (is_array($x['opsi'] ?? null) && $x['opsi'])),
                'Setiap bidang wajib punya "nama" (huruf kecil/angka/_), "label", dan "tipe" yang valid; tipe pilihan butuh "opsi".');
            if (count($data['field_formulir']) === 0) {
                throw ValidationException::withMessages(['field_formulir' => 'Minimal satu bidang formulir.']);
            }
            $data['syarat'] = $this->jsonTerstruktur($request->input('syarat') ?: '[]', 'syarat', fn ($x) => is_array($x) && filled($x['label'] ?? null), 'Setiap syarat wajib punya "label".');
            $data['template_html'] = app(PenyusunSurat::class)->bersihkanHtml($data['template_html']);
            $data['perlu_paraf'] = $request->boolean('perlu_paraf');
            if (! $data['perlu_paraf']) {
                $data['paraf_role'] = null;
            }
        }

        return $data;
    }

    private function jsonTerstruktur(?string $teks, string $kolom, callable $sah, string $pesan): array
    {
        $arr = json_decode((string) $teks, true);
        if (! is_array($arr) || ! array_is_list($arr) || collect($arr)->contains(fn ($x) => ! $sah($x))) {
            throw ValidationException::withMessages([$kolom => json_last_error() !== JSON_ERROR_NONE ? 'JSON tidak valid: '.json_last_error_msg() : $pesan]);
        }

        return $arr;
    }
}
