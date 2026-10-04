<?php

namespace App\Services;

use App\Models\JenisSurat;
use App\Models\Lampiran;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Pencatatan surat masuk: nomor agenda otomatis (transaksional) + pindaian di penyimpanan privat. */
class SuratMasukService
{
    public function __construct(private PenomoranService $penomoran, private PenyusunSurat $penyusun)
    {
    }

    /** Surat yang sama (nomor asal + pengirim + tanggal surat) tidak boleh dicatat dua kali. */
    public function cariDuplikat(string $nomorAsal, string $asal, string $tglSurat, ?int $kecuali = null): ?Surat
    {
        return Surat::where('arah', 'masuk')
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->whereRaw("lower(json_extract(data, '$.nomor_asal')) = ?", [mb_strtolower(trim($nomorAsal))])
            ->whereRaw('lower(asal_tujuan) = ?', [mb_strtolower(trim($asal))])
            ->whereDate('tgl_surat', $tglSurat)->first();
    }

    public function catat(User $oleh, JenisSurat $jenis, array $d, ?UploadedFile $scan = null, ?Surat $surat = null): Surat
    {
        if ($dup = $this->cariDuplikat($d['nomor_asal'], $d['asal'], $d['tgl_surat'], $surat?->id)) {
            throw ValidationException::withMessages(['nomor_asal' => "Surat ini sudah dicatat dengan Nomor Agenda {$dup->no_agenda}."]);
        }
        $jalurBaru = null;

        try {
            return DB::transaction(function () use ($oleh, $jenis, $d, $scan, $surat, &$jalurBaru) {
                $isian = $d['isian'] ?? [];
                $atribut = [
                    'arah' => 'masuk', 'klasifikasi_id' => ($d['klasifikasi_id'] ?? null) ?: $jenis->klasifikasi_id,
                    'perihal' => $d['perihal'], 'sifat' => $d['sifat'], 'asal_tujuan' => trim($d['asal']),
                    'tgl_surat' => $d['tgl_surat'], 'jenis_surat_id' => $jenis->id,
                    'data' => [
                        'nomor_asal' => trim($d['nomor_asal']), 'tgl_diterima' => $d['tgl_diterima'], 'lampiran' => $d['lampiran'] ?? '',
                        'isian' => $this->penyusun->formatIsian($jenis, $isian), 'isian_mentah' => $isian,
                    ],
                ];
                if ($surat) {
                    $surat->update($atribut);
                } else {
                    $agenda = $this->penomoran->agenda(Carbon::parse($d['tgl_diterima']));
                    $surat = Surat::create($atribut + ['no_agenda' => $agenda, 'status' => 'tercatat', 'dibuat_oleh' => $oleh->id]);
                }

                if ($scan) {
                    foreach ($surat->lampiran as $lama) {
                        Storage::disk('local')->delete($lama->path);
                        $lama->delete();
                    }
                    $ext = strtolower($scan->guessExtension() ?: $scan->getClientOriginalExtension());
                    $jalurBaru = $scan->storeAs("lampiran/surat-masuk/{$surat->id}", Str::random(24).'.'.$ext, 'local');
                    Lampiran::create([
                        'lampiranable_type' => $surat->getMorphClass(), 'lampiranable_id' => $surat->id, 'label' => 'Pindaian surat',
                        'nama_asli' => Str::limit($scan->getClientOriginalName(), 120, ''), 'path' => $jalurBaru,
                        'mime' => $scan->getMimeType(), 'ukuran' => $scan->getSize(), 'diunggah_oleh' => $oleh->id,
                    ]);
                }
                LogAktivitas::catat('surat_masuk', "Mencatat surat masuk {$surat->no_agenda}: {$surat->perihal}", $surat, [], $oleh->id);

                return $surat;
            });
        } catch (\Throwable $e) {
            if ($jalurBaru) {
                Storage::disk('local')->delete($jalurBaru);
            }
            throw $e;
        }
    }
}
