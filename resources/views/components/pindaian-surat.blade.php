{{-- Kolom unggah pindaian surat (opsional) pada Buat Surat dari format yang mengaktifkannya. $surat = draf yang sedang diubah (bila ada). --}}
@props(['surat' => null])
@php $ada = $surat?->lampiran->firstWhere('label', 'Pindaian surat'); @endphp
<x-kartu judul="Pindaian Surat" ikon="attach_file" deskripsi="Opsional — PDF, JPG, atau PNG, maksimal {{ config('sipersu.lampiran.maks_kb') / 1024 }} MB. Disimpan di penyimpanan privat.">
    @if ($ada)<p class="mb-3 flex items-center gap-2 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm"><x-ikon name="picture_as_pdf" class="text-[20px] text-primary" />Berkas saat ini: <a class="font-semibold text-primary underline" target="_blank" href="{{ route('lampiran.unduh', $ada) }}">{{ $ada->nama_asli }}</a> — unggah baru untuk mengganti.</p>@endif
    <input type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:border-0 file:bg-surface-container-high file:px-4 file:py-2.5 file:font-label-md">
    @error('scan')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
</x-kartu>
