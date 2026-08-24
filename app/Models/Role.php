<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const CANONICAL_CODES = ['customer', 'staff', 'kitchen', 'manager', 'admin'];

    public const EMPLOYEE_CODES = ['staff', 'kitchen', 'manager', 'admin'];

    protected $fillable = ['name', 'code', 'description'];

    public function scopeCanonical(Builder $query): Builder
    {
        return $query->whereIn('code', self::CANONICAL_CODES);
    }

    public function scopeEmployee(Builder $query): Builder
    {
        return $query->whereIn('code', self::EMPLOYEE_CODES);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
