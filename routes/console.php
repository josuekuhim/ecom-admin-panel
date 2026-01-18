<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:ping', function () {
    $this->info('🧪 Pinging database...');
    try {
        $version = DB::select('SELECT version() as v');
        $this->info('✅ DB OK: ' . ($version[0]->v ?? 'unknown'));
    } catch (\Throwable $e) {
        $this->error('❌ DB ping failed: ' . $e->getMessage());
    }
})->purpose('Quickly test DB connectivity');
