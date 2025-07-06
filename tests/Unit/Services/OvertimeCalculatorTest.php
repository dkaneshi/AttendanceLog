<?php

declare(strict_types=1);

use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\OvertimeCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new OvertimeCalculator();
    $this->user = User::factory()->create();
});

describe('calculateDailyOvertime', function () {
    test('returns zero for work under 8 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 16:00:00'), // 7 hours
            'lunch_start_time' => null,
            'lunch_end_time' => null,
        ]);

        $overtime = $this->calculator->calculateDailyOvertime($attendanceLog);

        expect($overtime)->toBe(0.0);
    });

    test('calculates overtime for work over 8 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'), // 10 hours
            'lunch_start_time' => null,
            'lunch_end_time' => null,
        ]);

        $overtime = $this->calculator->calculateDailyOvertime($attendanceLog);

        expect($overtime)->toBe(2.0);
    });

    test('calculates overtime with lunch break', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 19:00:00'), // 11 hours
            'lunch_start_time' => Carbon::parse('2025-01-01 12:00:00'),
            'lunch_end_time' => Carbon::parse('2025-01-01 13:00:00'), // 1 hour lunch
        ]);

        $overtime = $this->calculator->calculateDailyOvertime($attendanceLog);

        expect($overtime)->toBe(2.0); // 10 worked hours - 8 = 2 overtime
    });
});

describe('calculateWeeklyOvertime', function () {
    test('returns zero for work under 40 hours per week', function () {
        $attendanceLogs = collect([
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-01 17:00:00'), // 8 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-02 09:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-02 17:00:00'), // 8 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-03 09:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-03 17:00:00'), // 8 hours
            ]),
        ]);

        $weeklyOvertime = $this->calculator->calculateWeeklyOvertime($attendanceLogs);

        expect($weeklyOvertime)->toBe(0.0);
    });

    test('calculates weekly overtime for work over 40 hours', function () {
        $attendanceLogs = collect([
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'), // 10 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-02 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-02 18:00:00'), // 10 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-03 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-03 18:00:00'), // 10 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-04 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-04 18:00:00'), // 10 hours
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-05 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-05 13:00:00'), // 5 hours
            ]),
        ]);

        $weeklyOvertime = $this->calculator->calculateWeeklyOvertime($attendanceLogs);

        expect($weeklyOvertime)->toBe(5.0); // 45 total hours - 40 = 5 overtime
    });
});

describe('calculateOvertimeBreakdown', function () {
    test('provides comprehensive overtime breakdown', function () {
        $attendanceLogs = collect([
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-01 06:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-01 20:00:00'), // 14 hours (2 double time, 4 overtime)
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-02 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-02 18:00:00'), // 10 hours (2 overtime)
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-03 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-03 18:00:00'), // 10 hours (2 overtime)
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-04 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-04 18:00:00'), // 10 hours (2 overtime)
            ]),
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'shift_start_time' => Carbon::parse('2025-01-05 08:00:00'),
                'shift_end_time' => Carbon::parse('2025-01-05 16:00:00'), // 8 hours (0 overtime)
            ]),
        ]);

        $breakdown = $this->calculator->calculateOvertimeBreakdown($attendanceLogs);

        expect($breakdown['total_worked_hours'])->toBe(52.0);
        expect($breakdown['daily_overtime_hours'])->toBe(10.0);
        expect($breakdown['double_time_hours'])->toBe(2.0);
        expect($breakdown['weekly_overtime_hours'])->toBe(0.0); // 52 - 40 - 10 - 2 = 0
        expect($breakdown['total_overtime_hours'])->toBe(10.0);
        expect($breakdown['total_premium_hours'])->toBe(12.0);
    });
});

describe('calculateWorkedHours', function () {
    test('calculates worked hours without lunch', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 17:00:00'),
            'lunch_start_time' => null,
            'lunch_end_time' => null,
        ]);

        $workedHours = $this->calculator->calculateWorkedHours($attendanceLog);

        expect($workedHours)->toBe(8.0);
    });

    test('calculates worked hours with lunch break', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'),
            'lunch_start_time' => Carbon::parse('2025-01-01 12:00:00'),
            'lunch_end_time' => Carbon::parse('2025-01-01 13:00:00'),
        ]);

        $workedHours = $this->calculator->calculateWorkedHours($attendanceLog);

        expect($workedHours)->toBe(8.0); // 9 hours - 1 hour lunch = 8 hours
    });

    test('returns zero for incomplete time entries', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => null,
        ]);

        $workedHours = $this->calculator->calculateWorkedHours($attendanceLog);

        expect($workedHours)->toBe(0.0);
    });
});

describe('calculateTotalCompensatedHours', function () {
    test('calculates total compensated hours including vacation and sick', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 15:00:00'), // 6 hours worked
            'vacation_hours' => 2.0,
            'sick_hours' => 1.0,
        ]);

        $totalHours = $this->calculator->calculateTotalCompensatedHours($attendanceLog);

        expect($totalHours)->toBe(9.0); // 6 + 2 + 1 = 9
    });
});

describe('qualifiesForOvertime', function () {
    test('returns false for work under 8 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 16:00:00'), // 7 hours
        ]);

        $qualifies = $this->calculator->qualifiesForOvertime($attendanceLog);

        expect($qualifies)->toBeFalse();
    });

    test('returns true for work over 8 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'), // 10 hours
        ]);

        $qualifies = $this->calculator->qualifiesForOvertime($attendanceLog);

        expect($qualifies)->toBeTrue();
    });
});

describe('qualifiesForDoubleTime', function () {
    test('returns false for work under 12 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'), // 10 hours
        ]);

        $qualifies = $this->calculator->qualifiesForDoubleTime($attendanceLog);

        expect($qualifies)->toBeFalse();
    });

    test('returns true for work over 12 hours', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 06:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 20:00:00'), // 14 hours
        ]);

        $qualifies = $this->calculator->qualifiesForDoubleTime($attendanceLog);

        expect($qualifies)->toBeTrue();
    });
});

describe('formatOvertimeHours', function () {
    test('formats overtime hours correctly', function () {
        $formatted = $this->calculator->formatOvertimeHours(2.5);

        expect($formatted)->toBe('2h 30m');
    });

    test('formats whole hours correctly', function () {
        $formatted = $this->calculator->formatOvertimeHours(3.0);

        expect($formatted)->toBe('3h 0m');
    });
});

describe('calculateOvertimePayRates', function () {
    test('calculates pay rates for regular hours only', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 09:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 17:00:00'), // 8 hours
        ]);

        $payRates = $this->calculator->calculateOvertimePayRates($attendanceLog, 20.0);

        expect($payRates['regular_hours'])->toBe(8.0);
        expect($payRates['regular_pay'])->toBe(160.0);
        expect($payRates['overtime_hours'])->toBe(0.0);
        expect($payRates['overtime_pay'])->toBe(0.0);
        expect($payRates['double_time_hours'])->toBe(0.0);
        expect($payRates['double_time_pay'])->toBe(0.0);
        expect($payRates['total_pay'])->toBe(160.0);
    });

    test('calculates pay rates with overtime', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 08:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 18:00:00'), // 10 hours
        ]);

        $payRates = $this->calculator->calculateOvertimePayRates($attendanceLog, 20.0);

        expect($payRates['regular_hours'])->toBe(8.0);
        expect($payRates['regular_pay'])->toBe(160.0);
        expect($payRates['overtime_hours'])->toBe(2.0);
        expect($payRates['overtime_pay'])->toBe(60.0); // 2 * 20 * 1.5
        expect($payRates['double_time_hours'])->toBe(0.0);
        expect($payRates['double_time_pay'])->toBe(0.0);
        expect($payRates['total_pay'])->toBe(220.0);
    });

    test('calculates pay rates with double time', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'shift_start_time' => Carbon::parse('2025-01-01 06:00:00'),
            'shift_end_time' => Carbon::parse('2025-01-01 20:00:00'), // 14 hours
        ]);

        $payRates = $this->calculator->calculateOvertimePayRates($attendanceLog, 20.0);

        expect($payRates['regular_hours'])->toBe(8.0);
        expect($payRates['regular_pay'])->toBe(160.0);
        expect($payRates['overtime_hours'])->toBe(4.0);
        expect($payRates['overtime_pay'])->toBe(120.0); // 4 * 20 * 1.5
        expect($payRates['double_time_hours'])->toBe(2.0);
        expect($payRates['double_time_pay'])->toBe(80.0); // 2 * 20 * 2.0
        expect($payRates['total_pay'])->toBe(360.0);
    });
});

describe('getOvertimeThresholds', function () {
    test('returns correct overtime thresholds', function () {
        $thresholds = $this->calculator->getOvertimeThresholds();

        expect($thresholds['daily_standard_hours'])->toBe(8.0);
        expect($thresholds['weekly_standard_hours'])->toBe(40.0);
        expect($thresholds['daily_overtime_threshold'])->toBe(8.0);
        expect($thresholds['weekly_overtime_threshold'])->toBe(40.0);
        expect($thresholds['double_time_threshold'])->toBe(12.0);
    });
});
