# Technology Stack

## Framework & Core
- **Symfony 7.3** - Main PHP framework using MicroKernelTrait
- **PHP 8.2+** - Minimum required version
- **Doctrine ORM 3.5** - Database abstraction and entity management
- **PostgreSQL** - Primary database (Docker containerized)

## Key Dependencies
- **Doctrine Migrations** - Database schema versioning
- **Symfony Security** - Authentication and authorization
- **Symfony Messenger** - Async message handling
- **Monolog** - Logging framework
- **Twig** - Template engine
- **Symfony UX (Turbo/Stimulus)** - Frontend enhancement

## Development Tools
- **Codeception** - Primary testing framework (Unit, Functional, Acceptance)
- **Symfony Maker Bundle** - Code generation
- **Web Profiler** - Development debugging
- **Docker Compose** - Local development environment

## Common Commands

### Development Setup
```bash
# Start development environment
docker-compose up -d

# Install dependencies
composer install

# Run database migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear
```

### Testing
```bash
# Run all tests
composer test
# or
vendor/bin/codecept run

# Run tests with coverage
composer test-coverage
# or
vendor/bin/codecept run --coverage --coverage-html

# Run specific test suites
composer test-unit          # Unit tests only
composer test-functional    # Functional tests only
composer test-acceptance    # Acceptance tests only

# Build Codeception actors
vendor/bin/codecept build
```

### Database Operations
```bash
# Create migration
php bin/console make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Load fixtures (if available)
php bin/console doctrine:fixtures:load
```

### Code Generation
```bash
# Generate entity
php bin/console make:entity

# Generate controller
php bin/console make:controller

# Generate form
php bin/console make:form
```

## Build & Deployment
- Uses Symfony Flex for package management
- Asset Mapper for frontend asset handling
- Auto-scripts for post-install/update hooks
- Docker-ready configuration included