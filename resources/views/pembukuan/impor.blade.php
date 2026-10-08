<x-layouts::app title="Impor Pembukuan (CSV)" :cari="false">
<div class="mx-auto max-w-6xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('pembukuan.index') }}" class="hover:text-primary">Pembukuan</a><x-ikon name="chevron_right" class="text-[14px]" /><span>Impor CSV</span></nav>
    <h1 class="mb-1 font-headline-xl text-headline-xl">Impor Pembukuan dari CSV</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Masukkan banyak surat masuk, keluar, atau lainnya sekaligus berdasarkan nomor surat (mis. surat lama sebelum aplikasi dipakai).</p>
    @if (session('galat'))<x-peringatan jenis="bahaya" class="mb-4">{{ session('galat') }}</x-peringatan>@endif
    <div class="grid gap-5 lg:grid-cols-5">
        <div class="space-y-5 lg:col-span-2">
            <x-kartu judul="1. Unduh templat" ikon="download" deskripsi="Isi di Excel/Google Sheets, lalu simpan sebagai CSV.">
                <x-tombol :href="route('pembukuan.impor.templat')" ikon="download" class="w-full">Unduh Templat CSV</x-tombol>
                <ul class="mt-3 list-disc space-y-1 pl-5 font-body-sm text-body-sm text-on-surface-variant">
                    <li>Baris pertama = <strong>nama kolom</strong> (jangan diubah).</li>
                    <li>Excel: <em>Save As → CSV UTF-8 (Comma delimited)</em>. Pemisah <code>,</code> atau <code>;</code> dikenali otomatis.</li>
                    <li>Maksimal <strong>{{ $maks }} baris</strong> dan 2 MB per berkas.</li>
                    <li>Nomor yang sudah ada di pembukuan atau aplikasi <strong>dilewati</strong>, bukan ditimpa.</li>
                </ul>
            </x-kartu>
            <x-kartu judul="2. Unggah & periksa" ikon="upload_file" deskripsi="Diperiksa dulu; belum ada data yang disimpan.">
                <form method="post" action="{{ route('pembukuan.impor.periksa') }}" enctype="multipart/form-data" class="space-y-3">@csrf
                    <input type="file" name="berkas" accept=".csv,.txt,text/csv" required class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:border-0 file:bg-surface-container-high file:px-3 file:py-2 file:font-label-md">
                    @error('berkas')<p class="font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
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
                            ['arah', true, '<code>masuk</code>, <code>keluar</code>, atau <code>lain</code> (SK, nota dinas, dll.).'],
                            ['nomor', true, 'Nomor surat lengkap apa adanya (surat masuk: nomor dari pengirim). Contoh: <code>045/KET/II.3.AU/UMB-06/F/2026</code>.'],
                            ['tanggal_surat', true, '<code>2026-01-05</code> atau <code>05/01/2026</code>.'],
                            ['perihal', true, 'Hal/perihal surat (maks 255 karakter).'],
                            ['pihak', false, 'Surat masuk: asal. Surat keluar: tujuan.'],
                            ['lampiran', false, 'Mis. <code>2 berkas</code>.'],
                            ['sifat', false, '<code>biasa</code> / <code>penting</code> / <code>segera</code> / <code>rahasia</code> (kosong = biasa).'],
                            ['jenis', false, 'Mis. Surat Tugas, SK Dekan, Nota Dinas.'],
                            ['tanggal_diterima', false, 'Surat masuk. Kosong = sama dengan tanggal surat.'],
                            ['no_agenda', false, 'Nomor agenda surat masuk bila sudah ada.'],
                            ['keterangan', false, 'Catatan bebas.'],
                        ] as [$k, $w, $i])
                            <tr><td class="px-3 py-2 font-mono text-[12px] font-semibold">{{ $k }}</td><td class="py-2">@if ($w)<span class="rounded-full bg-error-container px-2 py-0.5 font-label-sm text-label-sm text-on-error-container">Wajib</span>@else<span class="font-label-sm text-label-sm text-on-surface-variant">Opsional</span>@endif</td><td class="py-2 text-on-surface-variant">{!! $i !!}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm"><strong>Penghitung nomor otomatis.</strong> Untuk <code>keluar</code>, bila nomor mengikuti pola penomoran (<code>{{ \App\Models\Pengaturan::ambil('format_nomor', '{urut}/{kekhususan}/II.3.AU/{unit}/{klasifikasi}/{tahun}') }}</code>) Anda dapat menyesuaikan penghitung unit kerja yang tercantum pada nomor sehingga nomor otomatis berikutnya melanjutkan nomor tertinggi di CSV (mis. CSV berakhir 045 → berikutnya 046). Pilihannya muncul di langkah konfirmasi.</div>
        </x-kartu>
    </div>
</div>
</x-layouts::app>
