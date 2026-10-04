<x-layouts::guest :title="$kode.' '.$judul" :tanpa-livewire="true">
<main class="flex min-h-screen items-center justify-center bg-surface px-4 font-jakarta">
    <section class="w-full max-w-lg rounded-xl bg-surface-container-lowest p-8 text-center shadow-md">
        <x-logo ukuran="h-14" class="mx-auto" />
        <p class="mt-5 font-headline-xl text-headline-xl text-primary tabular">{{ $kode }}</p>
        <h1 class="mt-1 font-headline-lg text-headline-lg text-on-surface">{{ $judul }}</h1>
        <p class="mt-2 font-body-md text-body-md text-on-surface-variant">{{ $pesan }}</p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @auth<x-tombol :href="route('dasbor')" ikon="home">Ke Dasbor</x-tombol>@else<x-tombol :href="route('login')" ikon="login">Ke Halaman Masuk</x-tombol>@endauth
            <x-tombol varian="sekunder" onclick="history.back()" ikon="arrow_back">Kembali</x-tombol>
        </div>
    </section>
</main>
</x-layouts::guest>
