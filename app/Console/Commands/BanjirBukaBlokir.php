<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class BanjirBukaBlokir extends Command
{
    protected $signature = 'banjir:buka {ip? : alamat IP yang dibuka blokirnya} {--semua : hapus semua blokir dan hitungan (mengosongkan cache pembatas)}';

    protected $description = 'Membuka blokir sementara pembatas banjir akses (mis. IP Admin ikut terblokir)';

    public function handle(): int
    {
        $cache = Cache::store(config('sipersu.batas_laju.store'));
        if ($this->option('semua')) {
            $cache->flush();
            $this->info('Semua blokir dan hitungan pembatas dihapus.');

            return self::SUCCESS;
        }
        $ip = $this->argument('ip');
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Berikan alamat IP yang valid, atau pakai --semua.');

            return self::FAILURE;
        }
        $cache->forget("banjir:blokir:$ip");
        $jendela = intdiv(time(), 60);
        $cache->forget("banjir:hitung:$ip:$jendela");
        $cache->forget('banjir:hitung:'.$ip.':'.($jendela - 1));
        $this->info("Blokir untuk {$ip} dibuka.");

        return self::SUCCESS;
    }
}
