@props(['kunci' => null, 'label' => null, 'name' => null, 'wajib' => false, 'bantuan' => null, 'maks' => null])
@php $galat = ($kunci ?? $name) ? $errors->first($kunci ?? $name) : null; @endphp
<div {{ $attributes->only('class')->class(['w-full']) }}>
    @if ($label)
        <div class="mb-1.5 flex items-baseline justify-between">
            <label for="{{ $attributes->get('id', $name) }}" class="font-label-lg text-label-lg text-on-surface">{{ $label }}@if ($wajib)<span class="text-error"> *</span>@endif</label>
            @if ($maks)<span class="font-body-sm text-body-sm text-on-surface-variant">Maks. {{ $maks }} karakter</span>@endif
        </div>
    @endif
    <textarea @if ($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif @if ($maks) maxlength="{{ $maks }}" @endif
        {{ $attributes->except('class')->class(['block w-full rounded-lg border bg-surface-container-lowest font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/20', 'border-outline-variant' => ! $galat, 'border-[#e11d48]' => $galat]) }}>{{ $slot }}</textarea>
    @if ($galat)<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $galat }}</p>@elseif ($bantuan)<p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $bantuan }}</p>@endif
</div>
