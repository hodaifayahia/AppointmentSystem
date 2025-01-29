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

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Format dates
            $appointmentDate = $this->formatDate($row['appointment_date']);
            $appointmentTime = $this->formatTime($row['appointment_time']);
            $dateOfBirth = isset($row['date_of_birth']) ? $this->formatDate($row['date_of_birth']) : null;
            
            // Clean phone number
            $phone = $this->cleanPhoneNumber($row['phone']);

            // Create or find the patient
            $patient = Patient::firstOrCreate(
                ['phone' => $phone],
                [
                    'Firstname' => ucfirst(strtolower(trim($row['first_name']))),
                    'Lastname' => ucfirst(strtolower(trim($row['last_name']))),
                    'created_by' => $this->createdBy,
                    'Idnum' => $row['idnum'] ?? null,
                    'dateOfBirth' => $dateOfBirth,
                ]
            );

            // Create the appointment without specifying an ID
            $appointment = new Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->doctor_id = $this->doctorId;
            $appointment->appointment_date = $appointmentDate;
            $appointment->appointment_time = $appointmentTime;
            $appointment->status = 0;
            $appointment->created_by = $this->createdBy;
            $appointment->notes = $row['notes'] ?? null;
            $appointment->save();

            $this->successCount++;
            return $appointment;

        } catch (Throwable $e) {
            $this->errors[] = "Error processing row: " . $e->getMessage();
            return null;
        }
    }

    /**
     * @param Throwable $e
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = "Error: " . $e->getMessage();
    }

    /**
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    /**
     * Reduce batch size to prevent potential issues
     * @return int
     */
    public function batchSize(): int
    {
        return 50;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Format date to Y-m-d (only date)
     * @param string $date
     * @return string|null
     */
    protected function formatDate($date)
    {
        if (empty($date)) {
            throw new \Exception("Appointment date is required.");
        }

        try {
            // Check if the date is a numeric value (Excel serial number)
            if (is_numeric($date)) {
                // Convert Excel serial number to a date string
                return Carbon::create(1900, 1, 1)->addDays((int) $date - 2)->format('Y-m-d');
            }

            // If not a numeric value, parse it as a regular date string
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable $e) {
            throw new \Exception("Invalid date format: {$date}. Expected format: Y-m-d or Excel serial number.");
        }
    }

    /**
     * Format time to H:i:s (only time)
     * @param string $time
     * @return string
     */
    protected function formatTime($time)
    {
        if (empty($time)) {
            throw new \Exception("Appointment time is required.");
        }

        try {
            // Check if the time is a numeric value (Excel serial number)
            if (is_numeric($time)) {
                // Convert Excel serial number to a time string
                $hours = (int) ($time * 24);
                $minutes = (int) (($time * 24 * 60) % 60);
                $seconds = (int) (($time * 24 * 60 * 60) % 60);
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            // If not a numeric value, parse it as a regular time string
            return Carbon::parse($time)->format('H:i:s');
        } catch (Throwable $e) {
            throw new \Exception("Invalid time format: {$time}. Expected format: H:i:s or Excel serial number.");
        }
    }

    /**
     * Clean phone number
     * @param string $phone
     * @return string
     */
    protected function cleanPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure minimum length
        if (strlen($phone) < 8) {
            throw new \Exception("Invalid phone number: too short");
        }

        return $phone;
    }
}