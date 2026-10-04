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
