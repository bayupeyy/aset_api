<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AssetLocationHistory extends Model {
    use HasUuids;
    protected $table    = 'asset_location_history';
    protected $fillable = ['asset_id','from_location_id','to_location_id','reason','moved_by','moved_at'];
    protected $casts    = ['moved_at' => 'datetime'];
    public function asset()        { return $this->belongsTo(Asset::class); }
    public function fromLocation() { return $this->belongsTo(Location::class, 'from_location_id'); }
    public function toLocation()   { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function movedBy()      { return $this->belongsTo(User::class, 'moved_by'); }
}
