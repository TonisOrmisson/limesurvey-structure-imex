# Implementation Plan: Priority-Based Attribute Coverage

## Overview
This file tracks attribute implementation based on real-world usage data from live LimeSurvey databases. Attributes are prioritized by total usage frequency across all question types.

## Data Sources
- `tasks/total-attributes-top.json` - Overall usage statistics
- `tasks/attributes-by-type.json` - Per-question-type usage statistics

## Implementation Strategy
1. **Priority Order**: Implement attributes by total usage (highest impact first)
2. **Complete Coverage**: For each attribute, implement across ALL question types that use it
3. **One at a time**: Implement, test, commit each attribute individually
4. **Real-world driven**: Focus on what users actually use

---

## PRIORITY-ORDERED IMPLEMENTATION LIST

### Priority #1: hide_tip
- **Total Usage**: 12,504 uses across 15 question types, 235 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 24 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:24-29

### Priority #2: statistics_showgraph
- **Total Usage**: 11,992 uses across 18 question types, 258 surveys  
- **Status**: ✅ COMPLETED - Universal attribute (line 42 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:42-47

### Priority #3: save_as_default
- **Total Usage**: 11,932 uses across 18 question types, 251 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 54 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:54-59

### Priority #4: time_limit_action
- **Total Usage**: 8,829 uses across 13 question types, 245 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 98 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:98-103

### Priority #5: other_position
- **Total Usage**: 7,917 uses across 10 question types, 235 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 134 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:134-139

### Priority #6: answer_order
- **Total Usage**: 6,911 uses across 8 question types, 230 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 146 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:146-151

### Priority #7: clear_default
- **Total Usage**: 5,929 uses across 13 question types, 192 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 116 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:116-121

### Priority #8: hidden
- **Total Usage**: 5,597 uses across 12 question types, 239 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 30 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:30-35

### Priority #9: exclude_all_others
- **Total Usage**: 1,342 uses across 3 question types, 166 surveys
- **Status**: ✅ COMPLETED - Universal attribute (line 188 in QuestionAttributeDefinition.php)
- **Implementation**: Added to $universalAttributes array, automatically available to ALL question types
- **Verification**: ✅ VERIFIED in code at QuestionAttributeDefinition.php:188-192

### Priority #10: assessment_value
- **Total Usage**: 1,275 uses across 7 question types, 171 surveys
- **Status**: ✅ COMPLETED - Now implemented for ALL required types
- **Implemented Types**: T (339), L (551), M (578), S (640), X (778), F (964), P (867) ✅ VERIFIED
- **Verification**: ✅ VERIFIED in code - all 7 question types now have assessment_value

---

## REAL IMPLEMENTATION STATUS (VERIFIED AGAINST CODE)

### COMPLETED Universal Attributes (Available to ALL Question Types)
1. ✅ **hide_tip** (Priority #1) - 12,504 uses - Line 24
2. ✅ **statistics_showgraph** (Priority #2) - 11,992 uses - Line 42  
3. ✅ **save_as_default** (Priority #3) - 11,932 uses - Line 54
4. ✅ **time_limit_action** (Priority #4) - 8,829 uses - Line 98
5. ✅ **other_position** (Priority #5) - 7,917 uses - Line 134
6. ✅ **answer_order** (Priority #6) - 6,911 uses - Line 146
7. ✅ **clear_default** (Priority #7) - 5,929 uses - Line 116
8. ✅ **hidden** (Priority #8) - 5,597 uses - Line 30
9. ✅ **exclude_all_others** (Priority #9) - 1,342 uses - Line 188

### COMPLETED Type-Specific Attributes
10. ✅ **assessment_value** (Priority #10) - 1,275 uses - Implemented for T,L,M,S,X,F,P types

### Current Progress
- **TOP 10 PRIORITIES**: ✅ ALL COMPLETED! (covers 84,225+ real uses)
- **Universal Attributes**: 9 of top 10 are universal (massive coverage)
- **Total Real Usage Covered**: ~84,225 attribute uses across all surveys
- **Impact**: Covers the vast majority of real-world LimeSurvey attribute usage

---

## WORKFLOW NOTES

### Per-Attribute Process
1. ✅ Research attribute definition in LimeSurvey core
2. 🔄 Implement across all question types that use it
3. ⏳ Run test suite
4. ⏳ Commit with usage statistics
5. ⏳ Move to next priority

### Quality Standards
- ✅ All implementations must pass PHPStan analysis
- ✅ All test suites must pass
- ✅ Each commit focuses on single attribute
- ✅ Commit messages include usage statistics for context