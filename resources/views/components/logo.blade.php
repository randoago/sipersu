@props(['ukuran' => 'h-8'])
<img src="{{ asset('images/logo-umb.png') }}" alt="Logo Universitas Muhammadiyah Buton" {{ $attributes->class([$ukuran, 'w-auto object-contain']) }}>
