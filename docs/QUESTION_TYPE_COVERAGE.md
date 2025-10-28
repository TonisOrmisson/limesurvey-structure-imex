# Question Type Coverage Documentation

This document summarizes automated coverage for LimeSurvey question types exercised by the StructureImEx functional export tests.

## Coverage Summary

**Total Question Types Covered: 29/29 (100%)**  
**Total Test Cases: 129**  
**Test Status: All passing ✅**

## Question Types and Tested Attributes

### L - List Radio (Dropdown)

**Test Cases: 7**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `answer_order` ('normal' → 'random')
  - `assessment_value` (0 → 1)
  - `scale_export` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'List validation tip')
  - `other_replace_text` ('' → 'Other option text')

### T - Long Free Text

**Test Cases: 6**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `maximum_chars` ('' → 500)
  - `text_input_width` ('' → 200)
  - `display_rows` (5 → 10)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Long text validation tip')

### N - Numerical Input

**Test Cases: 6**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `num_value_int_only` (0 → 1)
  - `min_num_value_n` ('' → 1)
  - `max_num_value_n` ('' → 100)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Number validation tip')

### M - Multiple Choice

**Test Cases: 7**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `min_answers` ('' → 2)
  - `max_answers` ('' → 5)
  - `answer_order` ('normal' → 'random')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Multiple choice validation tip')
  - `other_replace_text` ('' → 'Other choice text')

### S - Short Free Text

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `text_input_width` ('' → 100)
  - `maximum_chars` ('' → 50)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Short text validation tip')

### ! - List Dropdown

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `answer_order` ('normal' → 'random')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Dropdown validation tip')
  - `other_replace_text` ('' → 'Dropdown other text')

### F - Array (Flexible Labels)

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
  - `array_filter_style` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Array validation tip')

### Q - Multiple Short Text

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `text_input_columns` ('' → 6)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Multi short text validation tip')

### K - Multiple Numerical Input

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `num_value_int_only` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Multi numerical validation tip')

### X - Text Display

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `cssclass` ('' → 'display-class')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Text display validation tip')

### Y - Yes/No Radio

**Test Cases: 3**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Yes/No validation tip')

### G - Gender

**Test Cases: 3**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Gender validation tip')

### 1 - Array Dual Scale

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Array dual validation tip')

### 5 - 5 Point Choice

**Test Cases: 3**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → '5 point validation tip')

### D - Date

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `date_format` ('' → 'dd/mm/yyyy')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Date validation tip')

### A - Array 5 Point Choice

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → '5 point array validation tip')

### B - Array 10 Choice Questions

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → '10 choice array validation tip')

### C - Array Yes/Uncertain/No

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Yes/Uncertain/No array validation tip')

### E - Array Increase/Same/Decrease

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Inc/Same/Dec array validation tip')

### H - Array by Column

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Array column validation tip')

### I - Language Switch

**Test Cases: 3**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Language validation tip')

### O - List with Comment

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `answer_order` ('normal' → 'random')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'List with comment validation tip')
  - `other_replace_text` ('' → 'Comment field text')

### P - Multiple Choice with Comments

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `min_answers` ('' → 2)
  - `max_answers` ('' → 5)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Multiple choice with comments validation tip')

### R - Ranking

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `min_answers` ('' → 2)
  - `max_answers` ('' → 5)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Ranking validation tip')

### U - Huge Free Text

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `maximum_chars` ('' → 1000)
  - `display_rows` (10 → 20)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Huge text validation tip')

### | - File Upload

**Test Cases: 5**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `max_filesize` ('' → 2048)
  - `allowed_filetypes` ('' → 'pdf,doc,docx')
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'File upload validation tip')

### * - Equation

**Test Cases: 3**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Equation validation tip')

### : - Array Numbers

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Array numbers validation tip')

### ; - Array Text

**Test Cases: 4**

- **Global Attributes:**
  - `hidden` (0 → 1)
  - `hide_tip` (0 → 1)
  - `random_order` (0 → 1)
- **Language-Specific Attributes:**
  - `em_validation_q_tip` ('' → 'Array text validation tip')

## Attribute Categories

- **Display / Layout:** `hidden`, `hide_tip`, `cssclass`, `random_order`, `answer_order`, `array_filter_style`, `scale_export`, `assessment_value`
- **Text Inputs:** `maximum_chars`, `text_input_width`, `text_input_columns`, `display_rows`
- **Numerical Validation:** `num_value_int_only`, `min_num_value_n`, `max_num_value_n`
- **Choice Constraints:** `min_answers`, `max_answers`
- **File Handling:** `max_filesize`, `allowed_filetypes`
- **Language-Specific Overrides:** `em_validation_q_tip`, `other_replace_text`
- **Date Formatting:** `date_format`

## Testing Strategy

- Functional coverage lives in `tests/functional/export/ComprehensiveAttributeExportTest.php` via a data provider that enumerates every question type and attribute permutation.
- Each data row flips a single attribute from its default to a non-default value and asserts that the export payload captures the change.
- Language-specific attributes are populated for both English (`en`) and Estonian (`et`) to exercise multi-language export columns.
- Global attributes verify JSON payloads produced in the options column while language attributes verify per-language JSON blobs.
- Question codes are normalized for special characters so types such as `*`, `:`, `;`, `|`, and `!` generate valid survey entities before export.

## Quality Metrics

- 100% of supported LimeSurvey question types (29/29) are exercised.
- 129 attribute assertions provide regression coverage across display, validation, choice, file, and localization behaviors.
- Multi-language scenarios cover English and Estonian translations for every language-scoped attribute.

## Maintenance Notes

- Update `QuestionAttributeDefinition.php` together with the data provider when adding new attributes or question types.
- Extend the data provider with additional rows for each new attribute you introduce, keeping defaults in sync with LimeSurvey core config.
- Refresh this document after modifying tests so counts and attribute lists remain accurate.
