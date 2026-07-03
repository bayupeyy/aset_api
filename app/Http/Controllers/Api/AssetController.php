<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Asset, AssetCategory, AssetUserHistory, AssetLocationHistory, AssetStatusLog, Barcode};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['category','location','currentUser','barcode'])
            ->when($request->search, fn($q) =>
                $q->where(fn($sub) =>
                    $sub->where('name','like',"%{$request->search}%")
                        ->orWhere('asset_code','like',"%{$request->search}%")
                        ->orWhere('serial_number','like',"%{$request->search}%")
                        ->orWhere('brand','like',"%{$request->search}%")
                )
            )
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->status,       fn($q) => $q->where('status', $request->status))
            ->when($request->location_id,  fn($q) => $q->where('location_id', $request->location_id))
            ->when($request->user_id,      fn($q) => $q->where('current_user_id', $request->user_id))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'category_id'     => 'required|exists:asset_categories,id',
            'brand'           => 'nullable|string|max:100',
            'model'           => 'nullable|string|max:100',
            'serial_number'   => 'nullable|string|unique:assets,serial_number',
            'specs'           => 'nullable|array',
            'purchase_price'  => 'nullable|numeric|min:0',
            'purchase_date'   => 'nullable|date',
            'warranty_until'  => 'nullable|date|after_or_equal:purchase_date',
            'invoice_number'  => 'nullable|string|max:100',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'location_id'     => 'nullable|exists:locations,id',
            'current_user_id' => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $category            = AssetCategory::findOrFail($validated['category_id']);
            $validated['asset_code'] = Asset::generateCode($category->code_prefix);
            $asset = Asset::create($validated);

            // Buat barcode otomatis
            Barcode::create([
                'asset_id'      => $asset->id,
                'barcode_value' => $asset->asset_code,
                'qr_value'      => config('app.url') . '/api/scan/' . $asset->asset_code,
                'is_active'     => true,
                'generated_by'  => $request->user()->id,
            ]);

            // Catat pengguna awal
            if (!empty($validated['current_user_id'])) {
                AssetUserHistory::create([
                    'asset_id'   => $asset->id,
                    'user_id'    => $validated['current_user_id'],
                    'start_date' => now()->toDateString(),
                    'assigned_by'=> $request->user()->id,
                    'notes'      => 'Penugasan awal saat aset didaftarkan',
                ]);
            }

            // Catat lokasi awal
            if (!empty($validated['location_id'])) {
                AssetLocationHistory::create([
                    'asset_id'       => $asset->id,
                    'from_location_id' => null,
                    'to_location_id' => $validated['location_id'],
                    'reason'         => 'Lokasi awal saat aset didaftarkan',
                    'moved_by'       => $request->user()->id,
                    'moved_at'       => now(),
                ]);
            }

            // Catat status awal
            AssetStatusLog::create([
                'asset_id'   => $asset->id,
                'old_status' => '',
                'new_status' => 'active',
                'notes'      => 'Aset baru didaftarkan',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
            ]);

            return response()->json(
                $asset->load(['category','location','currentUser','barcode']), 201
            );
        });
    }

    public function show(Asset $asset)
    {
        return response()->json(
            $asset->load([
                'category','vendor','location','currentUser.division','barcode',
                'userHistory.user.division','userHistory.assignedBy',
                'locationHistory.fromLocation','locationHistory.toLocation','locationHistory.movedBy',
                'statusLog.changedBy',
                'maintenances.createdBy',
            ])
        );
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial_number'  => 'nullable|string|unique:assets,serial_number,'.$asset->id,
            'specs'          => 'nullable|array',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date'  => 'nullable|date',
            'warranty_until' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:100',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'notes'          => 'nullable|string',
        ]);

        $asset->update($validated);
        return response()->json($asset->fresh(['category','location','currentUser','barcode']));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->json(['message' => 'Aset berhasil dihapus']);
    }

    public function uploadPhoto(Request $request, Asset $asset)
    {
        $request->validate(['photo' => 'required|image|max:2048']);
        if ($asset->photo) Storage::disk('public')->delete($asset->photo);
        $path = $request->file('photo')->store('assets/photos', 'public');
        $asset->update(['photo' => $path]);
        return response()->json(['photo_url' => Storage::disk('public')->url($path)]);
    }

    // ── Assign pengguna ─────────────────────────────────────
    public function assign(Request $request, Asset $asset)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'start_date' => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $asset) {
            AssetUserHistory::where('asset_id', $asset->id)
                ->whereNull('end_date')
                ->update(['end_date' => now()->toDateString()]);

            $history = AssetUserHistory::create([
                'asset_id'    => $asset->id,
                'user_id'     => $request->user_id,
                'start_date'  => $request->start_date,
                'notes'       => $request->notes,
                'assigned_by' => $request->user()->id,
            ]);

            $asset->update(['current_user_id' => $request->user_id]);
            return response()->json($history->load(['user.division','assignedBy']));
        });
    }

    // ── Pindah lokasi ────────────────────────────────────────
    public function move(Request $request, Asset $asset)
    {
        $request->validate([
            'to_location_id' => 'required|exists:locations,id',
            'reason'         => 'required|string',
        ]);

        $history = AssetLocationHistory::create([
            'asset_id'         => $asset->id,
            'from_location_id' => $asset->location_id,
            'to_location_id'   => $request->to_location_id,
            'reason'           => $request->reason,
            'moved_by'         => $request->user()->id,
            'moved_at'         => now(),
        ]);

        $asset->update(['location_id' => $request->to_location_id]);
        return response()->json($history->load(['fromLocation','toLocation','movedBy']));
    }

    // ── Ubah status ──────────────────────────────────────────
    public function changeStatus(Request $request, Asset $asset)
    {
        $request->validate([
            'status' => 'required|in:active,maintenance,storage,borrowed,disposed,lost',
            'notes'  => 'nullable|string',
        ]);

        $log = AssetStatusLog::create([
            'asset_id'   => $asset->id,
            'old_status' => $asset->status,
            'new_status' => $request->status,
            'notes'      => $request->notes,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        $asset->update(['status' => $request->status]);
        return response()->json($log->load('changedBy'));
    }

    // ── Timeline riwayat ─────────────────────────────────────
    public function history(Asset $asset)
    {
        $asset->load([
            'userHistory.user.division','userHistory.assignedBy',
            'locationHistory.fromLocation','locationHistory.toLocation','locationHistory.movedBy',
            'statusLog.changedBy',
            'maintenances.createdBy',
        ]);
        return response()->json(['timeline' => $asset->timeline]);
    }

    // ── Cari via barcode ─────────────────────────────────────
    public function findByBarcode(string $code)
    {
        $barcode = Barcode::where('barcode_value', $code)
            ->orWhere('qr_value', 'like', "%{$code}")
            ->firstOrFail();

        $barcode->recordScan();

        return response()->json(
            $barcode->asset->load([
                'category','location','currentUser.division','barcode',
                'userHistory.user.division',
                'locationHistory.toLocation',
                'statusLog',
            ])
        );
    }
}
