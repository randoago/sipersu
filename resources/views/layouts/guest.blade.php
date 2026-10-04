@props(['title' => null, 'polos' => false, 'tanpaLivewire' => false])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}SIPERSU FT-UMB</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-umb.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    @unless ($tanpaLivewire)@livewireStyles @endunless
</head>
<body {{ $attributes->class(['font-body-md text-on-surface antialiased', $polos ? 'bg-white' : 'bg-surface']) }}>
    {{ $slot }}
    @unless ($tanpaLivewire)@livewireScripts @endunless
    @if ($tanpaLivewire)<script defer src="{{ asset('js/alpine.min.js') }}"></script>@endif
    @stack('skrip')
</body>
</html>
