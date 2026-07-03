<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Maintenance extends Model {
    use HasUuids;
    protected $table    = 'maintenance';
    protected $fillable = ['asset_id','type','vendor_name','description','scheduled_date','completed_date','cost','ticket_number','status','notes','created_by'];
    protected $casts    = ['scheduled_date' => 'date', 'completed_date' => 'date', 'cost' => 'decimal:2'];
    public function asset()     { return $this->belongsTo(Asset::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
