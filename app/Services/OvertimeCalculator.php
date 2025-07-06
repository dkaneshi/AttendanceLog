<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class OvertimeCalculator
{
    private const STANDARD_DAILY_HOURS = 8.0;

    private const STANDARD_WEEKLY_HOURS = 40.0;

    private const DAILY_OVERTIME_THRESHOLD = 8.0;

    private const WEEKLY_OVERTIME_THRESHOLD = 40.0;

    private const DOUBLE_TIME_THRESHOLD = 12.0;

    /**
     * Calculate daily overtime hours for a single attendance log entry
     */
    public function calculateDailyOvertime(AttendanceLog $attendanceLog): float
    {
        $workedHours = $this->calculateWorkedHours($attendanceLog);

        return max(0.0, $workedHours - self::DAILY_OVERTIME_THRESHOLD);
    }

    /**
     * Calculate weekly overtime hours for a collection of attendance logs
     */
    public function calculateWeeklyOvertime(Collection $weeklyAttendanceLogs): float
    {
        $totalWeeklyHours = 0.0;

        foreach ($weeklyAttendanceLogs as $log) {
            $totalWeeklyHours += $this->calculateWorkedHours($log);
        }

        return max(0.0, $totalWeeklyHours - self::WEEKLY_OVERTIME_THRESHOLD);
    }

    /**
     * Calculate comprehensive overtime breakdown including daily and weekly overtime
     */
    public function calculateOvertimeBreakdown(Collection $weeklyAttendanceLogs): array
    {
        $dailyOvertimeHours = 0.0;
        $weeklyOvertimeHours = 0.0;
        $doubleTimeHours = 0.0;
        $totalWorkedHours = 0.0;

        // Calculate daily overtime and double time
        foreach ($weeklyAttendanceLogs as $log) {
            $dailyWorkedHours = $this->calculateWorkedHours($log);
            $totalWorkedHours += $dailyWorkedHours;

            // Daily overtime (hours over 8 per day)
            if ($dailyWorkedHours > self::DAILY_OVERTIME_THRESHOLD) {
                // Double time for hours over 12 per day
                if ($dailyWorkedHours > self::DOUBLE_TIME_THRESHOLD) {
                    $doubleTimeForDay = $dailyWorkedHours - self::DOUBLE_TIME_THRESHOLD;
                    $doubleTimeHours += $doubleTimeForDay;
                    $dailyOvertimeHours += self::DOUBLE_TIME_THRESHOLD - self::DAILY_OVERTIME_THRESHOLD; // 12 - 8 = 4 hours of regular overtime
                } else {
                    $dailyOvertimeHours += $dailyWorkedHours - self::DAILY_OVERTIME_THRESHOLD;
                }
            }
        }

        // Weekly overtime (hours over 40 per week, but we don't double count daily overtime)
        // If there's daily overtime, we only count weekly overtime that's beyond the daily overtime already calculated
        $totalRegularHours = $totalWorkedHours - $dailyOvertimeHours - $doubleTimeHours;
        if ($totalRegularHours > self::WEEKLY_OVERTIME_THRESHOLD) {
            $weeklyOvertimeHours = $totalRegularHours - self::WEEKLY_OVERTIME_THRESHOLD;
        }

        return [
            'total_worked_hours' => round($totalWorkedHours, 2),
            'regular_hours' => round($totalWorkedHours - $dailyOvertimeHours - $weeklyOvertimeHours - $doubleTimeHours, 2),
            'daily_overtime_hours' => round($dailyOvertimeHours, 2),
            'weekly_overtime_hours' => round($weeklyOvertimeHours, 2),
            'double_time_hours' => round($doubleTimeHours, 2),
            'total_overtime_hours' => round($dailyOvertimeHours + $weeklyOvertimeHours, 2),
            'total_premium_hours' => round($dailyOvertimeHours + $weeklyOvertimeHours + $doubleTimeHours, 2),
        ];
    }

    /**
     * Calculate worked hours for a single attendance log entry
     */
    public function calculateWorkedHours(AttendanceLog $attendanceLog): float
    {
        if (! $attendanceLog->shift_start_time || ! $attendanceLog->shift_end_time) {
            return 0.0;
        }

        $startTime = Carbon::parse($attendanceLog->shift_start_time);
        $endTime = Carbon::parse($attendanceLog->shift_end_time);
        $totalMinutes = $startTime->diffInMinutes($endTime);

        // Subtract lunch break if both times are set
        if ($attendanceLog->lunch_start_time && $attendanceLog->lunch_end_time) {
            $lunchStart = Carbon::parse($attendanceLog->lunch_start_time);
            $lunchEnd = Carbon::parse($attendanceLog->lunch_end_time);
            $lunchMinutes = $lunchStart->diffInMinutes($lunchEnd);
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate total compensated hours (worked + vacation + sick)
     */
    public function calculateTotalCompensatedHours(AttendanceLog $attendanceLog): float
    {
        $workedHours = $this->calculateWorkedHours($attendanceLog);
        $vacationHours = (float) $attendanceLog->vacation_hours;
        $sickHours = (float) $attendanceLog->sick_hours;

        return round($workedHours + $vacationHours + $sickHours, 2);
    }

    /**
     * Check if attendance log qualifies for overtime
     */
    public function qualifiesForOvertime(AttendanceLog $attendanceLog): bool
    {
        return $this->calculateWorkedHours($attendanceLog) > self::DAILY_OVERTIME_THRESHOLD;
    }

    /**
     * Check if attendance log qualifies for double time
     */
    public function qualifiesForDoubleTime(AttendanceLog $attendanceLog): bool
    {
        return $this->calculateWorkedHours($attendanceLog) > self::DOUBLE_TIME_THRESHOLD;
    }

    /**
     * Format overtime hours as human-readable string
     */
    public function formatOvertimeHours(float $overtimeHours): string
    {
        $hours = floor($overtimeHours);
        $minutes = ($overtimeHours - $hours) * 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }

    /**
     * Calculate overtime pay rates based on worked hours
     */
    public function calculateOvertimePayRates(AttendanceLog $attendanceLog, float $hourlyRate): array
    {
        $workedHours = $this->calculateWorkedHours($attendanceLog);
        $regularHours = min($workedHours, self::DAILY_OVERTIME_THRESHOLD);
        $overtimeHours = max(0.0, min($workedHours - self::DAILY_OVERTIME_THRESHOLD, self::DOUBLE_TIME_THRESHOLD - self::DAILY_OVERTIME_THRESHOLD));
        $doubleTimeHours = max(0.0, $workedHours - self::DOUBLE_TIME_THRESHOLD);

        return [
            'regular_hours' => round($regularHours, 2),
            'regular_pay' => round($regularHours * $hourlyRate, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_pay' => round($overtimeHours * $hourlyRate * 1.5, 2),
            'double_time_hours' => round($doubleTimeHours, 2),
            'double_time_pay' => round($doubleTimeHours * $hourlyRate * 2.0, 2),
            'total_pay' => round(
                ($regularHours * $hourlyRate) +
                ($overtimeHours * $hourlyRate * 1.5) +
                ($doubleTimeHours * $hourlyRate * 2.0),
                2
            ),
        ];
    }

    /**
     * Get overtime calculation constants
     */
    public function getOvertimeThresholds(): array
    {
        return [
            'daily_standard_hours' => self::STANDARD_DAILY_HOURS,
            'weekly_standard_hours' => self::STANDARD_WEEKLY_HOURS,
            'daily_overtime_threshold' => self::DAILY_OVERTIME_THRESHOLD,
            'weekly_overtime_threshold' => self::WEEKLY_OVERTIME_THRESHOLD,
            'double_time_threshold' => self::DOUBLE_TIME_THRESHOLD,
        ];
    }
}
