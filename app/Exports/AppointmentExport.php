<?php

namespace App\Exports;

use App\Models\AppModelsAppointment;
use Maatwebsite\Excel\Concerns\FromCollection;

class AppointmentExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return AppModelsAppointment::all();
    }
}
