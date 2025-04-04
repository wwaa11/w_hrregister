<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'project_name',
        'project_detail',
        'project_active',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class)->where('slot_active', true)->orderBy('slot_index', 'asc');
    }
}
