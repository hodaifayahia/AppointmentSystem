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
    protected $usedSlots = [];
    protected $overflowMinutes;

    public function __construct($createdBy, $doctorId)
    {
        $this->createdBy = $createdBy;
        $this->doctorId = $doctorId;
        $this->overflowMinutes = 15; // Default time between overflow appointments
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
            return ['slots' => [], 'lastTime' => null];
        }
        
        $workingHours = [];
        $lastEndTime = null;
        
        foreach (['morning', 'afternoon'] as $shift) {
            $schedule = $schedules->firstWhere('shift_period', $shift);
            if ($schedule) {
                try {
                    $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                    $lastEndTime = $endTime; // Keep track of the latest end time
                    
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
                        
                        if (abs($totalMinutes) > 0 && abs($patientsPerShift) > 0) {
                            $patientsForPeriod = (int) ceil($patientsPerShift / 2);
                            $slotDuration = (int) ceil($totalMinutes / $patientsForPeriod);
                            
                            $currentTime = clone $startTime;
                            $slotsCreated = 0;

                            while ($currentTime < $endTime && $slotsCreated < $patientsForPeriod) {
                                $workingHours[] = $currentTime->format('H:i');
                                $currentTime->addMinutes(abs($slotDuration));
                                $slotsCreated++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing {$shift} schedule: " . $e->getMessage());
                }
            }
        }

        return [
            'slots' => array_unique($workingHours),
            'lastTime' => $lastEndTime
        ];
    }

    public function model(array $row)
    {
        try {
            if (empty($row['appointement_date'])) {
                throw new \Exception("Appointment date is required");
            }

            $appointmentDate = $this->formatDate($row['appointement_date']);
            
            if (Carbon::parse($appointmentDate)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                throw new \Exception("Cannot book appointments for past dates");
            }

            // Get or initialize slots for this date
            if (!isset($this->dateSlots[$appointmentDate])) {
                $doctorHours = $this->getDoctorWorkingHours($this->doctorId, $appointmentDate);
                $this->dateSlots[$appointmentDate] = $doctorHours['slots'];
                $this->usedSlots[$appointmentDate] = [];
                
                // Store the last end time for overflow handling
                $this->lastEndTime[$appointmentDate] = $doctorHours['lastTime'];
            }

            // Get existing appointments
            $bookedSlots = Appointment::where('doctor_id', $this->doctorId)
                ->where('appointment_date', $appointmentDate)
                ->pluck('appointment_time')
                ->map(function($time) {
                    return Carbon::parse($time)->format('H:i');
                })
                ->toArray();

            // Combine already booked slots with slots used in current import
            $unavailableSlots = array_merge($bookedSlots, $this->usedSlots[$appointmentDate]);

            // Get available slots
            $availableSlots = array_diff($this->dateSlots[$appointmentDate], $unavailableSlots);

            $appointmentTime = null;
            
            if (!empty($availableSlots)) {
                // Use the next available regular slot
                $appointmentTime = reset($availableSlots);
            } else {
                // Handle overflow: schedule after the last appointment
                $lastTime = empty($unavailableSlots) 
                    ? $this->lastEndTime[$appointmentDate]
                    : Carbon::parse(max($unavailableSlots));
                
                $appointmentTime = $lastTime->addMinutes($this->overflowMinutes)->format('H:i');
            }
            
            // Mark slot as used
            $this->usedSlots[$appointmentDate][] = $appointmentTime;

            // Process patient
            $phone = $this->cleanPhoneNumber($row['phone'] ?? '');
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

            // Create appointment
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

            $this->successCount++;
            return new Appointment($appointmentData);

        } catch (Throwable $e) {
            $this->errors[] = "Error processing row: " . $e->getMessage();
            Log::error("Import error: " . $e->getMessage());
            return null;
        }
    }
    protected function loadAvailableSlots($date)
    {
        // Get all slots from doctor's schedule
        $allSlots = $this->getDoctorWorkingHours($this->doctorId, $date);
        
        if (empty($allSlots)) {
            throw new \Exception("No available time slots found for this doctor on the specified date.");
        }

        // Filter out already booked slots
        $bookedSlots = Appointment::where('doctor_id', $this->doctorId)
            ->where('appointment_date', $date)
            ->pluck('appointment_time')
            ->map(function($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        $this->availableSlots = array_values(array_diff($allSlots, $bookedSlots));
        
        if (empty($this->availableSlots)) {
            throw new \Exception("All time slots are already booked for this doctor on the specified date.");
        }

        // Sort slots chronologically
        sort($this->availableSlots);
    }

    protected function getNextAvailableSlot()
    {
        if (!isset($this->availableSlots[$this->currentSlotIndex])) {
            return null;
        }

        $slot = $this->availableSlots[$this->currentSlotIndex];
        $this->currentSlotIndex++;
        
        return Carbon::parse($slot)->format('H:i:s');
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