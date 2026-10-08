<?php

namespace App\Services;

use App\Models\KlasifikasiSurat;
use App\Models\LogAktivitas;
use App\Models\Pembukuan;
use App\Models\Pengaturan;
use App\Models\Penomoran;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Pembukuan surat (buku agenda/register): catatan surat masuk, keluar, atau lainnya berdasarkan nomor surat.
 * Diisi manual atau impor CSV. Surat keluar yang nomornya mengikuti pola penomoran dapat MENYESUAIKAN penghitung
 * nomor otomatis, sehingga nomor otomatis berikutnya melanjutkan nomor terakhir di buku.
 */
class PembukuanService
{
    public const MAKS_BARIS = 1000;

    public const ARAH = ['masuk' => 'Surat Masuk', 'keluar' => 'Surat Keluar', 'lain' => 'Lainnya'];

    public const KOLOM = ['arah', 'nomor', 'tanggal_surat', 'pihak', 'perihal', 'lampiran', 'sifat', 'jenis', 'tanggal_diterima', 'no_agenda', 'keterangan'];

    private const WAJIB = ['arah', 'nomor', 'tanggal_surat', 'perihal'];

    private const ALIAS = [
        'arah' => 'arah', 'jenis_surat_masuk_keluar' => 'arah', 'masuk_keluar' => 'arah', 'buku' => 'arah', 'tipe' => 'arah',
        'nomor' => 'nomor', 'nomor_surat' => 'nomor', 'no_surat' => 'nomor', 'no' => 'nomor',
        'tanggal_surat' => 'tanggal_surat', 'tgl_surat' => 'tanggal_surat', 'tanggal' => 'tanggal_surat', 'tgl' => 'tanggal_surat',
        'pihak' => 'pihak', 'asal' => 'pihak', 'tujuan' => 'pihak', 'asal_tujuan' => 'pihak', 'dari' => 'pihak', 'kepada' => 'pihak', 'pengirim' => 'pihak',
        'perihal' => 'perihal', 'hal' => 'perihal', 'isi_ringkas' => 'perihal', 'uraian' => 'perihal',
        'lampiran' => 'lampiran', 'sifat' => 'sifat', 'jenis' => 'jenis', 'kategori' => 'jenis',
        'tanggal_diterima' => 'tanggal_diterima', 'tgl_diterima' => 'tanggal_diterima', 'diterima' => 'tanggal_diterima',
        'no_agenda' => 'no_agenda', 'nomor_agenda' => 'no_agenda', 'agenda' => 'no_agenda',
        'keterangan' => 'keterangan', 'catatan' => 'keterangan',
    ];

    private const ALIAS_ARAH = ['masuk' => 'masuk', 'surat_masuk' => 'masuk', 'sm' => 'masuk', 'in' => 'masuk', 'incoming' => 'masuk',
        'keluar' => 'keluar', 'surat_keluar' => 'keluar', 'sk' => 'keluar', 'out' => 'keluar', 'outgoing' => 'keluar',
        'lain' => 'lain', 'lainnya' => 'lain', 'lain_lain' => 'lain', 'lainya' => 'lain'];

    private const ALIAS_SIFAT = ['biasa' => 'biasa', 'penting' => 'penting', 'segera' => 'segera', 'rahasia' => 'rahasia', 'umum' => 'biasa'];

    public function __construct(private PenomoranService $penomoran)
    {
    }

    public function templat(): string
    {
        return Csv::tulis([
            self::KOLOM,
            ['keluar', '001/II.3.AU/UMB-06/A/2026', '2026-01-05', 'Seluruh Dosen dan Tendik', 'Pemberitahuan Libur Awal Tahun', '-', 'biasa', 'Surat Pemberitahuan', '', '', ''],
            ['keluar', '002/TGS/II.3.AU/UMB-06/D/2026', '05/01/2026', 'Rektor UM Buton', 'Surat Tugas Pengabdian Masyarakat', '1 berkas', 'penting', 'Surat Tugas', '', '', 'Arsip lama'],
            ['masuk', 'B-018/REK/UMB/I/2026', '2026-01-08', 'Rektorat Universitas Muhammadiyah Buton', 'Edaran Evaluasi Kinerja Dosen', '2 berkas', 'penting', '', '2026-01-09', 'AGD-2026/I/0001', ''],
            ['lain', 'SK/045/FT-UMB/I/2026', '2026-01-12', '', 'SK Dekan tentang Panitia Wisuda', '', 'biasa', 'SK Dekan', '', '', ''],
        ]);
    }

    /** Menguraikan nomor menurut pola penomoran (Pengaturan → Format Nomor). Null bila tidak cocok. */
    public function parseNomor(string $nomor): ?array
    {
        return $this->penomoran->uraikan($nomor);
    }

    /** Penghitung otomatis ikut naik ke nomor urut tertinggi di buku (hanya surat keluar yang cocok dengan pola; unit kosong = fakultas). */
    private function sinkronkan(?array $uraian, ?Carbon $tglSurat): ?array
    {
        $tahun = $uraian['tahun'] ?? $tglSurat?->year;
        if (! $uraian || ! $tahun) {
            return null;
        }
        $unit = $uraian['unit'] ?? PenomoranService::unitFakultas();
        $this->penomoran->selaraskan($unit, Carbon::create($tahun, 1, 1), $uraian['urut']);

        return ['klasifikasi' => $unit, 'tahun' => $tahun, 'urut' => $uraian['urut']];
    }

    /** Nomor yang sama sudah ada di buku atau pada surat aplikasi. */
    public function ada(string $arah, string $nomor, ?string $pihak, ?string $tgl, ?int $kecualiId = null): bool
    {
        $n = mb_strtolower(trim($nomor));
        $buku = Pembukuan::query()->where('arah', $arah)->whereRaw('lower(nomor) = ?', [$n])->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId));
        if ($arah === 'masuk') {
            // nomor surat pengirim bisa sama antar-pengirim: dianggap kembar bila pengirim dan tanggal juga sama
            $buku->whereRaw("lower(coalesce(pihak, '')) = ?", [mb_strtolower(trim((string) $pihak))])->where('tgl_surat', $tgl);

            return $buku->exists() || DB::table('surat')->where('arah', 'masuk')->whereRaw("lower(json_extract(data, '$.nomor_asal')) = ?", [$n])
                ->whereRaw("lower(coalesce(asal_tujuan, '')) = ?", [mb_strtolower(trim((string) $pihak))])->whereDate('tgl_surat', $tgl)->exists();
        }

        return $buku->exists() || DB::table('surat')->where('arah', 'keluar')->whereRaw('lower(nomor) = ?', [$n])->exists();
    }

    /**
     * Periksa CSV (tidak menyimpan apa pun).
     *
     * @return array{baris: array<int, array>, ringkas: array<string,int>, kolom_diabaikan: array<int,string>, penghitung: array<int, array>}
     */
    public function periksa(string $isi): array
    {
        [$header, $data, $diabaikan] = Csv::baca($isi, self::ALIAS, self::WAJIB, self::MAKS_BARIS);

        $hasil = [];
        $dalamBerkas = [];
        foreach ($data as $i => $mentah) {
            $no = $i + 2;
            $r = [];
            foreach ($header as $k => $nama) {
                $r[$nama] = trim((string) ($mentah[$k] ?? ''));
            }
            $r += array_fill_keys(self::KOLOM, '');
            $galat = [];

            $arah = self::ALIAS_ARAH[Csv::normal($r['arah'])] ?? null;
            if (! $arah) {
                $galat[] = 'arah harus masuk / keluar / lain';
            }
            $v = Validator::make($r, [
                'nomor' => ['required', 'max:120', 'regex:/^[\p{L}\p{N}\/\.\-\(\)_, ]+$/u'], 'perihal' => ['required', 'max:255'], 'pihak' => ['nullable', 'max:255'],
                'lampiran' => ['nullable', 'max:120'], 'jenis' => ['nullable', 'max:80'], 'no_agenda' => ['nullable', 'max:40'], 'keterangan' => ['nullable', 'max:1000'],
            ], ['nomor.required' => 'nomor kosong', 'nomor.regex' => 'nomor memuat karakter tidak diizinkan', 'perihal.required' => 'perihal kosong']);
            array_push($galat, ...$v->errors()->all());

            $tgl = $r['tanggal_surat'] !== '' ? Csv::tanggal($r['tanggal_surat']) : null;
            if (! $tgl) {
                $galat[] = 'tanggal_surat tidak valid (pakai 2026-01-05 atau 05/01/2026)';
            }
            $tglTerima = null;
            if ($r['tanggal_diterima'] !== '') {
                $tglTerima = Csv::tanggal($r['tanggal_diterima']);
                if (! $tglTerima) {
                    $galat[] = 'tanggal_diterima tidak valid';
                }
            }
            $sifat = 'biasa';
            if ($r['sifat'] !== '') {
                $sifat = self::ALIAS_SIFAT[Csv::normal($r['sifat'])] ?? null;
                if (! $sifat) {
                    $galat[] = 'sifat harus biasa / penting / segera / rahasia';
                    $sifat = 'biasa';
                }
            }

            $status = 'baru';
            $pesan = '';
            if (! $galat && $arah) {
                $kunci = $arah.'|'.mb_strtolower($r['nomor']).($arah === 'masuk' ? '|'.mb_strtolower($r['pihak']).'|'.$tgl : '');
                if (isset($dalamBerkas[$kunci])) {
                    $status = 'lewati';
                    $pesan = "Nomor sama dengan baris {$dalamBerkas[$kunci]} dalam berkas — dilewati";
                } elseif ($this->ada($arah, $r['nomor'], $r['pihak'], $tgl)) {
                    $status = 'lewati';
                    $pesan = 'Nomor sudah ada di pembukuan/aplikasi — dilewati';
                }
                $dalamBerkas[$kunci] ??= $no;
            }
            if ($galat) {
                $status = 'galat';
                $pesan = implode('; ', array_unique($galat));
            }

            $uraian = $arah === 'keluar' ? $this->parseNomor($r['nomor']) : null;
            $hasil[] = [
                'no' => $no, 'arah' => $arah, 'nomor' => $r['nomor'], 'tanggal' => $tgl, 'perihal' => $r['perihal'], 'status' => $status, 'pesan' => $pesan, 'uraian' => $uraian,
                'data' => ['arah' => $arah, 'nomor' => $r['nomor'], 'tgl_surat' => $tgl, 'tgl_diterima' => $arah === 'masuk' ? ($tglTerima ?? $tgl) : $tglTerima, 'pihak' => $r['pihak'] ?: null,
                    'perihal' => $r['perihal'], 'lampiran' => $r['lampiran'] ?: null, 'sifat' => $sifat, 'jenis' => $r['jenis'] ?: null,
                    'no_agenda' => $r['no_agenda'] ?: null, 'keterangan' => $r['keterangan'] ?: null],
            ];
        }

        $ringkas = collect($hasil)->countBy('status')->all() + ['baru' => 0, 'lewati' => 0, 'galat' => 0];

        return ['baris' => $hasil, 'ringkas' => $ringkas, 'kolom_diabaikan' => $diabaikan, 'penghitung' => $this->rencanaPenghitung($hasil)];
    }

    /** Pratinjau: penghitung nomor otomatis yang akan naik akibat impor surat keluar. */
    private function rencanaPenghitung(array $baris): array
    {
        $rencana = [];
        foreach ($baris as $b) {
            if ($b['status'] !== 'baru' || ! $b['uraian']) {
                continue;
            }
            $unit = $b['uraian']['unit'] ?? PenomoranService::unitFakultas();
            $tahun = $b['uraian']['tahun'] ?? ($b['tanggal'] ? Carbon::parse($b['tanggal'])->year : null);
            if (! $tahun) {
                continue;
            }
            $kunci = $unit.'|'.$tahun;
            $rencana[$kunci]['kode'] = $unit;
            $rencana[$kunci]['tahun'] = $tahun;
            $rencana[$kunci]['maks'] = max($rencana[$kunci]['maks'] ?? 0, $b['uraian']['urut']);
            $rencana[$kunci]['sekarang'] = (int) (Penomoran::where(['unit' => $unit, 'tahun' => $tahun])->value('nomor_terakhir') ?? 0);
        }

        return array_values($rencana);
    }

    /**
     * Menyimpan baris valid (status baru) dalam satu transaksi.
     *
     * @return array{tersimpan: int, dilewati: int, penghitung: array<int, array>}
     */
    public function proses(array $barisValid, User $oleh, bool $sinkron): array
    {
        $tersimpan = 0;
        $dilewati = 0;
        $penghitung = [];
        DB::transaction(function () use ($barisValid, $oleh, $sinkron, &$tersimpan, &$dilewati, &$penghitung) {
            foreach ($barisValid as $b) {
                if ($b['status'] !== 'baru') {
                    continue;
                }
                $d = $b['data'];
                if ($this->ada($d['arah'], $d['nomor'], $d['pihak'], $d['tgl_surat'])) {   // dicek ulang: berkas lain mungkin sudah menyimpan nomor ini
                    $dilewati++;

                    continue;
                }
                $uraian = $d['arah'] === 'keluar' ? $this->parseNomor($d['nomor']) : null;
                $klasifikasiId = $uraian && $uraian['klasifikasi'] ? KlasifikasiSurat::whereRaw('lower(kode) = ?', [mb_strtolower($uraian['klasifikasi'])])->value('id') : null;
                Pembukuan::create($d + ['no_urut' => $uraian['urut'] ?? null, 'klasifikasi_id' => $klasifikasiId, 'sumber' => 'impor', 'dibuat_oleh' => $oleh->id]);
                $tersimpan++;
                if ($sinkron && ($s = $this->sinkronkan($uraian, $d['tgl_surat'] ? Carbon::parse($d['tgl_surat']) : null))) {
                    $penghitung[$s['klasifikasi'].'|'.$s['tahun']] = ['kode' => $s['klasifikasi'], 'tahun' => $s['tahun'], 'maks' => max($penghitung[$s['klasifikasi'].'|'.$s['tahun']]['maks'] ?? 0, $s['urut'])];
                }
            }
            LogAktivitas::catat('impor_pembukuan', "Impor CSV pembukuan: {$tersimpan} dicatat, {$dilewati} dilewati".($sinkron ? ' (penghitung nomor disesuaikan)' : ''), null, [], $oleh->id);
        });

        return ['tersimpan' => $tersimpan, 'dilewati' => $dilewati, 'penghitung' => array_values($penghitung)];
    }

    /** Catatan manual (satu surat). */
    public function simpanManual(array $d, User $oleh, ?Pembukuan $b = null, bool $sinkron = false): Pembukuan
    {
        $uraian = $d['arah'] === 'keluar' ? $this->parseNomor($d['nomor']) : null;
        $klasifikasiId = $uraian && $uraian['klasifikasi'] ? KlasifikasiSurat::whereRaw('lower(kode) = ?', [mb_strtolower($uraian['klasifikasi'])])->value('id') : null;
        $atribut = $d + ['no_urut' => $uraian['urut'] ?? null, 'klasifikasi_id' => $klasifikasiId];

        return DB::transaction(function () use ($atribut, $b, $oleh, $uraian, $sinkron, $d) {
            if ($b) {
                $b->update($atribut);
            } else {
                $b = Pembukuan::create($atribut + ['sumber' => 'manual', 'dibuat_oleh' => $oleh->id]);
            }
            if ($sinkron) {
                $this->sinkronkan($uraian, ! empty($d['tgl_surat']) ? Carbon::parse($d['tgl_surat']) : null);
            }
            LogAktivitas::catat('pembukuan', ($b->wasRecentlyCreated ? 'Mencatat' : 'Mengubah')." pembukuan {$b->arah} {$b->nomor}", null, [], $oleh->id);

            return $b;
        });
    }

    /** Buku gabungan: surat dari aplikasi + catatan pembukuan, siap difilter/dipaginasi. */
    public function query(array $f = []): Builder
    {
        $aplikasi = DB::table('surat')->selectRaw("'aplikasi' as sumber, id, arah, CASE WHEN arah = 'masuk' THEN json_extract(data, '$.nomor_asal') ELSE nomor END as nomor, no_agenda, tgl_surat,
            CASE WHEN arah = 'masuk' THEN json_extract(data, '$.tgl_diterima') ELSE NULL END as tgl_diterima, asal_tujuan as pihak, perihal, sifat, status as keterangan, NULL as jenis, file_pdf, pengajuan_id")
            ->where(fn ($q) => $q->where('arah', 'masuk')->orWhere(fn ($w) => $w->where('arah', 'keluar')->whereNotNull('nomor')));
        $buku = DB::table('pembukuan')->selectRaw("'buku' as sumber, id, arah, nomor, no_agenda, tgl_surat, tgl_diterima, pihak, perihal, sifat, keterangan, jenis, NULL as file_pdf, NULL as pengajuan_id");

        $q = DB::query()->fromSub($aplikasi->unionAll($buku), 'buku_surat');
        if (! empty($f['arah']) && isset(self::ARAH[$f['arah']])) {
            $q->where('arah', $f['arah']);
        }
        if (! empty($f['tahun'])) {
            $q->whereRaw("strftime('%Y', tgl_surat) = ?", [(string) (int) $f['tahun']]);
        }
        if (! empty($f['sumber']) && in_array($f['sumber'], ['aplikasi', 'buku'], true)) {
            $q->where('sumber', $f['sumber']);
        }
        if (! empty($f['q'])) {
            $t = '%'.str_replace(['%', '_'], ['\%', '\_'], $f['q']).'%';
            $q->where(fn ($w) => $w->whereRaw("nomor like ? escape '\\'", [$t])->orWhereRaw("perihal like ? escape '\\'", [$t])
                ->orWhereRaw("pihak like ? escape '\\'", [$t])->orWhereRaw("no_agenda like ? escape '\\'", [$t]));
        }

        return $q->orderByDesc('tgl_surat')->orderByDesc('id');
    }

    /** Ekspor buku (sesuai filter) ke CSV. */
    public function ekspor(array $f, bool $sembunyikanRahasia): string
    {
        $baris = [['sumber', 'arah', 'nomor', 'no_agenda', 'tanggal_surat', 'tanggal_diterima', 'pihak', 'perihal', 'sifat', 'jenis', 'keterangan']];
        foreach ($this->query($f)->get() as $r) {
            $baris[] = [$r->sumber === 'aplikasi' ? 'aplikasi' : 'pembukuan', $r->arah, $r->nomor, $r->no_agenda, $r->tgl_surat, $r->tgl_diterima, $r->pihak,
                $sembunyikanRahasia && $r->sifat === 'rahasia' ? '(rahasia)' : $r->perihal, $r->sifat, $r->jenis, $r->keterangan];
        }

        return Csv::tulis($baris);
    }
}
