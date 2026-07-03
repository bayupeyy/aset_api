<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasUuids, HasApiTokens;
    protected $fillable = ['name','email','employee_id','phone','password','role','division_id','is_active'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['is_active' => 'boolean', 'password' => 'hashed'];
    public function division() { return $this->belongsTo(Division::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function canManage(): bool { return in_array($this->role, ['admin', 'staff']); }
}
