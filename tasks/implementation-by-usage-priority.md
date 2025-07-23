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
- **Status**: ✅ COMPLETED
- **Question Types to Implement**:
  - ! (List Dropdown): 104 uses, 75 surveys ✅ DONE
  - * (Equation): 42 uses, 6 surveys ✅ DONE
  - F (Array): 1,388 uses, 131 surveys ✅ DONE  
  - I (Language): 197 uses, 196 surveys ✅ DONE
  - K (Multiple Numerical): 24 uses, 13 surveys ✅ DONE
  - L (List Radio): 6,909 uses, 205 surveys ✅ DONE
  - M (Multiple Choice): 1,482 uses, 173 surveys ✅ DONE
  - N (Numerical): 262 uses, 191 surveys ✅ DONE
  - O (List with Comment): 235 uses, 115 surveys ✅ DONE
  - P (Multiple Choice with Comments): 18 uses, 14 surveys ✅ DONE
  - Q (Multiple Short Text): 31 uses, 30 surveys ✅ DONE
  - S (Short Free Text): 107 uses, 90 surveys ✅ DONE
  - T (Long Free Text): 1,553 uses, 139 surveys ✅ DONE
  - X (Text Display): 151 uses, 55 surveys ✅ DONE

### Priority #2: statistics_showgraph ⚠️ MOSTLY DEFAULTS
- **Total Usage**: 11,992 uses across 18 question types, 258 surveys  
- **Status**: ❌ SKIP - All sample values are "1" which matches LimeSurvey default
- **Note**: This attribute has default="1" in LimeSurvey core, so all database entries are likely defaults
- **Question Types to Implement**:
  - ! (List Dropdown): 120 uses, 26 surveys ❌ TODO
  - * (Equation): 362 uses, 151 surveys ❌ TODO
  - F (Array): 1,345 uses, 190 surveys ✅ DONE
  - H (Array Column): 45 uses, 6 surveys ❌ TODO
  - I (Language): 32 uses, 32 surveys ❌ TODO
  - K (Multiple Numerical): 67 uses, 29 surveys ❌ TODO
  - L (List Radio): 6,421 uses, 233 surveys ✅ DONE
  - M (Multiple Choice): 1,141 uses, 166 surveys ❌ TODO
  - N (Numerical): 202 uses, 138 surveys ❌ TODO
  - O (List with Comment): 184 uses, 101 surveys ❌ TODO
  - P (Multiple Choice with Comments): 18 uses, 14 surveys ❌ TODO
  - Q (Multiple Short Text): 56 uses, 50 surveys ❌ TODO
  - S (Short Free Text): 236 uses, 77 surveys ❌ TODO
  - T (Long Free Text): 1,297 uses, 174 surveys ✅ DONE
  - X (Text Display): 460 uses, 169 surveys ❌ TODO

### Priority #3: save_as_default
- **Total Usage**: 11,932 uses across 18 question types, 251 surveys
- **Status**: ❌ TODO
- **Question Types to Implement**: [Same as statistics_showgraph - both are common attributes]

### Priority #4: time_limit_action
- **Total Usage**: 8,829 uses across 13 question types, 245 surveys
- **Status**: ❌ TODO
- **Question Types to Implement**:
  - ! (List Dropdown): 120 uses, 26 surveys ❌ TODO
  - * (Equation): 45 uses, 9 surveys ❌ TODO
  - F (Array): 123 uses, 31 surveys ❌ TODO
  - L (List Radio): 6,420 uses, 233 surveys ✅ DONE
  - M (Multiple Choice): 96 uses, 48 surveys ❌ TODO
  - O (List with Comment): 26 uses, 15 surveys ❌ TODO
  - S (Short Free Text): 236 uses, 77 surveys ❌ TODO
  - T (Long Free Text): 1,297 uses, 174 surveys ✅ DONE
  - X (Text Display): 461 uses, 170 surveys ❌ TODO

### Priority #5: other_position
- **Total Usage**: 7,917 uses across 10 question types, 235 surveys
- **Status**: ❌ TODO

### Priority #6: answer_order
- **Total Usage**: 6,911 uses across 8 question types, 230 surveys
- **Status**: ❌ TODO

### Priority #7: clear_default
- **Total Usage**: 5,929 uses across 13 question types, 192 surveys
- **Status**: ❌ TODO
- **Note**: This was our previous focus - universal attribute

### Priority #8: hidden
- **Total Usage**: 5,597 uses across 12 question types, 239 surveys
- **Status**: ❌ TODO

### Priority #9: exclude_all_others
- **Total Usage**: 1,342 uses across 3 question types, 166 surveys
- **Status**: ✅ DONE (just implemented for M type)
- **Question Types to Implement**:
  - L (List Radio): 10 uses, 9 surveys ❌ TODO
  - M (Multiple Choice): 1,316 uses, 164 surveys ✅ DONE
  - P (Multiple Choice with Comments): 16 uses, 12 surveys ❌ TODO

### Priority #10: assessment_value
- **Total Usage**: 1,275 uses across 7 question types, 171 surveys
- **Status**: ❌ TODO

---

## IMPLEMENTATION STATUS SUMMARY

### Current Progress
- **Attributes Started**: 1 (hide_tip)  
- **Attributes Completed**: 1 (hide_tip)
- **Question Types with hide_tip**: 14/14 implemented (100%)

### Next Steps
1. **Start statistics_showgraph** (Priority #2)
2. **Test and commit statistics_showgraph**
3. **Start save_as_default** (Priority #3)

### Coverage Metrics
- **By Usage Volume**: Completing hide_tip covers 12,504 real uses
- **By Survey Impact**: Affects 235 surveys (high impact)
- **Efficiency**: Top 10 attributes cover majority of real-world usage

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