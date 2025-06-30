<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');
        });

        // Migrate existing data
        DB::table('users')->get()->each(function ($user): void {
            $nameParts = explode(' ', (string) $user->name);
            $firstName = $nameParts[0] ?? null;
            $lastName = count($nameParts) > 1 ? end($nameParts) : null;

            // Extract middle name (everything between first and last name)
            $middleName = null;
            if (count($nameParts) > 2) {
                $middleParts = array_slice($nameParts, 1, -1);
                $middleName = implode(' ', $middleParts);
            }

            // Update the user with the parsed name components
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'suffix']);
        });
    }
};
