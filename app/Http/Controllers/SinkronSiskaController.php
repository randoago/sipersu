<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Services\SinkronSiska;
use App\Services\SiskaClient;
use Illuminate\Http\Request;
use RuntimeException;

class SinkronSiskaController extends Controller
{
    public function __construct(private SiskaClient $siska, private SinkronSiska $sinkron)
    {
    }

    /** Halaman Koneksi SISKA (hanya Super Admin). */
    public function form()
    {
        $prodiSiska = [];
        $galatKoneksi = null;
        if ($this->siska->terkonfigurasi()) {
            try {
                $prodiSiska = $this->siska->prodi();
            } catch (RuntimeException $e) {
                $galatKoneksi = $e->getMessage();
            }
        }

        return view('master.siska', [
            'entitas' => 'siska', 'semua' => \App\Support\MasterData::semua(),
            'url' => $this->siska->url(), 'adaKunci' => $this->siska->kunci() !== null,
            'prodiSiska' => $prodiSiska, 'galatKoneksi' => $galatKoneksi,
            'prodiDipilih' => $this->sinkron->prodiDipilih(), 'statusDipilih' => $this->sinkron->statusDipilih(),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url:http,https', 'max:200'],
            'kunci' => ['nullable', 'string', 'min:20', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'prodi' => ['nullable', 'array'], 'prodi.*' => ['string', 'max:40'],
            'status' => ['required', 'array', 'min:1'], 'status.*' => ['in:'.implode(',', SinkronSiska::STATUS)],
        ], ['kunci.regex' => 'Kunci API hanya berisi huruf, angka, garis bawah, strip.', 'kunci.min' => 'Kunci API terlalu pendek.', 'status.required' => 'Pilih minimal satu status mahasiswa.']);

        if (! $this->siska->kunci() && empty($data['kunci'])) {
            return back()->withInput()->withErrors(['kunci' => 'Kunci API wajib diisi.']);
        }
        Pengaturan::simpan('siska_url', rtrim($data['url'], '/'));
        if (! empty($data['kunci'])) {
            SiskaClient::simpanKunci($data['kunci']);
        }
        Pengaturan::simpan('siska_prodi', json_encode(array_values($data['prodi'] ?? [])));
        Pengaturan::simpan('siska_status', json_encode(array_values($data['status'])));
        LogAktivitas::catat('siska_atur', 'Mengubah pengaturan Koneksi SISKA'.(! empty($data['kunci']) ? ' (kunci API diganti)' : ''));

        return redirect()->route('master.siska')->with('sukses', 'Pengaturan Koneksi SISKA disimpan.');
    }

    public function uji()
    {
        try {
            $n = count($this->siska->prodi());
        } catch (RuntimeException $e) {
            return redirect()->route('master.siska')->with('galat', $e->getMessage());
        }

        return redirect()->route('master.siska')->with('sukses', "Terhubung ke SISKA. $n program studi terbaca.");
    }

    /** Tombol "Sinkron dari SISKA" pada Master Data (Super Admin & Admin TU). */
    public function jalankan(Request $request, string $jenis)
    {
        $balik = redirect()->route('master.daftar', $jenis === 'prodi' ? 'prodi' : ['entitas' => 'pengguna', 'kelompok' => 'mahasiswa']);
        try {
            $hasil = $jenis === 'prodi' ? $this->sinkron->prodi($request->user()) : $this->sinkron->pengguna($request->user());
        } catch (RuntimeException $e) {
            return $balik->with('galat', $e->getMessage());
        }

        return $balik->with('sukses', "Sinkron SISKA selesai: {$hasil['ditambah']} ditambah, {$hasil['diperbarui']} diperbarui, {$hasil['tetap']} tidak berubah, {$hasil['dilewati']} dilewati.")
            ->with('sinkron_galat', $hasil['galat']);
    }
}
