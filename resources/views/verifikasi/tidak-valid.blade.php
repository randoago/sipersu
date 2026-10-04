<x-layouts::guest title="Dokumen Tidak Ditemukan" :tanpa-livewire="true">
<main class="flex min-h-screen items-center justify-center bg-surface px-4">
    <section class="w-full max-w-lg rounded-xl bg-surface-container-lowest p-8 text-center shadow-md">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-error-container text-error"><x-ikon name="cancel" filled class="text-4xl" /></div>
        <span class="mb-2 inline-block rounded-full bg-error px-3 py-1 font-label-md text-label-md uppercase text-on-error">Tidak Valid</span>
        <h1 class="font-headline-lg text-headline-lg text-error">Dokumen Tidak Ditemukan</h1>
        <p class="mt-2 font-body-md text-body-md text-on-surface-variant">Kode verifikasi ini tidak terdaftar pada SIPERSU FT-UMB. Dokumen kemungkinan palsu atau QR rusak. Hubungi Tata Usaha Fakultas Teknik UM Buton.</p>
    </section>
</main>
</x-layouts::guest>
