<!DOCTYPE html>
<html lang="id"><body style="font-family:Arial,Helvetica,sans-serif;color:#131b2e;background:#f8fafc;padding:24px">
<div style="max-width:560px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
    <div style="background:#0F6B3E;color:#fff;padding:16px 24px;font-weight:bold">SIPERSU FT-UMB</div>
    <div style="padding:24px">
        <h2 style="margin:0 0 12px;font-size:18px">{{ $judul }}</h2>
        @if ($isi)<p style="line-height:1.5">{{ $isi }}</p>@endif
        @if ($url)<p><a href="{{ url($url) }}" style="display:inline-block;background:#0F6B3E;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">Buka di SIPERSU</a></p>@endif
        <p style="color:#64748b;font-size:12px;margin-top:24px">Email otomatis dari Sistem Informasi Persuratan Fakultas Teknik UM Buton. Jangan membalas email ini.</p>
    </div>
</div></body></html>
