<x-layouts::app title="Impor Pengguna (CSV)" :cari="false">
<div class="mx-auto max-w-6xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Master Data</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Tambah banyak pengguna sekaligus dari berkas CSV.</p>
    @include('master._tab')
    <nav class="mb-4 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('master.daftar', 'pengguna') }}" class="hover:text-primary">Pengguna</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="text-on-surface">Impor CSV</span></nav>

    <div class="grid gap-5 lg:grid-cols-5">
        <div class="space-y-5 lg:col-span-2">
            <x-kartu judul="1. Unduh templat" ikon="file_download" deskripsi="Isi templat di Excel/Google Sheets, lalu simpan sebagai CSV.">
                <x-tombol :href="route('master.impor.templat')" ikon="file_download" class="w-full">Unduh Templat CSV</x-tombol>
                <ul class="mt-3 list-disc space-y-1 pl-5 font-body-sm text-body-sm text-on-surface-variant">
                    <li>Baris pertama = <strong>nama kolom</strong> (jangan diubah).</li>
                    <li>Excel: <em>Save As → CSV UTF-8 (Comma delimited)</em>. Pemisah <code>,</code> atau <code>;</code> dikenali otomatis.</li>
                    <li>Maksimal <strong>{{ $maks }} baris</strong> dan 1 MB per berkas.</li>
                </ul>
            </x-kartu>
            <x-kartu judul="2. Unggah & periksa" ikon="upload_file" deskripsi="Berkas diperiksa dulu; belum ada data yang disimpan.">
                <form method="post" action="{{ route('master.impor.periksa') }}" enctype="multipart/form-data" class="space-y-3">@csrf
                    <input type="file" name="berkas" accept=".csv,.txt,text/csv" required class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:border-0 file:bg-surface-container-high file:px-4 file:py-2.5 file:font-label-md">
                    @error('berkas')<p class="font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm"><input type="checkbox" name="perbarui" value="1" class="mt-0.5 h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"><span><strong>Perbarui</strong> pengguna yang nomor induknya sudah ada. <span class="text-on-surface-variant">Bila tidak dicentang, baris tersebut dilewati.</span></span></label>
                    <x-tombol type="submit" ikon="fact_check" class="w-full">Periksa Berkas</x-tombol>
                </form>
            </x-kartu>
        </div>

        <x-kartu judul="Format kolom CSV" ikon="table_view" class="lg:col-span-3">
            <div class="overflow-x-auto scroll-tipis">
                <table class="w-full min-w-[520px] text-left font-body-sm text-body-sm">
                    <thead class="bg-surface-container-low font-label-md text-label-md uppercase text-on-surface-variant"><tr><th class="px-3 py-2">Kolom</th><th>Wajib</th><th>Isi</th></tr></thead>
                    <tbody class="divide-y divide-surface-container align-top">
                        @foreach ([
                            ['nomor_induk', true, 'NPM / NIDN (huruf, angka, titik, strip; maks 30). Dipakai untuk login. Harus unik.'],
                            ['nama', true, 'Nama tanpa gelar.'],
                            ['peran', true, 'Satu atau lebih, dipisah <code>|</code>. Contoh: <code>dosen_tendik|kaprodi</code>.'],
                            ['prodi', false, 'Kode prodi: '.$prodi->map(fn ($p) => '<code>'.e($p->kode).'</code> ('.e($p->nama).')')->implode(', ').'.'],
                            ['email', false, 'Alamat email (unik) untuk notifikasi.'],
                            ['no_hp', false, 'Nomor HP, mis. 081234567890.'],
                            ['angkatan', false, '4 angka, mis. 2022 (mahasiswa).'],
                            ['tempat_lahir', false, 'Kota kelahiran.'],
                            ['tanggal_lahir', false, '<code>2003-08-17</code> atau <code>17/08/2003</code>.'],
                            ['alamat', false, 'Alamat lengkap (maks 300 karakter).'],
                            ['gelar_depan / gelar_belakang', false, 'Mis. <code>Dr.</code> dan <code>S.T., M.T.</code>'],
                            ['password', false, 'Minimal 8 karakter. <strong>Kosong → dibuatkan kata sandi acak</strong> yang ditampilkan SEKALI setelah impor.'],
                            ['aktif', false, '<code>ya</code> / <code>tidak</code> (kosong = ya).'],
                        ] as [$k, $w, $i])
                            <tr><td class="px-3 py-2 font-mono text-[12px] font-semibold">{{ $k }}</td><td class="py-2">@if ($w)<span class="rounded-full bg-error-container px-2 py-0.5 font-label-sm text-label-sm text-on-error-container">Wajib</span>@else<span class="text-on-surface-variant">opsional</span>@endif</td><td class="py-2 pr-3">{!! $i !!}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-4 font-label-lg text-label-lg">Nilai kolom <code>peran</code>:</p>
            <div class="mt-1.5 flex flex-wrap gap-1.5">@foreach ($peran as $p)@continue($p->value === 'super_admin' && ! $superAdmin)<span class="rounded-full bg-surface-container px-2.5 py-1 font-mono text-[12px]" title="{{ $p->label() }}">{{ $p->value }}</span>@endforeach</div>
            @unless ($superAdmin)<p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">Peran <code>super_admin</code> hanya dapat diberikan oleh Super Admin.</p>@endunless
            <p class="mb-1 mt-4 font-label-lg text-label-lg">Contoh isi berkas:</p>
            <pre class="overflow-x-auto rounded-lg bg-inverse-surface p-3 font-mono text-[11px] leading-relaxed text-inverse-on-surface scroll-tipis">nomor_induk,nama,peran,prodi,email,no_hp,angkatan,tempat_lahir,tanggal_lahir,alamat,gelar_depan,gelar_belakang,password,aktif
22650101,Ahmad Fauzi,mahasiswa,STI,ahmad.fauzi@mhs.umbuton.ac.id,081234567890,2022,Baubau,2003-08-17,"Jl. Betoambari No. 5, Baubau",,,,ya
0912099001,Siti Aisyah,dosen_tendik,TS,siti.aisyah@umbuton.ac.id,,,Makassar,1985-02-11,,,"S.T., M.T.",,ya
0912099002,Budi Santoso,dosen_tendik|kaprodi,RSK,,,,,,,,"S.Kom., M.Kom.",,ya</pre>
        </x-kartu>
    </div>
</div>
</x-layouts::app>
