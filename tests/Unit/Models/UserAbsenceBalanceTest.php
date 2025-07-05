<?php

declare(strict_types=1);

test('can calculate remaining vacation hours', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'vacation_hours_total' => 160.0,
        'vacation_hours_used' => 40.0,
    ]);

    expect($balance->vacation_hours_remaining)->toBe(120.0);
});

test('can calculate remaining sick hours', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'sick_hours_total' => 80.0,
        'sick_hours_used' => 20.0,
    ]);

    expect($balance->sick_hours_remaining)->toBe(60.0);
});

test('can check vacation balance availability', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'vacation_hours_total' => 160.0,
        'vacation_hours_used' => 40.0,
    ]);

    expect($balance->hasVacationBalance(50.0))->toBe(true);
    expect($balance->hasVacationBalance(150.0))->toBe(false);
});

test('can check sick balance availability', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'sick_hours_total' => 80.0,
        'sick_hours_used' => 20.0,
    ]);

    expect($balance->hasSickBalance(30.0))->toBe(true);
    expect($balance->hasSickBalance(70.0))->toBe(false);
});

test('can validate hours increment', function () {
    $balance = new App\Models\UserAbsenceBalance();

    expect($balance->validateHoursIncrement(1.0))->toBe(true);
    expect($balance->validateHoursIncrement(1.25))->toBe(true);
    expect($balance->validateHoursIncrement(1.5))->toBe(true);
    expect($balance->validateHoursIncrement(1.75))->toBe(true);
    expect($balance->validateHoursIncrement(1.1))->toBe(false);
    expect($balance->validateHoursIncrement(1.33))->toBe(false);
});

test('can validate max daily hours', function () {
    $balance = new App\Models\UserAbsenceBalance();

    expect($balance->validateMaxDailyHours(8.0))->toBe(true);
    expect($balance->validateMaxDailyHours(4.5))->toBe(true);
    expect($balance->validateMaxDailyHours(8.5))->toBe(false);
    expect($balance->validateMaxDailyHours(10.0))->toBe(false);
});

test('can calculate usage percentages', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'vacation_hours_total' => 160.0,
        'vacation_hours_used' => 40.0,
        'sick_hours_total' => 80.0,
        'sick_hours_used' => 20.0,
    ]);

    expect($balance->vacation_usage_percentage)->toBe(25.0);
    expect($balance->sick_usage_percentage)->toBe(25.0);
});

test('can validate time off request with errors', function () {
    $balance = new App\Models\UserAbsenceBalance([
        'vacation_hours_total' => 160.0,
        'vacation_hours_used' => 155.0, // Only 5 hours left
        'sick_hours_total' => 80.0,
        'sick_hours_used' => 75.0, // Only 5 hours left
    ]);

    // Valid request
    $errors = $balance->validateTimeOffRequest(2.5, 'vacation');
    expect($errors)->toBeEmpty();

    // Invalid increment
    $errors = $balance->validateTimeOffRequest(1.1, 'vacation');
    expect($errors)->toContain('Hours must be in 0.25 hour (15-minute) increments.');

    // Exceeds daily maximum
    $errors = $balance->validateTimeOffRequest(8.5, 'vacation');
    expect($errors)->toContain('Cannot exceed 8 hours per day.');

    // Insufficient balance
    $errors = $balance->validateTimeOffRequest(6.0, 'vacation');
    expect($errors)->toContain('Insufficient vacation balance. Available: 5.00 hours, Requested: 6.00 hours.');
});
