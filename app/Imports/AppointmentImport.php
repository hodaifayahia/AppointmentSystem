<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentImport implements 
    ToModel, 
    WithHeadingRow, 
    SkipsOnError,
    SkipsOnFailure,
    WithBatchInserts
{
    protected $createdBy;
    protected $doctorId;
    protected $errors = [];
    protected $successCount = 0;

    public function __construct($createdBy, $doctorId)
    {
        $this->createdBy = $createdBy;
        $this->doctorId = $doctorId;
    }

    public function model(array $row)
    {
        DB::beginTransaction();

        try {
            // Format dates and times
            $appointmentTime = $this->formatTime($row['appointment_time']);
            $appointmentDate = $this->formatDate('2025-2-11');

            $phone = $this->cleanPhoneNumber($row['phone'] ?? '');
            
            // Create or find the patient
            $patient = Patient::firstOrCreate(
                [
                    'phone' => $phone,
                    'Firstname' => ucfirst(strtolower(trim($row['firstname']))),
                    'Lastname' => ucfirst(strtolower(trim($row['lastname']))),
                ],
                [
                    'created_by' => $this->createdBy,
                ]
            );

            // Create the appointment
            $appointment = new Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->doctor_id = $this->doctorId;
            $appointment->appointment_date = $appointmentDate;
            $appointment->appointment_time = $appointmentTime; // Now properly formatted
            $appointment->status = 0;
            $appointment->created_by = $this->createdBy;
            $appointment->notes = $row['notes'] ?? null;
            $appointment->save();

            DB::commit();
            $this->successCount++;
            return $appointment;

        } catch (Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Error processing row: " . $e->getMessage();
            Log::error("Error processing row: " . $e->getMessage());
            return null;
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = "Error: " . $e->getMessage();
        Log::error("Error: " . $e->getMessage());
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            Log::error("Row {$failure->row()}: " . implode(', ', $failure->errors()));
        }
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    protected function formatDate($date)
    {
        if (empty($date)) {
            throw new \Exception("Appointment date is required.");
        }

        try {
            // Check if the date is a numeric value (Excel serial number)
            if (is_numeric($date)) {
                return Carbon::create(1900, 1, 1)->addDays((int) $date - 2)->format('Y-m-d');
            }

            // If not a numeric value, parse it as a regular date string
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable $e) {
            throw new \Exception("Invalid date format: {$date}. Expected format: Y-m-d or Excel serial number.");
        }
    }

    protected function formatTime($time)
    {
        if (empty($time)) {
            throw new \Exception("Appointment time is required.");
        }

        try {
            // Handle Excel time format (decimal between 0 and 1)
            if (is_numeric($time) && $time >= 0 && $time < 1) {
                $seconds = round($time * 86400); // Convert to seconds (24 * 60 * 60)
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $secs = $seconds % 60;
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
            }
            
            // Handle string time format
            if (is_string($time)) {
                return Carbon::parse($time)->format('H:i:s');
            }

            throw new \Exception("Invalid time value");
        } catch (Throwable $e) {
            throw new \Exception("Invalid time format: {$time}. Expected format: H:i:s or Excel time value.");
        }
    }

    protected function cleanPhoneNumber($phone)
    {
        return preg_replace('/[^0-9]/', '', (string)$phone);
    }
}