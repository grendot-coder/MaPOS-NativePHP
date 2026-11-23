<?php

namespace App\Console\Commands;

use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapos:sync {--push-only : Only push pending local sales} {--pull-only : Only pull products catalog}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize MaPOS local data with central server';

    /**
     * Execute the console command.
     */
    public function handle(SyncService $syncService): int
    {
        $this->info('🔄 Connecting to MaPOS Sync Server...');
        $this->line('   Server URL: '.config('sync.server_url'));
        $this->line('   Branch ID:  '.config('sync.branch_id'));

        // 1. Ping
        $ping = $syncService->ping();
        if (! $ping['success']) {
            $this->error('❌ '.$ping['message']);

            return self::FAILURE;
        }

        $this->info('✅ Server Online! Time: '.$ping['server_time']);

        // 2. Push Sales
        if (! $this->option('pull-only')) {
            $this->line('');
            $this->info('📤 [1/2] Pushing unsynced local sales...');
            $pushResult = $syncService->pushSales();

            if ($pushResult['success']) {
                $this->info('   ✅ '.$pushResult['message']);
            } else {
                $this->warn('   ⚠️ '.$pushResult['message']);
            }
        }

        // 3. Pull Catalog
        if (! $this->option('push-only')) {
            $this->line('');
            $this->info('📥 [2/2] Pulling product updates from server...');
            $pullResult = $syncService->pullCatalog();

            if ($pullResult['success']) {
                $this->info('   ✅ '.$pullResult['message']);
            } else {
                $this->warn('   ⚠️ '.$pullResult['message']);
            }
        }

        $this->line('');
        $this->info('🎉 Sync completed!');

        return self::SUCCESS;
    }
}
