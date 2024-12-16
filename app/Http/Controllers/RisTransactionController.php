<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RisTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RisTransactionController extends Controller
{
    public function create(): Response
    {
        $product = Product::where('prod_status', 'active')->get();
        return Inertia::render('Ris/Create', ['products' => $product]);
    }

}
