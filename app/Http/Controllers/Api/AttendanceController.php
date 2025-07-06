<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Services\AttendanceValidator;
use App\Services\OvertimeCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceValidator $validator,
        private readonly OvertimeCalculator $overtimeCalculator
    ) {}

    /**
     * Clock in - start a new attendance entry
     */
    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'sometimes|date',
        ]);

        $user = Auth::user();
        $date = $request->input('date', Carbon::today()->toDateString());
        $now = Carbon::now();

        // Check if user already has an entry for this date
        $existingEntry = AttendanceLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($existingEntry) {
            throw ValidationException::withMessages([
                'date' => ['You have already clocked in for this date.'],
            ]);
        }

        $attendanceData = [
            'user_id' => $user->id,
            'manager_id' => $user->manager_id,
            'date' => $date,
            'shift_start_time' => $now->toDateTimeString(),
            'approval_status' => 'pending',
        ];

        // Validate the data (skip business rules for clock-in as it's incomplete)
        $errors = $this->validator->validateRequiredFields($attendanceData);
        $errors = array_merge($errors, $this->validator->validateDateRestrictions($attendanceData));

        if (! empty($errors)) {
            throw ValidationException::withMessages(['errors' => $errors]);
        }

        $attendanceLog = AttendanceLog::create($attendanceData);

        return response()->json([
            'message' => 'Successfully clocked in',
            'data' => [
                'id' => $attendanceLog->id,
                'shift_start_time' => $attendanceLog->shift_start_time,
                'date' => $attendanceLog->date,
                'status' => 'clocked_in',
            ],
        ], 201);
    }

    /**
     * Start lunch break
     */
    public function startLunch(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->input('date', Carbon::today()->toDateString());
        $now = Carbon::now();

        $attendanceLog = $this->findTodaysAttendance($user->id, $date);

        if (! $attendanceLog->shift_start_time) {
            throw ValidationException::withMessages([
                'shift' => ['You must clock in before starting lunch.'],
            ]);
        }

        if ($attendanceLog->lunch_start_time) {
            throw ValidationException::withMessages([
                'lunch' => ['Lunch break has already been started.'],
            ]);
        }

        if ($attendanceLog->shift_end_time) {
            throw ValidationException::withMessages([
                'shift' => ['Cannot start lunch after clocking out.'],
            ]);
        }

        // Validate lunch start time
        $context = [
            'shift_start_time' => $attendanceLog->shift_start_time,
            'date' => $date,
        ];
        $errors = $this->validator->validateTimeEntry('lunch_start', $now->toDateTimeString(), $context);

        if (! empty($errors)) {
            throw ValidationException::withMessages(['errors' => $errors]);
        }

        $attendanceLog->update([
            'lunch_start_time' => $now->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'Lunch break started',
            'data' => [
                'id' => $attendanceLog->id,
                'lunch_start_time' => $attendanceLog->lunch_start_time,
                'status' => 'on_lunch',
            ],
        ]);
    }

    /**
     * End lunch break
     */
    public function endLunch(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->input('date', Carbon::today()->toDateString());
        $now = Carbon::now();

        $attendanceLog = $this->findTodaysAttendance($user->id, $date);

        if (! $attendanceLog->lunch_start_time) {
            throw ValidationException::withMessages([
                'lunch' => ['Lunch break has not been started.'],
            ]);
        }

        if ($attendanceLog->lunch_end_time) {
            throw ValidationException::withMessages([
                'lunch' => ['Lunch break has already been ended.'],
            ]);
        }

        if ($attendanceLog->shift_end_time) {
            throw ValidationException::withMessages([
                'shift' => ['Cannot end lunch after clocking out.'],
            ]);
        }

        // Validate lunch end time
        $context = [
            'lunch_start_time' => $attendanceLog->lunch_start_time,
        ];
        $errors = $this->validator->validateTimeEntry('lunch_end', $now->toDateTimeString(), $context);

        if (! empty($errors)) {
            throw ValidationException::withMessages(['errors' => $errors]);
        }

        $attendanceLog->update([
            'lunch_end_time' => $now->toDateTimeString(),
        ]);

        // Calculate lunch duration for response
        $lunchDuration = $attendanceLog->lunch_duration;

        return response()->json([
            'message' => 'Lunch break ended',
            'data' => [
                'id' => $attendanceLog->id,
                'lunch_end_time' => $attendanceLog->lunch_end_time,
                'lunch_duration_minutes' => $lunchDuration,
                'status' => 'working',
            ],
        ]);
    }

    /**
     * Clock out - end the attendance entry
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->input('date', Carbon::today()->toDateString());
        $now = Carbon::now();

        $attendanceLog = $this->findTodaysAttendance($user->id, $date);

        if (! $attendanceLog->shift_start_time) {
            throw ValidationException::withMessages([
                'shift' => ['You must clock in before clocking out.'],
            ]);
        }

        if ($attendanceLog->shift_end_time) {
            throw ValidationException::withMessages([
                'shift' => ['You have already clocked out.'],
            ]);
        }

        // If lunch was started but not ended, we need lunch end time
        if ($attendanceLog->lunch_start_time && ! $attendanceLog->lunch_end_time) {
            throw ValidationException::withMessages([
                'lunch' => ['You must end your lunch break before clocking out.'],
            ]);
        }

        // Validate shift end time
        $context = [];
        if ($attendanceLog->lunch_end_time) {
            $context['lunch_end_time'] = $attendanceLog->lunch_end_time;
        } else {
            $context['shift_start_time'] = $attendanceLog->shift_start_time;
        }

        $errors = $this->validator->validateTimeEntry('shift_end', $now->toDateTimeString(), $context);

        if (! empty($errors)) {
            throw ValidationException::withMessages(['errors' => $errors]);
        }

        // Calculate worked hours and overtime
        $workedHours = $this->overtimeCalculator->calculateWorkedHours($attendanceLog);
        $overtimeHours = $this->overtimeCalculator->calculateDailyOvertime($attendanceLog);

        $attendanceLog->update([
            'shift_end_time' => $now->toDateTimeString(),
            'overtime_hours' => $overtimeHours,
        ]);

        // Recalculate total hours after updating shift_end_time
        $attendanceLog->update([
            'total_hours' => $attendanceLog->calculateTotalHours(),
        ]);

        return response()->json([
            'message' => 'Successfully clocked out',
            'data' => [
                'id' => $attendanceLog->id,
                'shift_end_time' => $attendanceLog->shift_end_time,
                'total_hours' => $attendanceLog->total_hours,
                'overtime_hours' => $attendanceLog->overtime_hours,
                'worked_hours' => $workedHours,
                'lunch_duration_minutes' => $attendanceLog->lunch_duration,
                'status' => 'completed',
                'formatted_overtime' => $attendanceLog->formatted_overtime,
            ],
        ]);
    }

    /**
     * Get current attendance status
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->input('date', Carbon::today()->toDateString());

        $attendanceLog = AttendanceLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if (! $attendanceLog) {
            return response()->json([
                'message' => 'No attendance entry for this date',
                'data' => [
                    'status' => 'not_started',
                    'date' => $date,
                ],
            ]);
        }

        // Determine current status
        $status = $this->determineAttendanceStatus($attendanceLog);

        // Calculate real-time worked hours if actively working
        $realTimeData = [];
        if (in_array($status, ['working', 'on_lunch'])) {
            $tempLog = clone $attendanceLog;
            if ($status === 'working' && ! $attendanceLog->shift_end_time) {
                $tempLog->shift_end_time = Carbon::now()->toDateTimeString();
            }
            $realTimeData = [
                'current_worked_hours' => $this->overtimeCalculator->calculateWorkedHours($tempLog),
                'current_overtime_hours' => $this->overtimeCalculator->calculateDailyOvertime($tempLog),
            ];
        }

        return response()->json([
            'message' => 'Current attendance status',
            'data' => array_merge([
                'id' => $attendanceLog->id,
                'status' => $status,
                'date' => $attendanceLog->date,
                'shift_start_time' => $attendanceLog->shift_start_time,
                'lunch_start_time' => $attendanceLog->lunch_start_time,
                'lunch_end_time' => $attendanceLog->lunch_end_time,
                'shift_end_time' => $attendanceLog->shift_end_time,
                'vacation_hours' => $attendanceLog->vacation_hours,
                'sick_hours' => $attendanceLog->sick_hours,
                'total_hours' => $attendanceLog->total_hours,
                'overtime_hours' => $attendanceLog->overtime_hours,
                'lunch_duration_minutes' => $attendanceLog->lunch_duration,
            ], $realTimeData),
        ]);
    }

    /**
     * Update attendance entry (for corrections within allowed timeframe)
     */
    public function update(Request $request, AttendanceLog $attendanceLog): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if ($attendanceLog->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to modify this attendance entry',
            ], 403);
        }

        // Validate editability
        $editabilityErrors = $this->validator->validateEditable($attendanceLog->toArray());
        if (! empty($editabilityErrors)) {
            throw ValidationException::withMessages(['errors' => $editabilityErrors]);
        }

        $request->validate([
            'shift_start_time' => 'sometimes|date',
            'lunch_start_time' => 'sometimes|nullable|date',
            'lunch_end_time' => 'sometimes|nullable|date',
            'shift_end_time' => 'sometimes|nullable|date',
        ]);

        $updateData = array_filter($request->only([
            'shift_start_time',
            'lunch_start_time',
            'lunch_end_time',
            'shift_end_time',
        ]), fn ($value) => $value !== null);

        // Merge with existing data for validation
        $fullData = array_merge($attendanceLog->toArray(), $updateData);

        // Validate the complete data
        $errors = $this->validator->validate($fullData);

        if (! empty($errors)) {
            throw ValidationException::withMessages(['errors' => $errors]);
        }

        // Recalculate totals if times are being updated
        if (isset($updateData['shift_start_time']) || isset($updateData['shift_end_time']) ||
            isset($updateData['lunch_start_time']) || isset($updateData['lunch_end_time'])) {

            $tempLog = $attendanceLog->replicate();
            foreach ($updateData as $key => $value) {
                $tempLog->$key = $value;
            }

            if ($tempLog->shift_start_time && $tempLog->shift_end_time) {
                $updateData['total_hours'] = $this->overtimeCalculator->calculateWorkedHours($tempLog);
                $updateData['overtime_hours'] = $this->overtimeCalculator->calculateDailyOvertime($tempLog);
            }
        }

        $attendanceLog->update($updateData);

        return response()->json([
            'message' => 'Attendance entry updated successfully',
            'data' => [
                'id' => $attendanceLog->id,
                'shift_start_time' => $attendanceLog->shift_start_time,
                'lunch_start_time' => $attendanceLog->lunch_start_time,
                'lunch_end_time' => $attendanceLog->lunch_end_time,
                'shift_end_time' => $attendanceLog->shift_end_time,
                'total_hours' => $attendanceLog->total_hours,
                'overtime_hours' => $attendanceLog->overtime_hours,
                'status' => $this->determineAttendanceStatus($attendanceLog),
            ],
        ]);
    }

    /**
     * Get attendance history for a date range
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $limit = $request->input('limit', 30);

        $attendanceLogs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        $history = $attendanceLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'date' => $log->date->format('Y-m-d'),
                'shift_start_time' => $log->shift_start_time,
                'lunch_start_time' => $log->lunch_start_time,
                'lunch_end_time' => $log->lunch_end_time,
                'shift_end_time' => $log->shift_end_time,
                'vacation_hours' => $log->vacation_hours,
                'sick_hours' => $log->sick_hours,
                'total_hours' => $log->total_hours,
                'overtime_hours' => $log->overtime_hours,
                'lunch_duration_minutes' => $log->lunch_duration,
                'status' => $this->determineAttendanceStatus($log),
                'approval_status' => $log->approval_status,
                'formatted_overtime' => $log->formatted_overtime,
                'can_edit' => $log->canBeEdited(),
            ];
        });

        return response()->json([
            'message' => 'Attendance history retrieved',
            'data' => $history,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'count' => $history->count(),
            ],
        ]);
    }

    /**
     * Log vacation hours for a specific date
     */
    public function logVacation(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.25|max:24',
        ]);

        $user = Auth::user();
        $date = $request->input('date');
        $hours = (float) $request->input('hours');

        // Find existing entry or create new one
        $attendanceLog = AttendanceLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($attendanceLog) {
            // Update existing entry
            $attendanceLog->update([
                'vacation_hours' => $hours,
                'total_hours' => $attendanceLog->calculateTotalHours(),
            ]);
        } else {
            // Create new entry for vacation only
            $attendanceLog = AttendanceLog::create([
                'user_id' => $user->id,
                'manager_id' => $user->manager_id,
                'date' => $date,
                'vacation_hours' => $hours,
                'sick_hours' => 0,
                'total_hours' => $hours,
                'overtime_hours' => 0,
                'approval_status' => 'pending',
            ]);
        }

        return response()->json([
            'message' => 'Vacation hours logged successfully',
            'data' => [
                'id' => $attendanceLog->id,
                'date' => $attendanceLog->date,
                'vacation_hours' => $attendanceLog->vacation_hours,
                'total_hours' => $attendanceLog->total_hours,
                'approval_status' => $attendanceLog->approval_status,
            ],
        ], 201);
    }

    /**
     * Log sick hours for a specific date
     */
    public function logSick(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.25|max:24',
        ]);

        $user = Auth::user();
        $date = $request->input('date');
        $hours = (float) $request->input('hours');

        // Find existing entry or create new one
        $attendanceLog = AttendanceLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($attendanceLog) {
            // Update existing entry
            $attendanceLog->update([
                'sick_hours' => $hours,
                'total_hours' => $attendanceLog->calculateTotalHours(),
            ]);
        } else {
            // Create new entry for sick hours only
            $attendanceLog = AttendanceLog::create([
                'user_id' => $user->id,
                'manager_id' => $user->manager_id,
                'date' => $date,
                'vacation_hours' => 0,
                'sick_hours' => $hours,
                'total_hours' => $hours,
                'overtime_hours' => 0,
                'approval_status' => 'pending',
            ]);
        }

        return response()->json([
            'message' => 'Sick hours logged successfully',
            'data' => [
                'id' => $attendanceLog->id,
                'date' => $attendanceLog->date,
                'sick_hours' => $attendanceLog->sick_hours,
                'total_hours' => $attendanceLog->total_hours,
                'approval_status' => $attendanceLog->approval_status,
            ],
        ], 201);
    }

    /**
     * Update vacation hours for an existing attendance entry
     */
    public function updateVacation(Request $request, AttendanceLog $attendanceLog): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if ($attendanceLog->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to modify this attendance entry',
            ], 403);
        }

        // Check if entry can be edited
        if (! $attendanceLog->canBeEdited()) {
            return response()->json([
                'message' => 'This attendance entry cannot be edited',
            ], 422);
        }

        $request->validate([
            'hours' => 'required|numeric|min:0|max:24',
        ]);

        $hours = (float) $request->input('hours');

        $attendanceLog->update([
            'vacation_hours' => $hours,
            'total_hours' => $attendanceLog->calculateTotalHours(),
        ]);

        return response()->json([
            'message' => 'Vacation hours updated successfully',
            'data' => [
                'id' => $attendanceLog->id,
                'date' => $attendanceLog->date,
                'vacation_hours' => $attendanceLog->vacation_hours,
                'total_hours' => $attendanceLog->total_hours,
                'approval_status' => $attendanceLog->approval_status,
            ],
        ]);
    }

    /**
     * Update sick hours for an existing attendance entry
     */
    public function updateSick(Request $request, AttendanceLog $attendanceLog): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if ($attendanceLog->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to modify this attendance entry',
            ], 403);
        }

        // Check if entry can be edited
        if (! $attendanceLog->canBeEdited()) {
            return response()->json([
                'message' => 'This attendance entry cannot be edited',
            ], 422);
        }

        $request->validate([
            'hours' => 'required|numeric|min:0|max:24',
        ]);

        $hours = (float) $request->input('hours');

        $attendanceLog->update([
            'sick_hours' => $hours,
            'total_hours' => $attendanceLog->calculateTotalHours(),
        ]);

        return response()->json([
            'message' => 'Sick hours updated successfully',
            'data' => [
                'id' => $attendanceLog->id,
                'date' => $attendanceLog->date,
                'sick_hours' => $attendanceLog->sick_hours,
                'total_hours' => $attendanceLog->total_hours,
                'approval_status' => $attendanceLog->approval_status,
            ],
        ]);
    }

    /**
     * Find today's attendance entry or throw exception
     */
    private function findTodaysAttendance(int $userId, string $date): AttendanceLog
    {
        $attendanceLog = AttendanceLog::where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();

        if (! $attendanceLog) {
            throw ValidationException::withMessages([
                'attendance' => ['No attendance entry found for this date. Please clock in first.'],
            ]);
        }

        return $attendanceLog;
    }

    /**
     * Determine the current status of an attendance entry
     */
    private function determineAttendanceStatus(AttendanceLog $attendanceLog): string
    {
        if (! $attendanceLog->shift_start_time) {
            return 'not_started';
        }

        if ($attendanceLog->shift_end_time) {
            return 'completed';
        }

        if ($attendanceLog->lunch_start_time && ! $attendanceLog->lunch_end_time) {
            return 'on_lunch';
        }

        return 'working';
    }
}
