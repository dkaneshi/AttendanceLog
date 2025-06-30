<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the user's full name
     */
    public function getNameAttribute(): string
    {
        $nameParts = [];

        if ($this->first_name) {
            $nameParts[] = $this->first_name;
        }

        if ($this->middle_name) {
            $nameParts[] = $this->middle_name;
        }

        if ($this->last_name) {
            $nameParts[] = $this->last_name;
        }

        if ($this->suffix) {
            $nameParts[] = $this->suffix;
        }

        return implode(' ', $nameParts);
    }

    /**
     * Set the user's full name
     */
    public function setNameAttribute(string $value): void
    {
        $nameParts = explode(' ', $value);

        $this->attributes['first_name'] = $nameParts[0];
        $this->attributes['last_name'] = count($nameParts) > 1 ? end($nameParts) : null;

        // Extract middle name (everything between first and last name)
        if (count($nameParts) > 2) {
            $middleParts = array_slice($nameParts, 1, -1);
            $this->attributes['middle_name'] = implode(' ', $middleParts);
        } else {
            $this->attributes['middle_name'] = null;
        }

        // We still store the full name for backward compatibility
        $this->attributes['name'] = $value;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = '';

        if ($this->first_name) {
            $initials .= Str::substr($this->first_name, 0, 1);
        }

        if ($this->last_name) {
            $initials .= Str::substr($this->last_name, 0, 1);
        } elseif ($this->middle_name) {
            // If no last name but has middle name, use middle initial
            $initials .= Str::substr($this->middle_name, 0, 1);
        }

        return $initials;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
