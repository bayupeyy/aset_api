<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Asset extends Model {
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'asset_code','name','brand','model','serial_number','specs',
        'purchase_price','purchase_date','warranty_until','invoice_number',
        'photo','status','notes','category_id','vendor_id','location_id','current_user_id',
    ];
    protected $casts = [
        'specs'          => 'array',
        'purchase_date'  => 'date',
        'warranty_until' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function category()    { return $this->belongsTo(AssetCategory::class); }
    public function vendor()      { return $this->belongsTo(Vendor::class); }
    public function location()    { return $this->belongsTo(Location::class); }
    public function currentUser() { return $this->belongsTo(User::class, 'current_user_id'); }
    public function barcode()     { return $this->hasOne(Barcode::class)->where('is_active', true)->latest(); }
    public function userHistory()     { return $this->hasMany(AssetUserHistory::class)->latest(); }
    public function locationHistory() { return $this->hasMany(AssetLocationHistory::class)->latest('moved_at'); }
    public function statusLog()       { return $this->hasMany(AssetStatusLog::class)->latest('changed_at'); }
    public function maintenances()    { return $this->hasMany(Maintenance::class)->latest(); }

    public static function generateCode(string $prefix): string {
        $year  = date('Y');
        $count = static::withTrashed()->where('asset_code','like',"IT-{$year}-{$prefix}-%")->count();
        return sprintf('IT-%s-%s-%05d', $year, strtoupper($prefix), $count + 1);
    }

    public function getTimelineAttribute(): array {
        $items = collect();
        foreach ($this->userHistory as $h)     { $items->push(['type'=>'user',        'date'=>$h->created_at, 'data'=>$h]); }
        foreach ($this->locationHistory as $h) { $items->push(['type'=>'location',    'date'=>$h->moved_at,   'data'=>$h]); }
        foreach ($this->statusLog as $h)       { $items->push(['type'=>'status',      'date'=>$h->changed_at, 'data'=>$h]); }
        foreach ($this->maintenances as $h)    { $items->push(['type'=>'maintenance', 'date'=>$h->completed_date??$h->scheduled_date, 'data'=>$h]); }
        return $items->sortByDesc('date')->values()->toArray();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        if (array_key_exists('current_user', $array)) {
            $array['currentUser'] = $array['current_user'];
        }
        return $array;
    }
}
