<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Trainer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'photo',
        'bio',
        'specialization',
        'email',
        'phone',
        'is_active',
        'order',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($trainer) {
            if (empty($trainer->slug)) {
                $trainer->slug = Str::slug($trainer->first_name.' '.$trainer->last_name);
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Aktivní trenéři v pořadí, v jakém se mají zobrazit.
     *
     * Na stejný dotaz se ptají dvě různá místa: sdílená metoda
     * Controller::allTrainers() (pás trenérů na stránkách) a view composer
     * hlavičky v AppServiceProvider. Scope je drží pohromadě, aby se
     * nerozešly — jinak by se stalo, že hlavička ukazuje jiné trenéry
     * nebo jiné pořadí než blok pod ní.
     */
    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('order');
    }

    // Accessor - celé jméno
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
