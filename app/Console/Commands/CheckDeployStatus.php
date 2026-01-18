<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CheckDeployStatus extends Command
{
    protected $signature = 'deploy:check';
    protected $description = 'Check deployment status and configuration';

    public function handle()
    {
        $this->info('🚀 Checking E-commerce Admin deployment status...');
        $this->newLine();

        // Environment check
        $this->checkEnvironment();
        
        // Database check
        $this->checkDatabase();
        
        // Services check
        $this->checkServices();
        
        // Configuration check
        $this->checkConfiguration();
        
        $this->newLine();
        $this->info('✅ Deployment check completed!');
    }

    private function checkEnvironment()
    {
        $this->info('📋 Environment Configuration:');
        
        $env = app()->environment();
        $debug = config('app.debug');
        
        $this->line("Environment: <comment>{$env}</comment>");
        $this->line("Debug Mode: <comment>" . ($debug ? 'ON' : 'OFF') . "</comment>");
        $this->line("App URL: <comment>" . config('app.url') . "</comment>");
        
        if ($env === 'production' && $debug) {
            $this->warn('⚠️  Debug mode is ON in production - consider turning it OFF');
        }
        
        $this->newLine();
    }

    private function checkDatabase()
    {
        $this->info('🗄️  Database Status:');
        
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $driver = $connection->getDriverName();
            
            $this->line("Driver: <comment>{$driver}</comment>");
            $this->line("Connection: <info>✅ Connected</info>");
            
            // Check migrations
            try {
                $migrations = DB::table('migrations')->count();
                $this->line("Migrations: <comment>{$migrations} applied</comment>");
            } catch (\Exception $e) {
                $this->error('❌ Migrations table not found - run php artisan migrate');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function checkServices()
    {
        $this->info('🔧 External Services:');
        
        // Clerk
        $clerkSecret = config('clerk.secret_key');
        if ($clerkSecret) {
            $this->line('Clerk: <info>✅ Configured</info>');
        } else {
            $this->warn('Clerk: <comment>⚠️  Not configured</comment>');
        }
        
        // InfinitePay
        $infinitePayClient = config('infinitepay.client_id');
        if ($infinitePayClient) {
            $this->line('InfinitePay: <info>✅ Configured</info>');
        } else {
            $this->warn('InfinitePay: <comment>⚠️  Not configured</comment>');
        }
        
        // Cache
        try {
            $cacheDriver = config('cache.default');
            Cache::put('deploy_check', 'test', 60);
            $cached = Cache::get('deploy_check');
            Cache::forget('deploy_check');
            
            if ($cached === 'test') {
                $this->line("Cache ({$cacheDriver}): <info>✅ Working</info>");
            } else {
                $this->error("Cache ({$cacheDriver}): ❌ Not working properly");
            }
        } catch (\Exception $e) {
            $this->error('Cache: ❌ ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function checkConfiguration()
    {
        $this->info('⚙️  Configuration Check:');
        
        $checks = [
            'APP_KEY' => !empty(config('app.key')),
            'APP_URL' => !empty(config('app.url')),
            'DB_CONNECTION' => !empty(config('database.default')),
            'FILESYSTEM_DISK' => !empty(config('filesystems.default')),
        ];
        
        foreach ($checks as $key => $passed) {
            if ($passed) {
                $this->line("{$key}: <info>✅ Set</info>");
            } else {
                $this->error("{$key}: ❌ Missing");
            }
        }
        
        // Check storage permissions
        $storageWritable = is_writable(storage_path());
        if ($storageWritable) {
            $this->line('Storage Permissions: <info>✅ Writable</info>');
        } else {
            $this->error('Storage Permissions: ❌ Not writable');
        }
        
        $this->newLine();
    }
}