<x-layouts::app title="Koneksi SISKA" :cari="false">
<div class="mx-auto max-w-6xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Master Data</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Koneksi ke SISKA untuk menyinkronkan Program Studi dan Pengguna (mahasiswa) tanpa input manual.</p>
    @include('master._tab')

    <x-peringatan jenis="info" class="mb-5" judul="Cara kerja">
        Data dibaca langsung dari database SISKA lewat API integrasi baca-saja yang dilindungi <strong>kunci API</strong>. Kunci dibuat di server SISKA dengan
        <code>php artisan integrasi:buat-kunci sipersu</code>, lalu ditempel di sini (disimpan terenkripsi). Sinkronisasi satu arah (SISKA → SIPERSU): username, peran,
        kata sandi, dan kode unit kerja lokal tidak ditimpa. Dosen/tendik tidak ikut disinkronkan.
    </x-peringatan>

    @if ($galatKoneksi)<x-peringatan jenis="bahaya" class="mb-5" judul="Koneksi gagal">{{ $galatKoneksi }}</x-peringatan>@endif

    <form method="post" action="{{ route('master.siska.simpan') }}" class="space-y-5">@csrf @method('PUT')
        <x-kartu judul="Koneksi" ikon="cable" deskripsi="Alamat server SISKA dan kunci API.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-input label="Alamat server SISKA" name="url" type="url" wajib :value="old('url', $url)" placeholder="http://host.docker.internal:8090" bantuan="Tanpa /api. Dari kontainer Docker SIPERSU, server SISKA di komputer yang sama dijangkau lewat host.docker.internal:8090." />
                <x-input label="Kunci API" name="kunci" type="password" autocomplete="off" :wajib="! $adaKunci" placeholder="{{ $adaKunci ? '•••••••• tersimpan — kosongkan bila tidak diganti' : 'sk_…' }}" bantuan="Kunci asli hanya tampil sekali saat dibuat di SISKA." />
            </div>
        </x-kartu>

        <x-kartu judul="Cakupan sinkronisasi" ikon="tune" deskripsi="Pilih program studi Fakultas Teknik dan status mahasiswa yang diambil.">
            <div class="grid gap-6 md:grid-cols-2">
                <fieldset>
                    <legend class="mb-2 font-label-lg text-label-lg">Program studi di SISKA</legend>
                    @forelse ($prodiSiska as $p)
                        <label class="flex items-center gap-2 py-1 font-body-md text-body-md"><input type="checkbox" name="prodi[]" value="{{ $p['id_prodi'] }}" @checked(in_array($p['id_prodi'], old('prodi', $prodiDipilih), true)) class="rounded border-outline-variant text-primary focus:ring-primary">{{ $p['nama_program_studi'] }} <span class="text-on-surface-variant">({{ $p['jenjang'] }})</span></label>
                    @empty
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Simpan koneksi yang benar terlebih dulu; daftar program studi SISKA akan tampil di sini.</p>
                    @endforelse
                </fieldset>
                <fieldset>
                    <legend class="mb-2 font-label-lg text-label-lg">Status mahasiswa yang disinkronkan</legend>
                    @foreach (\App\Services\SinkronSiska::STATUS as $s)
                        <label class="flex items-center gap-2 py-1 font-body-md text-body-md"><input type="checkbox" name="status[]" value="{{ $s }}" @checked(in_array($s, old('status', $statusDipilih), true)) class="rounded border-outline-variant text-primary focus:ring-primary">{{ $s }}</label>
                    @endforeach
                    @error('status')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                </fieldset>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-2">
            <x-tombol type="submit" ikon="save">Simpan</x-tombol>
            @if ($url && $adaKunci)<x-tombol type="submit" varian="sekunder" ikon="wifi_tethering" form="uji-siska">Uji koneksi</x-tombol>@endif
        </div>
    </form>
    <form id="uji-siska" method="post" action="{{ route('master.siska.uji') }}">@csrf</form>
</div>
</x-layouts::app>
