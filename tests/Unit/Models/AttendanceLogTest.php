<?php

declare(strict_types=1);

test('can check approval status correctly', function () {
    $approved = new App\Models\AttendanceLog(['approval_status' => 'approved']);
    $pending = new App\Models\AttendanceLog(['approval_status' => 'pending']);
    $rejected = new App\Models\AttendanceLog(['approval_status' => 'rejected']);
    $correction = new App\Models\AttendanceLog(['approval_status' => 'requires_correction']);

    expect($approved->isApproved())->toBe(true);
    expect($pending->isPending())->toBe(true);
    expect($rejected->isRejected())->toBe(true);
    expect($correction->requiresCorrection())->toBe(true);
});

test('can format overtime hours as string', function () {
    $attendance = new App\Models\AttendanceLog(['overtime_hours' => 2.5]);

    expect($attendance->formatted_overtime)->toBe('2h 30m');
});
