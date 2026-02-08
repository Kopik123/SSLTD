# Contributing to S&S LTD

First off, thank you for considering contributing to S&S LTD! It's people like you that make this project better.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Enhancements](#suggesting-enhancements)
  - [Pull Requests](#pull-requests)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Commit Message Guidelines](#commit-message-guidelines)

## Code of Conduct

This project and everyone participating in it is governed by a professional code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

**Template for Bug Reports:**

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. See error

**Expected behavior**
What you expected to happen.

**Screenshots**
If applicable, add screenshots.

**Environment:**
 - OS: [e.g. Windows, Ubuntu]
 - PHP Version: [e.g. 8.1.2]
 - Database: [e.g. MySQL 8.0]
 - Browser (if applicable): [e.g. Chrome 98]

**Additional context**
Any other context about the problem.
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title**
- **Provide a step-by-step description** of the suggested enhancement
- **Explain why this enhancement would be useful** to most users
- **List some examples** where this enhancement would be used

### Pull Requests

1. **Fork the repository** and create your branch from `develop`
2. **Make your changes** following our [coding standards](#coding-standards)
3. **Add tests** for any new functionality
4. **Ensure all tests pass**: `vendor/bin/phpunit`
5. **Run the linter**: `php bin/php_lint.php`
6. **Update documentation** as needed
7. **Write a clear commit message** following our [guidelines](#commit-message-guidelines)
8. **Submit a pull request** to the `develop` branch

**Pull Request Template:**

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Manual testing performed
- [ ] All tests pass locally

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my code
- [ ] I have commented my code where necessary
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
```

## Development Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Kopik123/SSLTD.git
   cd SSLTD
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   # Edit .env with your local configuration
   ```

4. **Initialize database**:
   ```bash
   php bin/migrate.php
   php bin/seed.php
   ```

5. **Run local server**:
   ```bash
   php -S 127.0.0.1:8000 index.php
   ```

6. **Run tests**:
   ```bash
   vendor/bin/phpunit
   ```

## Coding Standards

### PHP Standards

- Follow **PSR-12** coding style standard
- Use **type declarations** wherever possible (PHP 8.0+)
- Prefer **dependency injection** over global state
- Keep methods **small and focused** (single responsibility)
- Use **meaningful variable and function names**

### File Organization

```
src/
├── Controllers/     # HTTP request handlers
├── Models/          # Domain models
├── Middleware/      # HTTP middleware
├── Database/        # Database layer
├── Support/         # Helper classes
└── ...
```

### Naming Conventions

- **Classes**: PascalCase (e.g., `UserController`, `ProjectModel`)
- **Methods**: camelCase (e.g., `getUserById`, `validateInput`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MAX_UPLOAD_SIZE`)
- **Database tables**: snake_case (e.g., `user_projects`, `project_leads`)

### Documentation

- Add **PHPDoc blocks** for all classes and public methods
- Include **@param** and **@return** annotations
- Describe **complex logic** with inline comments
- Update **README.md** for user-facing changes
- Update **API documentation** for endpoint changes

Example:
```php
/**
 * Retrieve a user by their ID
 *
 * @param int $userId The user's unique identifier
 * @return array|null User data or null if not found
 * @throws DatabaseException If database query fails
 */
public function getUserById(int $userId): ?array
{
    // Implementation
}
```

## Testing Guidelines

### Writing Tests

- Place unit tests in `tests/Unit/`
- Place integration tests in `tests/Integration/`
- Use descriptive test method names: `testUserCanBeCreatedWithValidData()`
- Follow **Arrange-Act-Assert** pattern
- Mock external dependencies
- Test both **happy paths** and **error cases**

### Test Coverage

- Aim for **80%+ code coverage** on new code
- **All new features** must include tests
- **Bug fixes** should include a test that would have caught the bug

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Unit

# Run with coverage (requires xdebug)
vendor/bin/phpunit --coverage-html coverage/
```

## Commit Message Guidelines

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, missing semi-colons, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks (dependencies, build, etc.)

### Examples

```
feat(auth): add two-factor authentication

Implement 2FA using TOTP algorithm. Users can enable 2FA
in their profile settings.

Closes #123
```

```
fix(upload): prevent directory traversal vulnerability

Sanitize file paths to prevent attackers from writing files
outside the upload directory.

Security: CVE-2024-XXXXX
```

### Guidelines

- Use **present tense** ("add feature" not "added feature")
- Use **imperative mood** ("move cursor to..." not "moves cursor to...")
- Keep subject line **under 72 characters**
- Reference issues and pull requests where appropriate
- Explain **what and why** in the body, not how

## Questions?

Feel free to open an issue with the `question` label or contact the maintainers directly.

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.

---

**Thank you for contributing to S&S LTD!** 🎉
