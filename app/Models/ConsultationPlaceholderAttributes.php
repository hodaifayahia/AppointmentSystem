<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationPlaceholderAttributes extends Model
{
    protected $table = 'consultation_placeholder_attributes';
    
    protected $fillable = [
        'consultation_id',
        'placeholder_id',
        'attribute_id',
        'attribute_value'
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function placeholder()
    {
        return $this->belongsTo(Placeholder::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
