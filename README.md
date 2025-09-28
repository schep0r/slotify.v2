# Slotify - Casino Slot Game Engine

A modern, dark-themed casino slot game application built with Symfony 7.3, featuring a sleek design with Tailwind CSS and light blue accents.

## Features

- 🎰 **Modern Dark Design** - Sleek dark theme with cyan/light blue accents
- 🎮 **User Registration & Authentication** - Secure user accounts with $100 welcome bonus
- 💰 **Balance Management** - Real-time balance tracking and transaction history
- 🎯 **Slot Game Engine** - Complete slot machine mechanics with configurable games
- 🔒 **Security** - Built-in CSRF protection and secure password hashing
- 📱 **Responsive Design** - Mobile-friendly interface using Tailwind CSS

## Quick Start

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Build CSS Assets**
   ```bash
   npm run build-css-prod
   ```

3. **Start the Server**
   ```bash
   symfony server:start
   ```

4. **Setup Database**
   Visit `http://127.0.0.1:8000/setup` to initialize the database

5. **Start Playing**
   - Go to `http://127.0.0.1:8000`
   - Click "Sign Up" to create an account
   - Get your $100 welcome bonus automatically!

## Technology Stack

- **Backend**: Symfony 7.3 with PHP 8.2+
- **Database**: SQLite (development) / PostgreSQL (production)
- **Frontend**: Tailwind CSS with custom dark theme
- **Security**: Symfony Security with custom authenticator
- **Forms**: Symfony Forms with custom styling

## Design Features

### Color Scheme
- **Primary**: Dark gray backgrounds (#1f2937, #374151)
- **Accent**: Cyan/Light blue (#06b6d4, #22d3ee)
- **Text**: White and light gray for contrast
- **Cards**: Dark gray with subtle borders

### Components
- **Buttons**: Rounded with hover effects and focus rings
- **Forms**: Dark inputs with cyan focus states
- **Navigation**: Clean header with user balance display
- **Alerts**: Color-coded success/error messages

## User Experience

### Registration Flow
1. User visits homepage with compelling hero section
2. Clicks "Sign Up" to access registration form
3. Fills out email, password, and agrees to terms
4. Receives $100 welcome bonus automatically
5. Gets logged in immediately after registration

### Authentication
- Secure login with remember me option
- CSRF protection on all forms
- Password hashing with Symfony's security component
- Automatic redirect to homepage after login

## Development

### CSS Development
```bash
# Watch for changes during development
npm run build-css

# Build for production
npm run build-css-prod
```

### Testing & Code Coverage

The project includes comprehensive testing with code coverage enforcement:

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# Run tests with HTML coverage report
composer test-coverage-html

# Run tests and check coverage threshold
composer check-coverage
```

#### Coverage Requirements
- **Minimum Coverage**: 6% (enforced in CI/CD)
- **Current Coverage**: ~6.4% (115/1793 lines)
- **Coverage Reports**: Generated in `coverage/` directory

#### GitHub Actions
The project uses GitHub Actions for continuous integration:
- Runs on PHP 8.2 with PostgreSQL 15
- Executes all tests with coverage reporting
- Enforces minimum coverage threshold
- Uploads coverage reports to Codecov
- Publishes test results as artifacts

#### Local Coverage Check
```bash
# Generate coverage and check threshold
XDEBUG_MODE=coverage php bin/phpunit --coverage-clover=coverage.xml
php bin/check-coverage
```

### Database Management
- Visit `/setup` to initialize database
- SQLite database stored in `var/data_dev.db`
- Includes sample "Classic Slots" game

### Adding New Games
Games can be added directly to the database with:
- Name, slug, and type
- Betting limits (min, max, step)
- RTP (Return to Player) percentage
- Reel configuration and paytables

## Security Features

- Password hashing with Symfony's auto hasher
- CSRF token protection on forms
- Secure session management
- Input validation and sanitization
- SQL injection prevention with Doctrine

## Future Enhancements

- Game lobby with multiple slot games
- Real-time gameplay with WebSocket support
- Progressive jackpots
- Tournament system
- Social features and leaderboards
- Mobile app integration

---

**Note**: This is a development setup. For production deployment, configure PostgreSQL database and proper environment variables.