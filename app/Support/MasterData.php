<?php

namespace App\Support;

use App\Enums\Peran;
use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Prodi;
use App\Models\User;

/** Definisi entitas Master Data untuk MasterController (daftar + form dinamis). */
class MasterData
{
    public static function semua(): array
    {
        $prodi = fn () => ['' => '— tidak ada —'] + Prodi::orderBy('nama')->pluck('nama', 'id')->all();
        $klas = fn () => ['' => '— pilih —'] + KlasifikasiSurat::orderBy('kode')->get()->mapWithKeys(fn ($k) => [$k->id => $k->kode.' — '.$k->nama])->all();
        $jabatan = fn () => ['' => '— pilih —'] + Jabatan::orderBy('nama')->pluck('nama', 'id')->all();
        $pejabat = fn () => ['' => '— kosong —'] + User::bukanMahasiswa()->where('aktif', true)->orderBy('nama')->get()->mapWithKeys(fn ($u) => [$u->id => $u->namaLengkap().' (NIDN '.$u->nomor_induk.')'])->all();

        return [
            'pengguna' => [
                'model' => User::class, 'judul' => 'Pengguna', 'ikon' => 'groups', 'with' => ['prodi', 'roles'],
                'cari' => ['nomor_induk', 'username', 'nama', 'email'],
                'kolom' => [
                    ['Nomor Induk', fn ($m) => $m->labelNomorInduk().' '.$m->nomor_induk.($m->username ? ' (login: '.$m->username.')' : ''), 'tabular'],
                    ['Nama', fn ($m) => $m->namaLengkap()],
                    ['Peran', fn ($m) => $m->roles->map(fn ($r) => Peran::tryFrom($r->name)?->label() ?? $r->name)->implode(', ')],
                    ['Prodi', fn ($m) => $m->prodi?->nama ?? '-'],
                ],
                'field' => [
                    ['nomor_induk', 'NPM (mahasiswa) / NIDN (dosen)', 'teks', ['required', 'string', 'max:30', 'unique:users,nomor_induk,{id}'], 'lebar' => 'setengah'],
                    ['username', 'Username (opsional)', 'teks', ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', fn ($atribut, $nilai, $gagal) => $nilai && User::whereRaw('lower(username) = ?', [mb_strtolower((string) $nilai)])->where('id', '!=', (int) request()->route('id'))->exists() ? $gagal('Username sudah dipakai (huruf besar/kecil dianggap sama).') : null], 'bantuan' => 'Nama masuk alternatif, mis. TU. Huruf, angka, titik, strip.', 'lebar' => 'setengah'],
                    ['nama', 'Nama (tanpa gelar)', 'teks', ['required', 'string', 'max:120'], 'lebar' => 'setengah'],
                    ['gelar_depan', 'Gelar depan', 'teks', ['nullable', 'string', 'max:50'], 'lebar' => 'setengah'],
                    ['gelar_belakang', 'Gelar belakang', 'teks', ['nullable', 'string', 'max:80'], 'lebar' => 'setengah'],
                    ['email', 'Email', 'teks', ['nullable', 'email', 'max:120', 'unique:users,email,{id}'], 'lebar' => 'setengah'],
                    ['no_hp', 'No. HP', 'teks', ['nullable', 'string', 'max:20'], 'lebar' => 'setengah'],
                    ['prodi_id', 'Program studi', 'pilihan', ['nullable', 'exists:prodi,id'], 'opsi' => $prodi, 'lebar' => 'setengah'],
                    ['angkatan', 'Angkatan (mahasiswa)', 'teks', ['nullable', 'digits:4'], 'lebar' => 'setengah'],
                    ['tempat_lahir', 'Tempat lahir', 'teks', ['nullable', 'string', 'max:80'], 'lebar' => 'setengah'],
                    ['tanggal_lahir', 'Tanggal lahir', 'tanggal', ['nullable', 'date', 'before:today'], 'lebar' => 'setengah'],
                    ['alamat', 'Alamat', 'area', ['nullable', 'string', 'max:300']],
                    ['peran', 'Peran (boleh lebih dari satu)', 'peran', ['required', 'array', 'min:1']],
                    ['password', 'Kata sandi', 'sandi', ['{wajib-buat}', 'nullable', 'string', 'min:8', 'max:100'], 'bantuan' => 'Kosongkan saat mengubah bila tidak diganti.'],
                    ['aktif', 'Akun aktif', 'ya_tidak', ['boolean']],
                ],
            ],
            'prodi' => [
                'model' => Prodi::class, 'judul' => 'Program Studi', 'ikon' => 'account_balance', 'with' => [],
                'cari' => ['kode', 'nama'],
                'kolom' => [['Kode', fn ($m) => $m->kode], ['Nama', fn ($m) => $m->nama], ['Jenjang', fn ($m) => $m->jenjang]],
                'field' => [
                    ['kode', 'Kode', 'teks', ['required', 'string', 'max:20', 'unique:prodi,kode,{id}'], 'lebar' => 'setengah'],
                    ['jenjang', 'Jenjang', 'pilihan', ['required', 'in:D3,S1,S2'], 'opsi' => fn () => ['S1' => 'S1', 'D3' => 'D3', 'S2' => 'S2'], 'lebar' => 'setengah'],
                    ['nama', 'Nama program studi', 'teks', ['required', 'string', 'max:150']],
                    ['aktif', 'Aktif', 'ya_tidak', ['boolean']],
                ],
            ],
            'jabatan' => [
                'model' => Jabatan::class, 'judul' => 'Jabatan & Pejabat', 'ikon' => 'badge', 'with' => ['pejabat', 'prodi'],
                'cari' => ['kode', 'nama'],
                'kolom' => [['Jabatan', fn ($m) => $m->nama], ['Pejabat aktif', fn ($m) => $m->pejabat?->namaLengkap() ?? '— kosong —'], ['Periode', fn ($m) => ($m->periode_mulai?->format('Y') ?? '?').' – '.($m->periode_selesai?->format('Y') ?? 'sekarang')]],
                'field' => [
                    ['kode', 'Kode (unik)', 'teks', ['required', 'string', 'max:30', 'regex:/^[a-z0-9\-]+$/', 'unique:jabatan,kode,{id}'], 'bantuan' => 'Huruf kecil, angka, strip. Contoh: kaprodi-ts', 'lebar' => 'setengah'],
                    ['prodi_id', 'Program studi (bila Kaprodi)', 'pilihan', ['nullable', 'exists:prodi,id'], 'opsi' => $prodi, 'lebar' => 'setengah'],
                    ['nama', 'Nama jabatan', 'teks', ['required', 'string', 'max:150'], 'bantuan' => 'Tercetak pada surat. Contoh: Dekan Fakultas Teknik'],
                    ['user_id', 'Pejabat aktif', 'pilihan', ['nullable', 'exists:users,id', fn ($a, $v, $gagal) => $v && User::mahasiswa()->whereKey($v)->exists() ? $gagal('Mahasiswa tidak dapat menjadi pejabat penandatangan.') : null], 'opsi' => $pejabat, 'bantuan' => 'Hanya dosen/tendik; mahasiswa tidak ditampilkan.'],
                    ['periode_mulai', 'Periode mulai', 'tanggal', ['nullable', 'date'], 'lebar' => 'setengah'],
                    ['periode_selesai', 'Periode selesai', 'tanggal', ['nullable', 'date', 'after_or_equal:periode_mulai'], 'lebar' => 'setengah'],
                    ['aktif', 'Aktif', 'ya_tidak', ['boolean']],
                ],
            ],
            'klasifikasi' => [
                'model' => KlasifikasiSurat::class, 'judul' => 'Klasifikasi Surat', 'ikon' => 'rule', 'with' => [],
                'cari' => ['kode', 'nama'],
                'kolom' => [['Kode', fn ($m) => $m->kode, 'tabular'], ['Nama', fn ($m) => $m->nama], ['Keterangan', fn ($m) => \Illuminate\Support\Str::limit((string) $m->keterangan, 70)]],
                'field' => [
                    ['kode', 'Kode klasifikasi', 'teks', ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-]+$/', 'unique:klasifikasi_surat,kode,{id}'], 'bantuan' => 'Dipakai pada nomor surat, mis. II.3.AU. Mengubah kode TIDAK mengubah nomor yang sudah terbit.'],
                    ['nama', 'Nama', 'teks', ['required', 'string', 'max:150']],
                    ['keterangan', 'Keterangan', 'area', ['nullable', 'string', 'max:300']],
                    ['aktif', 'Aktif', 'ya_tidak', ['boolean']],
                ],
            ],
        ];
    }
}
