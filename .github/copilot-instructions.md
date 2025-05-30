# Copilot Instructions for StructureImEx LimeSurvey Plugin

## Project Context
You are working on the **StructureImEx** plugin for LimeSurvey - a PHP plugin that enables importing and exporting survey question structures and relevance logic via Excel files. This plugin is compatible with LimeSurvey versions 3.x through 6.x.

## Development Environment
- **PHP Version**: >=8.0.4 with ext-json
- **Framework**: LimeSurvey plugin system extending `PluginBase`
- **Namespace**: `tonisormisson\ls\structureimex`
- **Autoloading**: PSR-4 from `src/` directory
- **License**: MIT
- **Dependencies**: OpenSpout for Excel handling (^4.0), LimeSurvey core APIs

## Architecture Overview

### Core Plugin Structure
- **Main Entry**: `StructureImEx.php` (wrapper loader)
- **Core Implementation**: `src/StructureImEx.php` extends `PluginBase`
- **Namespace Root**: `tonisormisson\ls\structureimex`
- **Source Directory**: All classes in `src/` with PSR-4 autoloading

### Key Component Classes
1. **Export System** (`tonisormisson\ls\structureimex\export`):
   - `AbstractExport` - Base class using OpenSpout for Excel generation
   - `ExportQuestions` - Exports survey question structure
   - `ExportRelevances` - Exports relevance equations

2. **Import System** (`tonisormisson\ls\structureimex\import`):
   - `ImportFromFile` - Abstract base for file-based imports
   - `ImportStructure` / `ImportStructureV4Plus` - Version-specific question imports
   - `ImportRelevance` - Relevance equation imports

3. **Validation System** (`tonisormisson\ls\structureimex\validation`):
   - `QuestionAttributeValidator` - Validates question attributes using LimeSurvey's core system
   - `MyQuestionAttribute` - Legacy attribute validation model

4. **Plugin Integration**:
   - Event subscriptions: `beforeToolsMenuRender`, `beforeSurveySettings`, `newSurveySettings`
   - Menu integration via LimeSurvey's tools menu
   - Survey-specific settings storage

## Coding Guidelines

### File Organization
- All source code in `src/` directory with PSR-4 autoloading organized by functionality:
  - `src/export/` - Export functionality (`AbstractExport`, `ExportQuestions`, `ExportRelevances`)
  - `src/import/` - Import functionality (`ImportFromFile`, `ImportStructure`, `ImportStructureV4Plus`, `ImportRelevance`)
  - `src/validation/` - Validation classes (`QuestionAttributeValidator`, `MyQuestionAttribute`)
  - `src/exceptions/` - Custom exception classes
  - `src/views/` - Plugin view templates
- Use abstract base classes for extensibility (`AbstractExport`, `ImportFromFile`)
- Implement trait-based shared functionality (`AppTrait`)
- Follow LimeSurvey plugin lifecycle with proper event subscriptions

### Architecture Patterns
- **Inheritance**: Extend `PluginBase` for main plugin class
- **Abstraction**: Use abstract base classes for export/import functionality
- **Exception Handling**: Use `ImexException` base class for structured error handling
- **Validation**: Leverage LimeSurvey's `QuestionAttributeFetcher` system
- **File Processing**: Use OpenSpout library for Excel file handling

### LimeSurvey Integration Points
- **Plugin Events**: Subscribe to LimeSurvey events for menu and settings integration
- **Survey State Validation**: Prevent structure import on active surveys
- **Question Attributes**: Use `QuestionAttributeValidator` for dynamic validation
- **Version Detection**: Auto-detect LimeSurvey v4+ vs older versions
- **Multi-language Support**: Handle language detection and survey translations

### File Processing Architecture
- **Excel Handling**: Use OpenSpout Reader/Writer for Excel files
- **Upload Security**: Use `CUploadedFile` for secure file handling
- **Download Streaming**: Direct file streaming with proper headers and cleanup
- **Error Handling**: Structured exception system with custom hierarchy

### Plugin Actions & Routing
- **Available Actions**: `questions`, `relevances`, `export` with type parameter
- **URL Generation**: Use `admin/pluginhelper` with method routing
- **File Operations**: Handle upload/download with proper validation

## Development Commands

### Setup & Dependencies
```bash
# Install dependencies
composer install

# Development install (includes phpstan)
composer install --dev

# Production install
composer install --no-dev && composer dump-autoload
```

### Code Quality & Analysis
```bash
# Run PHPStan static analysis (level 5)
./vendor/bin/phpstan analyse src

# PHPStan with baseline (allows existing issues)
./vendor/bin/phpstan analyse -c phpstan.neon
```

### Plugin Installation in LimeSurvey
```bash
# Clone to LimeSurvey plugins directory
cd /LimeSurveyFolder/plugins
git clone https://github.com/TonisOrmisson/limesurvey-structure-imex.git StructureImEx
cd StructureImEx && composer install
```

## Key Implementation Notes

### Question Attribute Validation
- Use `QuestionAttributeValidator` that leverages LimeSurvey's core system
- Implement dynamic validation that queries LimeSurvey for allowed attributes per question type
- Support type-aware validation (integer, select, boolean, etc.)
- Handle unknown attributes configurably for plugin/theme-specific attributes

### Version Compatibility
- Support LimeSurvey versions 3.x through 6.x
- Implement version-specific classes (`ImportStructure` vs `ImportStructureV4Plus`)
- Use automatic version detection for appropriate class selection

### Error Handling
- Use custom exception hierarchy with `ImexException` as base
- Implement model-based validation using CModel inheritance
- Provide structured error messages for user feedback

### Security Considerations
- Validate survey state before allowing structure modifications
- Use secure file upload handling with proper validation
- Implement proper cleanup of temporary files
- Validate imported data against LimeSurvey's validation rules

## Copilot Suggestions

When working on this codebase:
1. **Follow PSR-4 autoloading** - Place new classes in `src/` with proper namespace
2. **Extend appropriate base classes** - Use `AbstractExport`, `ImportFromFile`, or `PluginBase`
3. **Use LimeSurvey APIs** - Leverage existing LimeSurvey validation and data access patterns
4. **Handle exceptions properly** - Use `ImexException` hierarchy for error handling
5. **Consider version compatibility** - Test against different LimeSurvey versions
6. **Validate user input** - Use LimeSurvey's validation systems for data integrity
7. **Follow plugin patterns** - Use proper event subscriptions and plugin lifecycle methods
