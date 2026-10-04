<?php

return [
    // Folder kunci Ed25519 (di luar public). Jangan pernah dibagikan: storage/keys/ed25519.secret
    'kunci_path' => storage_path('keys'),

    // Alamat halaman verifikasi statis yang dituju QR (index.html dari `kunci:publikasi`), mis. GitHub Pages.
    // Kosong = memakai salinan di server sendiri: {APP_URL}/verifikasi (hanya terbuka di jaringan lokal).
    'verifikasi_url' => rtrim((string) (env('VERIFIKASI_URL') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/verifikasi'), '/'),

    // Berkas halaman statis yang dilayani di /verifikasi (dibuat oleh `php artisan kunci:publikasi`).
    'verifikasi_berkas' => base_path('verifikasi-statis/index.html'),

    // Rentang IP yang boleh mengakses aplikasi: loopback, IP privat, dan Tailscale (100.64.0.0/10). Selain itu 403.
    'jaringan_lokal' => array_filter(array_map('trim', explode(',', env(
        'LAN_CIDRS',
        '127.0.0.0/8,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,100.64.0.0/10,fc00::/7'
    )))),

    'lampiran' => [
        'maks_kb' => 2048,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'backup' => [
        'password' => env('BACKUP_PASSWORD'),
        'lokal' => env('BACKUP_LOCAL_PATH'),
        'simpan_harian' => 30,
        'simpan_mingguan' => 12,
        'rclone_remote' => env('BACKUP_RCLONE_REMOTE'),   // mis. gdrive:sipersu-backup
    ],
];
