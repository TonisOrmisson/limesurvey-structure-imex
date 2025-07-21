# Changelog

## [Unreleased]

### Added
- Clear survey contents checkbox for question imports - allows clearing all existing groups, questions, and quotas before importing

## [2.0.0] - 2025-06-19

### Added
- GitHub Actions CI/CD for PHP 8.1-8.3
- Complete attribute definitions for all question types
- Unit tests for QuestionAttributeDefinition

### Changed
- Limited PHP support to <8.4
- GitHub Actions uses MariaDB

### Fixed
- Unit test failures in QuestionAttributeDefinitionTest
- Risky test warning in ImportDebugTest
- PHP version constraints in composer files