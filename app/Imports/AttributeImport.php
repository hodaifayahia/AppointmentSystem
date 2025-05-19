<?php

namespace App\Imports;

use App\Models\Attribute;
use Maatwebsite\Excel\Concerns\ToModel;

class AttributeImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
         // Find the placeholder by its name
    $placeholder = Placeholder::where('name', $row[2])->first();

    // Optional: Handle case if not found (fail silently, or log, or throw)
    if (!$placeholder) {
        // You can return null to skip this row
        return null;

        // Or throw an exception
        // throw new \Exception("Placeholder not found: {$row[2]}");
    }

    return new Attribute([
        'name' => $row[0],
        'value' => $row[1],
        'placeholder_id' => $placeholder->id,
    ]);
    }
}
