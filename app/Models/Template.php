<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use doctor and tempalte placeholder relation
use App\Models\Doctor;
use App\Models\PlaceholderTemplate;
use App\Models\Specialization;


class Template extends Model
{
    protected $fillable = [
        'name',
        'description',
        'file_path',
        'content',
        'doctor_id',
        'mime_type',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function placeholders()
    {
        return $this->hasMany(PlaceholderTemplate::class);
    }

    public function specializations()
    {
        return $this->belongsTo(Specialization::class);
    }
}
