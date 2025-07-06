<?php

declare(strict_types=1);

use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\AttendanceValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->validator = new AttendanceValidator();
    $this->user = User::factory()->create();
});

describe('validateRequiredFields', function () {
    test('passes validation with required fields', function () {
        $data = [
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ];

        $errors = $this->validator->validateRequiredFields($data);

        expect($errors)->toBeEmpty();
    });

    test('fails validation when user_id is missing', function () {
        $data = [
            'date' => '2025-01-01',
        ];

        $errors = $this->validator->validateRequiredFields($data);

        expect($errors)->toContain('The user_id field is required.');
    });

    test('fails validation when date is missing', function () {
        $data = [
            'user_id' => $this->user->id,
        ];

        $errors = $this->validator->validateRequiredFields($data);

        expect($errors)->toContain('The date field is required.');
    });

    test('fails validation with invalid date format', function () {
        $data = [
            'user_id' => $this->user->id,
            'date' => 'invalid-date',
        ];

        $errors = $this->validator->validateRequiredFields($data);

        expect($errors)->toContain('The date field must be a valid date.');
    });
});

describe('validateTimeSequence', function () {
    test('passes validation with correct time sequence', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 13:00:00',
            'shift_end_time' => '2025-01-01 17:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toBeEmpty();
    });

    test('fails when lunch start is before shift start', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'lunch_start_time' => '2025-01-01 07:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Lunch start time must be after shift start time.');
    });

    test('fails when lunch end is before lunch start', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 11:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Lunch end time must be after lunch start time.');
    });

    test('fails when shift end is before lunch end', function () {
        $data = [
            'lunch_end_time' => '2025-01-01 13:00:00',
            'shift_end_time' => '2025-01-01 12:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Shift end time must be after lunch end time.');
    });

    test('fails when shift end is before shift start', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 07:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Shift end time must be after shift start time.');
    });

    test('fails when only lunch start is provided', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Both lunch start and lunch end times must be provided together.');
    });

    test('fails when only lunch end is provided', function () {
        $data = [
            'lunch_end_time' => '2025-01-01 13:00:00',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('Both lunch start and lunch end times must be provided together.');
    });

    test('handles invalid time format', function () {
        $data = [
            'shift_start_time' => 'invalid-time',
        ];

        $errors = $this->validator->validateTimeSequence($data);

        expect($errors)->toContain('One or more time fields contain invalid time format.');
    });
});

describe('validateLunchDuration', function () {
    test('passes validation with valid lunch duration', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 13:00:00', // 60 minutes
        ];

        $errors = $this->validator->validateLunchDuration($data);

        expect($errors)->toBeEmpty();
    });

    test('fails when lunch is too short', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 12:15:00', // 15 minutes
        ];

        $errors = $this->validator->validateLunchDuration($data);

        expect($errors)->toContain('Lunch break must be at least 30 minutes.');
    });

    test('fails when lunch is too long', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 15:00:00', // 180 minutes
        ];

        $errors = $this->validator->validateLunchDuration($data);

        expect($errors)->toContain('Lunch break cannot exceed 120 minutes.');
    });

    test('allows minimum lunch duration', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 12:30:00', // 30 minutes
        ];

        $errors = $this->validator->validateLunchDuration($data);

        expect($errors)->toBeEmpty();
    });

    test('allows maximum lunch duration', function () {
        $data = [
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 14:00:00', // 120 minutes
        ];

        $errors = $this->validator->validateLunchDuration($data);

        expect($errors)->toBeEmpty();
    });
});

describe('validateShiftDuration', function () {
    test('passes validation with valid shift duration', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 17:00:00', // 9 hours
        ];

        $errors = $this->validator->validateShiftDuration($data);

        expect($errors)->toBeEmpty();
    });

    test('fails when shift is too short', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 08:10:00', // 10 minutes
        ];

        $errors = $this->validator->validateShiftDuration($data);

        expect($errors)->toContain('Shift must be at least 15 minutes.');
    });

    test('fails when shift is too long', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-02 01:00:00', // 17 hours
        ];

        $errors = $this->validator->validateShiftDuration($data);

        expect($errors)->toContain('Shift cannot exceed 16 hours.');
    });

    test('allows minimum shift duration', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 08:15:00', // 15 minutes
        ];

        $errors = $this->validator->validateShiftDuration($data);

        expect($errors)->toBeEmpty();
    });

    test('allows maximum shift duration', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-02 00:00:00', // 16 hours
        ];

        $errors = $this->validator->validateShiftDuration($data);

        expect($errors)->toBeEmpty();
    });
});

describe('validateBusinessRules', function () {
    test('passes validation with valid vacation hours', function () {
        $data = [
            'vacation_hours' => 8.0,
            'sick_hours' => 0.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toBeEmpty();
    });

    test('passes validation with valid sick hours', function () {
        $data = [
            'vacation_hours' => 0.0,
            'sick_hours' => 8.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toBeEmpty();
    });

    test('fails with negative vacation hours', function () {
        $data = [
            'vacation_hours' => -1.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Vacation hours cannot be negative.');
    });

    test('fails with negative sick hours', function () {
        $data = [
            'sick_hours' => -1.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Sick hours cannot be negative.');
    });

    test('fails with excessive vacation hours', function () {
        $data = [
            'vacation_hours' => 25.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Vacation hours cannot exceed 24 hours per day.');
    });

    test('fails with excessive sick hours', function () {
        $data = [
            'sick_hours' => 25.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Sick hours cannot exceed 24 hours per day.');
    });

    test('fails when both worked time and vacation hours are present', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 17:00:00',
            'vacation_hours' => 8.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Cannot log both worked time and vacation/sick hours on the same day.');
    });

    test('fails when both worked time and sick hours are present', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 17:00:00',
            'sick_hours' => 8.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Cannot log both worked time and vacation/sick hours on the same day.');
    });

    test('fails when no time is logged', function () {
        $data = [
            'vacation_hours' => 0.0,
            'sick_hours' => 0.0,
        ];

        $errors = $this->validator->validateBusinessRules($data);

        expect($errors)->toContain('Must log either worked time or vacation/sick hours.');
    });
});

describe('validateNoOverlap', function () {
    test('passes when no existing attendance for date', function () {
        $data = [
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ];

        $existing = collect([]);
        $errors = $this->validator->validateNoOverlap($data, $existing);

        expect($errors)->toBeEmpty();
    });

    test('fails when attendance already exists for date', function () {
        $existingLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ]);

        $data = [
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ];

        $existing = collect([$existingLog]);
        $errors = $this->validator->validateNoOverlap($data, $existing);

        expect($errors)->toContain('An attendance entry already exists for this date.');
    });

    test('allows editing existing attendance entry', function () {
        $existingLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ]);

        $data = [
            'id' => $existingLog->id,
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ];

        $existing = collect([$existingLog]);
        $errors = $this->validator->validateNoOverlap($data, $existing);

        expect($errors)->toBeEmpty();
    });
});

describe('validateEditable', function () {
    test('passes for non-approved attendance', function () {
        $data = [
            'approval_status' => 'pending',
            'date' => Carbon::today()->toDateString(),
        ];

        $errors = $this->validator->validateEditable($data);

        expect($errors)->toBeEmpty();
    });

    test('fails for approved attendance', function () {
        $data = [
            'approval_status' => 'approved',
            'date' => Carbon::today()->toDateString(),
        ];

        $errors = $this->validator->validateEditable($data);

        expect($errors)->toContain('Cannot edit approved attendance entries.');
    });

    test('fails for attendance older than 7 days', function () {
        $data = [
            'approval_status' => 'pending',
            'date' => Carbon::today()->subDays(8)->toDateString(),
        ];

        $errors = $this->validator->validateEditable($data);

        expect($errors)->toContain('Cannot edit attendance entries older than 7 days.');
    });

    test('allows editing attendance within 7 days', function () {
        $data = [
            'approval_status' => 'pending',
            'date' => Carbon::today()->subDays(7)->toDateString(),
        ];

        $errors = $this->validator->validateEditable($data);

        expect($errors)->toBeEmpty();
    });
});

describe('validateDateRestrictions', function () {
    test('passes for today\'s date', function () {
        $data = [
            'date' => Carbon::today()->toDateString(),
        ];

        $errors = $this->validator->validateDateRestrictions($data);

        expect($errors)->toBeEmpty();
    });

    test('fails for future dates', function () {
        $data = [
            'date' => Carbon::tomorrow()->toDateString(),
        ];

        $errors = $this->validator->validateDateRestrictions($data);

        expect($errors)->toContain('Cannot log attendance for future dates.');
    });

    test('fails for dates older than 30 days', function () {
        $data = [
            'date' => Carbon::today()->subDays(31)->toDateString(),
        ];

        $errors = $this->validator->validateDateRestrictions($data);

        expect($errors)->toContain('Cannot log attendance for dates older than 30 days.');
    });

    test('allows date exactly 30 days ago', function () {
        $data = [
            'date' => Carbon::today()->subDays(30)->toDateString(),
        ];

        $errors = $this->validator->validateDateRestrictions($data);

        expect($errors)->toBeEmpty();
    });
});

describe('validateTimeEntry', function () {
    test('validates shift start time', function () {
        $errors = $this->validator->validateTimeEntry('shift_start', '2025-01-01 08:00:00', [
            'date' => '2025-01-01',
        ]);

        expect($errors)->toBeEmpty();
    });

    test('validates lunch start must be after shift start', function () {
        $errors = $this->validator->validateTimeEntry('lunch_start', '2025-01-01 07:00:00', [
            'shift_start_time' => '2025-01-01 08:00:00',
        ]);

        expect($errors)->toContain('Lunch start must be after shift start.');
    });

    test('validates lunch end must be after lunch start', function () {
        $errors = $this->validator->validateTimeEntry('lunch_end', '2025-01-01 11:00:00', [
            'lunch_start_time' => '2025-01-01 12:00:00',
        ]);

        expect($errors)->toContain('Lunch end must be after lunch start.');
    });

    test('validates shift end must be after lunch end', function () {
        $errors = $this->validator->validateTimeEntry('shift_end', '2025-01-01 12:00:00', [
            'lunch_end_time' => '2025-01-01 13:00:00',
        ]);

        expect($errors)->toContain('Shift end must be after lunch end.');
    });

    test('validates shift end must be after shift start when no lunch', function () {
        $errors = $this->validator->validateTimeEntry('shift_end', '2025-01-01 07:00:00', [
            'shift_start_time' => '2025-01-01 08:00:00',
        ]);

        expect($errors)->toContain('Shift end must be after shift start.');
    });

    test('handles invalid time format', function () {
        $errors = $this->validator->validateTimeEntry('shift_start', 'invalid-time');

        expect($errors)->toContain('Invalid shift_start time format.');
    });

    test('handles null time value', function () {
        $errors = $this->validator->validateTimeEntry('shift_start', null);

        expect($errors)->toBeEmpty();
    });
});

describe('getValidationRules', function () {
    test('returns correct validation rules', function () {
        $rules = $this->validator->getValidationRules();

        expect($rules['min_lunch_duration_minutes'])->toBe(30);
        expect($rules['max_lunch_duration_minutes'])->toBe(120);
        expect($rules['max_shift_duration_hours'])->toBe(16);
        expect($rules['min_shift_duration_minutes'])->toBe(15);
        expect($rules['max_daily_hours'])->toBe(24);
    });
});

describe('validate', function () {
    test('passes comprehensive validation with valid data', function () {
        $data = [
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'shift_start_time' => '2025-01-01 08:00:00',
            'lunch_start_time' => '2025-01-01 12:00:00',
            'lunch_end_time' => '2025-01-01 13:00:00',
            'shift_end_time' => '2025-01-01 17:00:00',
            'vacation_hours' => 0.0,
            'sick_hours' => 0.0,
        ];

        $errors = $this->validator->validate($data);

        expect($errors)->toBeEmpty();
    });

    test('fails comprehensive validation with multiple errors', function () {
        $data = [
            'user_id' => null,
            'date' => 'invalid-date',
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 07:00:00', // Invalid sequence
            'vacation_hours' => -1.0, // Negative
        ];

        $errors = $this->validator->validate($data);

        expect($errors)->toHaveCount(2);
        expect($errors)->toContain('The user_id field is required.');
        expect($errors)->toContain('The date field must be a valid date.');
    });

    test('stops early validation when required fields are missing', function () {
        $data = [
            'shift_start_time' => '2025-01-01 08:00:00',
            'shift_end_time' => '2025-01-01 07:00:00', // This would normally fail sequence validation
        ];

        $errors = $this->validator->validate($data);

        // Should only contain required field errors, not sequence errors
        expect($errors)->toHaveCount(2);
        expect($errors)->toContain('The user_id field is required.');
        expect($errors)->toContain('The date field is required.');
    });
});
