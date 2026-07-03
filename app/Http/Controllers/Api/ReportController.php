<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Asset, AssetCategory, Maintenance};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard()
    {
        $total       = Asset::count();
        $byStatus    = Asset::select('status', DB::raw('count(*) as total'))
                            ->groupBy('status')->pluck('total', 'status');
        $byCategory  = Asset::select('category_id', DB::raw('count(*) as total'))
                            ->with('category')
                            ->groupBy('category_id')
                            ->get()
                            ->map(fn($a) => ['category' => $a->category?->name, 'total' => $a->total]);
        $warrantyExp = Asset::whereNotNull('warranty_until')
                            ->whereBetween('warranty_until', [now(), now()->addDays(30)])
                            ->where('status', 'active')->count();
        $maintenance = Maintenance::where('status', 'scheduled')
                                  ->orWhere('status', 'in_progress')->count();
        $recentActivity = Asset::with(['category','currentUser'])
                               ->latest()->take(10)->get();

        return response()->json(compact(
            'total', 'byStatus', 'byCategory',
            'warrantyExp', 'maintenance', 'recentActivity'
        ));
    }

    public function assets(Request $request)
    {
        $query = Asset::with(['category','location','currentUser','vendor'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->status,       fn($q) => $q->where('status', $request->status))
            ->latest();

        return response()->json($query->paginate(50));
    }
}
