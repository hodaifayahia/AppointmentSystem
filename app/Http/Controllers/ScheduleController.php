<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScheduleResource;
use App\Models\Doctor;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Fetch schedules for the specified doctor
        $schedules = Schedule::where('doctor_id', $request->doctor_id)->get();
    
        // Fetch doctor details including name (assuming 'name' is the correct column name)
        $doctor = Doctor::where('id', $request->doctor_id)
        ->with('user:id,name') // Only select id and name from users table
        ->first();
        // If you want to return both schedules and doctor information
        return [
            'doctor_name' => $doctor ?$doctor->user->name : null, // Assuming you have a DoctorResource
            'patients_based_on_time' => $doctor ?$doctor->patients_based_on_time : null, // Assuming you have a DoctorResource
            'schedules' => ScheduleResource::collection($schedules)
        ];
    }


    public function updateSchedule(Request $request, $doctorId)
    {
        $validated = $request->validate([
            'frequency' => 'string|in:Daily,Weekly,Monthly',
            'patients_based_on_time' => 'boolean',
            'time_slot' => 'nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'number_of_patients_per_day' => 'required|integer|min:1',
            'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
            'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i|after:customDates.*.morningStartTime',
            'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
            'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i|after:customDates.*.afternoonStartTime',
            'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
            'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request, $doctorId) {
                $doctor = Doctor::findOrFail($doctorId);
                
                // Update doctor scheduling preferences
                $doctor->update([
                    'frequency' => $request->frequency,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slot' => $request->time_slot,
                ]);

                // Delete existing schedules
                $doctor->schedules()->delete();

                // Create new schedules based on frequency
                if ($request->frequency === 'Monthly' && $request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } else if ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }

                return response()->json([
                    'message' => 'Doctor schedules updated successfully!',
                    'schedules' => $doctor->fresh()->schedules
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error updating doctor schedule: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating doctor schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createCustomSchedules(Request $request, Doctor $doctor)
    {
        $customSchedules = [];

        foreach ($request->customDates as $dateInfo) {
            // Handle morning shift
            if (!empty($dateInfo['morningStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    Carbon::parse($dateInfo['date']),
                    'morning',
                    $dateInfo['morningStartTime'],
                    $dateInfo['morningEndTime'],
                    strtolower(Carbon::parse($dateInfo['date'])->format('l')),
                    $dateInfo['morningPatients'],
                    $request
                );
            }

            // Handle afternoon shift
            if (!empty($dateInfo['afternoonStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    Carbon::parse($dateInfo['date']),
                    'afternoon',
                    $dateInfo['afternoonStartTime'],
                    $dateInfo['afternoonEndTime'],
                    strtolower(Carbon::parse($dateInfo['date'])->format('l')),
                    $dateInfo['afternoonPatients'],
                    $request
                );
            }
        }

        if (!empty($customSchedules)) {
            Schedule::insert($customSchedules);
        }
    }

    private function createRegularSchedules(Request $request, Doctor $doctor)
    {
        $schedules = collect($request->schedules)->map(function ($schedule) use ($doctor) {
            return [
                'doctor_id' => $doctor->id,
                'day_of_week' => $schedule['day_of_week'],
                'shift_period' => $schedule['shift_period'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'number_of_patients_per_day' => $schedule['number_of_patients_per_day'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        Schedule::insert($schedules);
    }

    private function prepareScheduleData(Doctor $doctor, Carbon $date, string $shift, string $startTime, string $endTime, string $dayOfWeek, ?int $patients, Request $request): array
    {
        // Calculate number of patients based on scheduling type
        if ($patients !== null) {
            $numberOfPatients = $patients;
        } elseif ($request->patients_based_on_time && !empty($request->time_slot)) {
            $numberOfPatients = $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot);
        } else {
            $numberOfPatients = $request->number_of_patients_per_day;
        }

        return [
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'shift_period' => $shift,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'day_of_week' => $dayOfWeek,
            'is_active' => true,
            'number_of_patients_per_day' => $numberOfPatients,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function calculatePatientsPerShift($startTime, $endTime, $timeSlot)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $totalMinutes = $end->diffInMinutes($start);
        return (int) floor($totalMinutes / $timeSlot);
    }

    public function destroy($id , Request $request)
    {
        $schedule = Schedule::where('doctor_id', $id)->where('date', $request->date)->first();
        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully!'
        ]);
    }
    
}

