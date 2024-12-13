<?php

namespace App\Http\Controllers;

use App\Models\RisTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RisTransactionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Ris/Create');
    }

}
