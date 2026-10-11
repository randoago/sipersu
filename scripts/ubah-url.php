<?php

/*
 * Pembantu scripts\ubah-url.bat: menulis APP_URL, APP_FORCE_HTTPS (dan VERIFIKASI_URL bila perlu) ke .env.
 * Pemakaian:
 *   php scripts/ubah-url.php --tampil             tampilkan APP_URL sekarang
 *   php scripts/ubah-url.php --ip                 tampilkan IP lokal komputer ini
 *   php scripts/ubah-url.php --port               tampilkan port dari APP_URL (bawaan 80)
 *   php scripts/ubah-url.php --set=ALAMAT         ubah APP_URL (mis. 192.168.1.10, sipersu.local, https://sipersu.kampus.ac.id:8080)
 */

$env = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env';
$ambil = function (string $kunci) use ($env): string {
    if (is_file($env) && preg_match('/^'.preg_quote($kunci, '/').'=(.*)$/m', file_get_contents($env), $m)) {
        return trim(preg_replace('/\s+#.*$/', '', trim($m[1])), " \t\"'");
    }

    return '';
};

$ipLokal = function (): string {
    // Trik UDP: tidak ada paket yang dikirim, OS hanya memilih antarmuka keluar.
    $s = @stream_socket_client('udp://10.255.255.255:53', $no, $str, 1);
    if ($s) {
        $ip = explode(':', (string) stream_socket_get_name($s, false))[0];
        fclose($s);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '0.0.0.0') {
            return $ip;
        }
    }
    $ip = gethostbyname(gethostname());

    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : '127.0.0.1';
};

$opsi = getopt('', ['tampil', 'ip', 'port', 'set:']);

if (isset($opsi['ip'])) {
    echo $ipLokal();
    exit(0);
}
if (isset($opsi['tampil'])) {
    echo $ambil('APP_URL') ?: 'http://localhost';
    exit(0);
}
if (isset($opsi['port'])) {
    echo parse_url($ambil('APP_URL') ?: 'http://localhost', PHP_URL_PORT) ?: 80;
    exit(0);
}
if (! isset($opsi['set'])) {
    fwrite(STDERR, "Pemakaian: php scripts/ubah-url.php --set=ALAMAT | --tampil | --ip | --port\n");
    exit(2);
}

// Normalisasi: tanpa skema = http://; buang garis miring akhir.
$alamat = trim((string) $opsi['set']);
if ($alamat === '') {
    fwrite(STDERR, "Alamat kosong.\n");
    exit(1);
}
if (! preg_match('#^https?://#i', $alamat)) {
    $alamat = 'http://'.$alamat;
}
$alamat = rtrim($alamat, '/');
$bagian = parse_url($alamat);
if (! $bagian || empty($bagian['host']) || ! preg_match('/^[A-Za-z0-9.\-]+$/', $bagian['host']) || isset($bagian['path']) || isset($bagian['query'])) {
    fwrite(STDERR, "Alamat tidak valid: $alamat (contoh: 192.168.1.10 atau sipersu.local)\n");
    exit(1);
}
$https = strtolower($bagian['scheme']) === 'https';

if (! is_file($env)) {
    $contoh = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env.example';
    if (! is_file($contoh)) {
        fwrite(STDERR, "Berkas .env dan .env.example tidak ditemukan.\n");
        exit(1);
    }
    copy($contoh, $env);
}

$isi = file_get_contents($env);
$tulis = function (string $kunci, string $nilai) use (&$isi): void {
    $baris = $kunci.'='.$nilai;
    $pola = '/^'.preg_quote($kunci, '/').'=.*$/m';
    $isi = preg_match($pola, $isi) ? preg_replace($pola, str_replace(['\\', '$'], ['\\\\', '\\$'], $baris), $isi, 1) : rtrim($isi)."\n".$baris."\n";
};

$lama = $ambil('APP_URL');
$tulis('APP_URL', $alamat);
$tulis('APP_FORCE_HTTPS', $https ? 'true' : 'false');

// VERIFIKASI_URL hanya disentuh bila ia masih mengarah ke alamat lokal lama (bukan alamat internet buatan pengguna).
$verif = $ambil('VERIFIKASI_URL');
if ($verif !== '' && $lama !== '' && str_starts_with(rtrim($verif, '/'), $lama)) {
    $tulis('VERIFIKASI_URL', '');
    echo "VERIFIKASI_URL dikosongkan (mengikuti APP_URL baru).\n";
}

file_put_contents($env, $isi);
echo "APP_URL=$alamat\nAPP_FORCE_HTTPS=".($https ? 'true' : 'false')."\n";
if ($lama !== '' && $lama !== $alamat) {
    echo "Sebelumnya: $lama\n";
    echo "CATATAN: QR pada surat yang SUDAH terbit tetap menuju alamat lama; hanya surat baru memakai alamat baru.\n";
}
exit(0);
