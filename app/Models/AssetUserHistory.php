<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AssetUserHistory extends Model {
    use HasUuids;
    protected $table    = 'asset_user_history';
    protected $fillable = ['asset_id','user_id','start_date','end_date','notes','assigned_by'];
    protected $casts    = ['start_date' => 'date', 'end_date' => 'date'];
    public function asset()      { return $this->belongsTo(Asset::class); }
    public function user()       { return $this->belongsTo(User::class, 'user_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
