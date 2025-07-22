# Quota Import/Export Format Design

## Overview
This document defines the Excel/CSV format for importing and exporting LimeSurvey quotas, including quota members (conditions) and multi-language settings.

## Database Structure Summary
- `lime_quota`: Core quota info (id, name, limit, action, active, autoload_url)
- `lime_quota_members`: Quota conditions (quota_id, qid, code)  
- `lime_quota_languagesettings`: Language-specific content (quota_id, language, name, message, url, url_description)

## Export Format Design

### Approach: Flattened Structure with Grouped Rows
Each row represents one quota member (condition) with quota and language info repeated.

### Column Structure

#### Core Quota Columns
1. **quota_name** - Quota display name (max 255 chars)
2. **quota_limit** - Maximum responses allowed (integer)
3. **quota_action** - Termination action (1-4):
   - 1 = Terminate after related visible question
   - 2 = Soft terminate after related visible question  
   - 3 = Terminate after visible and hidden questions
   - 4 = Terminate after all page submissions
4. **quota_active** - Whether quota is active (0/1)
5. **quota_autoload_url** - Auto-redirect on quota hit (0/1)

#### Member Condition Columns
6. **member_question_code** - Question code (e.g., "Q1", "gender") 
7. **member_condition** - Question=answer condition (e.g., "gender=2", "age_group=1")

#### Language Settings Columns (Base Language)
8. **language** - Language code (e.g., "en", "de", "fr")
9. **language_name** - Display name in this language
10. **language_message** - Quota exceeded message (required)
11. **language_url** - End URL for this language
12. **language_url_description** - URL description text

#### Additional Language Columns (for multi-language surveys)
For each additional language, repeat columns 8-12 with suffix:
- **language_[lang]** (e.g., language_de, language_fr)
- **language_name_[lang]**
- **language_message_[lang]**
- **language_url_[lang]**
- **language_url_description_[lang]**

### Example Export Data

```csv
quota_name,quota_limit,quota_action,quota_active,quota_autoload_url,member_question_code,member_condition,language,language_name,language_message,language_url,language_url_description,language_de,language_name_de,language_message_de,language_url_de,language_url_description_de
"Male Quota",100,1,1,0,"gender","gender=M","en","Male Participants","Sorry, the male quota has been reached.","","","de","Männliche Teilnehmer","Entschuldigung, die Männerquote wurde erreicht.","",""
"Female Quota",150,1,1,0,"gender","gender=F","en","Female Participants","Sorry, the female quota has been reached.","","","de","Weibliche Teilnehmer","Entschuldigung, die Frauenquote wurde erreicht.","",""
"Age 18-25",50,2,1,1,"age_group","age_group=1","en","Young Adults","Quota reached. Redirecting...","https://example.com/thanks","Thank you page","de","Junge Erwachsene","Quote erreicht. Weiterleitung...","https://example.com/danke","Dankesseite"
"Premium Users",75,1,1,0,"subscription","subscription=premium","en","Premium Subscribers","Premium quota has been reached.","","","de","Premium-Abonnenten","Premium-Quote wurde erreicht.","",""
"Complex Quota",25,3,1,0,"education","education=1","en","Education-based Quota","Education quota reached.","","","de","Bildungsbasierte Quote","Bildungsquote erreicht.","",""
"Complex Quota",25,3,1,0,"education","education=2","en","Education-based Quota","Education quota reached.","","","de","Bildungsbasierte Quote","Bildungsquote erreicht.","",""
```

### Design Rationale

#### Advantages:
1. **Single File**: All quota data in one export file
2. **Multi-language Support**: Dynamic columns for any number of languages
3. **Human Readable**: Answer values included for reference
4. **Flat Structure**: Easy to edit in Excel/spreadsheet applications
5. **Grouped Data**: Related conditions grouped by quota name

#### Handling Complex Quotas:
- **Multiple Conditions**: Same quota appears on multiple rows (one per condition)
- **OR Logic**: Multiple rows with same quota_name = OR conditions
- **Multi-language**: Additional language columns added dynamically

## Import Processing Logic

### Step 1: Group Rows by Quota
```php
$quotas = [];
foreach ($rows as $row) {
    $quotaName = $row['quota_name'];
    if (!isset($quotas[$quotaName])) {
        $quotas[$quotaName] = [
            'core' => extractCoreData($row),
            'languages' => extractLanguageData($row),
            'members' => []
        ];
    }
    $quotas[$quotaName]['members'][] = extractMemberData($row);
}
```

### Step 2: Validate Data
- Check quota names are unique within survey
- Validate question codes exist in survey
- Validate answer codes exist for questions
- Ensure required language messages are provided
- Validate URLs if autoload_url enabled

### Step 3: Import to Database
1. Create quota record in `lime_quota`
2. Create language settings in `lime_quota_languagesettings`
3. Create member conditions in `lime_quota_members`

## Edge Cases & Considerations

### Question Type Handling
- **Array Questions**: Handle scale-value format (e.g., "1-3" for scale 1, value 3)
- **Multiple Choice**: Store "Y" value for selected options
- **Text Questions**: Store actual text values

### Validation Rules
- Quota names must be unique within survey
- Question codes must exist and be valid for quota conditions
- Answer codes must exist for the referenced questions
- Language messages are required (cannot be empty)
- URLs must be valid format if provided

### Error Handling
- Invalid question codes: Skip with warning
- Invalid answer codes: Skip with warning  
- Missing required fields: Abort import with error
- Duplicate quota names: Merge or abort (user choice)

### Performance Considerations
- Batch database inserts for large quota sets
- Pre-validate all data before starting import
- Transaction rollback on any validation failure

## Future Enhancements

### Advanced Features to Consider:
1. **Quota Logic**: Support for AND/OR conditions between different questions
2. **Quota Groups**: Support for quota hierarchies
3. **Dynamic Quotas**: Time-based or percentage-based quotas
4. **Quota Templates**: Reusable quota configurations

### Export Optimizations:
1. **Separate Sheets**: Core data, members, languages in separate Excel sheets
2. **Metadata Sheet**: Export survey info, question mappings
3. **Validation Sheet**: Include valid values for reference

This design provides a solid foundation for quota import/export while maintaining simplicity and human readability.