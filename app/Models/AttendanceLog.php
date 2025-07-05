<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AttendanceLog extends Model
{
    use SoftDeletes;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'manager_id',
        'date',
        'shift_start_time',
        'lunch_start_time',
        'lunch_end_time',
        'shift_end_time',
        'vacation_hours',
        'sick_hours',
        'total_hours',
        'overtime_hours',
        'approval_status',
        'manager_comments',
        'approved_at',
        'approved_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'shift_start_time' => 'datetime',
        'lunch_start_time' => 'datetime',
        'lunch_end_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'vacation_hours' => 'decimal:2',
        'sick_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateWorkedHours(): float
    {
        if (! $this->shift_start_time || ! $this->shift_end_time) {
            return 0.0;
        }

        $startTime = Carbon::parse($this->shift_start_time);
        $endTime = Carbon::parse($this->shift_end_time);
        $totalMinutes = $endTime->diffInMinutes($startTime);

        // Subtract lunch break if both times are set
        if ($this->lunch_start_time && $this->lunch_end_time) {
            $lunchStart = Carbon::parse($this->lunch_start_time);
            $lunchEnd = Carbon::parse($this->lunch_end_time);
            $lunchMinutes = $lunchEnd->diffInMinutes($lunchStart);
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    public function calculateOvertimeHours(): float
    {
        $workedHours = $this->calculateWorkedHours();
        $standardHours = 8.0;

        return max(0.0, $workedHours - $standardHours);
    }

    public function calculateTotalHours(): float
    {
        $workedHours = $this->calculateWorkedHours();

        return $workedHours + $this->vacation_hours + $this->sick_hours;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function requiresCorrection(): bool
    {
        return $this->approval_status === 'requires_correction';
    }

    public function canBeEdited(): bool
    {
        // Can only edit if not approved and within 7 days
        if ($this->isApproved()) {
            return false;
        }

        $daysDiff = Carbon::now()->diffInDays($this->date);

        return $daysDiff <= 7;
    }

    public function getLunchDurationAttribute(): ?int
    {
        if (! $this->lunch_start_time || ! $this->lunch_end_time) {
            return null;
        }

        $lunchStart = Carbon::parse($this->lunch_start_time);
        $lunchEnd = Carbon::parse($this->lunch_end_time);

        return $lunchEnd->diffInMinutes($lunchStart);
    }

    public function getFormattedOvertimeAttribute(): string
    {
        $overtimeHours = (float) $this->overtime_hours;
        $hours = floor($overtimeHours);
        $minutes = ($overtimeHours - $hours) * 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }
}
