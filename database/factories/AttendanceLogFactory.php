<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
final class AttendanceLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AttendanceLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'manager_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'shift_start_time' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'lunch_start_time' => null,
            'lunch_end_time' => null,
            'shift_end_time' => null,
            'vacation_hours' => 0.0,
            'sick_hours' => 0.0,
            'total_hours' => 0.0,
            'overtime_hours' => 0.0,
            'approval_status' => 'pending',
            'manager_comments' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * Configure the model factory for a completed workday.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('08:00:00', '09:00:00');
            $endTime = $this->faker->dateTimeBetween('17:00:00', '18:00:00');

            return [
                'shift_start_time' => $startTime,
                'shift_end_time' => $endTime,
                'lunch_start_time' => $this->faker->dateTimeBetween('12:00:00', '13:00:00'),
                'lunch_end_time' => $this->faker->dateTimeBetween('13:00:00', '14:00:00'),
            ];
        });
    }

    /**
     * Configure the model factory for an approved attendance log.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * Configure the model factory for a rejected attendance log.
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'approval_status' => 'rejected',
                'manager_comments' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Configure the model factory for overtime work.
     */
    public function overtime(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('07:00:00', '08:00:00');
            $endTime = $this->faker->dateTimeBetween('18:00:00', '20:00:00');

            return [
                'shift_start_time' => $startTime,
                'shift_end_time' => $endTime,
                'lunch_start_time' => $this->faker->dateTimeBetween('12:00:00', '13:00:00'),
                'lunch_end_time' => $this->faker->dateTimeBetween('13:00:00', '14:00:00'),
            ];
        });
    }

    /**
     * Configure the model factory with vacation hours.
     */
    public function withVacation(float $hours = 8.0): static
    {
        return $this->state(function (array $attributes) use ($hours) {
            return [
                'vacation_hours' => $hours,
                'shift_start_time' => null,
                'shift_end_time' => null,
            ];
        });
    }

    /**
     * Configure the model factory with sick hours.
     */
    public function withSick(float $hours = 8.0): static
    {
        return $this->state(function (array $attributes) use ($hours) {
            return [
                'sick_hours' => $hours,
                'shift_start_time' => null,
                'shift_end_time' => null,
            ];
        });
    }
}
