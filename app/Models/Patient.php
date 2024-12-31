<?php

namespace App\Models;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Patient extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'Firstname',
        'phone',
        'Lastname',
        'Idnum',
        'dateOfBirth',
        'created_at',
        'updated_at',
    ];

    public function appointments()
{
    return $this->hasMany(Appointment::class);
}
}
