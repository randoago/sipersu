{{-- Formulir isian dari definisi field_formulir (bukan Livewire): name="isian[kunci]". --}}
@props(['fields', 'nilai' => []])
<div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
    @foreach ($fields as $f)
        @php
            $n = 'isian['.$f['nama'].']'; $k = 'isian.'.$f['nama']; $v = old($k, $nilai[$f['nama']] ?? '');
            $setengah = ($f['lebar'] ?? null) === 'setengah';
        @endphp
        <div class="{{ $setengah ? '' : 'md:col-span-2' }}">
            @switch($f['tipe'])
                @case('tabel')
                    @php
                        $kolom = array_values($f['kolom'] ?? []);
                        $baris = old($k, is_array($v ?? null) ? $v : []);
                        $baris = collect(is_array($baris) ? $baris : [])->filter(fn ($b) => is_array($b))->values()->all() ?: [array_fill(0, max(1, count($kolom)), '')];
                    @endphp
                    <div x-data="{ baris: @js($baris) }" class="space-y-2">
                        <p class="font-label-lg text-label-lg">{{ $f['label'] }}@if ($f['wajib'] ?? false)<span class="text-error"> *</span>@endif</p>
                        <div class="overflow-x-auto rounded-lg border border-outline-variant scroll-tipis">
                            <table class="w-full min-w-[420px] font-body-sm text-body-sm">
                                <thead class="bg-surface-container-low"><tr><th class="w-10 px-2 py-2 text-center font-label-md text-label-md">No</th>@foreach ($kolom as $kn)<th class="px-2 py-2 text-left font-label-md text-label-md">{{ $kn }}</th>@endforeach<th class="w-10"></th></tr></thead>
                                <tbody>
                                    <template x-for="(b, i) in baris" :key="i">
                                        <tr class="border-t border-surface-container">
                                            <td class="px-2 text-center tabular text-on-surface-variant" x-text="i + 1"></td>
                                            @foreach ($kolom as $ci => $kn)<td class="p-1"><input type="text" :name="`{{ $n }}[${i}][{{ $ci }}]`" x-model="b[{{ $ci }}]" maxlength="200" class="h-9 w-full rounded-md border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-primary"></td>@endforeach
                                            <td class="px-1 text-center"><button type="button" @click="baris.length > 1 ? baris.splice(i, 1) : (b.fill(''))" class="rounded p-1 text-error hover:bg-error-container/50" aria-label="Hapus baris"><x-ikon name="delete" class="text-[18px]" /></button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <x-tombol varian="lembut" ukuran="sm" ikon="add" x-on:click="baris.push(Array({{ max(1, count($kolom)) }}).fill(''))">Tambah Baris</x-tombol>
                        @foreach ($errors->get($k) as $pesan)<p class="font-body-sm text-body-sm text-[#e11d48]">{{ $pesan }}</p>@endforeach
                    </div>@break
                @case('daftar')<x-textarea :label="$f['label']" :name="$n" :kunci="$k" :wajib="$f['wajib'] ?? false" rows="4" :placeholder="$f['placeholder'] ?? ''" bantuan="Satu per baris; nomor urut dibuat otomatis.">{{ is_array($v) ? '' : $v }}</x-textarea>@break
                @case('area')<x-textarea :label="$f['label']" :name="$n" :kunci="$k" :wajib="$f['wajib'] ?? false" rows="4" :placeholder="$f['placeholder'] ?? ''">{{ $v }}</x-textarea>@break
                @case('pilihan')
                    <x-select :label="$f['label']" :name="$n" :kunci="$k" :wajib="$f['wajib'] ?? false"><option value="">Pilih…</option>@foreach ($f['opsi'] ?? [] as $o)<option value="{{ $o }}" @selected($v === $o)>{{ $o }}</option>@endforeach</x-select>@break
                @case('tanggal')<x-input :label="$f['label']" :name="$n" :kunci="$k" type="date" :wajib="$f['wajib'] ?? false" :value="$v" />@break
                @case('angka')<x-input :label="$f['label']" :name="$n" :kunci="$k" type="number" step="any" :wajib="$f['wajib'] ?? false" :value="$v" :placeholder="$f['placeholder'] ?? ''" />@break
                @default<x-input :label="$f['label']" :name="$n" :kunci="$k" :wajib="$f['wajib'] ?? false" :value="$v" :placeholder="$f['placeholder'] ?? ''" />
            @endswitch
        </div>
    @endforeach
</div>
