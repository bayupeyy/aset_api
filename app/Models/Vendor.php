<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Vendor extends Model {
    use HasUuids;
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
