@php
    $j = $jenis;
    $peran = ['admin_tu' => 'Admin Tata Usaha', 'kaprodi' => 'Ketua Program Studi', 'wakil_dekan' => 'Wakil Dekan'];
    $alurTeks = array_values(array_filter([
        ['Pengajuan Mahasiswa', 'Pengisian formulir & kelengkapan berkas daring.'],
        ['Verifikasi '.($peran[$j->verifikator_role] ?? 'Petugas'), 'Pemeriksaan keaslian berkas dan kelayakan data.'],
        $j->perlu_paraf ? ['Paraf '.($peran[$j->paraf_role] ?? 'Pejabat'), 'Persetujuan draf surat oleh pimpinan.'] : null,
        ['Tanda Tangan '.($j->penandatanganJabatan?->nama ?? 'Dekan'), 'Penandatanganan elektronik dengan kode QR.'],
        ['Surat Terbit & Unduh Mandiri', 'Notifikasi masuk dan dokumen PDF siap diunduh.'],
    ]));
    $sla = $j->sla_hari <= 1 ? '1 Hari Kerja' : '1–'.$j->sla_hari.' Hari Kerja';
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant" aria-label="Breadcrumb">
            <a href="{{ route('dasbor') }}" class="inline-flex items-center gap-1 hover:text-primary"><x-ikon name="home" class="text-[14px]" />Beranda</a><x-ikon name="chevron_right" class="text-[14px]" />
            <a href="{{ route('layanan.katalog') }}" class="hover:text-primary">Pengajuan Surat</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="text-on-surface">{{ $j->nama }}</span>
        </nav>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <h1 class="font-headline-xl-mobile text-headline-xl-mobile text-on-surface lg:font-headline-xl lg:text-headline-xl">Permohonan {{ $j->nama }}</h1>
                <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Silakan lengkapi formulir administrasi di bawah ini untuk menerbitkan {{ mb_strtolower($j->nama) }}.</p>
            </div>
            <div class="flex flex-wrap gap-2 lg:flex-col lg:items-end">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 font-label-sm text-label-sm text-on-surface"><x-ikon name="schedule" class="text-[14px]" />Estimasi: {{ $sla }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 font-label-sm text-label-sm text-primary"><x-ikon name="{{ $j->mode_ttd === 'basah' ? 'print' : 'verified' }}" class="text-[14px]" />{{ $j->mode_ttd === 'basah' ? 'Surat Cetak (Tanda Tangan Basah & Cap)' : 'Tanda Tangan Digital Resmi (QR Code)' }}</span>
            </div>
        </div>
    </div>

    <x-kartu padding="p-space-md lg:p-space-lg">
        <x-stepper :aktif="$langkah" :langkah="[
            ['judul' => 'Data '.\Illuminate\Support\Str::of($j->kategori ?? 'Permohonan')->limit(14, ''), 'deskripsi' => 'Isian formulir surat'],
            ['judul' => 'Berkas Persyaratan', 'deskripsi' => 'Unggah dokumen pendukung'],
            ['judul' => 'Ringkasan & Kirim', 'deskripsi' => 'Verifikasi akhir'],
        ]" />
    </x-kartu>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- LANGKAH 1 --}}
            <div class="space-y-6" @if ($langkah !== 1) hidden @endif>
                <x-kartu judul="Data Akademik Pemohon" ikon="badge">
                    <x-slot:aksi><span class="rounded-full bg-primary-fixed px-2.5 py-1 font-label-sm text-label-sm text-primary">Dari profil akun</span></x-slot:aksi>
                    <dl class="grid grid-cols-2 gap-4 rounded-lg bg-surface-container-low p-4 md:grid-cols-4">
                        <div><dt class="font-label-sm text-label-sm text-on-surface-variant">Nama Mahasiswa</dt><dd class="mt-0.5 font-label-lg text-label-lg">{{ $user->nama }}</dd></div>
                        <div><dt class="font-label-sm text-label-sm text-on-surface-variant">Nomor Induk Mahasiswa (NIM)</dt><dd class="mt-0.5 font-label-lg text-label-lg tabular">{{ $user->nomor_induk }}</dd></div>
                        <div><dt class="font-label-sm text-label-sm text-on-surface-variant">Program Studi</dt><dd class="mt-0.5 font-label-lg text-label-lg">{{ $user->prodi?->nama }} ({{ $user->prodi?->jenjang }})</dd></div>
                        <div><dt class="font-label-sm text-label-sm text-on-surface-variant">Angkatan</dt><dd class="mt-0.5 font-label-lg text-label-lg">{{ $user->angkatan ?: '-' }}</dd></div>
                    </dl>
                    <p class="mt-3 flex items-center gap-1.5 font-body-sm text-body-sm text-on-surface-variant"><x-ikon name="info" class="text-[16px]" />Data profil diambil otomatis dari akun Anda. Bila ada kesalahan, hubungi Tata Usaha.</p>
                </x-kartu>

                <x-kartu :judul="'Data Detail '.$j->nama" :ikon="$j->ikon" deskripsi="Informasi ini akan dicetak langsung ke dalam naskah resmi surat.">
                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        @foreach ($j->field_formulir as $f)
                            @php $n = 'isian.'.$f['nama']; $setengah = ($f['lebar'] ?? null) === 'setengah'; @endphp
                            <div class="{{ $setengah ? '' : 'md:col-span-2' }}">
                                @if ($f['tipe'] === 'area')
                                    <x-textarea :label="$f['label']" :name="$n" :wajib="$f['wajib'] ?? false" :maks="$f['maks'] ?? 250" rows="3" wire:model.blur="{{ $n }}" :placeholder="$f['placeholder'] ?? ''" />
                                @elseif ($f['tipe'] === 'pilihan')
                                    <x-select :label="$f['label']" :name="$n" :wajib="$f['wajib'] ?? false" wire:model.live="{{ $n }}">
                                        <option value="">Pilih…</option>@foreach ($f['opsi'] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                    </x-select>
                                @else
                                    <x-input :label="$f['label']" :name="$n" :wajib="$f['wajib'] ?? false" wire:model.blur="{{ $n }}"
                                        :type="['tanggal' => 'date', 'angka' => 'number'][$f['tipe']] ?? 'text'" :placeholder="$f['placeholder'] ?? ''" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-kartu>
            </div>

            {{-- LANGKAH 2 --}}
            <div @if ($langkah !== 2) hidden @endif>
                <x-kartu judul="Unggah Berkas Persyaratan" ikon="attach_file" deskripsi="Format: PDF, JPG, atau PNG — maksimal 2 MB per berkas.">
                    @php $wajib = collect($j->syarat)->where('wajib', true)->count(); $opsi = count($j->syarat ?? []) - $wajib; @endphp
                    <x-slot:aksi><span class="rounded-full bg-surface-container px-2.5 py-1 font-label-sm text-label-sm">{{ $wajib }} Wajib{{ $opsi ? ", $opsi Opsional" : '' }}</span></x-slot:aksi>
                    <div class="space-y-5">
                        @forelse ($j->syarat ?? [] as $i => $s)
                            <div wire:key="syarat-{{ $i }}">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="font-label-lg text-label-lg">{{ $i + 1 }}. {{ $s['label'] }}</span>
                                    <span class="rounded-full px-2 py-0.5 font-label-sm text-label-sm {{ ($s['wajib'] ?? false) ? 'bg-primary-fixed text-primary' : 'bg-surface-container text-on-surface-variant' }}">{{ ($s['wajib'] ?? false) ? 'Wajib' : 'Opsional' }}</span>
                                </div>
                                @if (! empty($berkas[$i]))
                                    <div class="flex items-center gap-3 rounded-lg bg-primary-container/10 p-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-container text-on-primary"><x-ikon name="picture_as_pdf" class="text-[22px]" /></span>
                                        <div class="min-w-0 flex-1"><p class="truncate font-label-md text-label-md">{{ $berkas[$i]->getClientOriginalName() }}</p>
                                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ number_format($berkas[$i]->getSize() / 1048576, 2, ',', '.') }} MB • <span class="font-semibold text-primary">Berhasil diunggah</span></p></div>
                                        <button type="button" wire:click="hapusBerkas({{ $i }})" class="rounded-lg p-2 text-error hover:bg-error-container/50" aria-label="Hapus berkas"><x-ikon name="delete" class="text-[20px]" /></button>
                                    </div>
                                @else
                                    <label class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border-2 border-dashed border-outline-variant bg-surface-container-low/50 px-4 py-6 text-center transition hover:border-primary-container hover:bg-surface-container-low">
                                        <x-ikon name="cloud_upload" class="text-[28px] text-outline" />
                                        <span class="font-label-md text-label-md">Tarik & lepas berkas di sini, atau <span class="text-primary underline">Pilih Berkas</span></span>
                                        <span class="font-body-sm text-body-sm text-on-surface-variant">PDF, JPG, atau PNG maks. 2 MB</span>
                                        <input type="file" wire:model="berkas.{{ $i }}" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                                        <span wire:loading wire:target="berkas.{{ $i }}" class="font-label-sm text-label-sm text-primary">Mengunggah…</span>
                                    </label>
                                @endif
                                @error("berkas.$i")<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                            </div>
                        @empty
                            <p class="text-on-surface-variant">Surat ini tidak memerlukan berkas pendukung.</p>
                        @endforelse
                    </div>
                </x-kartu>
            </div>

            {{-- LANGKAH 3 --}}
            <div class="space-y-6" @if ($langkah !== 3) hidden @endif>
                <x-kartu judul="Ringkasan Permohonan" ikon="fact_check" deskripsi="Periksa kembali data sebelum dikirim. Data tidak dapat diubah setelah dikirim.">
                    <dl class="divide-y divide-surface-container">
                        @foreach ($j->field_formulir as $f)
                            <div class="grid gap-1 py-2.5 sm:grid-cols-3"><dt class="font-label-md text-label-md text-on-surface-variant">{{ $f['label'] }}</dt><dd class="font-body-md text-body-md sm:col-span-2">{{ ($isian[$f['nama']] ?? '') !== '' ? ($f['tipe'] === 'tanggal' ? \Illuminate\Support\Carbon::parse($isian[$f['nama']])->translatedFormat('j F Y') : $isian[$f['nama']]) : '-' }}</dd></div>
                        @endforeach
                        @foreach ($j->syarat ?? [] as $i => $s)
                            <div class="grid gap-1 py-2.5 sm:grid-cols-3"><dt class="font-label-md text-label-md text-on-surface-variant">{{ $s['label'] }}</dt>
                                <dd class="font-body-md text-body-md sm:col-span-2">{{ ! empty($berkas[$i]) ? $berkas[$i]->getClientOriginalName() : '— tidak diunggah' }}</dd></div>
                        @endforeach
                    </dl>
                    <label class="mt-4 flex cursor-pointer items-start gap-2 rounded-lg bg-surface-container-low p-3">
                        <input type="checkbox" wire:model.live="setuju" class="mt-0.5 h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">
                        <span class="font-body-sm text-body-sm">Saya menyatakan bahwa data dan berkas yang saya kirim benar dan dapat dipertanggungjawabkan.</span>
                    </label>
                    @error('setuju')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                </x-kartu>
            </div>

            {{-- Tombol navigasi --}}
            <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low p-space-md">
                @if ($langkah === 1)
                    <x-tombol :href="route('layanan.katalog')" varian="lembut" ikon="arrow_back">Kembali ke Katalog</x-tombol>
                @else
                    <x-tombol wire:click="kembali" varian="lembut" ikon="arrow_back">Kembali</x-tombol>
                @endif
                @if ($langkah < 3)
                    <x-tombol wire:click="lanjut" ikon-kanan="arrow_forward" class="min-w-44">{{ $langkah === 1 ? 'Lanjut ke Berkas' : 'Lanjut ke Konfirmasi' }}</x-tombol>
                @else
                    <x-tombol wire:click="kirim" wire:loading.attr="disabled" ikon="send" class="min-w-44">Kirim Pengajuan</x-tombol>
                @endif
            </div>
        </div>

        {{-- Panel samping --}}
        <aside class="space-y-6">
            <x-kartu judul="Ketentuan Pengajuan" deskripsi="Pedoman administrasi FT-UMB" ikon="gavel">
                <ul class="space-y-3 font-body-sm text-body-sm text-on-surface-variant">
                    @foreach ([
                        'Pastikan data profil dan isian formulir benar; isian dicetak apa adanya pada surat.',
                        'Berkas persyaratan berformat PDF/JPG/PNG, maksimal 2 MB per berkas.',
                        $j->mode_ttd === 'basah'
                            ? 'Surat disetujui oleh '.($j->penandatanganJabatan?->nama ?? 'Dekan').' lalu dicetak, ditandatangani basah, dan dicap di Tata Usaha.'
                            : 'Surat ditandatangani oleh '.($j->penandatanganJabatan?->nama ?? 'Dekan').' dengan tanda tangan elektronik (QR) yang dapat diverifikasi publik.',
                        'Estimasi proses '.$sla.' sejak pengajuan diverifikasi.',
                    ] as $t)
                        <li class="flex gap-2"><x-ikon name="check_circle" class="mt-0.5 shrink-0 text-[18px] text-primary" /><span>{{ $t }}</span></li>
                    @endforeach
                </ul>
            </x-kartu>
            <x-kartu judul="Alur Verifikasi Surat" deskripsi="Tahapan pemrosesan berkas" ikon="alt_route">
                <x-garis-waktu>
                    @foreach ($alurTeks as $k => [$jd, $ds])
                        <x-garis-waktu.butir :status="$k === 0 ? 'selesai' : 'menunggu'"><p class="font-label-lg text-label-lg">{{ $k + 1 }}. {{ $jd }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $ds }}</p></x-garis-waktu.butir>
                    @endforeach
                </x-garis-waktu>
            </x-kartu>
            <x-kartu judul="Butuh Bantuan TU?" deskripsi="Pelayanan Administrasi FT-UMB" ikon="support_agent">
                <p class="font-body-sm text-body-sm text-on-surface-variant">Jika terdapat ketidaksesuaian data atau kendala dalam pengajuan surat, hubungi front desk kami.</p>
                <p class="mt-3 flex items-center gap-2 font-body-sm text-body-sm"><x-ikon name="schedule" class="text-[18px] text-primary" />Senin – Jumat, 08.00 – 15.30 WITA</p>
            </x-kartu>
        </aside>
    </div>
</div>
