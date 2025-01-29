<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcludedDate extends Model
{
    protected $fillable = [
        'doctor_id',
        'end_date',
        'start_date',
        'apply_for_all_years',
        'reason',
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }
}
