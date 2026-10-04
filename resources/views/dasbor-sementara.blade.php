<x-layouts::app title="Dasbor">
    <h1 class="font-headline-xl text-headline-xl">Dasbor</h1>
    <div class="mt-space-lg grid grid-cols-1 gap-gutter sm:grid-cols-2 xl:grid-cols-4">
        <x-kartu-statistik label="Surat Masuk Bulan Ini" nilai="142" ikon="move_to_inbox" catatan="+12% dibanding bulan lalu" catatan-ikon="trending_up" />
        <x-kartu-statistik label="Surat Keluar Bulan Ini" nilai="89" ikon="outbox" warna="biru" catatan="Terakhir: 089/FT-UMB/B/II/…" />
        <x-kartu-statistik label="Menunggu Verifikasi" nilai="18" ikon="fact_check" warna="emas" catatan="5 permohonan prioritas tinggi" catatan-ikon="priority_high" />
        <x-kartu-statistik label="Menunggu Tanda Tangan" nilai="7" satuan="surat" ikon="draw" warna="ungu" />
    </div>
    <div class="mt-space-lg flex flex-wrap gap-2">
        @foreach (\App\Enums\StatusPengajuan::cases() as $s)<x-lencana-status :status="$s" />@endforeach
    </div>
    <div class="mt-space-lg flex flex-wrap gap-2">
        <x-tombol ikon="add">Utama</x-tombol><x-tombol varian="sekunder">Sekunder</x-tombol><x-tombol varian="aksen">Aksen</x-tombol><x-tombol varian="bahaya" ikon="close">Tolak</x-tombol>
        <x-tombol varian="sekunder" x-on:click="$dispatch('buka-modal','contoh')">Buka Modal</x-tombol>
    </div>
    <x-modal nama="contoh" judul="Tolak Pengajuan"><p>Isi modal.</p><x-slot:kaki><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol></x-slot:kaki></x-modal>
    <x-stepper class="mt-space-lg" :aktif="2" :langkah="[['judul'=>'Data Penelitian','deskripsi'=>'Detail tujuan'],['judul'=>'Berkas','deskripsi'=>'Unggah KTM'],['judul'=>'Kirim','deskripsi'=>'Verifikasi']]" />
    <x-kartu class="mt-space-lg" judul="Garis Waktu" ikon="timeline">
        <x-garis-waktu><x-garis-waktu.butir>Diajukan</x-garis-waktu.butir><x-garis-waktu.butir status="sekarang">Verifikasi</x-garis-waktu.butir><x-garis-waktu.butir status="menunggu">TTD</x-garis-waktu.butir></x-garis-waktu>
    </x-kartu>
    <x-peringatan class="mt-space-lg" jenis="peringatan" judul="Backup terakhir lebih dari 2 hari" tutup>Colokkan HDD eksternal.</x-peringatan>
    <x-tabel class="mt-space-lg" :jumlah="0"><x-slot:kepala><th class="px-4">No</th><th>Perihal</th></x-slot:kepala></x-tabel>
</x-layouts::app>
