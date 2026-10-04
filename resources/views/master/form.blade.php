<x-layouts::app :title="($m ? 'Ubah ' : 'Tambah ').$d['judul']" :cari="false">
<div class="mx-auto max-w-4xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('master.daftar', $entitas) }}" class="hover:text-primary">Master Data</a><x-ikon name="chevron_right" class="text-[14px]" /><span>{{ $d['judul'] }}</span></nav>
    <h1 class="mb-5 font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $m ? 'Ubah' : 'Tambah' }} {{ $d['judul'] }}</h1>
    <form method="post" action="{{ $m ? route('master.perbarui', [$entitas, $m->id]) : route('master.simpan', $entitas) }}" class="space-y-5">
        @csrf @if ($m) @method('PUT') @endif
        <x-kartu>
            <div class="grid gap-space-md md:grid-cols-2">
                @foreach ($d['field'] as $f)
                    @php [$nama, $label, $tipe] = $f; $v = old($nama, $nilai[$nama] ?? null); $setengah = ($f['lebar'] ?? null) === 'setengah'; $wajib = in_array('required', $f[3], true) || ($nama === 'password' && ! $m); @endphp
                    <div class="{{ $setengah ? '' : 'md:col-span-2' }}">
                        @switch($tipe)
                            @case('area')<x-textarea :label="$label" :name="$nama" :wajib="$wajib" rows="3" :bantuan="$f['bantuan'] ?? null">{{ $v }}</x-textarea>@break
                            @case('json')<x-textarea :label="$label" :name="$nama" :wajib="$wajib" rows="12" class="font-mono" :bantuan="$f['bantuan'] ?? null" spellcheck="false">{{ is_string($v) ? $v : json_encode($v) }}</x-textarea>@break
                            @case('kode')<x-textarea :label="$label" :name="$nama" :wajib="$wajib" rows="14" class="font-mono" :bantuan="$f['bantuan'] ?? null" spellcheck="false">{{ $v }}</x-textarea>@break
                            @case('pilihan')
                                <x-select :label="$label" :name="$nama" :wajib="$wajib" :bantuan="$f['bantuan'] ?? null">
                                    @foreach (($f['opsi'])() as $k => $t)<option value="{{ $k }}" @selected((string) $v === (string) $k)>{{ $t }}</option>@endforeach
                                </x-select>@break
                            @case('peran')
                                <p class="mb-1.5 font-label-lg text-label-lg">{{ $label }} <span class="text-error">*</span></p>
                                <div class="flex flex-wrap gap-2">@foreach ($peranBisa as $p)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 font-body-md text-body-md has-[:checked]:border-primary-container has-[:checked]:bg-primary-fixed/30"><input type="checkbox" name="peran[]" value="{{ $p->value }}" @checked(in_array($p->value, (array) $v)) class="rounded border-outline-variant text-primary focus:ring-primary">{{ $p->label() }}</label>
                                @endforeach</div>
                                @error('peran')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror @break
                            @case('ya_tidak')
                                <label class="flex cursor-pointer items-center gap-2 font-label-lg text-label-lg"><input type="checkbox" name="{{ $nama }}" value="1" @checked($v) class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">{{ $label }}</label>@break
                            @case('sandi')<x-input :label="$label" :name="$nama" type="password" :wajib="$wajib" autocomplete="new-password" :bantuan="$f['bantuan'] ?? null" />@break
                            @case('tanggal')<x-input :label="$label" :name="$nama" type="date" :value="$v" :wajib="$wajib" />@break
                            @case('angka')<x-input :label="$label" :name="$nama" type="number" :value="$v" :wajib="$wajib" />@break
                            @default<x-input :label="$label" :name="$nama" :value="$v" :wajib="$wajib" :bantuan="$f['bantuan'] ?? null" />
                        @endswitch
                    </div>
                @endforeach
            </div>
        </x-kartu>
        <div class="flex justify-end gap-2"><x-tombol :href="route('master.daftar', $entitas)" varian="sekunder">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan</x-tombol></div>
    </form>
</div>
</x-layouts::app>
