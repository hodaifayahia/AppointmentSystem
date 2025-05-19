<?php

namespace App\Models;

use App\Models\Consultation;
use App\Models\Template;
use App\Models\Folder;
use App\Models\Placeholder;
use Illuminate\Database\Eloquent\Model;

class PlaceholderTemplate extends Model
{
    protected $fillable = [
        'template_id',
        'placeholder_id',
    ];

    public function template()
    {
        return $this->hasMany(Template::class);
    }

    public function placeholder()
    {
        return $this->hasMany(Placeholder::class);
    }
    public function folder()
    {
        return $this->hasMany(Folder::class);
    }
    public function consultation()
    {
        return $this->hasMany(Consultation::class);
    }
}
