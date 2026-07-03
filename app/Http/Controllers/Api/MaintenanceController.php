<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Asset, Maintenance, AssetStatusLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Maintenance::with(['asset.category','createdBy'])
            ->when($request->asset_id, fn($q) => $q->where('asset_id', $request->asset_id))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->latest();
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'       => 'required|exists:assets,id',
            'type'           => 'required|in:preventive,corrective,upgrade',
            'vendor_name'    => 'nullable|string',
            'description'    => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'cost'           => 'nullable|numeric|min:0',
            'ticket_number'  => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);
        $validated['created_by'] = $request->user()->id;

        return DB::transaction(function () use ($validated, $request) {
            $maintenance = Maintenance::create($validated);

            // Otomatis ubah status aset ke maintenance
            $asset = Asset::find($validated['asset_id']);
            AssetStatusLog::create([
                'asset_id'   => $asset->id,
                'old_status' => $asset->status,
                'new_status' => 'maintenance',
                'notes'      => 'Masuk jadwal maintenance: ' . $maintenance->ticket_number,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
            ]);
            $asset->update(['status' => 'maintenance']);

            return response()->json($maintenance->load(['asset','createdBy']), 201);
        });
    }

    public function show(Maintenance $maintenance)
    {
        return response()->json($maintenance->load(['asset.category','createdBy']));
    }

    public function complete(Request $request, Maintenance $maintenance)
    {
        $request->validate([
            'completed_date' => 'required|date',
            'cost'           => 'nullable|numeric',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $maintenance) {
            $maintenance->update([
                'status'         => 'completed',
                'completed_date' => $request->completed_date,
                'cost'           => $request->cost,
                'notes'          => $request->notes,
            ]);

            // Kembalikan status aset ke active
            $asset = $maintenance->asset;
            AssetStatusLog::create([
                'asset_id'   => $asset->id,
                'old_status' => 'maintenance',
                'new_status' => 'active',
                'notes'      => 'Maintenance selesai: ' . $maintenance->ticket_number,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
            ]);
            $asset->update(['status' => 'active']);

            return response()->json($maintenance->fresh(['asset','createdBy']));
        });
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Maintenance dibatalkan']);
    }
}
