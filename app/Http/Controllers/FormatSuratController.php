<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\PenyusunSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Format surat yang diatur TU: nama/kegunaan, daftar isian, templat, penandatangan, bentuk (QR/tanpa QR). */
class FormatSuratController extends Controller
{
    private const TIPE = ['teks' => 'Teks pendek', 'area' => 'Teks panjang', 'tanggal' => 'Tanggal', 'angka' => 'Angka', 'pilihan' => 'Pilihan', 'daftar' => 'Daftar (satu per baris)', 'tabel' => 'Tabel (baris berulang)'];

    public function index(Request $request)
    {
        $sasaran = in_array($request->query('sasaran'), ['mahasiswa', 'staf', 'masuk'], true) ? $request->query('sasaran') : null;
        $daftar = JenisSurat::with('klasifikasi', 'penandatanganJabatan')
            ->when($sasaran, fn ($q) => $q->where('sasaran', $sasaran))
            ->orderByRaw("case sasaran when 'staf' then 1 when 'masuk' then 2 else 3 end")->orderBy('urutan')->orderBy('nama')->get();

        return view('format-surat.index', ['daftar' => $daftar, 'sasaran' => $sasaran]);
    }

    public function buat()
    {
        return view('format-surat.form', $this->dataForm(null));
    }

    public function ubah(JenisSurat $format)
    {
        return view('format-surat.form', $this->dataForm($format));
    }

    public function simpan(Request $request, ?JenisSurat $format = null)
    {
        $d = $this->validasi($request, $format);
        $isi = ['aktif' => $request->boolean('aktif'), 'perlu_paraf' => $request->boolean('perlu_paraf')] + $d['kolom'];
        if (! $isi['perlu_paraf']) {
            $isi['paraf_role'] = null;
        }
        if ($isi['sasaran'] !== 'mahasiswa') {
            $isi['verifikator_role'] = 'admin_tu';
        }
        if ($isi['sasaran'] === 'masuk') {
            // Surat masuk tidak ditandatangani/dirender: hanya kolom isian tambahan + klasifikasi bawaan.
            $isi = array_merge($isi, ['template_html' => '', 'penandatangan_jabatan_id' => null, 'perlu_paraf' => false, 'paraf_role' => null, 'mode_ttd' => 'qr', 'sla_hari' => 1, 'judul_surat' => null, 'perihal_template' => null]);
        }
        $isi['field_formulir'] = $d['fields'];
        $isi['syarat'] = $isi['sasaran'] === 'mahasiswa' ? $d['syarat'] : [];
        $isi['template_html'] = app(PenyusunSurat::class)->bersihkanHtml((string) $isi['template_html']);

        $format ??= new JenisSurat;
        $baru = ! $format->exists;
        $format->fill($isi)->save();
        LogAktivitas::catat($baru ? 'format_buat' : 'format_ubah', ($baru ? 'Membuat' : 'Mengubah').' format surat: '.$format->nama, $format);

        return redirect()->route('format-surat.index')->with('sukses', "Format \"{$format->nama}\" disimpan.");
    }

    public function aktif(JenisSurat $format)
    {
        $format->update(['aktif' => ! $format->aktif]);
        LogAktivitas::catat('format_aktif', ($format->aktif ? 'Mengaktifkan' : 'Menonaktifkan').' format surat: '.$format->nama, $format);

        return back()->with('sukses', 'Status format diperbarui.');
    }

    public function salin(JenisSurat $format)
    {
        $baru = $format->replicate();
        $baru->kode = $this->kodeUnik($format->kode.'-SALINAN');
        $baru->nama = $format->nama.' (salinan)';
        $baru->aktif = false;
        $baru->save();
        LogAktivitas::catat('format_salin', "Menyalin format surat: {$format->nama}", $baru);

        return redirect()->route('format-surat.ubah', $baru)->with('sukses', 'Format disalin (nonaktif). Sesuaikan lalu aktifkan.');
    }

    /** Pratinjau tampilan surat lengkap dengan data contoh (tidak menyimpan apa pun). */
    public function pratinjau(Request $request, PenyusunSurat $penyusun)
    {
        $fields = $this->bangunFields($request->input('fields', []), false);
        $isian = [];
        foreach ($fields as $f) {
            $isian[$f['nama']] = match ($f['tipe']) {
                'tanggal' => now()->addDays(7)->toDateString(), 'angka' => '1', 'pilihan' => $f['opsi'][0] ?? '…',
                'tabel' => [array_map(fn ($k) => '['.$k.']', $f['kolom'] ?? [])], 'daftar' => "[{$f['label']} 1]\n[{$f['label']} 2]",
                default => '['.$f['label'].']',
            };
        }
        $isian = $penyusun->formatIsian($fields, $isian);
        $jabatan = Jabatan::with('pejabat')->find($request->input('penandatangan_jabatan_id'));
        $data = [
            'isian' => $isian, 'pembuat' => ['nama' => $request->user()->namaLengkap()],
            'pemohon' => ['nama' => 'MUHAMMAD FAUZAN', 'nim' => '21650012', 'npm' => '21650012', 'prodi' => 'Sistem dan Teknologi Informasi (S1)', 'ttl' => 'Baubau, 14 Mei 2002', 'alamat' => 'Jl. Pahlawan No. 42, Baubau', 'angkatan' => '2021'],
        ];
        $isi = $penyusun->badan($penyusun->bersihkanHtml((string) $request->input('template_html')), $data, $jabatan);
        $judul = trim((string) $request->input('judul_surat'));
        $mode = in_array($request->input('mode_ttd'), ['qr', 'basah'], true) ? $request->input('mode_ttd') : 'qr';

        $gayaTanggal = $request->input('gaya_tanggal') === 'hijriah' ? 'hijriah' : 'dikeluarkan';

        return response()->json(['html' => $penyusun->htmlPratinjau($isi, $judul !== '' ? $judul : null, $jabatan, $mode, '', $gayaTanggal)]);
    }

    // ---- util -------------------------------------------------------------------------------------------

    private function dataForm(?JenisSurat $f): array
    {
        return [
            'f' => $f,
            'tipe' => self::TIPE,
            'klasifikasi' => KlasifikasiSurat::where('aktif', true)->orderBy('kode')->get(),
            'jabatan' => Jabatan::with('pejabat')->where('aktif', true)->orderBy('nama')->get(),
            'fields' => old('fields', collect($f?->field_formulir ?? [])->map(fn ($x) => [
                'nama' => $x['nama'], 'label' => $x['label'], 'tipe' => $x['tipe'], 'wajib' => (bool) ($x['wajib'] ?? false),
                'placeholder' => $x['placeholder'] ?? '', 'opsi' => implode("\n", $x['opsi'] ?? []), 'kolom' => implode("\n", $x['kolom'] ?? []), 'lebar' => $x['lebar'] ?? 'penuh',
            ])->all()),
            'syarat' => old('syarat', collect($f?->syarat ?? [])->map(fn ($x) => ['label' => $x['label'], 'wajib' => (bool) ($x['wajib'] ?? false)])->all()),
        ];
    }

    private function validasi(Request $request, ?JenisSurat $format): array
    {
        $masuk = $request->input('sasaran') === 'masuk';
        $kolom = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'regex:/^[A-Z0-9\-]+$/', 'max:30', Rule::unique('jenis_surat', 'kode')->ignore($format?->id)],
            'deskripsi' => ['nullable', 'string', 'max:200'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'ikon' => ['required', 'regex:/^[a-z0-9_]+$/', 'max:40'],
            'sasaran' => ['required', Rule::in(['mahasiswa', 'staf', 'masuk'])],
            'judul_surat' => ['nullable', 'string', 'max:150'],
            'perihal_template' => ['nullable', 'string', 'max:200'],
            'klasifikasi_id' => [$masuk ? 'nullable' : 'required', 'exists:klasifikasi_surat,id'],
            'penandatangan_jabatan_id' => [$masuk ? 'nullable' : 'required', 'exists:jabatan,id'],
            'mode_ttd' => [$masuk ? 'nullable' : 'required', Rule::in(['qr', 'basah'])],
            'paraf_role' => ['nullable', 'required_if:perlu_paraf,1', Rule::in(['wakil_dekan', 'kaprodi'])],
            'verifikator_role' => ['required_if:sasaran,mahasiswa', 'nullable', Rule::in(['admin_tu', 'kaprodi'])],
            'sla_hari' => [$masuk ? 'nullable' : 'required', 'integer', 'min:1', 'max:30'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'template_html' => [$masuk ? 'nullable' : 'required', 'string', 'max:20000'],
            'gaya_tanggal' => ['nullable', Rule::in(['dikeluarkan', 'hijriah'])],
        ], [
            'nama.required' => 'Nama format wajib diisi.', 'template_html.required' => 'Isi (templat) surat wajib diisi.',
            'klasifikasi_id.required' => 'Pilih klasifikasi untuk nomor surat.', 'penandatangan_jabatan_id.required' => 'Pilih penandatangan.',
            'kode.regex' => 'Kode hanya huruf besar, angka, dan strip.', 'paraf_role.required_if' => 'Pilih pemaraf bila surat memerlukan paraf.',
        ]);

        $kolom['urutan'] = $kolom['urutan'] ?? 50;
        $kolom['gaya_tanggal'] = $kolom['gaya_tanggal'] ?? 'dikeluarkan';
        $kolom['mode_ttd'] = $kolom['mode_ttd'] ?? 'qr';
        $kolom['sla_hari'] = $kolom['sla_hari'] ?? 1;
        $kolom['template_html'] = $kolom['template_html'] ?? '';
        $fields = $this->bangunFields($request->input('fields', []));
        if (! $fields && ! $masuk) {
            throw ValidationException::withMessages(['fields' => 'Tambahkan minimal satu isian.']);
        }
        if ($kolom['sasaran'] === 'mahasiswa' && collect($fields)->contains('tipe', 'tabel')) {
            throw ValidationException::withMessages(['fields' => 'Isian bertipe Tabel belum didukung untuk surat mahasiswa (e-Layanan). Pakai Teks panjang atau Daftar.']);
        }
        // Token {{ isian.xxx }} pada templat harus ada di daftar isian.
        preg_match_all('/\{\{\s*isian\.([a-z0-9_]+)\s*\}\}/i', $kolom['template_html'], $m);
        $dikenal = array_column($fields, 'nama');
        if (! $masuk && ($tak = array_diff(array_unique($m[1]), $dikenal))) {
            throw ValidationException::withMessages(['template_html' => 'Isian pada templat tidak ada di daftar isian: '.implode(', ', $tak)]);
        }

        $kolom['kode'] = ($kolom['kode'] ?? '') ?: ($format?->kode ?? $this->kodeUnik(Str::upper(Str::slug($kolom['nama'], '-'))));
        foreach (['deskripsi', 'kategori', 'judul_surat', 'perihal_template'] as $k) {
            $kolom[$k] = ($kolom[$k] ?? '') === '' ? null : $kolom[$k];
        }
        unset($kolom['perlu_paraf']);

        $syarat = collect($request->input('syarat', []))->filter(fn ($s) => filled($s['label'] ?? null))
            ->map(fn ($s) => ['label' => Str::limit(trim($s['label']), 120, ''), 'wajib' => ! empty($s['wajib'])])->values()->all();

        return ['kolom' => $kolom, 'fields' => $fields, 'syarat' => $syarat];
    }

    /** Dari baris formulir pembangun → definisi field_formulir yang bersih. */
    private function bangunFields(array $baris, bool $ketat = true): array
    {
        $hasil = [];
        $dipakai = [];
        foreach ($baris as $b) {
            $label = trim((string) ($b['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $tipe = $b['tipe'] ?? 'teks';
            if (! isset(self::TIPE[$tipe])) {
                throw ValidationException::withMessages(['fields' => "Tipe isian \"$label\" tidak valid."]);
            }
            $nama = Str::lower(trim((string) ($b['nama'] ?? '')));
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $nama)) {
                $nama = Str::snake(Str::ascii($label));
                $nama = preg_replace('/[^a-z0-9]+/', '_', $nama);
                $nama = trim($nama, '_');
                if ($nama === '' || ! ctype_alpha($nama[0])) {
                    $nama = 'isian_'.$nama;
                }
                $nama = rtrim(Str::limit($nama, 40, ''), '_');
            }
            $asal = $nama;
            for ($i = 2; in_array($nama, $dipakai, true); $i++) {
                $nama = $asal.'_'.$i;
            }
            $dipakai[] = $nama;

            $f = ['nama' => $nama, 'label' => Str::limit($label, 120, ''), 'tipe' => $tipe, 'wajib' => ! empty($b['wajib'])];
            if (filled($b['placeholder'] ?? null)) {
                $f['placeholder'] = Str::limit(trim($b['placeholder']), 150, '');
            }
            if (($b['lebar'] ?? '') === 'setengah') {
                $f['lebar'] = 'setengah';
            }
            if ($tipe === 'area') {
                $f['maks'] = 1000;
            }
            if ($tipe === 'tabel') {
                $kolom = array_slice(array_values(array_filter(array_map(fn ($k) => Str::limit(trim($k), 60, ''), preg_split('/[\r\n]+/', (string) ($b['kolom'] ?? ''))))), 0, 6);
                if (! $kolom && $ketat) {
                    throw ValidationException::withMessages(['fields' => "Isian \"$label\" bertipe Tabel: isi nama kolom (satu per baris, maks. 6)."]);
                }
                $f['kolom'] = $kolom;
            }
            if ($tipe === 'daftar') {
                $f['maks'] = 1000;
            }
            if ($tipe === 'pilihan') {
                $opsi = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string) ($b['opsi'] ?? '')))));
                if (! $opsi && $ketat) {
                    throw ValidationException::withMessages(['fields' => "Isian \"$label\" bertipe Pilihan: isi minimal satu opsi (satu per baris)."]);
                }
                $f['opsi'] = array_map(fn ($o) => str_replace(',', ' ', Str::limit($o, 80, '')), $opsi);
            }
            $hasil[] = $f;
        }

        return $hasil;
    }

    private function kodeUnik(string $dasar): string
    {
        $kode = Str::limit(preg_replace('/[^A-Z0-9\-]+/', '-', Str::upper($dasar)) ?: 'FORMAT', 26, '');
        $asli = $kode;
        for ($i = 2; JenisSurat::where('kode', $kode)->exists(); $i++) {
            $kode = $asli.'-'.$i;
        }

        return $kode;
    }
}
