<?php

namespace App\Console\Commands;

use App\Models\ProductInventory;
use Illuminate\Console\Command;
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
    protected $description = 'Execute logic when a new year begins';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Add your logic here
        Log::info("Year change command triggered"); // Example log for confirmation
        $query = ProductInventory::all();

        foreach ($query as $product) {
            $product->update(['qty_physical_count' => $product->qty_on_stock]);
        }
    }
}