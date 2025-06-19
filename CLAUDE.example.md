# CLAUDE.example.md

This is a generic example of Claude Code guidance for this project. Copy this to CLAUDE.md and customize for your environment.

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