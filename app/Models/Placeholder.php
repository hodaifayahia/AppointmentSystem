<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Model;

class Placeholder extends Model
{

    protected $table = 'placeholders';
    protected $fillable = [
        'name',
        'description',
        'doctor_id',
        'specializations_id',
    ];
    public function doctor()
    {
        return $this->belongsTo(Doctor::class ,'doctor_id');
    }
    public function specializations() {

        return $this->belongsTo(Specialization::class ,'specializations_id');
    }
    
    

}
