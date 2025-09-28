# Testing Guide

This project uses both **PHPUnit** and **Codeception** for comprehensive testing coverage. This guide explains when and how to use each testing framework.

## Overview

- **PHPUnit**: Used for pure unit tests in the `tests/Unit/` directory
- **Codeception**: Used for unit, functional, and acceptance tests in the `tests/Codeception/` directory

## Quick Start

### Run All Tests
```bash
# PHPUnit tests
composer test

# Codeception tests
vendor/bin/codecept run

# Run both
composer test && vendor/bin/codecept run
```

### Run Tests with Coverage
```bash
# PHPUnit with HTML coverage report
composer test-coverage-html

# Codeception with coverage
vendor/bin/codecept run --coverage --coverage-html
```

## PHPUnit Testing

### Configuration
- **Config file**: `phpunit.xml`
- **Test directory**: `tests/Unit/`
- **Bootstrap**: `tests/bootstrap.php`
- **Namespace**: `App\Tests\`

### Running PHPUnit Tests

```bash
# Run all PHPUnit tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/Services/DepositServiceTest.php

# Run tests with coverage (requires Xdebug)
XDEBUG_MODE=coverage php bin/phpunit --coverage-html=coverage

# Run tests with text coverage
XDEBUG_MODE=coverage php bin/phpunit --coverage-text

# Using composer scripts
composer test                    # Run tests
composer test-coverage          # Run with coverage report
composer test-coverage-html     # Generate HTML coverage report
```

### PHPUnit Test Structure

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MyService;

final class MyServiceTest extends TestCase
{
    private MyService $service;

    protected function setUp(): void
    {
        $this->service = new MyService();
    }

    public function testSomething(): void
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = $this->service->doSomething($input);
        
        // Assert
        $this->assertSame('expected', $result);
    }
}
```

### When to Use PHPUnit
- Pure unit tests with minimal dependencies
- Testing individual classes in isolation
- When you need advanced mocking capabilities
- Performance-critical test suites

## Codeception Testing

### Configuration
- **Config file**: `codeception.yml`
- **Test directory**: `tests/Codeception/`
- **Namespace**: `App\Tests\Codeception`

### Test Suites

#### 1. Unit Tests (`tests/Codeception/Unit/`)
- **Actor**: `UnitTester`
- **Modules**: Asserts
- **Purpose**: Unit testing with Codeception's BDD-style syntax

#### 2. Functional Tests (`tests/Codeception/Functional/`)
- **Actor**: `FunctionalTester`
- **Modules**: Asserts, Symfony, Doctrine
- **Purpose**: Testing application functionality with Symfony integration

#### 3. Acceptance Tests (`tests/Codeception/Acceptance/`)
- **Actor**: `AcceptanceTester`
- **Modules**: PhpBrowser
- **Purpose**: End-to-end testing through HTTP requests

### Running Codeception Tests

```bash
# Run all Codeception tests
vendor/bin/codecept run

# Run specific suite
vendor/bin/codecept run Unit
vendor/bin/codecept run Functional
vendor/bin/codecept run Acceptance

# Run specific test
vendor/bin/codecept run Unit Generator/ReelGeneratorTest

# Run with coverage
vendor/bin/codecept run --coverage --coverage-html

# Run with debug output
vendor/bin/codecept run --debug

# Run tests in parallel (if configured)
vendor/bin/codecept run --ext DotReporter
```

### Codeception Test Structure

#### Unit Test Example
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

#### Functional Test Example
```php
<?php

namespace App\Tests\Codeception\Functional;

use App\Tests\Codeception\Support\FunctionalTester;

class GameCest
{
    public function testCreateGame(FunctionalTester $I): void
    {
        $I->amOnPage('/api/games');
        $I->sendPost('/api/games', [
            'name' => 'Test Game',
            'type' => 'slot'
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeInDatabase('game', ['name' => 'Test Game']);
    }
}
```

#### Acceptance Test Example
```php
<?php

namespace App\Tests\Codeception\Acceptance;

use App\Tests\Codeception\Support\AcceptanceTester;

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

### When to Use Codeception
- BDD-style testing with readable scenarios
- Integration testing with Symfony framework
- Database testing with automatic cleanup
- Acceptance testing through HTTP
- When you need built-in Symfony integration

## Test Organization

### Directory Structure
```
tests/
├── Unit/                          # PHPUnit tests
│   ├── Services/
│   └── .gitignore
├── Codeception/
│   ├── Unit/                      # Codeception unit tests
│   │   ├── Generator/
│   │   └── Services/
│   ├── Functional/                # Integration tests
│   ├── Acceptance/                # End-to-end tests
│   ├── Support/                   # Test helpers and actors
│   │   ├── Helper/
│   │   ├── Data/
│   │   ├── AcceptanceTester.php
│   │   ├── FunctionalTester.php
│   │   └── UnitTester.php
│   └── _output/                   # Test output and coverage
└── bootstrap.php
```

### Naming Conventions
- **PHPUnit**: `{ClassName}Test.php`
- **Codeception Unit**: `{ClassName}Test.php`
- **Codeception Functional/Acceptance**: `{Feature}Cest.php`

## Coverage Reports

### Generating Coverage Reports

```bash
# PHPUnit HTML coverage
XDEBUG_MODE=coverage php bin/phpunit --coverage-html=coverage

# Codeception HTML coverage
vendor/bin/codecept run --coverage --coverage-html

# Combined coverage (run both)
composer test-coverage-html && vendor/bin/codecept run --coverage --coverage-html
```

### Coverage Thresholds
- **Minimum**: 50% (configured in codeception.yml)
- **Target**: 90% (configured in codeception.yml)

## Database Testing

### PHPUnit with Database
```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        // Manual database setup required
    }
}
```

### Codeception with Database (Recommended)
```php
// Automatic database cleanup between tests
public function testDatabaseOperation(FunctionalTester $I): void
{
    $I->haveInDatabase('user', ['name' => 'Test User']);
    $I->seeInDatabase('user', ['name' => 'Test User']);
}
```

## Mocking and Fixtures

### PHPUnit Mocking
```php
$mock = $this->createMock(ServiceInterface::class);
$mock->expects($this->once())
     ->method('process')
     ->willReturn('result');
```

### Codeception with Doctrine Fixtures
```php
// In functional tests, fixtures are loaded automatically
$I->haveInDatabase('game', [
    'name' => 'Test Game',
    'type' => 'slot'
]);
```

## Continuous Integration

### GitHub Actions Example
```yaml
- name: Run PHPUnit Tests
  run: composer test

- name: Run Codeception Tests
  run: vendor/bin/codecept run

- name: Generate Coverage
  run: |
    XDEBUG_MODE=coverage composer test-coverage
    vendor/bin/codecept run --coverage
```

## Best Practices

### General
1. **Test Naming**: Use descriptive test method names
2. **AAA Pattern**: Arrange, Act, Assert
3. **One Assertion**: Focus on one behavior per test
4. **Test Data**: Use meaningful test data, not just dummy values

### PHPUnit Specific
1. Use `setUp()` and `tearDown()` for test preparation
2. Mock external dependencies
3. Use data providers for testing multiple scenarios
4. Prefer `assertSame()` over `assertEquals()` for strict comparison

### Codeception Specific
1. Use descriptive scenario names in Cest files
2. Leverage built-in Symfony integration for functional tests
3. Use database cleanup features for integration tests
4. Write acceptance tests from user perspective

## Debugging Tests

### PHPUnit Debugging
```bash
# Run with verbose output
php bin/phpunit --verbose

# Stop on first failure
php bin/phpunit --stop-on-failure

# Filter specific test
php bin/phpunit --filter testMethodName
```

### Codeception Debugging
```bash
# Debug mode
vendor/bin/codecept run --debug

# Step-by-step execution
vendor/bin/codecept run --steps

# HTML report
vendor/bin/codecept run --html
```

## Performance

### Running Tests Faster
```bash
# PHPUnit parallel execution (if configured)
php bin/phpunit --parallel

# Codeception with specific groups
vendor/bin/codecept run --group fast

# Skip slow tests
vendor/bin/codecept run --skip slow
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

- Use **PHPUnit** for pure unit tests and when you need advanced mocking
- Use **Codeception** for integration tests, database testing, and BDD-style scenarios
- Both frameworks can coexist and complement each other
- Choose the right tool based on your testing needs and team preferences