<?php

namespace App\Imports;

use App\Models\Placeholder;
use Maatwebsite\Excel\Concerns\ToModel;

class PlaceholderImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Placeholder([
            'name' => $row[0],
            'description' => $row[1],
            'doctor_id' => $row[2],
            'specializations_id' => $row[3],
        ]);
        
    }
}
