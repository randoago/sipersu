<main class="flex min-h-screen w-full flex-col font-jakarta lg:flex-row" x-data="{ peran: 'mahasiswa', lihat: false }">
    {{-- PANEL KIRI: branding --}}
    <div class="relative flex flex-col justify-between overflow-hidden bg-umb-green p-8 text-white sm:p-12 lg:w-1/2 lg:p-16">
        <div class="pola-hero pointer-events-none absolute inset-0 opacity-60"></div>
        <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-emerald-600/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 -right-28 h-96 w-96 rounded-full bg-umb-gold/15 blur-3xl"></div>

        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/10 p-1.5 shadow-lg backdrop-blur-md">
                    <img src="{{ asset('images/logo-umb.png') }}" alt="Logo UM Buton" class="h-full w-full object-contain">
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-widest text-umb-gold">Portal Resmi</span>
                    <span class="text-sm font-bold tracking-tight text-white/90">SIPERSU FT-UMB</span>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-medium text-white/90 backdrop-blur-sm">
                <span class="mr-2 h-2 w-2 animate-pulse rounded-full bg-umb-gold"></span>Tahun Ajaran {{ $tahunAkademik }}
            </span>
        </div>

        <div class="relative z-10 my-12 max-w-lg lg:my-auto">
            <div class="mb-6 inline-flex items-center space-x-2 rounded-lg border border-umb-gold/30 bg-emerald-950/40 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-umb-gold">
                <x-ikon name="verified_user" class="text-base" /><span>Layanan Administrasi Digital Terpadu</span>
            </div>
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                Sistem Informasi <br>
                <span class="bg-gradient-to-r from-amber-300 via-umb-gold to-amber-200 bg-clip-text text-transparent">Persuratan</span>
            </h1>
            <p class="mt-4 text-base font-medium leading-relaxed text-emerald-100/90 sm:text-lg">Fakultas Teknik Universitas Muhammadiyah Buton</p>
            <p class="mt-3 text-sm leading-relaxed text-emerald-200/75">Platform digital pengelolaan surat masuk, surat keluar, disposisi, pengajuan surat aktif kuliah, pengantar kerja praktik, hingga tugas akhir secara efisien, transparan, dan terarsip otomatis.</p>
            <div class="mt-8 grid grid-cols-2 gap-3 border-t border-emerald-500/25 pt-6">
                @foreach ([['Tanda Tangan Elektronik', 'Validasi QR Code resmi'], ['Disposisi Instan', 'Notifikasi berjenjang real-time']] as [$j, $d])
                    <div class="flex items-start space-x-2.5">
                        <div class="mt-0.5 rounded-md bg-umb-gold/20 p-1 text-umb-gold"><x-ikon name="check" class="text-sm" /></div>
                        <div><p class="text-xs font-semibold text-white">{{ $j }}</p><p class="text-[11px] text-emerald-200/70">{{ $d }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative z-10 flex flex-col items-start justify-between gap-2 border-t border-emerald-500/20 pt-6 text-xs text-emerald-200/70 sm:flex-row sm:items-center">
            <div class="flex items-center space-x-2"><x-ikon name="location_on" class="text-sm text-umb-gold" /><span>Kampus FT-UMB, Jl. Betoambari No. 36, Baubau, Sulawesi Tenggara</span></div>
            <div class="font-mono text-[11px] text-emerald-300/80">v{{ config('app.versi') }}</div>
        </div>
    </div>

    {{-- PANEL KANAN: formulir --}}
    <div class="flex items-center justify-center bg-white p-6 sm:p-12 lg:w-1/2 lg:p-16">
        <div class="w-full max-w-md">
            <div class="mb-8 text-left">
                <div class="mb-6 flex items-center space-x-3 border-b border-gray-100 pb-4 lg:hidden">
                    <img src="{{ asset('images/logo-umb.png') }}" alt="Logo UM Buton" class="h-10 w-10 object-contain">
                    <div><div class="text-sm font-bold text-gray-900">SIPERSU FT-UMB</div><div class="text-xs text-gray-500">Universitas Muhammadiyah Buton</div></div>
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Selamat Datang Kembali</h2>
                <p class="mt-2 text-sm text-gray-500">Silakan masukkan identitas akun akademik Anda untuk masuk ke sistem persuratan.</p>
            </div>

            {{-- Petunjuk peran (hanya mengubah contoh isian; login tetap satu formulir) --}}
            <div class="mb-6 flex space-x-1 rounded-lg border border-gray-200/70 bg-gray-100 p-1" role="tablist">
                @foreach (['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'staf' => 'Staf TU / Admin'] as $k => $l)
                    <button type="button" @click="peran = '{{ $k }}'" :class="peran === '{{ $k }}' ? 'bg-white text-umb-green shadow-sm border border-gray-200/50 font-semibold' : 'text-gray-600 hover:text-gray-900 font-medium'" class="flex-1 rounded-md py-2 text-xs transition">{{ $l }}</button>
                @endforeach
            </div>

            <form wire:submit="masuk" class="space-y-5" novalidate>
                <div>
                    <label for="nomor_induk" class="mb-1.5 block text-sm font-semibold text-gray-700">NPM / NIDN <span class="text-rose-500">*</span></label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"><x-ikon name="person" class="text-[18px]" /></div>
                        <input type="text" id="nomor_induk" wire:model="nomor_induk" autocomplete="username" autofocus inputmode="numeric"
                               :placeholder="{ mahasiswa: 'Contoh: 21650012', dosen: 'Contoh: 0912038401', staf: 'Contoh: 198701012010011001' }[peran]"
                               class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 transition focus:border-umb-green focus:outline-none focus:ring-2 focus:ring-umb-green @error('nomor_induk') border-rose-400 @enderror">
                    </div>
                    @error('nomor_induk')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @else<p class="mt-1 text-[11px] text-gray-400">Gunakan NPM untuk mahasiswa dan NIDN untuk dosen/pejabat.</p>@enderror
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-gray-700">Kata Sandi <span class="text-rose-500">*</span></label>
                        <span class="text-xs text-gray-400">Lupa kata sandi? Hubungi Tata Usaha.</span>
                    </div>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"><x-ikon name="lock" class="text-[18px]" /></div>
                        <input :type="lihat ? 'text' : 'password'" id="password" wire:model="password" autocomplete="current-password" placeholder="Masukkan kata sandi akun"
                               class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 transition focus:border-umb-green focus:outline-none focus:ring-2 focus:ring-umb-green">
                        <button type="button" @click="lihat = !lihat" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 focus:outline-none" aria-label="Tampilkan kata sandi">
                            <x-ikon name="visibility" class="text-[18px]" x-show="!lihat" /><x-ikon name="visibility_off" class="text-[18px]" x-show="lihat" x-cloak />
                        </button>
                    </div>
                    @error('password')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center pt-1">
                    <input id="ingat" type="checkbox" wire:model="ingat" class="h-4 w-4 cursor-pointer rounded border-gray-300 text-umb-green focus:ring-umb-green">
                    <label for="ingat" class="ml-2 block cursor-pointer select-none text-xs text-gray-600">Ingat saya di perangkat ini selama 30 hari</label>
                </div>

                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="flex w-full items-center justify-center rounded-lg border border-transparent bg-umb-green px-4 py-3 text-sm font-bold text-white shadow-md transition duration-150 hover:bg-umb-green-dark focus:outline-none focus:ring-2 focus:ring-umb-green focus:ring-offset-2 active:bg-[#083a21] disabled:opacity-70">
                        <span wire:loading.remove>Masuk ke SIPERSU</span><span wire:loading>Memeriksa…</span>
                        <x-ikon name="arrow_forward" class="ml-2 text-base" wire:loading.remove />
                    </button>
                </div>
            </form>

            <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-6 text-xs text-gray-500">
                <div class="flex items-center space-x-1.5"><x-ikon name="help" class="text-base text-umb-green" /><span>Butuh bantuan akses?</span></div>
                <a href="{{ route('bantuan') }}" class="font-semibold text-umb-green hover:underline">Hubungi Tata Usaha FT</a>
            </div>
            <p class="mt-6 text-center text-[11px] text-gray-400">&copy; {{ now()->year }} Fakultas Teknik Universitas Muhammadiyah Buton. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</main>
