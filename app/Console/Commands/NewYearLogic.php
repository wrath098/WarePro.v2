<?php

namespace App\Console\Commands;

use App\Models\ProductInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewYearLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'year:change';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the No. of IAR, RIS and Beginning Bla';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
    
        $updatedCount = ProductInventory::query()->update([
            'qty_physical_count' => DB::raw('qty_on_stock'),
            'qty_purchase' => 0,
            'qty_issued' => 0,
        ]);

        Log::info("Year-end inventory reset completed", [
            'action' => 'yearly_inventory_reset',
            'products_updated' => $updatedCount,
            'execution_time' => microtime(true) - $startTime,
        ]);
    }
}