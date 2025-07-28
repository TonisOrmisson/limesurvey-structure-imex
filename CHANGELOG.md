# Changelog

## [Unreleased]

### Added
- Clear survey contents checkbox for question imports - allows clearing all existing groups, questions, and quotas before importing
- 100% question attribute coverage - ALL LimeSurvey question attributes are now supported
- Universal attributes system - 26 common attributes available to all question types
- Support for equation, showpopups, and exclude_all_others attributes

### Changed
- Quota import now allowed on active surveys - removed unnecessary restriction

### Fixed
- M (Multiple Choice) questions no longer export duplicate answer options - only exports subquestions as intended
- Cyrillic characters in question attributes now export properly without Unicode escaping
- Added missing array filter attributes (array_filter, array_filter_style, array_filter_exclude) for M questions
- Added support for script field "Use for all languages" functionality (same_script field)
- Added comprehensive main table columns reference to help sheet with proper formatting

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