<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use Illuminate\Console\Command;

class KunciCadangkan extends Command
{
    protected $signature = 'kunci:cadangkan {tujuan : Folder tujuan (mis. D:\\kunci-sipersu)}';

    protected $description = 'Menyalin kunci Ed25519 ke folder lain (flashdisk/brankas)';

    public function handle(): int
    {
        if (! KunciTte::ada()) {
            $this->error('Kunci belum dibuat.');

            return self::FAILURE;
        }
        $tujuan = rtrim($this->argument('tujuan'), '/\\');
        if (! is_dir($tujuan) && ! @mkdir($tujuan, 0700, true)) {
            $this->error("Folder tujuan tidak dapat dibuat: $tujuan");

            return self::FAILURE;
        }
        foreach (['ed25519.secret', 'ed25519.public'] as $f) {
            copy(KunciTte::jalur($f), $tujuan.DIRECTORY_SEPARATOR.$f);
        }
        $this->info("Kunci disalin ke $tujuan. Simpan di tempat aman dan JANGAN dikirim lewat chat/email.");

        return self::SUCCESS;
    }
}
