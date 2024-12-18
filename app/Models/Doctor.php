<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    

    protected $fillable = [
        'id',
        'specialization',
        'day',
        'start_time',
        'end_time',
        'number_of_patient',
        'frequency',
        'specific_date',
        'created_at',
        'updated_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
