<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $offices = Office::query()
        ->when($search, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('office_code', 'like', '%' . $search . '%')
                  ->orWhere('office_name', 'like', '%' . $search . '%')
                  ->orWhere('office_head', 'like', '%' . $search . '%');
            });
        }, function ($query) {})
        ->with('creator')
        ->where('office_status', 'active')
        ->orderBy('office_name')
        ->paginate(10)
        ->through(fn($office) => [
            'id' => $office->id,
            'code' => $office->office_code,
            'name' => $office->office_name,
            'head' => $office->office_head,
            'position' => $office->position_head,
            'status' => $office->office_status,
            'addedBy' => $office->creator->name,
        ]);

        $data = Office::with('creator')
            ->where('office_status', 'active')
            ->orderBy('office_name')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'code' => $office->office_code,
                    'name' => $office->office_name,
                    'head' => $office->office_head,
                    'position' => $office->position_head,
                    'status' => $office->office_status,
                    'addedBy' => optional($office->creator)->name ?? 'N/A',
                ];
            });

        return Inertia::render('Office/Index', ['data' => $data, 'offices' => $offices, 'filters' => $request->only(['search']), 'authUserId' => Auth::id()]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'offCode' => 'nullable|string|max:50',
            'offName' => 'required|string|max:150',
            'offHead' => 'nullable|string|max:150',
            'posHead' => 'nullable|string|max:150',
            'createdBy' => 'nullable|integer',
        ]);

        try {
            Office::create([
                'office_code' => $validatedData['offCode'],
                'office_name' => $validatedData['offName'],
                'office_head' => $validatedData['offHead'],
                'position_head' => $validatedData['posHead'],
                'created_by' => $validatedData['createdBy'],
            ]);

            return redirect()->back()
                ->with(['message' => 'New office has been successfully added.']);
        }catch (\Exception $e) {
            Log::error('Creation of Office: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while adding the new office. Please contact your administrator.']);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Office $office)
    {
        $validatedData = $request->validate([
            'offId' => 'required|integer|',
            'offCode' => 'nullable|string|max:50',
            'offName' => 'required|string|max:150',
            'offHead' => 'nullable|string|max:150',
            'posHead' => 'nullable|string|max:150',
            'updatedBy' => 'nullable|integer',
        ]);

        try {
            $office = Office::findOrFail($validatedData['offId']);

            $office->fill([
                'office_code' => $validatedData['offCode'],
                'office_name' => $validatedData['offName'],
                'office_head' => $validatedData['offHead'],
                'position_head' => $validatedData['posHead'],
                'updated_by' => $validatedData['updatedBy'],
            ])->save();

            return redirect()->back()
                ->with(['message' => 'Office has been successfully updated.']);
        } catch (\Exception $e) {
            Log::error('Update of Office: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while adding the new office. Please contact your administrator.']);
        }
    }

    public function deactivate(Request $request, Office $office)
    {
        $validatedData = $request->validate([
            'offId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);
        try {
            $office = Office::findOrFail($validatedData['offId']);

            $office->fill([
                'office_status' => 'deactivated',
                'updated_by' => $validatedData['updatedBy'],
            ])->save();

            return redirect()->back()
                ->with(['message' => 'Office was successfully removed.']);
        } catch (\Exception $e) {
            Log::error('Deletion of Office: ' . $e->getMessage());
            return redirect()->back()
            ->with(['error' => 'An error occurred while removing the office. Please contact your administrator.']);
        }
    }
}
