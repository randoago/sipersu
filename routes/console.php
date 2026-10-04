<?php

use Illuminate\Support\Facades\Schedule;

/*
| Dijalankan oleh Windows Task Scheduler setiap menit: php artisan schedule:run  (lihat scripts/jalankan-scheduler.bat)
| Tanpa queue worker terpisah: antrean email diproses di sini.
*/
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping(5);
Schedule::command('backup:run --jenis=harian')->dailyAt('16:30')->withoutOverlapping(60);
Schedule::command('backup:run --jenis=mingguan')->fridays()->at('17:30')->withoutOverlapping(120);
Schedule::command('backup:periksa')->dailyAt('08:00');
Schedule::command('queue:prune-failed --hours=720')->weekly();
