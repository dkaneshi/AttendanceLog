# AttendanceLog Project Guidelines

This document provides guidelines and instructions for developers working on the AttendanceLog project.

## Build/Configuration Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js and npm

### Setup Steps

1. **Clone the repository**

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up environment file**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up the database**
   The project is configured to use SQLite by default for development:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

6. **Start the development server**
   ```bash
   composer dev
   ```
   This command runs multiple services concurrently:
   - Laravel development server
   - Queue worker
   - Laravel Pail (log viewer)
   - Vite development server

## Testing Information

### Testing Framework
The project uses Pest PHP, a testing framework built on top of PHPUnit with a more expressive syntax.

### Running Tests

- **Run all tests**
  ```bash
  composer test
  ```

- **Run only unit tests**
  ```bash
  composer test:unit
  ```

- **Run code style checks**
  ```bash
  composer test:lint
  ```

- **Run static analysis**
  ```bash
  composer test:types
  ```

### Adding New Tests

#### Unit Tests
Unit tests should be placed in the `tests/Unit` directory. Here's an example of a simple unit test:

```php
<?php

declare(strict_types=1);

test('string can be reversed', function () {
    $original = 'Hello World';
    $reversed = strrev($original);
    
    expect($reversed)->toBe('dlroW olleH');
});
```

#### Feature Tests
Feature tests should be placed in the `tests/Feature` directory. These tests typically test the application's HTTP endpoints and Livewire components. Here's an example of a simple feature test:

```php
<?php

declare(strict_types=1);

test('returns a successful response', function () {
    $response = $this->get('/');
    
    $response->assertStatus(200);
});
```

#### Testing Livewire Components
For testing Livewire components, use the Pest Livewire plugin:

```php
<?php

declare(strict_types=1);

use App\Livewire\Counter;

test('can increment counter', function () {
    Livewire::test(Counter::class)
        ->assertSee(0)
        ->call('increment')
        ->assertSee(1);
});
```

## Additional Development Information

### Code Style
The project uses Laravel Pint for code style enforcement. To check and fix code style issues:

```bash
# Check code style
composer test:lint

# Fix code style issues
composer lint
```

### Static Analysis
The project uses PHPStan via Larastan for static analysis. To run static analysis:

```bash
composer test:types
```

### Code Refactoring
The project uses Rector for automated code refactoring. The configuration is in `rector.php`.

### Project Structure
- `app/` - Application code
- `config/` - Configuration files
- `database/` - Database migrations, factories, and seeders
- `resources/` - Views, assets, and language files
- `routes/` - Route definitions
- `tests/` - Test files
- `vendor/` - Composer dependencies

### Livewire Components
The project uses Livewire for interactive UI components. Livewire components are located in the `app/Livewire` directory.

### Flux UI
The project uses Livewire Flux for UI components. Flux Pro is configured via a custom Composer repository.

### Data Integrity
The project shall use soft deletes for all database records and cascade on delete should not be used for foreign key relationships.
