<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Asset, Barcode};
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    // Data label untuk satu aset
    public function labelData(Asset $asset)
    {
        $barcode = $asset->barcode;
        if (!$barcode)
            return response()->json(['message' => 'Barcode belum tersedia'], 404);

        return response()->json([
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'serial_number' => $asset->serial_number,
            'category' => $asset->category?->name,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'barcode_value' => $barcode->barcode_value,
            'qr_value' => $barcode->qr_value,
            'current_user' => $asset->currentUser?->name,
            'location' => $asset->location?->full_name,
        ]);
    }

    // Data label massal
    public function batchLabelData(Request $request)
    {
        $request->validate([
            'asset_ids' => 'required|array|min:1|max:100',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $assets = Asset::whereIn('id', $request->asset_ids)
            ->with(['barcode', 'category', 'location', 'currentUser'])
            ->get();

        $labels = $assets->map(fn($a) => [
            'asset_code' => $a->asset_code,
            'name' => $a->name,
            'serial_number' => $a->serial_number,
            'category' => $a->category?->name,
            'brand' => $a->brand,
            'model' => $a->model,
            'barcode_value' => $a->barcode?->barcode_value,
            'qr_value' => $a->barcode?->qr_value,
            'current_user' => $a->currentUser?->name,
            'location' => $a->location?->full_name,
        ]);

        return response()->json(['labels' => $labels]);
    }

    // Regenerate barcode
    public function regenerate(Request $request, Asset $asset)
    {
        \App\Models\Barcode::where('asset_id', $asset->id)->update(['is_active' => false]);

        $barcode = Barcode::create([
            'asset_id' => $asset->id,
            'barcode_value' => $asset->asset_code,
            'qr_value' => config('app.url') . '/api/scan/' . $asset->asset_code,
            'is_active' => true,
            'generated_by' => $request->user()->id,
        ]);

        return response()->json($barcode);
    }
}
