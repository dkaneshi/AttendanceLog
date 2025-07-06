<?php

declare(strict_types=1);

use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('clockIn', function () {
    test('can clock in successfully', function () {
        $response = $this->postJson('/api/attendance/clock-in');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'shift_start_time',
                    'date',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'clocked_in',
                ],
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $this->user->id,
        ]);

        // Check the date separately since it's stored as datetime
        $log = AttendanceLog::where('user_id', $this->user->id)->first();
        expect($log->date->toDateString())->toBe(Carbon::today()->toDateString());
    });

    test('can clock in for specific date', function () {
        $date = Carbon::yesterday()->toDateString();

        $response = $this->postJson('/api/attendance/clock-in', [
            'date' => $date,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'shift_start_time',
                    'date',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'clocked_in',
                ],
            ]);

        // Check the date separately
        $responseData = $response->json('data');
        expect(Carbon::parse($responseData['date'])->toDateString())->toBe($date);
    });

    test('cannot clock in twice for same date', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/attendance/clock-in');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('cannot clock in for future date', function () {
        $response = $this->postJson('/api/attendance/clock-in', [
            'date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });

    test('requires authentication', function () {
        $this->refreshApplication();

        $response = $this->postJson('/api/attendance/clock-in');

        $response->assertStatus(401);
    });
});

describe('startLunch', function () {
    test('can start lunch break successfully', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(2),
        ]);

        $response = $this->postJson('/api/attendance/start-lunch');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'lunch_start_time',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'on_lunch',
                ],
            ]);

        $attendanceLog->refresh();
        expect($attendanceLog->lunch_start_time)->not->toBeNull();
    });

    test('cannot start lunch without clocking in', function () {
        $response = $this->postJson('/api/attendance/start-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendance']);
    });

    test('cannot start lunch before shift start', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->addHour(),
        ]);

        $response = $this->postJson('/api/attendance/start-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });

    test('cannot start lunch twice', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(2),
            'lunch_start_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/start-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch']);
    });

    test('cannot start lunch after clocking out', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(8),
            'shift_end_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/start-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift']);
    });
});

describe('endLunch', function () {
    test('can end lunch break successfully', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(3),
            'lunch_start_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/end-lunch');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'lunch_end_time',
                    'lunch_duration_minutes',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'working',
                ],
            ]);

        $attendanceLog->refresh();
        expect($attendanceLog->lunch_end_time)->not->toBeNull();
    });

    test('cannot end lunch without starting it', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(2),
        ]);

        $response = $this->postJson('/api/attendance/end-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch']);
    });

    test('cannot end lunch twice', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(3),
            'lunch_start_time' => Carbon::now()->subHours(2),
            'lunch_end_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/end-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch']);
    });

    test('cannot end lunch before starting it', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(2),
            'lunch_start_time' => Carbon::now()->addMinutes(30),
        ]);

        $response = $this->postJson('/api/attendance/end-lunch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });
});

describe('clockOut', function () {
    test('can clock out successfully', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(8),
        ]);

        $response = $this->postJson('/api/attendance/clock-out');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'shift_end_time',
                    'total_hours',
                    'overtime_hours',
                    'worked_hours',
                    'status',
                    'formatted_overtime',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        $attendanceLog->refresh();
        expect($attendanceLog->shift_end_time)->not->toBeNull();
        expect($attendanceLog->total_hours)->toBeGreaterThan(0);
    });

    test('can clock out with lunch break', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(9),
            'lunch_start_time' => Carbon::now()->subHours(5),
            'lunch_end_time' => Carbon::now()->subHours(4),
        ]);

        $response = $this->postJson('/api/attendance/clock-out');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'lunch_duration_minutes',
                ],
            ]);

        $attendanceLog->refresh();
        expect($attendanceLog->total_hours)->toBeLessThan(9); // Should subtract lunch time
    });

    test('cannot clock out without clocking in', function () {
        $response = $this->postJson('/api/attendance/clock-out');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendance']);
    });

    test('cannot clock out twice', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(8),
            'shift_end_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/clock-out');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift']);
    });

    test('cannot clock out with incomplete lunch break', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(8),
            'lunch_start_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson('/api/attendance/clock-out');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch']);
    });
});

describe('status', function () {
    test('returns not_started status when no attendance', function () {
        $response = $this->getJson('/api/attendance/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'not_started',
                    'date' => Carbon::today()->toDateString(),
                ],
            ]);
    });

    test('returns working status when clocked in', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(2),
        ]);

        $response = $this->getJson('/api/attendance/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'current_worked_hours',
                    'current_overtime_hours',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'working',
                ],
            ]);
    });

    test('returns on_lunch status during lunch break', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(3),
            'lunch_start_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->getJson('/api/attendance/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'on_lunch',
                ],
            ]);
    });

    test('returns completed status when clocked out', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::now()->subHours(8),
            'shift_end_time' => Carbon::now(),
            'total_hours' => 8.0,
        ]);

        $response = $this->getJson('/api/attendance/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    });

    test('can get status for specific date', function () {
        $date = '2025-01-01';
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => $date,
            'shift_start_time' => Carbon::parse($date)->addHours(8),
        ]);

        $response = $this->getJson("/api/attendance/status?date={$date}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'working',
                ],
            ]);
    });
});

describe('update', function () {
    test('can update attendance entry', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::today()->addHours(8),
            'shift_end_time' => Carbon::today()->addHours(17),
            'approval_status' => 'pending',
        ]);

        $newStartTime = Carbon::today()->addHours(9);

        $response = $this->putJson("/api/attendance/{$attendanceLog->id}", [
            'shift_start_time' => $newStartTime->toDateTimeString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'shift_start_time',
                    'total_hours',
                    'overtime_hours',
                    'status',
                ],
            ]);

        $attendanceLog->refresh();
        expect($attendanceLog->shift_start_time->format('Y-m-d H:i:s'))
            ->toBe($newStartTime->format('Y-m-d H:i:s'));
    });

    test('cannot update attendance of another user', function () {
        $otherUser = User::factory()->create();
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $otherUser->id,
            'date' => Carbon::today(),
        ]);

        $response = $this->putJson("/api/attendance/{$attendanceLog->id}", [
            'shift_start_time' => Carbon::now()->toDateTimeString(),
        ]);

        $response->assertStatus(403);
    });

    test('cannot update approved attendance', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'approval_status' => 'approved',
        ]);

        $response = $this->putJson("/api/attendance/{$attendanceLog->id}", [
            'shift_start_time' => Carbon::now()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });

    test('cannot update old attendance entries', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(8),
            'approval_status' => 'pending',
        ]);

        $response = $this->putJson("/api/attendance/{$attendanceLog->id}", [
            'shift_start_time' => Carbon::now()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });

    test('validates time sequence when updating', function () {
        $attendanceLog = AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'shift_start_time' => Carbon::today()->addHours(8),
            'shift_end_time' => Carbon::today()->addHours(17),
            'approval_status' => 'pending',
        ]);

        $response = $this->putJson("/api/attendance/{$attendanceLog->id}", [
            'shift_start_time' => Carbon::today()->addHours(18)->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['errors']);
    });
});

describe('history', function () {
    test('returns attendance history', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(1),
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(2),
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(3),
        ]);

        $response = $this->getJson('/api/attendance/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'shift_start_time',
                        'total_hours',
                        'status',
                        'approval_status',
                        'can_edit',
                    ],
                ],
                'meta' => [
                    'start_date',
                    'end_date',
                    'count',
                ],
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    test('can filter history by date range', function () {
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-01-15',
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-02-01',
        ]);

        $response = $this->getJson('/api/attendance/history?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
        expect($response->json('meta.start_date'))->toBe('2025-01-01');
        expect($response->json('meta.end_date'))->toBe('2025-01-31');
    });

    test('can limit history results', function () {
        for ($i = 1; $i <= 5; $i++) {
            AttendanceLog::factory()->create([
                'user_id' => $this->user->id,
                'date' => Carbon::today()->subDays($i),
            ]);
        }

        $response = $this->getJson('/api/attendance/history?limit=3');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(3);
    });

    test('only returns own attendance history', function () {
        $otherUser = User::factory()->create();

        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDay(),
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $otherUser->id,
            'date' => Carbon::today()->subDay(),
        ]);

        $response = $this->getJson('/api/attendance/history');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe(AttendanceLog::where('user_id', $this->user->id)->first()->id);
    });

    test('returns history ordered by date descending', function () {
        $date1 = Carbon::today()->subDays(1)->toDateString();
        $date2 = Carbon::today()->subDays(2)->toDateString();
        $date3 = Carbon::today()->subDays(3)->toDateString();

        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => $date1,
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => $date3,
        ]);
        AttendanceLog::factory()->create([
            'user_id' => $this->user->id,
            'date' => $date2,
        ]);

        $response = $this->getJson('/api/attendance/history');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(3);
        // Most recent first (descending order)
        expect($data[0]['date'])->toBe($date1);
        expect($data[1]['date'])->toBe($date2);
        expect($data[2]['date'])->toBe($date3);
    });
});
