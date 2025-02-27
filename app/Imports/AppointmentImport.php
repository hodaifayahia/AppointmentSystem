<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use App\DayOfWeekEnum;
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
    protected $dateSlots = [];
    protected $lastAppointmentTimes = [];
    protected $intervalMinutes = 15;
    protected $usedSlots = [];
    protected $existingAppointments = [];
    protected $processedPatients = [];

    public function __construct($createdBy, $doctorId)
    {
        $this->createdBy = $createdBy;
        $this->doctorId = $doctorId;
    }

    protected function getDoctorWorkingHours($doctorId, $date)
    {
        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
        
        $schedules = Schedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->get();
        
        $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
        
        if (!$doctor || $schedules->isEmpty()) {
            return [];
        }
        
        $workingHours = [];
        
        foreach (['morning', 'afternoon'] as $shift) {
            $schedule = $schedules->firstWhere('shift_period', $shift);
            if ($schedule) {
                try {
                    $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                    
                    if ($doctor->time_slot !== null) {
                        // Time slot based calculation
                        $timeSlotMinutes = (int) $doctor->time_slot;
                        if ($timeSlotMinutes > 0) {
                            $currentTime = clone $startTime;
                            while ($currentTime < $endTime) {
                                $workingHours[] = $currentTime->format('H:i');
                                $currentTime->addMinutes($timeSlotMinutes);
                            }
                        }
                    } else {
                        // Patient count based calculation
                        $totalMinutes = $endTime->diffInMinutes($startTime);
                        $patientsPerShift = (int) $schedule->number_of_patients_per_day;
                        
                        if ($totalMinutes > 0 && $patientsPerShift > 0) {
                            $patientsForPeriod = (int) ceil($patientsPerShift / 2);
                            $slotDuration = (int) ceil($totalMinutes / $patientsForPeriod);
                            
                            $currentTime = clone $startTime;
                            $slotsCreated = 0;

                            while ($currentTime < $endTime && $slotsCreated < $patientsForPeriod) {
                                $workingHours[] = $currentTime->format('H:i');
                                $currentTime->addMinutes($slotDuration);
                                $slotsCreated++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing {$shift} schedule: " . $e->getMessage());
                }
            }
        }

        return array_unique($workingHours);
    }

    protected function getOrCreateTimeSlot($appointmentDate)
    {
        // If we don't have any slots for this date yet, initialize them
        if (!isset($this->dateSlots[$appointmentDate])) {
            $this->dateSlots[$appointmentDate] = $this->getDoctorWorkingHours($this->doctorId, $appointmentDate);
        }

        // If we don't have a last appointment time for this date yet
        if (!isset($this->lastAppointmentTimes[$appointmentDate])) {
            // Get the latest appointment for this date
            $lastAppointment = Appointment::where('doctor_id', $this->doctorId)
                ->where('appointment_date', $appointmentDate)
                ->orderBy('appointment_time', 'desc')
                ->first();

            if ($lastAppointment) {
                $this->lastAppointmentTimes[$appointmentDate] = Carbon::parse($lastAppointment->appointment_time);
            } elseif (!empty($this->dateSlots[$appointmentDate])) {
                // If no appointments exist, use the last scheduled slot
                $this->lastAppointmentTimes[$appointmentDate] = Carbon::parse($appointmentDate . ' ' . end($this->dateSlots[$appointmentDate]));
            } else {
                // If no schedule exists, start from 9 AM
                $this->lastAppointmentTimes[$appointmentDate] = Carbon::parse($appointmentDate . ' 09:00:00');
            }
        }

        // Create next time slot by adding interval to last appointment time
        $nextTime = (clone $this->lastAppointmentTimes[$appointmentDate])->addMinutes($this->intervalMinutes);
        $this->lastAppointmentTimes[$appointmentDate] = $nextTime;

        return $nextTime->format('H:i:s');
    }

    protected function loadExistingAppointments($appointmentDate)
    {
        if (!isset($this->existingAppointments[$appointmentDate])) {
            // Get all existing appointments for this date and doctor
            $existingAppointments = Appointment::where('doctor_id', $this->doctorId)
                ->where('appointment_date', $appointmentDate)
                ->with('patient:id,phone,Firstname,Lastname')
                ->get();

            $this->existingAppointments[$appointmentDate] = [];
            
            foreach ($existingAppointments as $appointment) {
                // Create a unique key using phone, first name, and last name
                $key = $this->createPatientKey(
                    $appointment->patient->phone,
                    $appointment->patient->Firstname,
                    $appointment->patient->Lastname
                );
                
                $this->existingAppointments[$appointmentDate][$key] = $appointment;
            }
        }
    }

    protected function createPatientKey($phone, $firstName, $lastName)
    {
        $phone = $this->cleanPhoneNumber($phone);
        $firstName = strtolower(trim($firstName));
        $lastName = strtolower(trim($lastName));
        return "{$phone}_{$firstName}_{$lastName}";
    }

    public function model(array $row)
    {
        try {
            // Check for required appointment date
            if (empty($row['appointement_date'])) {
                throw new \Exception("Appointment date is required");
            }
    
            $appointmentDate = $this->formatDate($row['appointement_date']);
            
            if (Carbon::parse($appointmentDate)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                throw new \Exception("Cannot book appointments for past dates");
            }
    
            // Validate firstname and lastname
            $firstName = trim($row['firstname'] ?? '');
            $lastName = trim($row['lastname'] ?? '');

            // Skip if either firstname or lastname is empty
            if (empty($firstName) ||  empty($lastName)) {
                Log::info("Skipping appointment due to missing name. Date: {$appointmentDate}, " . 
                         "Firstname: {$firstName}, Lastname: {$lastName}");
                return null;
            }

            // Clean and prepare patient data
            $phone = $this->cleanPhoneNumber($row['phone'] ?? '');
            $firstName = ucfirst(strtolower($firstName));
            $lastName = ucfirst(strtolower($lastName));
            
            // Load existing appointments for this date if not already loaded
            $this->loadExistingAppointments($appointmentDate);
            
            // Create a unique key for this patient
            $patientKey = $this->createPatientKey($phone, $firstName, $lastName);
            
            // Check if this exact patient already has an appointment on this date
            if (isset($this->existingAppointments[$appointmentDate][$patientKey])) {
                Log::info("Skipping duplicate appointment for patient: {$patientKey} on date: {$appointmentDate}");
                return null;
            }
            
            // Check if we've already processed this patient in the current import
            if (isset($this->processedPatients[$appointmentDate][$patientKey])) {
                Log::info("Skipping duplicate patient from import file: {$patientKey} on date: {$appointmentDate}");
                return null;
            }
    
            // Get or create patient
            $patient = Patient::firstOrCreate(
                [
                    'phone' => $phone,
                    'Firstname' => $firstName,
                    'Lastname' => $lastName,
                ],
                [
                    'created_by' => $this->createdBy,
                ]
            );
    
            // Force create a new time slot after the last appointment
            $appointmentTime = $this->getOrCreateTimeSlot($appointmentDate);
    
            // Create appointment data
            $appointmentData = [
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctorId,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'status' => 0,
                'created_by' => $this->createdBy,
                'notes' => $row['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
    
            // Mark this patient as processed for this date
            $this->processedPatients[$appointmentDate][$patientKey] = true;
            
            // Add to existing appointments to prevent duplicates in subsequent rows
            $this->existingAppointments[$appointmentDate][$patientKey] = new Appointment($appointmentData);
    
            $this->successCount++;
            return new Appointment($appointmentData);
    
        } catch (Throwable $e) {
            $this->errors[] = "Error processing row: " . $e->getMessage();
            Log::error("Import error: " . $e->getMessage());
            return null;
        }
    }

    protected function formatDate($date)
    {
        if (empty($date)) {
            throw new \Exception("Appointment date is required");
        }

        try {
            if (is_numeric($date)) {
                return Carbon::create(1899, 12, 30)->addDays((int)$date)->format('Y-m-d');
            }

            $formats = ['d/m/Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'];
            
            foreach ($formats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $date);
                    if ($parsed !== false) {
                        return $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            throw new \Exception("Invalid date format");
        } catch (Throwable $e) {
            throw new \Exception("Invalid date format. Please use DD/MM/YYYY");
        }
    }

    protected function cleanPhoneNumber($phone)
    {
        return preg_replace('/[^0-9]/', '', (string)$phone);
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = "Error: " . $e->getMessage();
        Log::error("Import error: " . $e->getMessage());
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            Log::error("Import failure at row {$failure->row()}: " . implode(', ', $failure->errors()));
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
}