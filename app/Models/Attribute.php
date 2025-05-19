<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Placeholder;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'value',
        'input_type',
        'placeholder_id'
    ];

    public function placeholder()
    {
        return $this->belongsTo(Placeholder::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    

}
