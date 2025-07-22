# MockSurveyHelper Documentation

## Overview

The `MockSurveyHelper` class provides reusable mock data for testing the StructureImEx plugin. It creates realistic survey data structures that mimic LimeSurvey's data models without requiring a database connection.

## Key Features

- **Complete Mock Data**: Creates surveys, groups, questions, and attributes
- **Multilingual Support**: Supports multiple language versions (English & German by default)
- **Flexible Survey IDs**: Allows custom survey IDs for test isolation
- **Simple & Complex Variants**: Provides both simple and comprehensive mock data
- **Global Data Setting**: Utility to set global variables for testable classes

## Usage Examples

### 1. Basic Usage - Create Complete Mock Survey

```php
use tonisormisson\ls\structureimex\Tests\MockSurveyHelper;

// Create full mock survey data with default ID (123456)
$mockData = MockSurveyHelper::createMockSurveyData();

// Access components
$survey = $mockData['survey'];      // Mock survey object
$groups = $mockData['groups'];      // 2 question groups
$questions = $mockData['questions']; // 3 questions (Text, List, Numerical)
$attributes = $mockData['attributes']; // 4 question attributes
$languages = $mockData['languages']; // ['en', 'de']
```

### 2. Custom Survey ID

```php
// Create mock data with custom survey ID
$customSurveyId = 999999;
$mockData = MockSurveyHelper::createMockSurveyData($customSurveyId);

// All objects will have the custom survey ID
assert($mockData['survey']->sid === $customSurveyId);
```

### 3. Simple Mock Survey

```php
// Create minimal mock data for basic tests
$simpleMockData = MockSurveyHelper::createSimpleMockSurvey();

// Contains: 1 group, 1 question, no attributes, English only
assert(count($simpleMockData['groups']) === 1);
assert(count($simpleMockData['questions']) === 1);
assert(count($simpleMockData['attributes']) === 0);
assert($simpleMockData['languages'] === ['en']);
```

### 4. Setting Global Mock Data

```php
// For classes that use global variables (like TestableExportQuestions)
$mockData = MockSurveyHelper::createMockSurveyData();
MockSurveyHelper::setGlobalMockData($mockData);

// This sets global variables: $mockGroups, $mockQuestions, $mockAttributes
// Which can be accessed by test classes that override database calls
```

### 5. Creating Mock Plugin

```php
// Create mock plugin instance
$mockData = MockSurveyHelper::createMockSurveyData();
$mockPlugin = MockSurveyHelper::createMockPlugin($mockData['survey']);

// Use in tests that need a plugin instance
$mockPlugin->getSurvey(); // Returns the mock survey
```

## Mock Data Structure

### Survey Object
- **ID**: 123456 (default) or custom
- **Status**: Inactive ('N')
- **Languages**: English ('en') and German ('de')
- **Methods**: `getPrimaryKey()`, `getAllLanguages()`

### Question Groups (2 groups)
1. **Demographics** (ID: 1)
   - English: "Demographics" / "Basic demographic questions"  
   - German: "Demografie" / "Grundlegende demografische Fragen"

2. **Preferences** (ID: 2)
   - English: "Preferences" / "User preference questions"
   - German: "Präferenzen" / "Benutzerpräferenz Fragen"

### Questions (3 questions)

1. **Q001 - Text Question** (ID: 1, Group: 1, Type: 'T')
   - English: "What is your name?" / "Please enter your full name"
   - German: "Wie ist Ihr Name?" / "Bitte geben Sie Ihren vollständigen Namen ein"
   - Mandatory: No

2. **Q002 - Multiple Choice** (ID: 2, Group: 1, Type: 'L')  
   - English: "What is your age group?" / "Select your age range"
   - German: "Wie alt sind Sie?" / "Wählen Sie Ihre Altersgruppe"
   - Mandatory: Yes
   - Has JavaScript and attributes

3. **Q003 - Numerical** (ID: 3, Group: 2, Type: 'N')
   - English: "How many years of experience do you have?" / "Enter a number between 0 and 50"
   - German: "Wie viele Jahre Erfahrung haben Sie?" / "Geben Sie eine Zahl zwischen 0 und 50 ein" 
   - Mandatory: No
   - Relevance: `Q002.NAOK == "A3"`

### Question Attributes (4 attributes)

- **Q002 Attributes**:
  - `random_order`: '1'
  - `other_option`: 'Y'

- **Q003 Attributes**:  
  - `min_answers`: '0'
  - `max_answers`: '50'

## Test Implementation Example

```php
namespace tonisormisson\ls\structureimex\Tests;

use PHPUnit\Framework\TestCase;

class MyExportTest extends TestCase 
{
    private $mockData;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock data
        $this->mockData = MockSurveyHelper::createMockSurveyData();
        
        // Set global data for testable classes
        MockSurveyHelper::setGlobalMockData($this->mockData);
    }
    
    public function testMyExportFunction()
    {
        // Use mock data in your test
        $exporter = new MyTestableExporter($this->mockData['survey'], $this->mockData['languages']);
        
        // Test export functionality...
        $this->assertTrue($exporter->export());
    }
}
```

## Benefits

1. **No Database Dependency**: Tests run without needing LimeSurvey database
2. **Consistent Test Data**: Reproducible mock data across test runs  
3. **Multilingual Testing**: Built-in support for testing translations
4. **Realistic Data**: Mock objects mirror real LimeSurvey structure
5. **Flexible Complexity**: Choose simple or complex mock data as needed
6. **Reusable**: One helper for all plugin tests
7. **Easy Maintenance**: Centralized mock data management

## Integration with Existing Tests

The helper is designed to work alongside existing test structures. You can:

- Refactor existing tests to use the helper
- Create new tests using the helper  
- Mix helper-based and custom mock data as needed
- Gradually migrate test suites to use centralized mock data

## Files

- **MockSurveyHelper.php**: Main helper class
- **ExportQuestionsTest.php**: Original comprehensive test  
- **MockSurveyHelperExampleTest.php**: Usage examples
- **bootstrap.php**: Mock LimeSurvey class definitions
