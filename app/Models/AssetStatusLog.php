<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AssetStatusLog extends Model {
    use HasUuids;
    protected $table    = 'asset_status_log';
    protected $fillable = ['asset_id','old_status','new_status','notes','changed_by','changed_at'];
    protected $casts    = ['changed_at' => 'datetime'];
    public function asset()     { return $this->belongsTo(Asset::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
