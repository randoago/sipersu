<x-layouts::app title="Profil Saya" :cari="false">
<div class="mx-auto max-w-4xl space-y-6">
    <h1 class="font-headline-xl text-headline-xl">Profil Saya</h1>
    <x-kartu>
        <div class="flex items-center gap-4">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-container font-headline-md text-headline-md text-on-primary-container">{{ $u->inisial() }}</span>
            <div><p class="font-headline-md text-headline-md">{{ $u->namaLengkap() }}</p><p class="font-body-md text-body-md text-on-surface-variant tabular">{{ $u->nomor_induk }}</p>
                <p class="mt-1 flex flex-wrap gap-1.5">@foreach ($u->roles as $r)<span class="rounded-full bg-primary-fixed px-2.5 py-0.5 font-label-sm text-label-sm text-primary">{{ \App\Enums\Peran::tryFrom($r->name)?->label() ?? $r->name }}</span>@endforeach</p></div>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-3">
            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Program Studi</dt><dd class="font-body-md text-body-md">{{ $u->prodi?->nama ?? '-' }}</dd></div>
            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Email</dt><dd class="font-body-md text-body-md">{{ $u->email ?? '-' }}</dd></div>
            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">No. HP</dt><dd class="font-body-md text-body-md">{{ $u->no_hp ?? '-' }}</dd></div>
        </dl>
        <p class="mt-4 font-body-sm text-body-sm text-on-surface-variant">Untuk mengubah data identitas, hubungi Tata Usaha.</p>
    </x-kartu>

    @if ($pejabat)
        <x-kartu judul="Spesimen Tanda Tangan & Stempel" ikon="draw" deskripsi="Dicetak pada surat ber-QR yang Anda tandatangani, di samping kode QR. Surat tanpa QR dibiarkan kosong.">
            <div class="grid gap-5 md:grid-cols-2">
                @foreach (['stempel' => ['Tanda tangan + stempel', 'Dipakai pada surat ber-QR (disarankan). Bila belum diunggah, dipakai spesimen tanpa stempel.', $u->spesimen_stempel], 'ttd' => ['Tanda tangan saja (tanpa stempel)', 'Cadangan bila spesimen berstempel belum ada.', $u->spesimen_ttd]] as $jenis => [$judul, $ket, $ada])
                    <div class="space-y-3 rounded-lg border border-outline-variant/60 p-space-md">
                        <div><p class="font-label-lg text-label-lg">{{ $judul }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $ket }}</p></div>
                        <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-outline-variant bg-white p-2">
                            @if ($ada)<img src="{{ route('profil.spesimen.lihat', ['jenis' => $jenis]) }}&v={{ md5($ada) }}" alt="Spesimen {{ $judul }}" class="max-h-full max-w-full object-contain">@else<span class="text-center font-body-sm text-body-sm text-on-surface-variant">Belum ada spesimen</span>@endif
                        </div>
                        <form method="post" action="{{ route('profil.spesimen') }}" enctype="multipart/form-data" class="space-y-2">@csrf
                            <input type="hidden" name="jenis" value="{{ $jenis }}">
                            <input type="file" name="spesimen" accept="image/png,image/jpeg" required class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:border-0 file:bg-surface-container-high file:px-3 file:py-2 file:font-label-md">
                            @if (old('jenis') === $jenis) @error('spesimen')<p class="font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror @endif
                            <x-tombol type="submit" ukuran="sm" ikon="upload">Unggah</x-tombol>
                        </form>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">PNG (latar transparan disarankan) atau JPG, maks. 2 MB. Disimpan di penyimpanan privat.</p>
        </x-kartu>
    @endif

    <x-kartu judul="Ganti Kata Sandi" ikon="lock">
        <form method="post" action="{{ route('profil.sandi') }}" class="grid gap-4 md:grid-cols-3">@csrf
            <x-input label="Kata sandi lama" name="password_lama" type="password" wajib autocomplete="current-password" />
            <x-input label="Kata sandi baru" name="password" type="password" wajib autocomplete="new-password" bantuan="Minimal 8 karakter, huruf dan angka." />
            <x-input label="Ulangi kata sandi baru" name="password_confirmation" type="password" wajib autocomplete="new-password" />
            <div class="md:col-span-3"><x-tombol type="submit" ikon="save">Simpan Kata Sandi</x-tombol></div>
        </form>
    </x-kartu>
</div>
</x-layouts::app>
