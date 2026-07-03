<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Location extends Model {
    use HasUuids;
    protected $fillable = ['building', 'floor', 'room', 'description'];
    public function getFullNameAttribute(): string {
        return implode(' · ', array_filter([$this->building, $this->floor, $this->room]));
    }
}
