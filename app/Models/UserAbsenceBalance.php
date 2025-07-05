<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UserAbsenceBalance extends Model
{
    use SoftDeletes;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'vacation_hours_total',
        'vacation_hours_used',
        'sick_hours_total',
        'sick_hours_used',
        'year',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'vacation_hours_total' => 'decimal:2',
        'vacation_hours_used' => 'decimal:2',
        'sick_hours_total' => 'decimal:2',
        'sick_hours_used' => 'decimal:2',
        'year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getVacationHoursRemainingAttribute(): float
    {
        return $this->vacation_hours_total - $this->vacation_hours_used;
    }

    public function getSickHoursRemainingAttribute(): float
    {
        return $this->sick_hours_total - $this->sick_hours_used;
    }

    public function hasVacationBalance(float $hours): bool
    {
        return $this->vacation_hours_remaining >= $hours;
    }

    public function hasSickBalance(float $hours): bool
    {
        return $this->sick_hours_remaining >= $hours;
    }

    public function useVacationHours(float $hours): bool
    {
        if (!$this->hasVacationBalance($hours)) {
            return false;
        }

        $this->vacation_hours_used += $hours;
        return $this->save();
    }

    public function useSickHours(float $hours): bool
    {
        if (!$this->hasSickBalance($hours)) {
            return false;
        }

        $this->sick_hours_used += $hours;
        return $this->save();
    }

    public function refundVacationHours(float $hours): bool
    {
        $this->vacation_hours_used = max(0, $this->vacation_hours_used - $hours);
        return $this->save();
    }

    public function refundSickHours(float $hours): bool
    {
        $this->sick_hours_used = max(0, $this->sick_hours_used - $hours);
        return $this->save();
    }

    public function getVacationUsagePercentageAttribute(): float
    {
        if ($this->vacation_hours_total == 0) {
            return 0.0;
        }

        return ($this->vacation_hours_used / $this->vacation_hours_total) * 100;
    }

    public function getSickUsagePercentageAttribute(): float
    {
        if ($this->sick_hours_total == 0) {
            return 0.0;
        }

        return ($this->sick_hours_used / $this->sick_hours_total) * 100;
    }

    public function validateHoursIncrement(float $hours): bool
    {
        // Must be in 0.25 hour (15-minute) increments
        return fmod($hours, 0.25) === 0.0;
    }

    public function validateMaxDailyHours(float $hours): bool
    {
        // Cannot exceed 8 hours per day
        return $hours <= 8.0;
    }

    public function validateTimeOffRequest(float $hours, string $type): array
    {
        $errors = [];

        // Check increment validation
        if (!$this->validateHoursIncrement($hours)) {
            $errors[] = 'Hours must be in 0.25 hour (15-minute) increments.';
        }

        // Check daily maximum
        if (!$this->validateMaxDailyHours($hours)) {
            $errors[] = 'Cannot exceed 8 hours per day.';
        }

        // Check available balance
        if ($type === 'vacation' && !$this->hasVacationBalance($hours)) {
            $errors[] = sprintf(
                'Insufficient vacation balance. Available: %.2f hours, Requested: %.2f hours.',
                $this->vacation_hours_remaining,
                $hours
            );
        }

        if ($type === 'sick' && !$this->hasSickBalance($hours)) {
            $errors[] = sprintf(
                'Insufficient sick balance. Available: %.2f hours, Requested: %.2f hours.',
                $this->sick_hours_remaining,
                $hours
            );
        }

        return $errors;
    }

    public static function getOrCreateForUser(int $userId, int $year): self
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'year' => $year,
        ], [
            'vacation_hours_total' => 160.00, // 20 days × 8 hours
            'vacation_hours_used' => 0.00,
            'sick_hours_total' => 80.00, // 10 days × 8 hours
            'sick_hours_used' => 0.00,
        ]);
    }
}
