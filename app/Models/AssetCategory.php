<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AssetCategory extends Model {
    use HasUuids;
    protected $fillable = ['name', 'code_prefix', 'description', 'icon'];
    public function assets() { return $this->hasMany(Asset::class, 'category_id'); }
}
