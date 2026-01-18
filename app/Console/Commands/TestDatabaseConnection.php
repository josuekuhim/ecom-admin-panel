<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Exception;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test-connection {--detailed : Show detailed connection information}';
    protected $description = 'Test database connection with detailed diagnostics';

    public function handle()
    {
        $this->info('🔍 Testing Database Connection');
        $this->info('================================');

        $config = config('database.connections.' . config('database.default'));
        
        // Show configuration (hide password)
        $this->info('📋 Configuration:');
        $this->line('   Driver: ' . $config['driver']);
        $this->line('   Host: ' . $config['host']);
        $this->line('   Port: ' . $config['port']);
        $this->line('   Database: ' . $config['database']);
        $this->line('   Username: ' . $config['username']);
        $this->line('   SSL Mode: ' . ($config['sslmode'] ?? 'not set'));
        $this->line('   Password: ' . (empty($config['password']) ? '(empty)' : str_repeat('*', strlen($config['password']))));
        $this->newLine();

        // Test 1: Laravel DB connection
        $this->info('🧪 Test 1: Laravel Database Connection');
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $this->info('✅ Laravel connection: SUCCESS');
            
            // Test query
            $version = DB::select('SELECT version() as version')[0]->version;
            $this->line('   PostgreSQL Version: ' . $version);
            
        } catch (Exception $e) {
            $this->error('❌ Laravel connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        if ($this->option('detailed')) {
            $this->runDetailedTests($config);
        }

        // Test database tables
        $this->info('🧪 Test: Database Tables');
        try {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $this->info('✅ Tables found: ' . count($tables));
            foreach ($tables as $table) {
                $this->line('   - ' . $table->tablename);
            }
        } catch (Exception $e) {
            $this->error('❌ Could not list tables: ' . $e->getMessage());
        }
        $this->newLine();

        $this->info('🏁 Test completed');
    }

    private function runDetailedTests($config)
    {
        // Test 2: Raw PDO connection
        $this->info('🧪 Test 2: Raw PDO Connection');
        try {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']};sslmode=" . ($config['sslmode'] ?? 'require');
            $this->line('   DSN: ' . $dsn);
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 30,
            ]);
            $this->info('✅ Raw PDO connection: SUCCESS');
            
        } catch (Exception $e) {
            $this->error('❌ Raw PDO connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 3: PHP Extensions
        $this->info('🧪 Test 3: PHP Extensions');
        $extensions = ['pdo', 'pdo_pgsql', 'pgsql'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->info("✅ $ext: Loaded");
            } else {
                $this->error("❌ $ext: NOT loaded");
            }
        }
        $this->newLine();

        // Test 4: Network connectivity
        $this->info('🧪 Test 4: Network Connectivity');
        $connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
        if ($connection) {
            $this->info('✅ Network connectivity: SUCCESS');
            fclose($connection);
        } else {
            $this->error('❌ Network connectivity: FAILED');
            $this->error("   Error: $errstr ($errno)");
        }
        $this->newLine();
    }
}