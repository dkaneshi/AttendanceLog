## Relevant Files

- `app/Models/User.php` - User model with authentication and role management (completed)
- `tests/Unit/Models/UserTest.php` - Unit tests for User model
- `app/Models/AttendanceLog.php` - Core attendance log model with overtime calculations (completed)
- `tests/Unit/Models/AttendanceLogTest.php` - Unit tests for AttendanceLog model
- `app/Models/UserAbsenceBalance.php` - Model for tracking vacation/sick hour balances (completed)
- `tests/Unit/Models/UserAbsenceBalanceTest.php` - Unit tests for UserAbsenceBalance model
- `app/Livewire/Attendance/TimeEntry.php` - Main time entry component
- `tests/Feature/Livewire/Attendance/TimeEntryTest.php` - Feature tests for time entry
- `app/Livewire/Manager/ApprovalQueue.php` - Manager approval interface component
- `tests/Feature/Livewire/Manager/ApprovalQueueTest.php` - Tests for approval workflow
- `app/Services/OvertimeCalculator.php` - Service class for overtime calculations (completed)
- `tests/Unit/Services/OvertimeCalculatorTest.php` - Unit tests for overtime calculator (completed)
- `app/Services/AttendanceValidator.php` - Service class for attendance data validation (completed)
- `tests/Unit/Services/AttendanceValidatorTest.php` - Unit tests for attendance validator (completed)
- `app/Http/Controllers/Api/AttendanceController.php` - API controller for attendance logging endpoints (completed)
- `tests/Feature/Api/AttendanceControllerTest.php` - Feature tests for attendance API endpoints (completed)
- `routes/api.php` - API routes for attendance endpoints (completed)
- `database/migrations/2025_07_06_085017_add_manager_id_to_users_table.php` - Migration to add manager relationship (completed)
- `database/factories/AttendanceLogFactory.php` - Factory for creating test attendance log data (completed)
- `database/migrations/2025_07_05_083420_create_attendance_logs_table.php` - Migration for attendance logs (completed)
- `database/migrations/2025_07_05_083826_create_user_absence_balances_table.php` - Migration for absence balances (completed)
- `database/migrations/2025_07_05_083951_add_role_to_users_table.php` - Migration to add role field to users table (completed)
- `database/seeders/AttendanceSeeder.php` - Seeder for creating test users and attendance data (completed)
- `database/seeders/DatabaseSeeder.php` - Main database seeder updated to call AttendanceSeeder (completed)

### Notes

- Unit tests should typically be placed alongside the code files they are testing (e.g., `MyComponent.php` and `MyComponent.test.php` in the same directory).
- Use `composer test:unit` to run tests. Running without a path executes all tests found by the Pest configuration.

## Tasks

- [x] 1.0 Set up database schema and models
  - [x] 1.1 Create migration for attendance_logs table with all required fields
  - [x] 1.2 Create migration for user_absence_balances table
  - [x] 1.3 Update users table migration to add role field and name fields (first_name, middle_name, last_name, suffix)
  - [x] 1.4 Create AttendanceLog model with relationships and calculated attributes
  - [x] 1.5 Create UserAbsenceBalance model with validation logic
  - [x] 1.6 Update User model to include role management and relationships
  - [x] 1.7 Create database seeders for testing data
- [ ] 2.0 Implement core attendance logging functionality
  - [x] 2.1 Create OvertimeCalculator service class with calculation logic
  - [x] 2.2 Implement attendance validation rules (time sequence, lunch duration, etc.)
  - [x] 2.3 Create attendance logging API endpoints (clock-in, lunch-start, lunch-end, clock-out)
  - [ ] 2.4 Implement vacation/sick hours logging endpoints
  - [ ] 2.5 Create balance checking and deduction logic
  - [ ] 2.6 Implement real-time overtime calculation
  - [ ] 2.7 Add prevention for duplicate daily entries
  - [ ] 2.8 Create edit restrictions (current day only, 7-day limit)
- [ ] 3.0 Build user interface components
  - [ ] 3.1 Create main dashboard layout with Flux UI components
  - [ ] 3.2 Build TimeEntry Livewire component for logging attendance
  - [ ] 3.3 Implement real-time validation and calculation displays
  - [ ] 3.4 Create vacation/sick hours input interface with balance checking
  - [ ] 3.5 Design mobile-responsive layouts
  - [ ] 3.6 Build attendance history view component
  - [ ] 3.7 Create quick action buttons and status indicators
  - [ ] 3.8 Implement confirmation dialogs for critical actions
- [ ] 4.0 Develop reporting and analytics features
  - [ ] 4.1 Create daily attendance summary report
  - [ ] 4.2 Build weekly/monthly attendance reports with filters
  - [ ] 4.3 Implement overtime summary reports with visual charts
  - [ ] 4.4 Create vacation/sick leave usage reports (hourly tracking)
  - [ ] 4.5 Build CSV and PDF export functionality
  - [ ] 4.6 Implement date range and employee filters
  - [ ] 4.7 Create balance tracking dashboards with progress indicators
  - [ ] 4.8 Design print-friendly report layouts
- [ ] 5.0 Implement manager approval workflow
  - [ ] 5.1 Create ApprovalQueue Livewire component for managers
  - [ ] 5.2 Build weekly timesheet generation logic
  - [ ] 5.3 Implement approval status tracking (pending, approved, rejected, requires_correction)
  - [ ] 5.4 Create bulk approval functionality
  - [ ] 5.5 Build comment system for manager feedback
  - [ ] 5.6 Implement email notifications for approval requests and status changes
  - [ ] 5.7 Create timesheet locking mechanism for approved weeks
  - [ ] 5.8 Build manager dashboard with team summary and pending approvals