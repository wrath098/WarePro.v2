<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use Carbon\Carbon;
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
    protected $description = 'Reset the No. of IAR, RIS and Beginning Balance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $countUpdated = 0;
        $currentYear = Carbon::now()->year;
        $startTime = microtime(true);
        $yearStart = Carbon::create($currentYear, 1, 1);

        $lastYearStart = Carbon::create($currentYear - 1, 1, 1);
        $lastYearEnd   = Carbon::create($currentYear, 1, 1)->subSecond();

        $productList = Product::withTrashed()->with('inventory')->get();

        foreach ($productList as $product) {
            $productInventory = $product->inventory;

            if($productInventory) {
                $transactionLastYear = ProductInventoryTransaction::withTrashed()
                    ->where('prod_id', $product->id)
                    ->whereBetween('created_at', [$lastYearStart, $lastYearEnd])
                    ->orderBy('created_at', 'asc')
                    ->get();

                $first = $transactionLastYear->first();
                $currentStock = $first ? $first->current_stock : 0;

                foreach ($transactionLastYear as $transaction) {

                    if($transaction->id != $first->id) {
                        if($transaction->type == 'purchase' || $transaction->type == 'adjustment') {
                            $currentStock += $transaction->qty;
                        } elseif($transaction->type == 'issuance') {
                            $currentStock -= $transaction->qty;
                        }
                    }
                }

                if($transactionLastYear->count() == 0) {
                    $productInventory->qty_physical_count = $productInventory->qty_on_stock;
                }

                $productInventory->qty_physical_count = $currentStock;

                $productInventory->qty_purchase = 0;
                $productInventory->qty_issued = 0;
                $productInventory->save();

                $countUpdated++;
            }
        }

        Log::channel('backups')->info('Year-end inventory reset completed', [
            'action' => 'yearly_inventory_reset',
            'products_updated' => $countUpdated,
            'execution_time' => microtime(true) - $startTime,
        ]);
    }
}