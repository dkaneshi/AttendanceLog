# Attendance Log System
## Product Requirements Document

**Version:** 1.0  
**Date:** June 30, 2025  
**Document Owner:** Development Team  

---

## 1. Executive Summary

### 1.1 Product Overview
The Attendance Log System is a web-based application designed to track employee daily attendance including shift times, lunch breaks, and overtime calculations. The system will provide accurate time tracking with automated overtime calculation based on standard 8-hour work days.

### 1.2 Business Objectives
- Automate attendance tracking and reduce manual timekeeping errors
- Provide accurate overtime calculations for payroll processing
- Improve transparency in time tracking for both employees and management
- Create a reliable audit trail for attendance records

### 1.3 Success Metrics
- 100% accurate overtime calculations
- Reduced time spent on manual attendance processing by 80%
- User adoption rate of 95% within 30 days of deployment
- Zero data loss incidents

---

## 2. Product Requirements

### 2.1 Target Users
- **Primary Users:** Employees logging their daily attendance
- **Secondary Users:** HR personnel and managers reviewing attendance data
- **System Administrators:** IT staff managing user accounts and system configuration

### 2.2 User Stories

#### Employee User Stories
- As an employee, I want to log my shift start time so that my work hours are accurately recorded
- As an employee, I want to log my lunch break times so that my break duration is properly tracked
- As an employee, I want to log my shift end time so that my total work hours are calculated
- As an employee, I want to view my overtime hours so that I can track my additional compensation
- As an employee, I want to view my attendance history so that I can verify my recorded hours
- As an employee, I want to log vacation or sick hours on any workday so that my time off is properly tracked and deducted from my available balance

#### Manager/HR User Stories
- As a manager, I want to view employee attendance reports so that I can monitor team productivity
- As a manager, I want to approve my employees' weekly attendance reports so that I can verify accuracy before payroll processing
- As an HR representative, I want to export attendance data so that I can process payroll accurately
- As a supervisor, I want to see overtime reports so that I can manage labor costs effectively

### 2.3 Core Features

#### 2.3.1 Time Logging
- **Shift Start Time:** Record when employee begins their work day
- **Lunch Start Time:** Record when employee begins lunch break
- **Lunch End Time:** Record when employee returns from lunch break
- **Shift End Time:** Record when employee ends their work day

#### 2.3.2 Absence Tracking
- **Vacation Hours:** Allow employees to log vacation hours on any workday (full or partial days)
- **Sick Hours:** Allow employees to log sick hours on any workday (full or partial days)
- **Mixed Day Support:** Enable logging both work hours and vacation/sick hours on the same day
- **Hourly Balance Tracking:** Track vacation/sick time usage in hours with decimal precision

#### 2.3.3 Overtime Calculation
- Automatically calculate overtime hours when total work time exceeds 8 hours
- Formula: `(Shift End - Shift Start - Lunch Duration) - 8 hours = Overtime`
- Display overtime in hours and minutes format (e.g., "2h 30m")

#### 2.3.3 Weekly Approval Workflow
- Generate weekly attendance summaries for manager review
- Enable managers to approve or reject weekly timesheets
- Provide comments/notes functionality for approval decisions
- Send notifications to employees upon approval status changes
- Lock approved weeks from further employee modifications

#### 2.3.4 Data Management
- Store all attendance records with timestamps
- Provide historical data access for employees and administrators
- Enable data export functionality for payroll integration

---

## 3. Functional Requirements

### 3.1 Authentication & Authorization
- User login/logout functionality
- Role-based access control (Employee, Manager, Admin)
- Password reset capability
- Session management

### 3.2 Attendance Logging Interface
- Simple, intuitive time entry form
- Real-time validation of time entries
- Prevent duplicate entries for the same day
- Allow editing of current day entries only
- Automatic calculation and display of total hours worked
- Live overtime calculation display
- Vacation/sick hour entry fields on work days
- Automatic calculation of remaining work hours when vacation/sick time is used

### 3.3 Data Validation Rules
- Shift start time must be before lunch start time
- Lunch end time must be after lunch start time
- Shift end time must be after lunch end time
- Maximum lunch break duration: 2 hours
- Minimum lunch break duration: 30 minutes
- Cannot log future dates
- Cannot modify entries older than 7 days (admin override available)
- Vacation/sick hours must be in 0.25 hour increments (15-minute intervals)
- Total vacation/sick hours cannot exceed 8 hours per day
- Combined work hours + vacation/sick hours should equal expected daily hours (typically 8)
- Cannot use more vacation/sick hours than available in balance

### 3.4 Reporting & Analytics
- Daily attendance summary
- Weekly/monthly attendance reports
- Overtime summary reports
- Vacation/sick leave usage reports (by hours)
- Export functionality (CSV, PDF)
- Filter reports by date range and employee
- Hourly balance tracking and utilization reports

### 3.5 Dashboard Features
- Current day attendance status
- Week-to-date hours summary
- Month-to-date overtime total
- Recent attendance history (last 7 days)
- Quick action buttons for common tasks
- Vacation/sick hour balances display (hours remaining)
- **Manager Dashboard:** Pending approval notifications and team summary

### 3.6 Weekly Approval System
- Generate weekly attendance summaries every Monday for the previous week
- Manager dashboard showing pending approvals
- Bulk approval functionality for multiple employees
- Individual timesheet review with detailed daily breakdown
- Approval status tracking (Pending, Approved, Rejected, Requires Correction)
- Email notifications for approval requests and status updates
- Comments system for manager feedback
- Escalation process for rejected timesheets

---

## 4. Non-Functional Requirements

### 4.1 Performance
- Page load times under 2 seconds
- Support for 100 concurrent users
- Database query response times under 500ms
- Real-time calculations without page refresh

### 4.2 Security
- Secure user authentication
- Data encryption in transit and at rest
- SQL injection prevention
- XSS protection
- CSRF protection

### 4.3 Usability
- Mobile-responsive design
- Intuitive user interface requiring minimal training
- Accessible design following WCAG 2.1 guidelines
- Multi-browser compatibility (Chrome, Firefox, Safari, Edge)

### 4.4 Reliability
- 99.9% uptime availability
- Automated daily database backups
- Error logging and monitoring
- Graceful error handling with user-friendly messages

---

## 5. Technical Specifications

### 5.1 Technology Stack
- **Backend Framework:** PHP 8.1+ with Laravel 10
- **Frontend Framework:** Livewire 3.0
- **JavaScript:** Alpine.js 3.x
- **CSS Framework:** Tailwind CSS 3.x
- **UI Components:** Flux UI component library
- **Database:** MySQL 8.0+ or PostgreSQL 13+
- **Web Server:** Apache 2.4+ or Nginx 1.18+

### 5.2 Database Schema

#### Users Table
- id (Primary Key)
- name (VARCHAR 255)
- first_name (VARCHAR 255)
- middle_name (VARCHAR 255)
- last_name (VARCHAR 255)
- suffix (VARCHAR 255)
- email (VARCHAR 255, Unique)
- password (VARCHAR 255, Hashed)
- role (ENUM: employee, manager, admin)
- created_at, updated_at (Timestamps)

#### Attendance Logs Table
- id (Primary Key)
- user_id (Foreign Key to Users)
- manager_id (Foreign Key to Users, Nullable)
- date (DATE, Unique per user)
- shift_start_time (TIME, Nullable)
- lunch_start_time (TIME, Nullable)
- lunch_end_time (TIME, Nullable)
- shift_end_time (TIME, Nullable)
- vacation_hours (DECIMAL 4,2, Default: 0)
- sick_hours (DECIMAL 4,2, Default: 0)
- total_hours (DECIMAL 4,2, Calculated)
- overtime_hours (DECIMAL 4,2, Calculated)
- approval_status (ENUM: pending, approved, rejected, requires_correction)
- manager_comments (TEXT, Nullable)
- approved_at (TIMESTAMP, Nullable)
- approved_by (Foreign Key to Users, Nullable)
- created_at, updated_at (Timestamps)

#### User Absence Balances Table
- id (Primary Key)
- user_id (Foreign Key to Users, Unique)
- vacation_hours_total (DECIMAL 6,2, Default: 160) -- 20 days × 8 hours
- vacation_hours_used (DECIMAL 6,2, Default: 0)
- sick_hours_total (DECIMAL 6,2, Default: 80) -- 10 days × 8 hours
- sick_hours_used (DECIMAL 6,2, Default: 0)
- year (INTEGER)
- created_at, updated_at (Timestamps)

### 5.3 API Endpoints
- `POST /attendance/clock-in` - Record shift start
- `POST /attendance/lunch-start` - Record lunch break start
- `POST /attendance/lunch-end` - Record lunch break end
- `POST /attendance/clock-out` - Record shift end
- `POST /attendance/vacation-hours` - Add vacation hours to current day
- `POST /attendance/sick-hours` - Add sick hours to current day
- `GET /attendance/today` - Get current day attendance
- `GET /attendance/history` - Get attendance history
- `GET /attendance/balances` - Get vacation/sick hour balances
- `GET /reports/overtime` - Get overtime reports
- `GET /reports/time-off` - Get vacation/sick time usage reports
- `GET /manager/pending-approvals` - Get pending weekly approvals
- `POST /manager/approve-week` - Approve employee's weekly timesheet
- `POST /manager/reject-week` - Reject employee's weekly timesheet
- `GET /manager/team-summary` - Get team attendance summary

### 5.4 Business Logic

#### Overtime Calculation Formula
```php
function calculateOvertime($shiftStart, $shiftEnd, $lunchStart, $lunchEnd) {
    $totalShiftMinutes = $shiftEnd->diffInMinutes($shiftStart);
    $lunchMinutes = $lunchEnd->diffInMinutes($lunchStart);
    $workedMinutes = $totalShiftMinutes - $lunchMinutes;
    $standardWorkMinutes = 8 * 60; // 8 hours
    
    $overtimeMinutes = max(0, $workedMinutes - $standardWorkMinutes);
    return $overtimeMinutes / 60; // Return as decimal hours
}

function calculateTotalHours($workedHours, $vacationHours, $sickHours) {
    return $workedHours + $vacationHours + $sickHours;
}

function updateTimeOffBalance($userId, $hours, $type, $year) {
    $balance = UserAbsenceBalance::where('user_id', $userId)
                                ->where('year', $year)
                                ->first();
    
    if ($type === 'vacation') {
        $balance->vacation_hours_used += $hours;
    } elseif ($type === 'sick') {
        $balance->sick_hours_used += $hours;
    }
    
    $balance->save();
}

function validateTimeOffRequest($userId, $hours, $type, $year) {
    $balance = UserAbsenceBalance::where('user_id', $userId)
                                ->where('year', $year)
                                ->first();
    
    $available = $type === 'vacation' 
        ? ($balance->vacation_hours_total - $balance->vacation_hours_used)
        : ($balance->sick_hours_total - $balance->sick_hours_used);
    
    return $hours <= $available;
}
```

---

## 6. User Interface Requirements

### 6.1 Main Dashboard
- Clean, card-based layout using Flux components
- Current time display
- Quick status indicators (In/Out, Lunch status)
- Today's hours summary
- Action buttons for time logging

### 6.2 Time Entry Form
- Large, touch-friendly buttons for mobile use
- Real-time validation feedback
- Progress indicator showing completion status
- Confirmation dialogs for critical actions
- Vacation/sick hour input fields with decimal support (0.25 hour increments)
- Real-time balance checking and remaining hours display
- Auto-calculation of total daily hours (work + vacation + sick)

### 6.3 Reports Interface
- Filterable data tables
- Export options prominently displayed
- Visual charts for overtime trends
- Time-off usage dashboards showing hourly consumption
- Balance tracking with visual indicators (progress bars)
- Print-friendly layouts

### 6.4 Manager Approval Interface
- Clean approval queue with employee cards
- Weekly summary view with daily breakdowns
- One-click approval for accurate timesheets
- Detailed review mode for questionable entries
- Bulk actions for multiple approvals
- Comment system for feedback
- Status indicators and filtering options

### 6.5 Responsive Design
- Mobile-first approach
- Tablet and desktop optimizations
- Touch-friendly interface elements
- Consistent spacing and typography

---

## 7. Implementation Timeline

### Phase 1: Core Development (Weeks 1-4)
- Database schema design and migration
- User authentication system
- Basic attendance logging functionality
- Hourly vacation/sick time tracking system
- Overtime calculation engine
- Balance validation logic

### Phase 2: User Interface (Weeks 5-6)
- Dashboard implementation
- Time entry forms
- Basic reporting interface
- Manager approval interface
- Mobile responsiveness

### Phase 3: Advanced Features (Weeks 7-8)
- Advanced reporting and analytics
- Data export functionality
- Admin management interface
- Email notification system
- Performance optimization

### Phase 4: Testing & Deployment (Weeks 9-10)
- Comprehensive testing (unit, integration, user acceptance)
- Security audit
- Performance testing
- Production deployment and monitoring setup

---

## 8. Risk Assessment

### 8.1 Technical Risks
- **Database Performance:** Large datasets may impact query performance
  - *Mitigation:* Implement proper indexing and query optimization
- **Time Zone Handling:** Different user locations may cause confusion
  - *Mitigation:* Store all times in UTC, display in user's local timezone

### 8.2 Business Risks
- **User Adoption:** Employees may resist new system
  - *Mitigation:* Provide comprehensive training and gradual rollout
- **Data Accuracy:** Incorrect time entries may affect payroll
  - *Mitigation:* Implement validation rules and manager approval workflows

### 8.3 Security Risks
- **Data Breach:** Attendance data contains sensitive employee information
  - *Mitigation:* Implement strong encryption and access controls
- **Time Fraud:** Employees may attempt to manipulate time entries
  - *Mitigation:* Implement audit trails and approval processes

---

## 9. Assumptions and Dependencies

### 9.1 Assumptions
- Users have basic computer/smartphone literacy
- Standard 8-hour work day with 30-60 minute lunch breaks
- Single timezone operation (initially)
- Internet connectivity available during work hours

### 9.2 Dependencies
- Laravel framework updates and security patches
- MySQL/PostgreSQL database availability
- Web server infrastructure
- SSL certificate for secure connections

---

## 10. Acceptance Criteria

### 10.1 Functional Acceptance
- [ ] Users can successfully log all four time points (shift start/end, lunch start/end)
- [ ] Users can add vacation/sick hours to any workday with accurate balance deduction
- [ ] System prevents users from exceeding available vacation/sick hour balances
- [ ] Overtime calculations are accurate to the minute
- [ ] Total daily hours calculation includes work + vacation + sick time
- [ ] Weekly approval workflow functions correctly for managers
- [ ] Email notifications are sent for approval requests and status changes
- [ ] Time-off balances are accurately calculated and displayed in hours
- [ ] Reports generate correctly with proper data filtering
- [ ] Data export functions work without corruption
- [ ] Mobile interface is fully functional

### 10.2 Performance Acceptance
- [ ] Page loads complete within 2 seconds
- [ ] System supports 100 concurrent users without degradation
- [ ] Database queries execute within 500ms
- [ ] Mobile interface responds immediately to touch inputs

### 10.3 Security Acceptance
- [ ] All user authentication functions work properly
- [ ] Data transmission is encrypted
- [ ] SQL injection and XSS vulnerabilities are eliminated
- [ ] User data access is properly restricted by role

---

## 11. Post-Launch Considerations

### 11.1 Maintenance Requirements
- Regular database maintenance and optimization
- Security patch updates for Laravel and dependencies
- User support and training documentation updates
- Performance monitoring and optimization

### 11.2 Future Enhancements
- Integration with payroll systems
- Mobile app development
- Advanced analytics and reporting
- Multi-timezone support
- API for third-party integrations
- Geolocation verification for remote workers
- Vacation request approval workflow
- Holiday calendar integration
- Time-off policy automation and accrual rules
- Floating holiday and personal time tracking

---

**Document Approval:**

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product Owner | | | |
| Technical Lead | | | |
| QA Lead | | | |

---

*This document serves as the primary reference for the Attendance Log System development project and should be updated as requirements evolve during the development process.*
