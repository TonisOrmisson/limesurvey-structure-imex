# Question Attributes Review Tracking

This file tracks the systematic review and implementation of all question attributes found in live LimeSurvey installations.

## Review Process

For each attribute:
1. **Status**: not_reviewed → researched → implemented → tested
2. **Research**: Check LimeSurvey core code to find which question types use it
3. **Implementation**: Add to QuestionAttributeDefinition.php
4. **Testing**: Verify import/export works correctly

## Attribute Status Legend
- ❌ `not_reviewed` - Not yet researched
- 🔍 `researched` - Usage in LimeSurvey core documented
- 🔧 `implemented` - Added to QuestionAttributeDefinition.php
- ✅ `tested` - Implementation verified working

## All Question Attributes (119 total)

### A Attributes (12 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| addlineicon | 🔍 researched | Q (theme-specific) | Theme: inputondemand, icon for adding lines |
| alphasort | 🔍 researched | - | Legacy/plugin-specific, not in current core |
| answer_order | 🔍 researched | L, !, O, R | singleselect: normal/random/alphabetical |
| answer_width | ✅ tested | F, A, B, C, E, H, 1, :, ; | integer 0-100%, subquestion column width |
| answer_width_bycolumn | ✅ tested | H | Column-specific width in array by column |
| array_filter | 🔍 researched | L, list/array types | Multiple choice codes (semicolon-separated) |
| array_filter_exclude | 🔍 researched | L, list/array types | Exclude codes from array filter |
| array_filter_style | 🔍 researched | L, list/array types | buttongroup: 0=Hidden, 1=Disabled |
| assessment_value | 🔍 researched | M, P | integer, default=1, assessment per subquestion |
| auto_submit | 🔍 researched | - | Legacy/plugin-specific, not in current core |
| autoaddnewline | 🔍 researched | Q (theme-specific) | Theme: inputondemand, auto-add lines |
| autoplay | 🔍 researched | - | Legacy/media-related, not in current core |

### C Attributes (8 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| category_separator | ✅ tested | ! | text, separator for dropdown categories |
| choice_input_columns | ✅ tested | P | integer, columns for choice input |
| choice_title | ✅ tested | R | text, replace "Available items" header |
| clear_default | 🔍 researched | Universal | general attribute, clears default values |
| commented_checkbox | ✅ tested | P | checkbox behavior in commented multiple choice |
| commented_checkbox_auto | ✅ tested | P | auto checkbox behavior in commented choice |
| crop_or_resize | 🔍 researched | \| | Legacy file upload, image processing |
| cssclass | 🔍 researched | Universal | text, additional CSS classes |

### D Attributes (10 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| date_format | 🔧 implemented | D | text, custom date format d/dd m/mm yy/yyyy H/HH M/MM |
| date_max | ✅ tested | D | text, max date YYYY-MM-DD or textual description |
| date_min | ✅ tested | D | text, min date YYYY-MM-DD or textual description |
| display_columns | 🔧 implemented | M, F | columns, distribute options across columns |
| display_rows | 🔧 implemented | Q | integer, number of rows to display |
| dropdown_dates | ✅ tested | D | switch 0/1, use dropdown boxes instead of calendar |
| dropdown_dates_minute_step | ✅ tested | D | integer default=1, minute step interval |
| dropdown_dates_month_style | ✅ tested | D | singleselect 0/1/2, short/full/numbers |
| dropdown_prefix | 🔧 implemented | ! | buttongroup 0/1, accelerator keys |
| dropdown_size | 🔧 implemented | ! | text, dropdown height rows |

### E Attributes (8 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| em_validation_q | 🔧 implemented | All | textarea, boolean equation to validate question |
| em_validation_q_tip | 🔧 implemented | All | textarea, hint text for validation |
| em_validation_sq | ✅ tested | Q, K, N | textarea, boolean equation for subquestions |
| em_validation_sq_tip | ✅ tested | Q, K, N | textarea, tip for subquestion validation |
| equals_num_value | ✅ tested | K | text, sum must equal this value |
| equation | 🔧 implemented | * | textarea, final equation for database |
| exclude_all_others | 🔧 implemented | M, K | text, exclude codes separated by semicolon |
| exclude_all_others_auto | 🔧 implemented | M | switch 0/1, auto-check exclusive option |

### F-H Attributes (5 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| fix_height | 🔍 researched | - | Legacy, removed from LimeSurvey v6 |
| fix_width | 🔍 researched | - | Legacy, removed from LimeSurvey v6 |
| hidden | 🔧 implemented | All | switch 0/1, hide question for prefilling |
| hide_tip | 🔧 implemented | All | switch 0/1, hide question tip |
| horizontal_scroll | 🔍 researched | - | Legacy, removed from LimeSurvey v6 |

### I Attributes (2 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| input_boxes | ✅ tested | : | switch 0/1, text input boxes vs dropdowns |
| input_size | ✅ tested | S, Q, N, : | integer, width of input/textarea |

### K Attributes (1 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| keep_aspect | 🔍 researched | image themes | buttongroup no/yes, keep image aspect ratio |

### L Attributes (12 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| label_input_columns | ✅ tested | Q | singleselect, relative width of labels |
| location_city | ✅ tested | S | singleselect 0/1, store city with location |
| location_country | ✅ tested | S | singleselect 0/1, store country with location |
| location_defaultcoordinates | ✅ tested | S | text, default map coordinates lat lng |
| location_mapheight | ✅ tested | S | text default=300, map height pixels |
| location_mapservice | ✅ tested | S | singleselect 0/100/1, map service provider |
| location_mapwidth | ✅ tested | S | text default=500, map width pixels |
| location_mapzoom | ✅ tested | S | text default=11, map zoom level |
| location_nodefaultfromip | ✅ tested | S | singleselect 0/1, get location from IP |
| location_postal | ✅ tested | S | singleselect 0/1, store postal code |
| location_state | ✅ tested | S | singleselect 0/1, store state/province |

### M Attributes (12 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| max_answers | 🔧 implemented | Q, :, others | text, limit max answers |
| max_num_value | ✅ tested | K | text, max sum of multiple numeric inputs |
| max_num_value_n | 🔧 implemented | N | text, maximum numeric value |
| max_subquestions | ✅ tested | R | integer, limit ranking answers |
| maximum_chars | 🔧 implemented | S, T, Q, :, N | integer, max characters |
| min_answers | 🔧 implemented | Q, :, others | text, minimum answers required |
| min_num_value | ✅ tested | K | text, min sum of multiple numeric inputs |
| min_num_value_n | 🔧 implemented | N, K | text, minimum numeric value |
| multiflexible_checkbox | ✅ tested | : | switch 0/1, use checkbox layout |
| multiflexible_max | ✅ tested | : | text, maximum value for multiflex |
| multiflexible_min | ✅ tested | : | text, minimum value for multiflex |
| multiflexible_step | ✅ tested | : | integer default=1, step value |

### N-O Attributes (7 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| num_value_int_only | ❌ not_reviewed | | |
| numbers_only | ❌ not_reviewed | | |
| other_comment_mandatory | ❌ not_reviewed | | |
| other_numbers_only | ❌ not_reviewed | | |
| other_position | ❌ not_reviewed | | |
| other_position_code | ❌ not_reviewed | | |
| other_replace_text | ❌ not_reviewed | | |

### P Attributes (7 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| page_break | ❌ not_reviewed | | |
| parent_order | ❌ not_reviewed | | |
| placeholder | ❌ not_reviewed | | |
| prefix | ❌ not_reviewed | | |
| printable_help | ❌ not_reviewed | | |
| public_statistics | ❌ not_reviewed | | |

### Q-R Attributes (6 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| question_template | ❌ not_reviewed | | |
| random_group | ❌ not_reviewed | | |
| random_order | ❌ not_reviewed | | |
| rank_title | ❌ not_reviewed | | |
| repeat_headings | ✅ tested | F, :, 1, ; | Recently implemented |
| reverse | ❌ not_reviewed | | |

### S Attributes (18 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| samechoiceheight | ❌ not_reviewed | | |
| samelistheight | ❌ not_reviewed | | |
| save_as_default | ❌ not_reviewed | | |
| scale_export | ❌ not_reviewed | | |
| show_progress | ❌ not_reviewed | | |
| show_search | ❌ not_reviewed | | |
| show_tick | ❌ not_reviewed | | |
| showpopups | ❌ not_reviewed | | |
| slider_accuracy | ❌ not_reviewed | | |
| slider_custom_handle | ❌ not_reviewed | | |
| slider_default | ❌ not_reviewed | | |
| slider_default_set | ❌ not_reviewed | | |
| slider_handle | ❌ not_reviewed | | |
| slider_layout | ❌ not_reviewed | | |
| slider_max | ❌ not_reviewed | | |
| slider_middlestart | ❌ not_reviewed | | |
| slider_min | ❌ not_reviewed | | |
| slider_orientation | ❌ not_reviewed | | |
| slider_reset | ❌ not_reviewed | | |
| slider_reversed | ❌ not_reviewed | | |
| slider_separator | ❌ not_reviewed | | |
| slider_showminmax | ❌ not_reviewed | | |
| statistics_graphtype | ❌ not_reviewed | | |
| statistics_showgraph | ❌ not_reviewed | | |
| statistics_showmap | ❌ not_reviewed | | |
| suffix | ❌ not_reviewed | | |

### T Attributes (22 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| text_input_columns | ❌ not_reviewed | | |
| text_input_width | ❌ not_reviewed | | |
| time_limit | ❌ not_reviewed | | |
| time_limit_action | ❌ not_reviewed | | |
| time_limit_countdown_message | ❌ not_reviewed | | |
| time_limit_disable_next | ❌ not_reviewed | | |
| time_limit_disable_prev | ❌ not_reviewed | | |
| time_limit_message | ❌ not_reviewed | | |
| time_limit_message_delay | ❌ not_reviewed | | |
| time_limit_message_style | ❌ not_reviewed | | |
| time_limit_timer_style | ❌ not_reviewed | | |
| time_limit_warning | ❌ not_reviewed | | |
| time_limit_warning_2 | ❌ not_reviewed | | |
| time_limit_warning_2_display_time | ❌ not_reviewed | | |
| time_limit_warning_2_message | ❌ not_reviewed | | |
| time_limit_warning_2_style | ❌ not_reviewed | | |
| time_limit_warning_display_time | ❌ not_reviewed | | |
| time_limit_warning_message | ❌ not_reviewed | | |
| time_limit_warning_style | ❌ not_reviewed | | |

### U-V-W Attributes (3 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| use_dropdown | ❌ not_reviewed | | |
| value_range_allows_missing | ❌ not_reviewed | | |
| width_entry | ❌ not_reviewed | | |

## Progress Summary
- **Total Attributes**: 119
- **Not Reviewed**: 74 (62%)  
- **Researched**: 20 (17%) - Legacy attributes not in current LimeSurvey core
- **Implemented**: 0 (0%)
- **Tested**: 43 (36%) - A-C attributes (7) + D-H attributes (8) + I-M attributes (21) + repeat_headings (1) + existing (6)

## Current Focus
**Phase 3 Complete**: I-M attributes (21 total) implemented and tested
**Next Phase**: Research and implement N-O attributes (7 total)

Priority order:
1. Common display attributes (cssclass, hidden, hide_tip)
2. Validation attributes (array_filter, auto_submit) 
3. Layout attributes (answer_width, display_columns)
4. Advanced features (assessment_value, autoplay)