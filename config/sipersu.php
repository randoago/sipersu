<?php

return [
    // Folder kunci Ed25519 (di luar public). Jangan pernah dibagikan: storage/keys/ed25519.secret
    'kunci_path' => storage_path('keys'),

    // Alamat file verifikasi offline statis (mis. GitHub Pages) — tampil sebagai petunjuk di halaman verifikasi.
    'verifikasi_offline_url' => env('VERIFIKASI_OFFLINE_URL'),

    // Rentang IP jaringan lokal yang boleh mengakses seluruh aplikasi (selain /v/*).
    'jaringan_lokal' => array_filter(array_map('trim', explode(',', env(
        'LAN_CIDRS',
        '127.0.0.0/8,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,fc00::/7'
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
