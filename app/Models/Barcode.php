<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Barcode extends Model {
    use HasUuids;
    protected $fillable = ['asset_id','barcode_value','qr_value','is_active','last_scanned_at','scan_count','generated_by'];
    protected $casts = ['is_active' => 'boolean', 'last_scanned_at' => 'datetime'];
    public function asset() { return $this->belongsTo(Asset::class); }
    public function recordScan(): void {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => now()]);
    }
}
