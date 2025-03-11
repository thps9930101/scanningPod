<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}


