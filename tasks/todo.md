# Project Tasks and TODOs

This file tracks pending tasks, issues, and planned improvements for the StructureImEx plugin.

## Active Tasks

### IMPORTANT: Take tasks one by one. If stuck, ASK FOR HELP!

### Question Type Coverage Expansion ⚠️ HIGH PRIORITY

**CURRENT STATE: Multi-language export fixed ✅ - Question type coverage MASSIVELY EXPANDED! 🎉**

### Phase 1: Add Missing Question Types ✅ COMPLETED!
**Goal: Get from 34.5% to 100% question type coverage with basic attributes**

**ACHIEVEMENT: 130 test cases now covering ALL 29 question types! 🚀**

**Successfully Added (ALL 29/29):**
- ✅ L (List Radio), T (Long Free Text), N (Numerical), M (Multiple Choice)
- ✅ S (Short Free Text), ! (List Dropdown), F (Array), Q (Multiple Short Text)  
- ✅ K (Multiple Numerical), X (Text Display), Y (Yes/No Radio), G (Gender)
- ✅ **1 (Array Dual Scale)** - Added basic attributes: hidden, hide_tip, em_validation_q_tip
- ✅ **5 (5 Point Choice)** - Added basic attributes: hidden, hide_tip, em_validation_q_tip
- ✅ **A (Array 5 Point)** - Added basic attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **B (Array 10 Choice)** - Added basic attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **C (Array Yes/Uncertain/No)** - Added basic attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **D (Date)** - Added specific attributes: hidden, hide_tip, date_format, em_validation_q_tip
- ✅ **E (Array Inc/Same/Dec)** - Added basic attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **H (Array Column)** - Added basic attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **I (Language)** - Added basic attributes: hidden, hide_tip, em_validation_q_tip
- ✅ **O (List with Comment)** - Added attributes: hidden, hide_tip, other_replace_text, em_validation_q_tip
- ✅ **P (Multiple Choice with Comments)** - Added attributes: hidden, hide_tip, min_answers, max_answers, em_validation_q_tip
- ✅ **R (Ranking)** - Added specific attributes: hidden, hide_tip, min_answers, max_answers, em_validation_q_tip
- ✅ **U (Huge Free Text)** - Added text attributes: hidden, hide_tip, maximum_chars, display_rows, em_validation_q_tip
- ✅ **| (File Upload)** - Added file attributes: hidden, hide_tip, max_filesize, allowed_filetypes, em_validation_q_tip
- ✅ **\* (Equation)** - Added equation attributes: hidden, hide_tip, em_validation_q_tip
- ✅ **: (Array Numbers)** - Added array attributes: hidden, hide_tip, answer_order, em_validation_q_tip
- ✅ **; (Array Text)** - Added array attributes: hidden, hide_tip, answer_order, em_validation_q_tip

**Technical Fixes Completed:**
- ✅ Fixed question creation qid NULL issue (alphanumeric validation)
- ✅ Fixed special character handling in question codes (*,:,;,|,!)
- ✅ Added proper Question type constants (QT_B_ARRAY_10_CHOICE_QUESTIONS, QT_VERTICAL_FILE_UPLOAD, etc.)

### Phase 2: Comprehensive Testing Strategy ✅ MAJOR PROGRESS!
20. ✅ **Update ComprehensiveAttributeExportTest.php** 
    - ✅ Added data provider entries for ALL 29 question types (130 test cases total!)
    - ✅ Test 2-4 representative attributes per question type  
    - ✅ Ensured both global and language-specific attributes are tested

21. ✅ **Fix question creation issues in comprehensive test**
    - ✅ Fixed qid => NULL issue (LimeSurvey question code validation)
    - ✅ Fixed alphanumeric-only requirement for question codes
    - ✅ All question types can now be created properly in test environment

22. ⚠️ **Comprehensive test execution status**
    - ✅ 130 test cases created covering all question types
    - ⚠️ Some test cases failing (need attribute definition updates)
    - ⚠️ Need to debug and fix failing assertions for complete success

### Phase 3: Validation and Integration  
23. ❌ **Update QuestionAttributeDefinition for all types**
    - Ensure all 29 question types have proper attribute definitions
    - Verify isValidAttribute() works for all types
    - Verify isNonDefaultValue() works for all types

24. ❌ **Integration testing**
    - Test complete export cycle for all question types
    - Verify multi-language attributes work for all types
    - Test that no question type breaks the export

### Phase 4: Documentation and Cleanup
25. ❌ **Update test documentation**
    - Document which attributes are tested for each question type
    - Create coverage report showing 100% question type coverage
    - Document any question types with special requirements

### Question Attribute System Redesign

#### Phase 1: Core Infrastructure ✅ COMPLETED
1. ✅ **Create QuestionAttributeDefinition class** 
   - Define interface for question type -> attributes mapping
   - Each attribute must have: name, default_value, validation_rules
   - Support all LimeSurvey question types (T, L, Z, O, M, F, Q, K, N, X, P, S, *, etc.)
   - Must be easily extensible for new question types
   - Location: `src/validation/QuestionAttributeDefinition.php`

2. ✅ **Define attributes for basic question types first**
   - Start with: T (Long free text), L (Dropdown), N (Numerical)
   - Research LimeSurvey core to find accurate default values
   - Each attribute definition must include exact default value from LimeSurvey core

3. ✅ **Create comprehensive test for QuestionAttributeDefinition**
   - Test that all question types return proper attribute definitions
   - Test that default values are correctly defined
   - Test extensibility (adding new question types)

#### Phase 2: Export Integration  
4. ✅ **Update ExportQuestions to use QuestionAttributeDefinition**
   - Replace current filtering logic with QuestionAttributeDefinition
   - Only export attributes defined for each question type
   - Only export attributes where value != default_value
   - Remove all hardcoded attribute lists

5. ❌ **Create export tests for each question type**
   - Test that only defined attributes are exported
   - Test that default values are NOT exported  
   - Test that non-default values ARE exported
   - Create test for each major question type (T, L, M, N minimum)

#### Phase 3: Import Integration
6. ❌ **Update ImportStructure to use QuestionAttributeDefinition**
   - Replace current validation with QuestionAttributeDefinition
   - Reject any attributes not defined for question type
   - Validate attribute values according to definition rules

7. ❌ **Create import tests for each question type**
   - Test that undefined attributes are rejected
   - Test that defined attributes are imported correctly
   - Test that default values work correctly
   - Test database changes are applied correctly

#### Phase 4: Complete All Question Types
8. ❌ **Complete all remaining LimeSurvey question types**
   - Add definitions for: Z, O, F, Q, K, X, P, S, *, etc.
   - Research and verify default values for each
   - Add tests for each question type

9. ❌ **Integration testing**
   - Test complete export -> import cycle for each question type
   - Test that attributes roundtrip correctly
   - Test error handling for invalid attributes

10. ❌ **Documentation and cleanup**
    - Document new QuestionAttributeDefinition system
    - Remove old validation code
    - Update README with new approach

### make functional tests work on github actions ✅ COMPLETED
- ✅ make sure the database defined in .env exists and is created (created limesurvey_vendor_test)
- ✅ make sure you can install the lime application from scratch in a new database defined in tests .env
  - Successfully installed LimeSurvey in vendor folder
  - Database: limesurvey_vendor_test (as defined in tests/.env)
- ✅ CHECK!! that the new installation contains the Lime tables (its actually installed)
  - SUCCESS: 59 LimeSurvey tables created including lime_plugins table
- ✅ The app is not required to run in web browser but it must be functional for us in tests & database access mode
- ✅ work out a way that the plugin can be installed for the app running in the vendor folder (added to GitHub Actions)
- ✅ make sure that the plugin is installed in the app running in the vendor folder. Check the database plugins table!!
  - Plugin files copied to vendor/limesurvey/limesurvey/upload/plugins/StructureImEx
  - Plugin dependencies installed (openspout/openspout)
  - Plugin successfully registered in database: ID: 18, Active: YES
  - Created simple-plugin-installer.php service to handle plugin registration
  - Verified: StructureImEx appears in lime_plugins table as active user plugin
- 
- ✅ try to run unit tests in the app running in the vendor folder
  - SUCCESS: All unit tests pass (1 test, 3 assertions)
  - Using vendor LimeSurvey installation with environment variables
- ✅ try to run functional tests in the app running in the vendor folder
  - SUCCESS: 20 out of 21 tests pass (150 assertions)
  - 1 failure: testQuestionLocalizationSystem (looking for 'lime_questions' table)
  - 1 risky: Debug output from DebugSurveyStructureTest (expected)
  - Overall: Functional tests work in vendor environment!
- ✅ make sure the functional & unit tests configuration suits the github actions infra. the .env.example file should be with the suitable conf to work on github actions
  - Added plugin registration step to GitHub Actions workflow
  - Updated .env.example to use limesurvey_vendor_test database
  - Ensured all environment variables match between local and CI
- ✅ review the github workflows. please keep only ONE workflow
  - Removed php.yml, static-analysis.yml, and tests.yml
  - Kept only test.yml which includes all jobs: unit-tests, functional-tests, and code-quality



## Completed Tasks

## Future Enhancements

## Known Issues

## Notes
