# GitHub Actions CI/CD Setup

This document summarizes the GitHub Actions workflows configured for the LimeSurvey StructureImEx plugin.

## Workflows

### 1. Main CI Pipeline (`php.yml`)
- **Triggers**: Push/PR to main, master, develop branches
- **PHP Versions**: 8.0, 8.1, 8.2, 8.3, 8.4
- **Features**:
  - Composer validation and dependency installation
  - PHPUnit tests with coverage
  - PHPStan static analysis (continues on error)
  - Dependency audit

### 2. Dedicated Tests (`tests.yml`)
- **Focus**: Pure test execution across PHP versions
- **Features**:
  - Fast test execution with optimized caching
  - Clean test output with `--testdox` format
  - Fail-fast disabled for matrix testing

### 3. Static Analysis (`static-analysis.yml`)
- **Focus**: Code quality analysis
- **Features**:
  - PHPStan analysis on PHP 8.3
  - Continues on error (LimeSurvey integration warnings expected)
  - Optimized caching

## Status Badges

The README includes status badges for all workflows:

```markdown
[![Tests](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/tests.yml/badge.svg)](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/static-analysis.yml)
[![CI](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/php.yml/badge.svg)](https://github.com/TonisOrmisson/limesurvey-structure-imex/actions/workflows/php.yml)
```

## Local Development

Run the same commands locally:

```bash
# Install dependencies
composer install

# Run tests (same as CI)
vendor/bin/phpunit --testdox --colors=never

# Run static analysis (same as CI)
vendor/bin/phpstan analyse --no-progress
```

## Expected Warnings

The tests produce harmless PHP warnings about missing `PluginBase.php` and `MockEvent.php` files. These are expected because:

1. Our bootstrap properly mocks these classes
2. Yii's autoloader tries to find the files but our mock classes work correctly
3. All tests pass successfully with 28 assertions

## Test Coverage

Current test coverage includes:
- ✅ Export Questions functionality (28 assertions)
- ✅ Mock survey data creation and validation
- ✅ Multilingual content handling
- ✅ Question attributes and relevance logic
- ✅ Excel file generation and content validation

## Next Steps

When pushing to GitHub, the workflows will automatically:
1. Run tests across all supported PHP versions
2. Validate code quality with static analysis
3. Check for outdated dependencies
4. Generate coverage reports
5. Update status badges in the README
