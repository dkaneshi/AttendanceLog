<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

final class AttendanceValidator
{
    private const MIN_LUNCH_DURATION_MINUTES = 30;

    private const MAX_LUNCH_DURATION_MINUTES = 120;

    private const MAX_SHIFT_DURATION_HOURS = 16;

    private const MIN_SHIFT_DURATION_MINUTES = 15;

    private const MAX_DAILY_HOURS = 24;

    /**
     * Validate attendance log data
     */
    public function validate(array $attendanceData): array
    {
        $errors = [];

        // Validate required fields
        $errors = array_merge($errors, $this->validateRequiredFields($attendanceData));

        // Only proceed with time validation if basic fields are present
        if (empty($errors)) {
            $errors = array_merge($errors, $this->validateTimeSequence($attendanceData));
            $errors = array_merge($errors, $this->validateLunchDuration($attendanceData));
            $errors = array_merge($errors, $this->validateShiftDuration($attendanceData));
            $errors = array_merge($errors, $this->validateBusinessRules($attendanceData));
        }

        return $errors;
    }

    /**
     * Validate required fields are present
     */
    public function validateRequiredFields(array $attendanceData): array
    {
        $errors = [];
        $requiredFields = ['user_id', 'date'];

        foreach ($requiredFields as $field) {
            if (! isset($attendanceData[$field]) || $attendanceData[$field] === null) {
                $errors[] = "The {$field} field is required.";
            }
        }

        // Validate date format
        if (isset($attendanceData['date'])) {
            try {
                Carbon::parse($attendanceData['date']);
            } catch (Exception $e) {
                $errors[] = 'The date field must be a valid date.';
            }
        }

        return $errors;
    }

    /**
     * Validate time sequence is logical
     */
    public function validateTimeSequence(array $attendanceData): array
    {
        $errors = [];

        // Extract time fields
        $shiftStart = $attendanceData['shift_start_time'] ?? null;
        $lunchStart = $attendanceData['lunch_start_time'] ?? null;
        $lunchEnd = $attendanceData['lunch_end_time'] ?? null;
        $shiftEnd = $attendanceData['shift_end_time'] ?? null;

        // Parse times if they exist
        $times = [];
        try {
            if ($shiftStart) {
                $times['shift_start'] = Carbon::parse($shiftStart);
            }
            if ($lunchStart) {
                $times['lunch_start'] = Carbon::parse($lunchStart);
            }
            if ($lunchEnd) {
                $times['lunch_end'] = Carbon::parse($lunchEnd);
            }
            if ($shiftEnd) {
                $times['shift_end'] = Carbon::parse($shiftEnd);
            }
        } catch (Exception $e) {
            $errors[] = 'One or more time fields contain invalid time format.';

            return $errors;
        }

        // Validate sequence: shift_start < lunch_start < lunch_end < shift_end
        if (isset($times['shift_start']) && isset($times['lunch_start'])) {
            if ($times['shift_start']->greaterThanOrEqualTo($times['lunch_start'])) {
                $errors[] = 'Lunch start time must be after shift start time.';
            }
        }

        if (isset($times['lunch_start']) && isset($times['lunch_end'])) {
            if ($times['lunch_start']->greaterThanOrEqualTo($times['lunch_end'])) {
                $errors[] = 'Lunch end time must be after lunch start time.';
            }
        }

        if (isset($times['lunch_end']) && isset($times['shift_end'])) {
            if ($times['lunch_end']->greaterThanOrEqualTo($times['shift_end'])) {
                $errors[] = 'Shift end time must be after lunch end time.';
            }
        }

        if (isset($times['shift_start']) && isset($times['shift_end'])) {
            if ($times['shift_start']->greaterThanOrEqualTo($times['shift_end'])) {
                $errors[] = 'Shift end time must be after shift start time.';
            }
        }

        // Validate lunch times are both present or both absent
        if ((isset($times['lunch_start']) && ! isset($times['lunch_end'])) ||
            (! isset($times['lunch_start']) && isset($times['lunch_end']))) {
            $errors[] = 'Both lunch start and lunch end times must be provided together.';
        }

        return $errors;
    }

    /**
     * Validate lunch duration is within acceptable limits
     */
    public function validateLunchDuration(array $attendanceData): array
    {
        $errors = [];

        $lunchStart = $attendanceData['lunch_start_time'] ?? null;
        $lunchEnd = $attendanceData['lunch_end_time'] ?? null;

        if ($lunchStart && $lunchEnd) {
            try {
                $startTime = Carbon::parse($lunchStart);
                $endTime = Carbon::parse($lunchEnd);
                $durationMinutes = $startTime->diffInMinutes($endTime);

                if ($durationMinutes < self::MIN_LUNCH_DURATION_MINUTES) {
                    $errors[] = 'Lunch break must be at least '.self::MIN_LUNCH_DURATION_MINUTES.' minutes.';
                }

                if ($durationMinutes > self::MAX_LUNCH_DURATION_MINUTES) {
                    $errors[] = 'Lunch break cannot exceed '.self::MAX_LUNCH_DURATION_MINUTES.' minutes.';
                }
            } catch (Exception $e) {
                $errors[] = 'Invalid lunch time format.';
            }
        }

        return $errors;
    }

    /**
     * Validate shift duration is within reasonable limits
     */
    public function validateShiftDuration(array $attendanceData): array
    {
        $errors = [];

        $shiftStart = $attendanceData['shift_start_time'] ?? null;
        $shiftEnd = $attendanceData['shift_end_time'] ?? null;

        if ($shiftStart && $shiftEnd) {
            try {
                $startTime = Carbon::parse($shiftStart);
                $endTime = Carbon::parse($shiftEnd);
                $durationMinutes = $startTime->diffInMinutes($endTime);
                $durationHours = $durationMinutes / 60;

                if ($durationMinutes < self::MIN_SHIFT_DURATION_MINUTES) {
                    $errors[] = 'Shift must be at least '.self::MIN_SHIFT_DURATION_MINUTES.' minutes.';
                }

                if ($durationHours > self::MAX_SHIFT_DURATION_HOURS) {
                    $errors[] = 'Shift cannot exceed '.self::MAX_SHIFT_DURATION_HOURS.' hours.';
                }
            } catch (Exception $e) {
                $errors[] = 'Invalid shift time format.';
            }
        }

        return $errors;
    }

    /**
     * Validate business rules
     */
    public function validateBusinessRules(array $attendanceData): array
    {
        $errors = [];

        // Validate vacation/sick hours
        $vacationHours = $attendanceData['vacation_hours'] ?? 0;
        $sickHours = $attendanceData['sick_hours'] ?? 0;

        if ($vacationHours < 0) {
            $errors[] = 'Vacation hours cannot be negative.';
        }

        if ($sickHours < 0) {
            $errors[] = 'Sick hours cannot be negative.';
        }

        if ($vacationHours > self::MAX_DAILY_HOURS) {
            $errors[] = 'Vacation hours cannot exceed '.self::MAX_DAILY_HOURS.' hours per day.';
        }

        if ($sickHours > self::MAX_DAILY_HOURS) {
            $errors[] = 'Sick hours cannot exceed '.self::MAX_DAILY_HOURS.' hours per day.';
        }

        // Cannot have both worked time and vacation/sick time on the same day
        $hasWorkedTime = ($attendanceData['shift_start_time'] ?? null) && ($attendanceData['shift_end_time'] ?? null);
        $hasVacationOrSick = $vacationHours > 0 || $sickHours > 0;

        if ($hasWorkedTime && $hasVacationOrSick) {
            $errors[] = 'Cannot log both worked time and vacation/sick hours on the same day.';
        }

        // Must have either worked time or vacation/sick time
        if (! $hasWorkedTime && ! $hasVacationOrSick) {
            $errors[] = 'Must log either worked time or vacation/sick hours.';
        }

        return $errors;
    }

    /**
     * Validate that attendance doesn't overlap with existing entries
     */
    public function validateNoOverlap(array $attendanceData, Collection $existingAttendance): array
    {
        $errors = [];

        $userId = $attendanceData['user_id'] ?? null;
        $date = $attendanceData['date'] ?? null;
        $attendanceId = $attendanceData['id'] ?? null;

        if (! $userId || ! $date) {
            return $errors;
        }

        // Check for existing entry on the same date
        $existingForDate = $existingAttendance->filter(function ($log) use ($userId, $date, $attendanceId) {
            $logDate = Carbon::parse($log->date)->format('Y-m-d');
            $inputDate = Carbon::parse($date)->format('Y-m-d');

            return $log->user_id === $userId &&
                   $logDate === $inputDate &&
                   ($attendanceId === null || $log->id !== $attendanceId);
        });

        if ($existingForDate->isNotEmpty()) {
            $errors[] = 'An attendance entry already exists for this date.';
        }

        return $errors;
    }

    /**
     * Validate that attendance can be edited (not approved and within time limit)
     */
    public function validateEditable(array $attendanceData): array
    {
        $errors = [];

        $approvalStatus = $attendanceData['approval_status'] ?? null;
        $date = $attendanceData['date'] ?? null;

        if ($approvalStatus === 'approved') {
            $errors[] = 'Cannot edit approved attendance entries.';
        }

        if ($date) {
            try {
                $attendanceDate = Carbon::parse($date);
                $today = Carbon::today();

                if ($attendanceDate->lessThan($today->subDays(7))) {
                    $errors[] = 'Cannot edit attendance entries older than 7 days.';
                }
            } catch (Exception $e) {
                $errors[] = 'Invalid date format.';
            }
        }

        return $errors;
    }

    /**
     * Validate future date restrictions
     */
    public function validateDateRestrictions(array $attendanceData): array
    {
        $errors = [];

        $date = $attendanceData['date'] ?? null;

        if ($date) {
            try {
                $attendanceDate = Carbon::parse($date);
                $today = Carbon::today();

                if ($attendanceDate->greaterThan($today)) {
                    $errors[] = 'Cannot log attendance for future dates.';
                }

                // Don't allow entries older than 30 days
                if ($attendanceDate->lessThan($today->subDays(30))) {
                    $errors[] = 'Cannot log attendance for dates older than 30 days.';
                }
            } catch (Exception $e) {
                $errors[] = 'Invalid date format.';
            }
        }

        return $errors;
    }

    /**
     * Get validation rules configuration
     */
    public function getValidationRules(): array
    {
        return [
            'min_lunch_duration_minutes' => self::MIN_LUNCH_DURATION_MINUTES,
            'max_lunch_duration_minutes' => self::MAX_LUNCH_DURATION_MINUTES,
            'max_shift_duration_hours' => self::MAX_SHIFT_DURATION_HOURS,
            'min_shift_duration_minutes' => self::MIN_SHIFT_DURATION_MINUTES,
            'max_daily_hours' => self::MAX_DAILY_HOURS,
        ];
    }

    /**
     * Quick validation for time entry components
     */
    public function validateTimeEntry(string $timeType, ?string $timeValue, array $context = []): array
    {
        $errors = [];

        if ($timeValue === null) {
            return $errors;
        }

        try {
            $time = Carbon::parse($timeValue);
        } catch (Exception $e) {
            $errors[] = "Invalid {$timeType} time format.";

            return $errors;
        }

        // Context-specific validations
        switch ($timeType) {
            case 'shift_start':
                if (isset($context['date'])) {
                    $date = Carbon::parse($context['date']);
                    if ($time->lessThan($date->startOfDay()) || $time->greaterThan($date->endOfDay())) {
                        $errors[] = 'Shift start time must be within the same day.';
                    }
                }
                break;

            case 'lunch_start':
                if (isset($context['shift_start_time'])) {
                    $shiftStart = Carbon::parse($context['shift_start_time']);
                    if ($time->lessThanOrEqualTo($shiftStart)) {
                        $errors[] = 'Lunch start must be after shift start.';
                    }
                }
                break;

            case 'lunch_end':
                if (isset($context['lunch_start_time'])) {
                    $lunchStart = Carbon::parse($context['lunch_start_time']);
                    if ($time->lessThanOrEqualTo($lunchStart)) {
                        $errors[] = 'Lunch end must be after lunch start.';
                    }
                }
                break;

            case 'shift_end':
                if (isset($context['lunch_end_time'])) {
                    $lunchEnd = Carbon::parse($context['lunch_end_time']);
                    if ($time->lessThanOrEqualTo($lunchEnd)) {
                        $errors[] = 'Shift end must be after lunch end.';
                    }
                } elseif (isset($context['shift_start_time'])) {
                    $shiftStart = Carbon::parse($context['shift_start_time']);
                    if ($time->lessThanOrEqualTo($shiftStart)) {
                        $errors[] = 'Shift end must be after shift start.';
                    }
                }
                break;
        }

        return $errors;
    }
}
