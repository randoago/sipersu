<?php

namespace App\Livewire;

use App\Models\JenisSurat;
use App\Models\Lampiran;
use App\Services\AlurPengajuan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Ajukan Surat')]
class AjukanSurat extends Component
{
    use WithFileUploads;

    public JenisSurat $jenis;

    /** @var array<string, mixed> */
    public array $isian = [];

    /** @var array<int, mixed> berkas per indeks syarat */
    public array $berkas = [];

    public int $langkah = 1;

    public bool $setuju = false;

    public function mount(JenisSurat $jenis): void
    {
        abort_unless($jenis->aktif && $jenis->sasaran === 'mahasiswa', 404);
        abort_unless(auth()->user()->adalahMahasiswa(), 403, 'Hanya mahasiswa yang dapat mengajukan surat.');
        $this->jenis = $jenis;
        foreach ($jenis->field_formulir as $f) {
            $this->isian[$f['nama']] = '';
        }
    }

    // ---- aturan validasi dinamis ------------------------------------------------------------------------

    private function aturanIsian(): array
    {
        $aturan = [];
        foreach ($this->jenis->field_formulir as $f) {
            $r = [($f['wajib'] ?? false) ? 'required' : 'nullable'];
            $r = array_merge($r, match ($f['tipe']) {
                'area' => ['string', 'max:'.($f['maks'] ?? 250)],
                'tanggal' => ['date', 'after_or_equal:'.($f['nama'] === 'tgl_selesai' ? 'isian.tgl_mulai' : 'today')],
                'angka' => ['integer', 'min:1', 'max:30'],
                'pilihan' => ['string', 'in:'.implode(',', $f['opsi'] ?? [])],
                default => ['string', 'max:255'],
            });
            $aturan['isian.'.$f['nama']] = $r;
        }

        return $aturan;
    }

    private function aturanBerkas(): array
    {
        $aturan = [];
        foreach ($this->jenis->syarat ?? [] as $i => $s) {
            $aturan["berkas.$i"] = [($s['wajib'] ?? false) ? 'required' : 'nullable', 'file', 'max:'.config('sipersu.lampiran.maks_kb'), 'mimes:'.implode(',', config('sipersu.lampiran.mimes'))];
        }

        return $aturan;
    }

    private function namaAtribut(): array
    {
        $n = [];
        foreach ($this->jenis->field_formulir as $f) {
            $n['isian.'.$f['nama']] = mb_strtolower($f['label']);
        }
        foreach ($this->jenis->syarat ?? [] as $i => $s) {
            $n["berkas.$i"] = 'berkas "'.$s['label'].'"';
        }

        return $n;
    }

    public function updatedBerkas($value, $key): void
    {
        $this->validateOnly("berkas.$key", $this->aturanBerkas(), [], $this->namaAtribut());
    }

    public function hapusBerkas(int $i): void
    {
        unset($this->berkas[$i]);
        $this->resetValidation("berkas.$i");
    }

    // ---- navigasi langkah -------------------------------------------------------------------------------

    public function lanjut(): void
    {
        if ($this->langkah === 1) {
            $this->validate($this->aturanIsian(), [], $this->namaAtribut());
        } elseif ($this->langkah === 2) {
            $this->validate($this->aturanBerkas(), [], $this->namaAtribut());
        }
        $this->langkah = min(3, $this->langkah + 1);
    }

    public function kembali(): void
    {
        $this->langkah = max(1, $this->langkah - 1);
    }

    /** Pratinjau tampilan surat dari isian saat ini (belum dikirim). */
    public function pratinjau(\App\Services\PenyusunSurat $penyusun): void
    {
        $isian = $penyusun->isianPratinjau($this->jenis->field_formulir, $this->isian);
        $p = new \App\Models\Pengajuan(['data_isian' => $isian]);
        $p->setRelation('pemohon', auth()->user()->loadMissing('prodi'));
        $p->setRelation('jenis', $this->jenis);
        $jabatan = $this->jenis->penandatanganJabatan;
        $isi = $penyusun->badan($this->jenis->template_html, $penyusun->dataPengajuan($p), $jabatan);

        $this->dispatch('tampil-pratinjau', html: $penyusun->htmlPratinjau($isi, $this->jenis->judul_surat, $jabatan, $this->jenis->mode_ttd ?? 'qr'));
    }

    public function kirim(AlurPengajuan $alur)
    {
        $this->validate($this->aturanIsian() + $this->aturanBerkas() + ['setuju' => ['accepted']], ['setuju.accepted' => 'Centang pernyataan kebenaran data terlebih dahulu.'], $this->namaAtribut());

        $pengajuan = DB::transaction(function () use ($alur) {
            $isian = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $this->isian);
            $p = $alur->ajukan(auth()->user(), $this->jenis, $isian);

            foreach ($this->jenis->syarat ?? [] as $i => $s) {
                $f = $this->berkas[$i] ?? null;
                if (! $f) {
                    continue;
                }
                $ext = strtolower($f->guessExtension() ?: $f->getClientOriginalExtension());
                $path = $f->storeAs("lampiran/pengajuan/{$p->id}", Str::random(24).'.'.$ext, 'local');
                Lampiran::create([
                    'lampiranable_type' => $p->getMorphClass(), 'lampiranable_id' => $p->id,
                    'label' => $s['label'], 'nama_asli' => Str::limit($f->getClientOriginalName(), 120, ''),
                    'path' => $path, 'mime' => $f->getMimeType(), 'ukuran' => $f->getSize(), 'diunggah_oleh' => auth()->id(),
                ]);
            }

            return $p;
        });

        session()->flash('sukses', "Pengajuan {$pengajuan->kode} berhasil dikirim. Pantau statusnya di halaman ini.");

        return $this->redirectRoute('pengajuan.show', $pengajuan);
    }

    public function render()
    {
        return view('livewire.ajukan-surat', ['user' => auth()->user()->load('prodi')]);
    }
}
