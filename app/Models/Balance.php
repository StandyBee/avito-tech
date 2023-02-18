<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance'
    ];

    protected $attributes = [
        'balance' => 0.0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
