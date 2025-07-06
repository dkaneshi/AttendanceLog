# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AttendanceLog is a modern Laravel 12.x application using Livewire 3.x with Flux UI components for building interactive attendance tracking features.

## Essential Commands

### Development
```bash
# Start all development services (Laravel, Queue, Logs, Vite)
composer dev

# Run individual services
php artisan serve     # Laravel server
npm run dev          # Vite with hot reload
php artisan queue:listen  # Queue worker
php artisan pail     # Log viewer

# Should use 'php artisan' commands as much as possible when creating new files.
# For a list of available commands use:
php artisan list
```

### Testing & Code Quality
```bash
# Run full test suite (tests, lint, types, refactor check)
composer test

# Individual test commands
composer test:unit   # Run Pest tests with 70% minimum coverage
composer lint        # Fix code style with Laravel Pint
composer refactor    # Apply Rector refactorings
composer test:lint   # Check code style without fixing
composer test:types  # Run PHPStan static analysis
composer test:refactor  # Dry-run Rector changes
```

### Database
```bash
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Reset and re-run migrations
touch database/database.sqlite  # Create SQLite database file
```

## Architecture & Key Patterns

### Technology Stack
- **Backend**: Laravel 12.x with PHP 8.3+
- **Frontend**: Livewire 3.x, Livewire Volt (single-file components), Flux UI, Tailwind CSS v4
- **Database**: SQLite (default), supports other databases via configuration
- **Testing**: Pest PHP with parallel execution and coverage requirements

### Component Organization
- **Livewire Components**: `app/Livewire/` - Full-page components for auth and settings
- **Volt Components**: `resources/views/livewire/` - Single-file components using Livewire Volt
- **Flux UI**: Pre-built components from Livewire Flux Pro (authenticated via Composer)

### Authentication System
Full authentication scaffolding is implemented with:
- Login/Registration (`app/Livewire/Auth/`)
- Email verification with signed URLs
- Password reset flow
- User profile management with extended fields (first_name, middle_name, last_name, suffix)
- Appearance settings

### Code Quality Standards
- **Strict Types**: All PHP files must declare `declare(strict_types=1);`
- **Final Classes**: Classes should be final unless explicitly designed for inheritance
- **Return Types**: All methods must have explicit return type declarations
- **Code Coverage**: Minimum 70% test coverage required
- **Static Analysis**: PHPStan level 5 with Larastan extensions

### Data Integrity
- Use soft deletes for all database records
- Never use cascade on delete for foreign key relationships
- All models should implement soft delete functionality

## Development Workflow

1. Make changes to code
2. Run `composer lint` to fix code style
3. Run `composer test:types` to check for type errors
4. Run `composer test:unit` to ensure tests pass with coverage
5. Run `composer refactor` if making significant structural changes

## Testing Patterns

### Unit Tests (tests/Unit/)
```php
test('example unit test', function () {
    expect($result)->toBe($expected);
});
```

### Feature Tests (tests/Feature/)
```php
test('example feature test', function () {
    $response = $this->get('/route');
    $response->assertStatus(200);
});
```

### Livewire Component Tests
```php
use Livewire\Livewire;

test('livewire component interaction', function () {
    Livewire::test(ComponentClass::class)
        ->set('property', 'value')
        ->call('method')
        ->assertSee('expected output');
});
```

## Documentation
- **[Laravel](https://laravel.com/docs/12.x)** - you will also find documentation on Pint here.
- **[Livewire](https://livewire.laravel.com/docs/quickstart)**
- **[Alpine.js](https://alpinejs.dev/start-here)**
- **[TailwindCSS](https://tailwindcss.com/docs/installation/using-vite)**
- **[Pest PHP](https://pestphp.com/docs/installation)**
- **[Larastan](https://github.com/larastan/larastan?tab=readme-ov-file)**
- **[PHPStan](https://phpstan.org/user-guide/getting-started)**
- **[Rector](https://getrector.com/documentation)**
