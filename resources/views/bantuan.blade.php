<x-layouts::guest title="Bantuan">
    <main class="mx-auto max-w-xl px-space-md py-16">
        <x-logo ukuran="h-14" />
        <h1 class="mt-space-md font-headline-lg text-headline-lg">Bantuan Akses SIPERSU</h1>
        <p class="mt-space-sm text-on-surface-variant">Jika Anda lupa kata sandi atau belum memiliki akun, hubungi Tata Usaha Fakultas Teknik UM Buton pada jam kerja (Senin–Jumat, 08.00–15.30 WITA).</p>
        <x-tombol :href="route('login')" varian="sekunder" ikon="arrow_back" class="mt-space-lg">Kembali ke halaman masuk</x-tombol>
    </main>
</x-layouts::guest>
