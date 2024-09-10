<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $funds = Fund::all();
        foreach ($funds as $list) {
            $creator = User::findOrFail($list->created_by);
            $list['nameOfCreator'] = $creator->name;
        }
        return Inertia::render('Fund/Index', ['fundCluster' => $funds, 'authUserId' => Auth::id()]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = $request->validate([
            'fundName' => 'required|string|max:255',
            'fundDesc' => 'nullable|string|max:255',
            'createdBy' => 'nullable|integer',
        ]);

        try {
            DB::transaction(function () use ($validation) {
                Fund::create([
                    'fund_name' => $validation['fundName'],
                    'description' => $validation['fundDesc'],
                    'created_by' => $validation['createdBy'],
                ]);
            });
            return redirect()->route('fund.display.all')->with(['message' => 'Fund created successfully']);
        } catch (\Exception $e) {
            return redirect()->route('fund.display.all')->with(['error' => 'Failed to create fund']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Fund $fund)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fund $fund)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fund $fund)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fund $fund)
    {
        //
    }
}
