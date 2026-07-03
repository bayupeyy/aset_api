<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{AssetCategory, Vendor, Location, Division, User};
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    // ── Kategori ─────────────────────────────
    public function categories()    { return response()->json(AssetCategory::all()); }
    public function storeCategory(Request $request) {
        $data = $request->validate(['name'=>'required','code_prefix'=>'required|unique:asset_categories','description'=>'nullable']);
        return response()->json(AssetCategory::create($data), 201);
    }
    public function updateCategory(Request $request, AssetCategory $category) {
        $data = $request->validate(['name'=>'required','description'=>'nullable']);
        $category->update($data);
        return response()->json($category);
    }
    public function destroyCategory(AssetCategory $category) {
        $category->delete(); return response()->json(['message'=>'Dihapus']);
    }

    // ── Vendor ───────────────────────────────
    public function vendors()    { return response()->json(Vendor::where('is_active',true)->get()); }
    public function storeVendor(Request $request) {
        $data = $request->validate(['name'=>'required','contact_person'=>'nullable','phone'=>'nullable','email'=>'nullable|email','address'=>'nullable']);
        return response()->json(Vendor::create($data), 201);
    }
    public function updateVendor(Request $request, Vendor $vendor) {
        $data = $request->validate(['name'=>'required','contact_person'=>'nullable','phone'=>'nullable','email'=>'nullable|email','address'=>'nullable']);
        $vendor->update($data); return response()->json($vendor);
    }

    // ── Lokasi ───────────────────────────────
    public function locations()   { return response()->json(Location::all()); }
    public function storeLocation(Request $request) {
        $data = $request->validate(['building'=>'required','floor'=>'nullable','room'=>'nullable','description'=>'nullable']);
        return response()->json(Location::create($data), 201);
    }
    public function updateLocation(Request $request, Location $location) {
        $data = $request->validate(['building'=>'required','floor'=>'nullable','room'=>'nullable']);
        $location->update($data); return response()->json($location);
    }
    public function destroyLocation(Location $location) {
        $location->delete(); return response()->json(['message'=>'Dihapus']);
    }

    // ── Divisi ───────────────────────────────
    public function divisions()   { return response()->json(Division::all()); }
    public function storeDivision(Request $request) {
        $data = $request->validate(['name'=>'required','code'=>'required|unique:divisions','description'=>'nullable']);
        return response()->json(Division::create($data), 201);
    }
    public function updateDivision(Request $request, Division $division) {
        $data = $request->validate(['name'=>'required','description'=>'nullable']);
        $division->update($data); return response()->json($division);
    }

    // ── User ─────────────────────────────────
    public function users(Request $request) {
        return response()->json(User::with('division')
            ->when($request->search, fn($q)=>$q->where('name','like',"%{$request->search}%"))
            ->where('is_active', true)->get());
    }
    public function storeUser(Request $request) {
        $data = $request->validate([
            'name'=>'required','email'=>'required|email|unique:users',
            'password'=>'required|min:8','role'=>'required|in:admin,staff,viewer',
            'employee_id'=>'nullable|unique:users','phone'=>'nullable',
            'division_id'=>'nullable|exists:divisions,id',
        ]);
        return response()->json(User::create($data)->load('division'), 201);
    }
    public function updateUser(Request $request, User $user) {
        $data = $request->validate([
            'name'=>'required','role'=>'required|in:admin,staff,viewer',
            'phone'=>'nullable','division_id'=>'nullable|exists:divisions,id',
            'is_active'=>'boolean',
        ]);
        if ($request->password) $data['password'] = bcrypt($request->password);
        $user->update($data);
        return response()->json($user->load('division'));
    }
    public function destroyUser(User $user) {
        $user->update(['is_active' => false]);
        return response()->json(['message' => 'User dinonaktifkan']);
    }
}
