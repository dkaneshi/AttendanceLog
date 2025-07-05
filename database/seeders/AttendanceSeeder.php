<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\UserAbsenceBalance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users with different roles
        $admin = User::create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $manager = User::create([
            'name' => 'Manager Smith',
            'first_name' => 'Manager',
            'last_name' => 'Smith',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $employee1 = User::create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);

        $employee2 = User::create([
            'name' => 'Jane Wilson',
            'first_name' => 'Jane',
            'last_name' => 'Wilson',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);

        // Create absence balances for current year
        $currentYear = now()->year;
        $users = [$admin, $manager, $employee1, $employee2];

        foreach ($users as $user) {
            UserAbsenceBalance::create([
                'user_id' => $user->id,
                'vacation_hours_total' => 160.00,
                'vacation_hours_used' => rand(0, 40) + (rand(0, 3) * 0.25), // Random usage
                'sick_hours_total' => 80.00,
                'sick_hours_used' => rand(0, 20) + (rand(0, 3) * 0.25), // Random usage
                'year' => $currentYear,
            ]);
        }

        // Create sample attendance logs for the past 30 days
        $startDate = now()->subDays(30);
        $employees = [$employee1, $employee2];

        foreach ($employees as $employee) {
            for ($i = 0; $i < 20; $i++) { // 20 days of attendance
                $date = $startDate->copy()->addDays($i);
                
                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                $shiftStart = $date->copy()->setTime(8, rand(0, 30)); // 8:00-8:30 AM
                $lunchStart = $date->copy()->setTime(12, rand(0, 30)); // 12:00-12:30 PM
                $lunchEnd = $lunchStart->copy()->addMinutes(rand(30, 90)); // 30-90 min lunch
                $shiftEnd = $date->copy()->setTime(17, rand(0, 60)); // 5:00-6:00 PM

                $attendanceLog = AttendanceLog::create([
                    'user_id' => $employee->id,
                    'manager_id' => $manager->id,
                    'date' => $date->toDateString(),
                    'shift_start_time' => $shiftStart,
                    'lunch_start_time' => $lunchStart,
                    'lunch_end_time' => $lunchEnd,
                    'shift_end_time' => $shiftEnd,
                    'vacation_hours' => rand(0, 10) > 8 ? rand(1, 4) * 0.25 : 0, // 20% chance of vacation
                    'sick_hours' => rand(0, 20) > 18 ? rand(1, 8) * 0.25 : 0, // 10% chance of sick time
                    'approval_status' => $i < 15 ? 'approved' : 'pending', // Most recent 5 days pending
                    'approved_by' => $i < 15 ? $manager->id : null,
                    'approved_at' => $i < 15 ? now()->subDays(20 - $i) : null,
                ]);

                // Calculate and update hours
                $workedHours = $attendanceLog->calculateWorkedHours();
                $overtimeHours = $attendanceLog->calculateOvertimeHours();
                $totalHours = $attendanceLog->calculateTotalHours();

                $attendanceLog->update([
                    'total_hours' => $totalHours,
                    'overtime_hours' => $overtimeHours,
                ]);
            }
        }

        // Create some manager attendance logs
        for ($i = 0; $i < 15; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            if ($date->isWeekend()) {
                continue;
            }

            $shiftStart = $date->copy()->setTime(9, rand(0, 15)); // 9:00-9:15 AM
            $lunchStart = $date->copy()->setTime(12, rand(30, 60)); // 12:30-1:00 PM
            $lunchEnd = $lunchStart->copy()->addMinutes(rand(45, 75)); // 45-75 min lunch
            $shiftEnd = $date->copy()->setTime(18, rand(0, 30)); // 6:00-6:30 PM

            $attendanceLog = AttendanceLog::create([
                'user_id' => $manager->id,
                'manager_id' => $admin->id,
                'date' => $date->toDateString(),
                'shift_start_time' => $shiftStart,
                'lunch_start_time' => $lunchStart,
                'lunch_end_time' => $lunchEnd,
                'shift_end_time' => $shiftEnd,
                'vacation_hours' => 0,
                'sick_hours' => 0,
                'approval_status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(15 - $i),
            ]);

            // Calculate and update hours
            $workedHours = $attendanceLog->calculateWorkedHours();
            $overtimeHours = $attendanceLog->calculateOvertimeHours();
            $totalHours = $attendanceLog->calculateTotalHours();

            $attendanceLog->update([
                'total_hours' => $totalHours,
                'overtime_hours' => $overtimeHours,
            ]);
        }

        $this->command->info('Created test users and attendance data successfully!');
        $this->command->table(
            ['Role', 'Name', 'Email'],
            [
                ['Admin', $admin->name, $admin->email],
                ['Manager', $manager->name, $manager->email],
                ['Employee', $employee1->name, $employee1->email],
                ['Employee', $employee2->name, $employee2->email],
            ]
        );
    }
}
