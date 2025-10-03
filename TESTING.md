# Testing Guide

This project uses **Codeception** as the primary testing framework for comprehensive testing coverage. Codeception provides unit, functional, and acceptance testing capabilities with excellent Symfony integration.

## Overview

- **Codeception Unit Tests**: Pure unit tests with BDD-style syntax
- **Codeception Functional Tests**: Integration tests with Symfony framework
- **Codeception Acceptance Tests**: End-to-end HTTP testing

## Quick Start

### Run All Tests
```bash
# All Codeception tests
vendor/bin/codecept run

# Or using composer script
composer test
```

### Run Tests with Coverage
```bash
# Generate HTML coverage report
vendor/bin/codecept run --coverage --coverage-html

# Generate XML coverage for CI
vendor/bin/codecept run --coverage --coverage-xml

# Using composer script
composer test-coverage
```

## Codeception Configuration

### Main Configuration
- **Config file**: `codeception.yml`
- **Test directory**: `tests/`
- **Bootstrap**: `tests/bootstrap.php`
- **Namespace**: `App\Tests\Codeception`
- **Coverage**: Enabled with 50% minimum, 90% target

### Running Tests

```bash
# Run all tests
vendor/bin/codecept run

# Run specific suite
vendor/bin/codecept run Unit
vendor/bin/codecept run Functional
vendor/bin/codecept run Acceptance

# Run specific test
vendor/bin/codecept run Unit/Services/GameServiceTest

# Run with coverage
vendor/bin/codecept run --coverage --coverage-html

# Run with debug output
vendor/bin/codecept run --debug

# Using composer scripts
composer test                    # Run all tests
composer test-coverage          # Run with coverage
composer test-unit              # Run unit tests only
composer test-functional        # Run functional tests only
```

## Test Suites

### 1. Unit Tests (`tests/Unit/`)
- **Actor**: `UnitTester`
- **Modules**: Asserts, Symfony
- **Purpose**: Unit testing with Codeception's BDD-style syntax and Symfony integration

### 2. Functional Tests (`tests/Functional/`)
- **Actor**: `FunctionalTester`
- **Modules**: Asserts, Symfony, Doctrine2
- **Purpose**: Testing application functionality with full Symfony integration

### 3. Acceptance Tests (`tests/Acceptance/`)
- **Actor**: `AcceptanceTester`
- **Modules**: PhpBrowser, REST
- **Purpose**: End-to-end testing through HTTP requests

## Test Structure Examples

### Unit Test Example
```php
<?php

declare(strict_types=1);

namespace App\Tests\Codeception\Unit\Generator;

use App\Generator\ReelGenerator;
use Codeception\Test\Unit;

final class ReelGeneratorTest extends Unit
{
    private ReelGenerator $generator;

    protected function _before(): void
    {
        $this->generator = new ReelGenerator($mockRng);
    }

    public function testGeneratePositions(): void
    {
        $positions = $this->generator->generateReelPositions($game);
        $this->assertCount(5, $positions);
    }
}
```

### Functional Test Example
```php
<?php

namespace App\Tests\Codeception\Functional;

use App\Tests\Support\FunctionalTester;

class GameCest
{
    public function testCreateGame(FunctionalTester $I): void
    {
        $I->sendPost('/api/games', [
            'name' => 'Test Game',
            'type' => 'slot'
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeInDatabase('game', ['name' => 'Test Game']);
    }
}
```

### Acceptance Test Example
```php
<?php

namespace App\Tests\Codeception\Acceptance;

use App\Tests\Support\AcceptanceTester;

class HomepageCest
{
    public function testHomepage(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Welcome');
        $I->seeElement('h1');
    }
}
```

## Test Organization

### Directory Structure
```
tests/
├── Unit/                          # Codeception unit tests
│   ├── Generator/
│   ├── Services/
│   └── .gitignore
├── Functional/                    # Integration tests
├── Acceptance/                    # End-to-end tests
├── Support/                       # Test helpers and actors
│   ├── Helper/
│   ├── Data/
│   ├── _generated/               # Auto-generated actor methods
│   ├── AcceptanceTester.php
│   ├── FunctionalTester.php
│   └── UnitTester.php
├── _output/                       # Test output and coverage
├── Unit.suite.yml                # Unit test suite configuration
├── Functional.suite.yml          # Functional test suite configuration
├── Acceptance.suite.yml          # Acceptance test suite configuration
└── bootstrap.php
```

### Naming Conventions
- **Unit Tests**: `{ClassName}Test.php`
- **Functional/Acceptance Tests**: `{Feature}Cest.php`

## Coverage Reports

### Generating Coverage Reports

```bash
# HTML coverage report
vendor/bin/codecept run --coverage --coverage-html

# XML coverage for CI
vendor/bin/codecept run --coverage --coverage-xml

# Text coverage summary
vendor/bin/codecept run --coverage --coverage-text

# Using composer scripts
composer test-coverage
```

### Coverage Thresholds
- **Minimum**: 50% (configured in codeception.yml)
- **Target**: 90% (configured in codeception.yml)

## Database Testing

Codeception provides excellent database testing capabilities with automatic cleanup:

```php
// Functional test with database operations
public function testDatabaseOperation(FunctionalTester $I): void
{
    // Insert test data
    $I->haveInDatabase('user', ['name' => 'Test User', 'email' => 'test@example.com']);
    
    // Verify data exists
    $I->seeInDatabase('user', ['name' => 'Test User']);
    
    // Test application behavior
    $I->sendGet('/api/users/1');
    $I->seeResponseCodeIs(200);
    $I->seeResponseContainsJson(['name' => 'Test User']);
}
```

Database is automatically cleaned between tests when using Doctrine2 module.

## Mocking and Fixtures

### Codeception Mocking
```php
// In unit tests
protected function _before(): void
{
    $this->mock = \Codeception\Stub::make(ServiceInterface::class, [
        'process' => 'mocked result'
    ]);
}
```

### Database Fixtures
```php
// Create test data in functional tests
$I->haveInDatabase('game', [
    'name' => 'Test Game',
    'type' => 'slot',
    'rtp' => 96.5
]);

// Use fixtures from Support/Data directory
$I->haveInDatabase('user', $I->grabFixture('users', 'admin'));
```

## Continuous Integration

### GitHub Actions Example
```yaml
- name: Run Tests
  run: vendor/bin/codecept run

- name: Generate Coverage
  run: vendor/bin/codecept run --coverage --coverage-xml

- name: Upload Coverage
  uses: codecov/codecov-action@v4
  with:
    file: ./tests/_output/coverage.xml
```

## Best Practices

### General
1. **Test Naming**: Use descriptive test method names
2. **BDD Style**: Write tests that read like specifications
3. **One Scenario**: Focus on one behavior per test method
4. **Test Data**: Use meaningful test data, not just dummy values

### Codeception Specific
1. Use descriptive scenario names in Cest files
2. Leverage built-in Symfony integration for functional tests
3. Use database cleanup features for integration tests
4. Write acceptance tests from user perspective
5. Use `_before()` and `_after()` methods for test setup/cleanup
6. Prefer `$I->see()` over complex assertions for readability

## Debugging Tests

### Codeception Debugging
```bash
# Debug mode with detailed output
vendor/bin/codecept run --debug

# Step-by-step execution
vendor/bin/codecept run --steps

# HTML report
vendor/bin/codecept run --html

# Stop on first failure
vendor/bin/codecept run --fail-fast

# Run specific test with debug
vendor/bin/codecept run Unit/Services/GameServiceTest --debug
```

## Performance

### Running Tests Faster
```bash
# Run specific test groups
vendor/bin/codecept run --group fast

# Skip slow tests
vendor/bin/codecept run --skip slow

# Run only failed tests from previous run
vendor/bin/codecept run --failed

# Run tests in parallel (requires extension)
vendor/bin/codecept run --ext "Codeception\Extension\RunProcess"
```

## Troubleshooting

### Common Issues

1. **Xdebug Coverage**: Ensure `XDEBUG_MODE=coverage` is set
2. **Database Permissions**: Check test database access
3. **Memory Limits**: Increase PHP memory limit for large test suites
4. **Autoloading**: Run `composer dump-autoload` after adding new test classes

### Environment Variables
```bash
# Set test environment
export APP_ENV=test

# Enable Xdebug coverage
export XDEBUG_MODE=coverage

# Increase memory limit
export PHP_MEMORY_LIMIT=512M
```

## Summary

- **Codeception** is the primary testing framework for this project
- Use **Unit tests** for testing individual classes and methods
- Use **Functional tests** for integration testing with Symfony and database
- Use **Acceptance tests** for end-to-end HTTP API testing
- Leverage Codeception's BDD-style syntax for readable test scenarios
- Take advantage of built-in Symfony integration and automatic database cleanup