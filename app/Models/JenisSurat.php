<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSurat extends Model
{
    protected $table = 'jenis_surat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'field_formulir' => 'array',
            'syarat' => 'array',
            'aktif' => 'boolean',
            'perlu_paraf' => 'boolean',
        ];
    }

    public function scopeUntukMahasiswa($q)
    {
        return $q->where('sasaran', 'mahasiswa');
    }

    public function scopeUntukStaf($q)
    {
        return $q->where('sasaran', 'staf');
    }

    public function adalahUntukStaf(): bool
    {
        return $this->sasaran === 'staf';
    }

    /** Aturan validasi untuk formulir isian yang dibentuk dari format ini. */
    public function aturanIsian(string $awalan = 'isian.', bool $tanggalBolehLampau = true): array
    {
        $aturan = [];
        foreach ($this->field_formulir as $f) {
            if ($f['tipe'] === 'tabel') {
                $wajib = (bool) ($f['wajib'] ?? false);
                $aturan[$awalan.$f['nama']] = [$wajib ? 'required' : 'nullable', 'array', 'max:30', function ($atribut, $nilai, $gagal) use ($wajib, $f) {
                    $ada = collect((array) $nilai)->contains(fn ($b) => is_array($b) && array_filter($b, fn ($x) => trim((string) $x) !== ''));
                    if ($wajib && ! $ada) {
                        $gagal('Isi minimal satu baris pada tabel "'.mb_strtolower($f['label']).'".');
                    }
                }];
                $aturan[$awalan.$f['nama'].'.*'] = ['nullable', 'array', 'max:'.max(1, count($f['kolom'] ?? []))];
                $aturan[$awalan.$f['nama'].'.*.*'] = ['nullable', 'string', 'max:200'];

                continue;
            }
            $r = [($f['wajib'] ?? false) ? 'required' : 'nullable'];
            $r = array_merge($r, match ($f['tipe']) {
                'daftar' => ['string', 'max:'.($f['maks'] ?? 1000)],
                'area' => ['string', 'max:'.($f['maks'] ?? 1000)],
                'tanggal' => ['date', $f['nama'] === 'tgl_selesai' ? 'after_or_equal:'.$awalan.'tgl_mulai' : ($tanggalBolehLampau ? 'date' : 'after_or_equal:today')],
                'angka' => ['numeric', 'min:0', 'max:100000000'],
                'pilihan' => ['string', 'in:'.implode(',', array_map(fn ($o) => str_replace(',', ' ', $o), $f['opsi'] ?? []))],
                default => ['string', 'max:255'],
            });
            $aturan[$awalan.$f['nama']] = array_values(array_unique($r));
        }

        return $aturan;
    }

    public function atributIsian(string $awalan = 'isian.'): array
    {
        return collect($this->field_formulir)->mapWithKeys(fn ($f) => [$awalan.$f['nama'] => mb_strtolower($f['label'])])->all();
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function penandatanganJabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'penandatangan_jabatan_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }
}
