<?php

namespace App\Services;

use App\DayOfWeekEnum;
use App\Models\Appointment;
use App\Models\AppointmentAvailableMonth;
use App\Models\ExcludedDate;
use App\Models\Schedule;
use Carbon\Carbon;

class AppointmentAvailabilityService
{
    /**
     * Check availability and return formatted response
     */
    public function checkAvailability($doctorId, Carbon $startDate, $range = 0, $includeSlots = false)
    {
        // Check if the doctor has any dates in the Schedule model
        $doctorHasSchedule = Schedule::where('doctor_id', $doctorId)
            ->where('date', $startDate->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();

        // Get excluded dates for the specific doctor and for all doctors
        $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId)
                  ->where('exclusionType', 'complete')
                  ->orWhereNull('doctor_id');
        })->get();

        // Find next available appointment
        if ($range > 0) {
            $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange(
                $startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates
            );
        } else {
            $nextAvailableDate = $this->findNextAvailableAppointment(
                $startDate, $doctorId, $doctorHasSchedule, $excludedDates
            );
        }

        if (!$nextAvailableDate) {
            return [
                'next_available_date' => null,
            ];
        }

        // Calculate period difference from current date
        $daysDifference = abs($nextAvailableDate->diffInDays(Carbon::now()));
        $period = $this->calculatePeriod($daysDifference);

        $response = [
            'current_date' => Carbon::now()->format('Y-m-d'),
            'next_available_date' => $nextAvailableDate->format('Y-m-d'),
            'period' => $period
        ];

        if ($includeSlots) {
            $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
            $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
            // Find available slots by subtracting booked slots from working hours
            $availableSlots = array_diff($workingHours, $bookedSlots);

            // Add slots information to response
            $response['available_slots'] = array_values($availableSlots);
        }

        return $response;
    }
    
    /**
     * Find the next available appointment date
     */
    private function findNextAvailableAppointmentWithinRange(Carbon $startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates)
    {
        // First check the start date itself
        $currentDate = clone $startDate;
        
        // Get all available dates within range
        $availableDates = collect();
        
        // Check backward
        if ($range > 0) {
            $checkDate = clone $startDate;
            for ($i = $range; $i > 0; $i--) {
                $checkDate = clone $startDate;
                $checkDate->subDays($i);
                
                $month = $checkDate->month;
                $year = $checkDate->year;
                
                $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('is_available', true)
                    ->exists();
                    
                if (!$isMonthAvailable) {
                    continue;
                }
                
                if ($this->isDateAvailableforthisdate($doctorId, $checkDate, $doctorHasSchedule, $excludedDates)) {
                    $availableDates->push(clone $checkDate);
                }
            }
        }
        
        // Check current date
        if ($this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule, $excludedDates)) {
            $availableDates->push(clone $currentDate);
        }
        
        // Check forward
        $forwardDate = clone $startDate;
        for ($i = 1; $i <= $range; $i++) {
            $forwardDate = clone $startDate;
            $forwardDate->addDays($i);
            
            $month = $forwardDate->month;
            $year = $forwardDate->year;
            
            $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                ->where('month', $month)
                ->where('year', $year)
                ->where('is_available', true)
                ->exists();
                
            if (!$isMonthAvailable) {
                continue;
            }
            
            if ($this->isDateAvailableforthisdate($doctorId, $forwardDate, $doctorHasSchedule, $excludedDates)) {
                $availableDates->push(clone $forwardDate);
            }
        }
        
        // Sort dates and return the earliest one
        if ($availableDates->isNotEmpty()) {
            return $availableDates->sort()->first();
        }
        
        return null;
    }
    private function findNextAvailableAppointment(Carbon $startDate, $doctorId, $doctorHasSchedule, $excludedDates) {
        $currentDate = clone $startDate;
        $endOfYear = Carbon::now()->endOfYear();
        
        // Fetch all available months for this doctor in one query
        $availableMonths = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('year', '>=', $currentDate->year)
            ->where('is_available', true)
            ->get()
            ->mapToGroups(function ($item) {
                return ["{$item->year}" => $item->month];
            });
        
        // Get excluded dates in a more efficient format
        $excludedDateRanges = $excludedDates->filter(function($date) {
            return $date->exclusionType === 'complete';
        })->map(function($date) {
            return [
                'start' => $date->start_date->format('Y-m-d'),
                'end' => optional($date->end_date)->format('Y-m-d') ?? $date->start_date->format('Y-m-d')
            ];
        });
        
        // Cache for schedules to avoid repeated queries
        $scheduleCache = [];
        
        // Loop through dates using pre-fetched data
        while ($currentDate->lte($endOfYear)) {
            $year = $currentDate->year;
            $month = $currentDate->month;
            $dayOfWeek = DayOfWeekEnum::cases()[$currentDate->dayOfWeek]->value; // Use the enum value
            $dateString = $currentDate->format('Y-m-d');
            
            // Skip if date is in the past
            if ($currentDate->startOfDay()->lt(Carbon::now()->startOfDay())) {
                $currentDate->addDay();
                continue;
            }
            
            // Check if month is available using our cached data
            $isMonthAvailable = $availableMonths->has((string)$year) && 
                                in_array($month, $availableMonths[(string)$year]->toArray());
            
            if (!$isMonthAvailable) {
                // Skip to the first day of next month
                $currentDate->addMonth()->startOfMonth();
                continue;
            }
            
            // Check if date is excluded
            $isExcluded = $excludedDateRanges->contains(function($range) use ($dateString) {
                return $dateString >= $range['start'] && $dateString <= $range['end'];
            });
            
            if ($isExcluded) {
                $currentDate->addDay();
                continue;
            }
            
            // Use the specific doctor schedules using the static method
            // Cache by day of week to avoid repeated queries
            if (!isset($scheduleCache[$dayOfWeek])) {
                $scheduleCache[$dayOfWeek] = Schedule::getSchedulesForDoctor($doctorId, $dayOfWeek);
            }
            
            $schedules = $scheduleCache[$dayOfWeek];
            
            if ($schedules->isNotEmpty()) {
                // Check for available time slots
                $workingHours = $this->getDoctorWorkingHours($doctorId, $dateString);
                
                if (!empty($workingHours)) {
                    $bookedSlots = $this->getBookedSlots($doctorId, $dateString);
                    
                    if (count(array_diff($workingHours, $bookedSlots)) > 0) {
                        return $currentDate;
                    }
                }
            }
            
            $currentDate->addDay();
        }
        
        return null;
    }
    private function isDateAvailableforthisdate($doctorId, Carbon $date, $doctorHasSchedule, $excludedDates)
    {
        // Check if the date is in the past
        if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
            return false;
        }
            // dd($date);
        // Check excluded dates
        $excludedType = null;
        $isExcluded = $excludedDates->contains(function ($excludedDate) use ($date, &$excludedType) {
            $endDate = $excludedDate->end_date ?? $excludedDate->start_date;
            if ($date->between($excludedDate->start_date, $endDate)) {
                $excludedType = $excludedDate->exclusionType;
                return true;
            }
            return false;
        });
        
        
        // If exclusionType is 'complete', totally exclude this date
        if ($isExcluded && $excludedType === 'complete') {
            return false;
        }
    
        // Check month availability
        $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $date->month)
            ->where('year', $date->year)
            ->where('is_available', true)
            ->exists();
    
        if (!$isMonthAvailable) {
            return false;
        }
    
        $dayOfWeek = DayOfWeekEnum::cases()[$date->dayOfWeek]->value;
        // Check for specific date schedule (custom dates)
     // Get all schedules for this doctor in one query
    $schedules = Schedule::where('doctor_id', $doctorId)
    ->where(function($query) use ($date, $dayOfWeek) {
        $query->where('date', $date->format('Y-m-d'))
              ->orWhere(function($q) use ($dayOfWeek) {
                  $q->whereNull('date')
                    ->where('day_of_week', $dayOfWeek);
              });
    })
    ->where('is_active', true)
    ->get();
    
    $hasSpecificDateSchedule = $schedules->where('date', $date->format('Y-m-d'))->isNotEmpty();
    $hasRecurringSchedule = $schedules->whereNull('date')->where('day_of_week', $dayOfWeek)->isNotEmpty();
    
        // If doctor has a scheduled working date (either specific or recurring), it’s available
        if ($hasSpecificDateSchedule || $hasRecurringSchedule) {
            // Check if the doctor has working hours on this date
            $workingHours = $this->getDoctorWorkingHours($doctorId, $date->format('Y-m-d'));
            if (!empty($workingHours)) {
                // Check if there are available slots after booked slots
                $bookedSlots = $this->getBookedSlots($doctorId, $date->format('Y-m-d'));
                if (count(array_diff($workingHours, $bookedSlots)) > 0) {
                    return true;
                }
            }
        }
    
        // If no schedule is found, but the date is 'limited' excluded and is sooner than any scheduled date, allow it
        if ($isExcluded && $excludedType === 'limited') {
            return true;
        }
    
        return false;
    }
    
    /**
     * Get doctor's working hours for a specific date
     */
    public function getDoctorWorkingHours($doctorId, $date) {
        // Create a cache key for this specific doctor and date
        $cacheKey = "doctor_{$doctorId}_hours_{$date}";
        
        // Check if data is already cached (5 minutes is a reasonable time for appointment data)
        return Cache::remember($cacheKey, 5, function() use ($doctorId, $date) {
            // Parse the date parameter into a Carbon instance
            $dateObj = Carbon::parse($date);
            $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
            
            // Fetch doctor data with a single query
            $doctor = Doctor::select(['id', 'patients_based_on_time', 'time_slot'])
                          ->findOrFail($doctorId);
            
            // Fetch excluded dates and regular schedules in parallel
            $excludedDate = ExcludedDate::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
                ->where('doctor_id', $doctorId)
                ->where('exclusionType', 'limited')
                ->where(function ($query) use ($date) {
                    $query->where('start_date', '<=', $date)
                          ->where(function ($q) use ($date) {
                              $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $date);
                          });
                })
                ->where('is_active', true)
                ->first();
                
            // Only query regular schedules if no excluded date is found (avoid unnecessary query)
            $schedules = $excludedDate 
                ? collect([$excludedDate]) 
                : Schedule::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
                    ->where('doctor_id', $doctorId)
                    ->where('is_active', true)
                    ->where('day_of_week', $dayOfWeek)
                    ->get();
            
            if ($schedules->isEmpty()) {
                return [];
            }
            
            $workingHours = [];
            
            // Get current date and time - do this once outside the loops
            $now = Carbon::now();
            $isToday = $dateObj->isSameDay($now);
            $bufferTime = $now->copy()->addMinutes(5);
            
            // Prepare date string once
            $dateString = $dateObj->format('Y-m-d');
            
            // If doctor has a fixed time slot
            if ($doctor->time_slot !== null && (int)$doctor->time_slot > 0) {
                $timeSlotMinutes = (int)$doctor->time_slot;
                
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;
                    
                    // Optimize Carbon instance creation - only create what's needed
                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);
                    
                    // Pre-calculate total slots to avoid creating unnecessary Carbon instances
                    $totalMinutes = $endTime->diffInMinutes($startTime);
                    $totalSlots = floor($totalMinutes / $timeSlotMinutes) + 1;
                    
                    for ($i = 0; $i < $totalSlots; $i++) {
                        $slotMinutes = $i * $timeSlotMinutes;
                        $slotTime = $startTime->copy()->addMinutes($slotMinutes);
                        
                        if ($slotTime >= $endTime) {
                            break; // Stop if we've passed the end time
                        }
                        
                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                }
            } else {
                // Calculate based on number of patients per shift
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;
                    
                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);
                    
                    $patientsForShift = (int)$schedule->number_of_patients_per_day;
                    
                    if ($patientsForShift <= 0) continue;
                    
                    if ($patientsForShift == 1) {
                        if (!$isToday || $startTime->greaterThan($bufferTime)) {
                            $workingHours[] = $startTime->format('H:i');
                        }
                        continue;
                    }
                    
                    // Calculate total duration and slot duration once
                    $totalDuration = $endTime->diffInMinutes($startTime);
                    $slotDuration = abs($totalDuration / ($patientsForShift - 1));
                    
                    // Pre-calculate all slot times at once
                    for ($i = 0; $i < $patientsForShift; $i++) {
                        $minutesToAdd = round($i * $slotDuration);
                        $slotTime = $startTime->copy()->addMinutes($minutesToAdd);
                        
                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                }
            }
            
            return array_unique($workingHours);
        });
    }
    
    /**
     * Get booked slots for a doctor on a specific date
     */
    private function getBookedSlots($doctorId, $date)
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', $this->excludedStatuses) // Correct filtering
            ->pluck('appointment_time') // Get only appointment times
            ->map(fn($time) => Carbon::parse($time)->format('H:i')) // Proper formatting
            ->unique() // Ensure uniqueness if necessary
            ->toArray();
    }
    
    
    /**
     * Calculate period based on day difference
     */
   
 private function calculatePeriod($days)
 {
     // Ensure that $days is an integer before processing
     $days = (int) $days;
 
     if ($days >= 365) {
         $years = floor($days / 365);
         $remainingDays = $days % 365;
         return $years . ' year(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
     }
 
     if ($days >= 30) {
         $months = floor($days / 30);
         $remainingDays = $days % 30;
         return $months . ' month(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
     }
 
     return $days . ' day(s)';
 }
}
