<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function index(): Response
    {
        $offices = Office::with(['updater', 'creator'])
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
                    'updatedBy' => $office->updater ? $office->updater->name : $office->creator->name,
                    'updatedAt' => $office->updated_at ? $office->updated_at->format('F j, Y') : $office->created_at->format('F j, Y'),
                ];
            });

        return Inertia::render('Office/Index', ['offices' => $offices, 'authUserId' => Auth::id()]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'offCode' => 'nullable|string|max:50',
            'offName' => 'required|string|max:150',
            'offHead' => 'nullable|string|max:150',
            'posHead' => 'nullable|string|max:150',
            'createdBy' => 'nullable|integer',
        ]);

        try {
            $existingCode = Office::withTrashed()
                ->whereRaw('LOWER(office_code) = ?', [strtolower($validatedData['offCode'])])
                ->first();

            if($existingCode) {
                DB::rollback();
                return redirect()->back()
                    ->with(['error' => 'Office code already exist.']);
            }

            Office::create([
                'office_code' => $validatedData['offCode'],
                'office_name' => $validatedData['offName'],
                'office_head' => $validatedData['offHead'],
                'position_head' => $validatedData['posHead'],
                'created_by' => $validatedData['createdBy'],
            ]);

            DB::commit();
            return redirect()->back()
                ->with(['message' => 'New office has been successfully added.']);
        }catch (\Exception $e) {

            DB::rollBack();
            Log::error("Creation of New Office Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with(['error' => 'Creation of New Office failed. Please try again!']);
        }
    }

    public function update(Request $request, Office $office)
    {
        DB::beginTransaction();

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

            $existingCode = Office::withTrashed()
                ->where('id', '!=', $office->id)
                ->whereRaw('LOWER(office_code) = ?', [strtolower($validatedData['offCode'])])
                ->get();

            if($existingCode->isNotEmpty()) {
                DB::rollback();
                return redirect()->back()
                    ->with(['error' => 'Office code already exist.']);
            }

            $office->fill([
                'office_code' => $validatedData['offCode'],
                'office_name' => $validatedData['offName'],
                'office_head' => $validatedData['offHead'],
                'position_head' => $validatedData['posHead'],
                'updated_by' => $validatedData['updatedBy'],
            ])->save();

            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Office has been successfully updated.']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Updating Office Information  Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with(['error' => 'Updating Office Information failed. Please try again!']);
        }
    }

    public function deactivate(Request $request, Office $office)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'offId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $office = Office::findOrFail($validatedData['offId']);
            $office->load('requestPpmp');

            if($office->requestPpmp->isEmpty()) {
                $office->forceDelete();
                DB::commit();
                return redirect()->back()
                    ->with(['message' => 'Office has been successfully removed.']);
            }

            // $office->fill([
            //     'office_status' => 'deactivated',
            //     'updated_by' => $validatedData['updatedBy'],
            // ])->save();

            DB::commit();
            return redirect()->back()
                ->with(['error' => 'Unable to removed the Office due to dependencies. ']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Deletion of Office Information Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with(['error' => 'Deletion Office Information failed. Please try again!']);
        }
    }
}
