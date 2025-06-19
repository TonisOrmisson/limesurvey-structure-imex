# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# StructureImEx LimeSurvey Plugin

## Project Overview
- **Type**: LimeSurvey v3-6 compatible plugin for importing/exporting survey structure and relevance logic
- **Framework**: PSR-4 autoloaded PHP classes extending LimeSurvey's PluginBase
- **Core Purpose**: Allow import/export of survey questions structure and relevance equations via Excel files
- **License**: MIT

## Development Commands

### Installation & Setup
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
./vendor/bin/phpstan

```

### Plugin Installation in LimeSurvey
```bash
# Clone to LimeSurvey plugins directory
cd /LimeSurveyFolder/plugins
git clone https://github.com/TonisOrmisson/limesurvey-structure-imex.git StructureImEx
cd StructureImEx && composer install
```

## Architecture Overview

### Core Plugin Structure
- **StructureImEx.php**: Wrapper loader that delegates to `src/StructureImEx.php`
- **Main Class**: `tonisormisson\ls\structureimex\StructureImEx` extends `PluginBase`
- **Namespace**: `tonisormisson\ls\structureimex` (PSR-4 autoloaded from `src/`)

### Key Components
1. **Export Classes**: 
   - `AbstractExport` - Base class using OpenSpout for Excel generation
   - `ExportQuestions` - Exports survey question structure
   - `ExportRelevances` - Exports relevance equations

2. **Import Classes**:
   - `ImportFromFile` - Abstract base for file-based imports
   - `ImportStructure` / `ImportStructureV4Plus` - Version-specific question imports
   - `ImportRelevance` - Relevance equation imports

3. **Validation Components**:
   - `QuestionAttributeValidator` - Validates question attributes using LimeSurvey's core system
   - `MyQuestionAttribute` - Legacy attribute validation model

4. **Plugin Integration**:
   - Event subscriptions: `beforeToolsMenuRender`, `beforeSurveySettings`, `newSurveySettings`
   - Menu integration via LimeSurvey's tools menu
   - Survey-specific settings storage

### File Processing Architecture
- **Reader/Writer**: Uses OpenSpout library for Excel file handling
- **Version Detection**: Automatic detection of LimeSurvey v4+ vs older versions
- **Error Handling**: Structured exception system with `ImexException` base class
- **Data Validation**: Model-based validation using CModel inheritance

### Question Attribute Validation
- **QuestionAttributeValidator**: Leverages LimeSurvey's `QuestionAttributeFetcher` system
- **Dynamic Validation**: Queries LimeSurvey core for allowed attributes per question type
- **Type-aware Validation**: Validates attribute values based on their input type (integer, select, boolean, etc.)
- **Unknown Attribute Handling**: Configurable import of plugin/theme-specific attributes
- **Integration**: Used by both `ImportStructure` and `ImportStructureV4Plus` classes

### Plugin Actions & Routing
- **Actions**: `questions`, `relevances`, `export` with type parameter
- **URLs**: Generated via `admin/pluginhelper` with method routing
- **File Upload**: Uses CUploadedFile for secure file handling
- **Download**: Direct file streaming with proper headers and cleanup

### Survey Integration Points
- **Survey State**: Prevents structure import on active surveys
- **Question Attributes**: Configurable import of unknown attributes
- **Relevance Logic**: Direct integration with LimeSurvey's expression system
- **Multi-language**: Language detection and handling for survey translations

## Key Dependencies
- **openspout/openspout**: Excel file reading/writing (^4.0)
- **PHP**: >=8.0.4 with ext-json
- **LimeSurvey**: Compatible with versions 3.x through 6.x

## Development Patterns
- All source code in `src/` directory with PSR-4 autoloading
- Abstract base classes for extensibility (`AbstractExport`, `ImportFromFile`)
- Trait-based shared functionality (`AppTrait`)
- Exception-based error handling with custom exception hierarchy
- LimeSurvey plugin lifecycle integration with proper event subscriptions

## Critical Development Rules

### Exception Handling
**NEVER EVER EVER catch a generic `\Exception`** - Always let exceptions bubble up and be handled appropriately by the calling code. Catching generic exceptions masks real problems and makes debugging impossible.

```php
// ❌ NEVER DO THIS
try {
    $result = someOperation();
} catch (\Exception $e) {
    // This masks all errors
    return null;
}

// ✅ DO THIS INSTEAD
try {
    $result = someOperation();
} catch (SpecificException $e) {
    // Handle specific known exceptions only
    throw new ImexException("Specific error context: " . $e->getMessage());
}
```

## Development Reminders
- **ALWAYS check and update CHANGELOG.md** when making significant changes to track development progress

## Logging
- log category is 'plugin.andmemasin.imex' 