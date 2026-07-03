<?php
use App\Http\Controllers\Api\{
    AuthController,
    AssetController,
    BarcodeController,
    MasterDataController,
    MaintenanceController,
    ReportController
};
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Asset API is running successfully.'
    ]);
});

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/scan/{code}', [AssetController::class, 'findByBarcode']);

// ── Protected ────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Assets CRUD
    Route::apiResource('assets', AssetController::class);
    Route::post('/assets/{asset}/photo', [AssetController::class, 'uploadPhoto']);
    Route::post('/assets/{asset}/assign', [AssetController::class, 'assign']);
    Route::post('/assets/{asset}/move', [AssetController::class, 'move']);
    Route::post('/assets/{asset}/status', [AssetController::class, 'changeStatus']);
    Route::get('/assets/{asset}/history', [AssetController::class, 'history']);

    // Barcode
    Route::get('/assets/{asset}/label-data', [BarcodeController::class, 'labelData']);
    Route::post('/assets/{asset}/barcode/regenerate', [BarcodeController::class, 'regenerate']);
    Route::post('/barcodes/batch', [BarcodeController::class, 'batchLabelData']);

    // Master Data
    Route::get('/categories', [MasterDataController::class, 'categories']);
    Route::post('/categories', [MasterDataController::class, 'storeCategory']);
    Route::put('/categories/{category}', [MasterDataController::class, 'updateCategory']);
    Route::delete('/categories/{category}', [MasterDataController::class, 'destroyCategory']);

    Route::get('/vendors', [MasterDataController::class, 'vendors']);
    Route::post('/vendors', [MasterDataController::class, 'storeVendor']);
    Route::put('/vendors/{vendor}', [MasterDataController::class, 'updateVendor']);

    Route::get('/locations', [MasterDataController::class, 'locations']);
    Route::post('/locations', [MasterDataController::class, 'storeLocation']);
    Route::put('/locations/{location}', [MasterDataController::class, 'updateLocation']);
    Route::delete('/locations/{location}', [MasterDataController::class, 'destroyLocation']);

    Route::get('/divisions', [MasterDataController::class, 'divisions']);
    Route::post('/divisions', [MasterDataController::class, 'storeDivision']);
    Route::put('/divisions/{division}', [MasterDataController::class, 'updateDivision']);

    Route::get('/users', [MasterDataController::class, 'users']);
    Route::post('/users', [MasterDataController::class, 'storeUser']);
    Route::put('/users/{user}', [MasterDataController::class, 'updateUser']);
    Route::delete('/users/{user}', [MasterDataController::class, 'destroyUser']);

    // Maintenance
    Route::apiResource('maintenance', MaintenanceController::class);
    Route::post('/maintenance/{maintenance}/complete', [MaintenanceController::class, 'complete']);

    // Reports
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/assets', [ReportController::class, 'assets']);
});
