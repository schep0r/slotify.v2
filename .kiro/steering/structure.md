# Project Structure & Architecture

## Directory Organization

### Core Application (`src/`)
```
src/
├── Contracts/          # Interfaces defining system contracts
├── Entity/            # Doctrine entities (Game, User, Transaction, GameSession)
├── Repository/        # Doctrine repositories for data access
├── Controller/        # HTTP controllers (minimal, API-focused)
├── DTOs/             # Data Transfer Objects for structured data
├── Engines/          # Game engine implementations
├── Processors/       # Game logic processors (payouts, bonuses)
├── Strategies/       # Strategy pattern implementations
├── Managers/         # Business logic managers
├── Generators/       # Random number and reel generators
├── Validators/       # Input validation logic
├── Loggers/          # Game-specific logging
├── Exceptions/       # Custom exception classes
├── Enums/           # Enumeration classes
└── Providers/       # Service providers
```

## Architecture Patterns

### Contract-Based Design
- All major components implement interfaces from `src/Contracts/`
- Enables dependency injection and testing
- Key contracts: `GameEngineInterface`, `SpinStrategyInterface`, `PayoutCalculatorInterface`

### Strategy Pattern
- `SlotGameEngine` uses strategies for different spin types
- `BetSpinStrategy` vs `FreeSpinStrategy`
- Extensible for new game mechanics

### Domain-Driven Structure
- **Entities**: Core business objects (Game, User, Transaction)
- **DTOs**: Data containers for API responses (`GameResultDto`, `SlotGameDataDto`)
- **Processors**: Specialized logic handlers (`PayoutProcessor`, `JackpotProcessor`)
- **Managers**: Orchestrate business workflows

## Naming Conventions

### Classes
- **Entities**: Singular nouns (`Game`, `User`, `Transaction`)
- **Repositories**: `{Entity}Repository` (`GameRepository`)
- **Interfaces**: `{Purpose}Interface` (`GameEngineInterface`)
- **DTOs**: `{Purpose}Dto` (`GameResultDto`)
- **Exceptions**: `{Reason}Exception` (`InvalidBetException`)

### Methods
- **Entities**: Standard getters/setters (`getName()`, `setName()`)
- **Services**: Descriptive verbs (`processGame()`, `calculatePayout()`)
- **Validators**: `validate{Purpose}()` (`validateBet()`)

## Configuration Structure
```
config/
├── packages/         # Bundle-specific configuration
├── routes/          # Route definitions
├── services.yaml    # Service container configuration
└── bundles.php     # Bundle registration
```

## Key Architectural Rules

1. **Separation of Concerns**: Each processor handles one specific aspect
2. **Interface Segregation**: Small, focused interfaces
3. **Dependency Injection**: Constructor injection preferred
4. **Immutable DTOs**: Data transfer objects should be read-only
5. **Exception Handling**: Custom exceptions for domain-specific errors
6. **Transaction Safety**: Financial operations must be atomic

## File Organization Guidelines

- Group related functionality in dedicated folders
- Keep interfaces separate from implementations
- Use descriptive, domain-specific naming
- Maintain consistent namespace structure following PSR-4