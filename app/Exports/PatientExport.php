<?php

namespace App\Exports;

use App\Models\Patient;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PatientExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Patient::select('Firstname', 'Lastname', 'phone', 'Idnum', 'dateOfBirth')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Firstname',
            'Lastname',
            'Phone',
            'ID Number', 
            'Date of Birth',
        ];
    }
}