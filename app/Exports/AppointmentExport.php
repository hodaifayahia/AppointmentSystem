<?php

namespace App\Exports;

use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppointmentExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Fetch appointments with related patient data
        return Appointment::with('patient')->get();
    }

    /**
     * Map the data for each row
     *
     * @param mixed $appointment
     * @return array
     */
    public function map($appointment): array
    {
        return [
            // Appointment data
            $appointment->id,
            $appointment->doctor_id,
            $appointment->appointment_date,
            $appointment->appointment_time,
            $this->formatStatus($appointment->status), // Convert enum to integer or string
            $appointment->notes,
            $appointment->created_at,
            $appointment->updated_at,

            // Patient data
            $appointment->patient->Firstname ?? 'N/A', // Use null coalescing to handle missing relationships
            $appointment->patient->Lastname ?? 'N/A',
            $appointment->patient->phone ?? 'N/A',
            $appointment->patient->Idnum ?? 'N/A',
            $appointment->patient->dateOfBirth ?? 'N/A',
        ];
    }

    /**
     * Define the headings for the Excel file
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            // Appointment headers
            'Appointment ID',
            'Doctor ID',
            'Appointment Date',
            'Appointment Time',
            'Status',
            'Notes',
            'Created At',
            'Updated At',

            // Patient headers
            'Patient Firstname',
            'Patient Lastname',
            'Patient Phone',
            'Patient ID Number',
            'Patient Date of Birth',
        ];
    }

    /**
     * Convert the status enum to its integer value
     *
     * @param mixed $status
     * @return int
     */
    protected function formatStatus($status): int
    {
        // If the status is an enum, return its integer value
        if ($status instanceof \BackedEnum) {
            return $status->value; // Use the enum's value
        }

        // If the status is not an enum, return a default value (e.g., 0)
        return 0;
    }
}