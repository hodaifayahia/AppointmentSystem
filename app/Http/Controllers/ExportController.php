<?php

namespace App\Http\Controllers;

use App\Exports\AppointmentExport;
use App\Exports\PatientExport;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function ExportUsers()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }
    public function ExportPatients()
    {
        return Excel::download(new PatientExport, 'Patients.xlsx');
    }
    public function ExportAppointment()
    {
        return Excel::download(new AppointmentExport, 'appointments.xlsx');
    }
}
