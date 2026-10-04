@props(['title' => null, 'cari' => true, 'tanpaPadding' => false])
@php
    $user = auth()->user();
    $menu = \App\Support\Menu::untuk($user);
    $mahasiswa = $user->hasRole('mahasiswa') && $user->roles->count() === 1;
    $belumDibaca = $user->notifikasi()->whereNull('dibaca_pada')->count();
    $ta = \App\Support\TahunAkademik::saatIni();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}SIPERSU FT-UMB</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-umb.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    @livewireStyles
</head>
<body class="bg-surface font-body-md text-on-surface antialiased" x-data="{ menuBuka: false }">
    {{-- Sidebar (desktop tetap; <lg menjadi laci) --}}
    <div x-show="menuBuka" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-[#0f172a]/45 lg:hidden" @click="menuBuka = false"></div>
    <aside :class="menuBuka ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed left-0 top-0 z-50 flex h-full w-64 flex-col justify-between bg-surface-container-low shadow-soft transition-transform">
        <div class="flex flex-1 flex-col overflow-y-auto scroll-tipis">
            <a href="{{ route('dasbor') }}" class="flex items-center gap-space-sm p-space-lg">
                <x-logo ukuran="h-9" />
                <div class="flex min-w-0 flex-col">
                    <span class="truncate font-headline-sm text-headline-sm font-bold tracking-tight text-primary">SIPERSU FT-UMB</span>
                    <span class="truncate font-label-sm text-label-sm text-on-surface-variant">Fakultas Teknik – UM Buton</span>
                </div>
            </a>
            <nav class="flex-1 space-y-space-md px-space-sm py-space-xs" aria-label="Menu utama">
                @foreach ($menu as $bagian)
                    <div class="space-y-1">
                        @if ($bagian['judul'])<p class="px-space-md pb-1 pt-2 font-label-sm text-label-sm tracking-wider text-on-surface-variant/80">{{ $bagian['judul'] }}</p>@endif
                        @foreach ($bagian['butir'] as $b)
                            <a href="{{ $b['url'] }}" @if ($b['aktif']) aria-current="page" @endif
                               @class(['flex items-center justify-between rounded-lg px-space-md py-space-sm transition-all',
                                    'bg-primary-container font-semibold text-on-primary-container shadow-sm' => $b['aktif'],
                                    'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' => ! $b['aktif']])>
                                <span class="flex items-center gap-space-sm">
                                    <x-ikon :name="$b['ikon']" class="text-[20px]" />
                                    <span class="font-label-lg text-label-lg">{{ $b['label'] }}</span>
                                </span>
                                @if (! empty($b['badge']))
                                    <span class="rounded-full bg-primary-fixed px-2 py-0.5 font-label-sm text-label-sm text-on-primary-fixed">{{ $b['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </div>
        <div class="m-space-sm rounded-lg bg-surface-container-lowest p-space-md shadow-soft">
            <div class="mb-1.5 flex items-center gap-space-xs">
                <x-ikon name="event_available" class="text-[18px] text-primary" />
                <span class="font-label-sm text-label-sm font-semibold text-primary">TA {{ $ta }}</span>
            </div>
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="font-label-sm text-label-sm">Versi Sistem</span>
                <span class="font-label-sm text-label-sm font-semibold">v{{ config('app.versi') }}</span>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="fixed left-0 right-0 top-0 z-30 flex h-16 items-center justify-between gap-space-sm bg-surface/80 px-space-md shadow-soft backdrop-blur-xl lg:left-64 lg:px-space-lg">
            <div class="flex min-w-0 flex-1 items-center gap-space-sm">
                <button type="button" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-high lg:hidden" @click="menuBuka = true" aria-label="Buka menu"><x-ikon name="menu" class="text-[24px]" /></button>
                @if ($cari && Route::has('arsip.index'))
                    <form action="{{ route('arsip.index') }}" method="get" class="relative hidden w-96 max-w-full sm:flex">
                        <x-ikon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant" />
                        <input name="q" type="search" placeholder="Cari nomor atau perihal surat…" class="h-10 w-full rounded-lg border-0 bg-surface-container-lowest pl-10 pr-4 font-body-sm text-body-sm shadow-soft placeholder:text-on-surface-variant/70 focus:ring-2 focus:ring-primary">
                    </form>
                @elseif ($mahasiswa)
                    <x-logo ukuran="h-8" /><span class="truncate font-headline-sm text-headline-sm text-primary">SIPERSU <span class="font-normal text-on-surface-variant">| Fakultas Teknik UM Buton</span></span>
                @endif
            </div>
            <div class="flex items-center gap-space-sm">
                @if (Route::has('layanan.katalog') && $mahasiswa)
                    {{-- tombol ajukan ada di beranda mahasiswa --}}
                @elseif (Route::has('surat-keluar.buat') && $user->hasAnyRole(['super_admin', 'admin_tu', 'kaprodi', 'dosen_tendik']))
                    <x-tombol :href="route('surat-keluar.buat')" varian="aksen" ikon="add" class="hidden md:inline-flex">Buat Surat</x-tombol>
                @endif
                <a href="{{ Route::has('notifikasi.index') ? route('notifikasi.index') : '#' }}" class="relative rounded-lg p-2 text-on-surface-variant transition-all hover:bg-surface-container-high hover:text-on-surface" aria-label="Notifikasi">
                    <x-ikon name="notifications" class="text-[22px]" />
                    @if ($belumDibaca)<span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-bold text-on-error">{{ $belumDibaca > 9 ? '9+' : $belumDibaca }}</span>@endif
                </a>
                <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
                    <button type="button" @click="buka = !buka" class="flex items-center gap-space-sm rounded-lg py-1 pl-space-sm pr-1 hover:bg-surface-container-high">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container font-label-md text-label-md text-on-primary-container">{{ $user->inisial() }}</span>
                        <span class="hidden flex-col items-start text-left md:flex">
                            <span class="max-w-[180px] truncate font-label-md text-label-md font-semibold">{{ $user->nama }}</span>
                            <span class="rounded bg-surface-container px-1.5 py-0.5 font-label-sm text-label-sm text-on-surface-variant">{{ $user->labelPeran() }}</span>
                        </span>
                        <x-ikon name="expand_more" class="hidden text-[18px] text-on-surface-variant md:block" />
                    </button>
                    <div x-show="buka" x-cloak x-transition class="absolute right-0 top-full mt-2 w-56 overflow-hidden rounded-lg bg-surface-container-lowest py-1 shadow-popover">
                        <div class="border-b border-surface-container px-space-md py-space-sm md:hidden">
                            <p class="truncate font-label-md text-label-md font-semibold">{{ $user->nama }}</p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $user->labelPeran() }}</p>
                        </div>
                        @if (Route::has('profil'))
                            <a href="{{ route('profil') }}" class="flex items-center gap-space-sm px-space-md py-space-sm font-body-md text-body-md hover:bg-surface-container-low"><x-ikon name="person" class="text-[20px] text-on-surface-variant" />Profil Saya</a>
                        @endif
                        <form method="post" action="{{ route('keluar') }}">@csrf
                            <button class="flex w-full items-center gap-space-sm px-space-md py-space-sm text-left font-body-md text-body-md text-error hover:bg-error-container/40"><x-ikon name="logout" class="text-[20px]" />Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="min-h-screen bg-surface pt-16">
            <div @class(['px-space-md py-space-md pb-24 lg:px-space-lg lg:py-space-lg lg:pb-space-lg' => ! $tanpaPadding])>
                @if (session('sukses'))<x-peringatan jenis="sukses" tutup class="mb-space-md">{{ session('sukses') }}</x-peringatan>@endif
                @if (session('galat'))<x-peringatan jenis="bahaya" tutup class="mb-space-md">{{ session('galat') }}</x-peringatan>@endif
                {{ $slot }}
            </div>
        </main>
    </div>

    {{-- Navigasi bawah (ponsel) khusus mahasiswa --}}
    @if ($mahasiswa)
        <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-outline-variant/60 bg-surface-container-lowest lg:hidden" aria-label="Navigasi bawah">
            @foreach ([['dasbor', 'Beranda', 'space_dashboard'], ['layanan.katalog', 'Pengajuan', 'post_add'], ['layanan.riwayat', 'Riwayat', 'inbox'], ['profil', 'Profil', 'account_circle']] as [$r, $l, $i])
                @if (Route::has($r))
                    <a href="{{ route($r) }}" @class(['flex flex-col items-center gap-0.5 py-2 font-label-sm text-label-sm', 'text-primary font-semibold' => request()->routeIs($r), 'text-on-surface-variant' => ! request()->routeIs($r)])><x-ikon :name="$i" class="text-[24px]" />{{ $l }}</a>
                @endif
            @endforeach
        </nav>
    @endif

    @livewireScripts
    @stack('skrip')
</body>
</html>
