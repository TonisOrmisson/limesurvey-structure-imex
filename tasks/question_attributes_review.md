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

### D Attributes (8 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| date_format | ❌ not_reviewed | | |
| date_max | ❌ not_reviewed | | |
| date_min | ❌ not_reviewed | | |
| display_columns | ❌ not_reviewed | | |
| display_rows | ❌ not_reviewed | | |
| dropdown_dates | ❌ not_reviewed | | |
| dropdown_dates_minute_step | ❌ not_reviewed | | |
| dropdown_dates_month_style | ❌ not_reviewed | | |
| dropdown_prefix | ❌ not_reviewed | | |
| dropdown_size | ❌ not_reviewed | | |

### E Attributes (10 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| em_validation_q | ❌ not_reviewed | | |
| em_validation_q_tip | ❌ not_reviewed | | |
| em_validation_sq | ❌ not_reviewed | | |
| em_validation_sq_tip | ❌ not_reviewed | | |
| equals_num_value | ❌ not_reviewed | | |
| equation | ❌ not_reviewed | | |
| exclude_all_others | ❌ not_reviewed | | |
| exclude_all_others_auto | ❌ not_reviewed | | |

### F-H Attributes (9 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| fix_height | ❌ not_reviewed | | |
| fix_width | ❌ not_reviewed | | |
| hidden | ❌ not_reviewed | | |
| hide_tip | ❌ not_reviewed | | |
| horizontal_scroll | ❌ not_reviewed | | |

### I Attributes (2 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| input_boxes | ❌ not_reviewed | | |
| input_size | ❌ not_reviewed | | |

### K Attributes (1 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| keep_aspect | ❌ not_reviewed | | |

### L Attributes (12 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| label_input_columns | ❌ not_reviewed | | |
| location_city | ❌ not_reviewed | | |
| location_country | ❌ not_reviewed | | |
| location_defaultcoordinates | ❌ not_reviewed | | |
| location_mapheight | ❌ not_reviewed | | |
| location_mapservice | ❌ not_reviewed | | |
| location_mapwidth | ❌ not_reviewed | | |
| location_mapzoom | ❌ not_reviewed | | |
| location_nodefaultfromip | ❌ not_reviewed | | |
| location_postal | ❌ not_reviewed | | |
| location_state | ❌ not_reviewed | | |

### M Attributes (12 total)
| Attribute | Status | Question Types | Notes |
|-----------|--------|----------------|-------|
| max_answers | ❌ not_reviewed | | |
| max_num_value | ❌ not_reviewed | | |
| max_num_value_n | ❌ not_reviewed | | |
| max_subquestions | ❌ not_reviewed | | |
| maximum_chars | ❌ not_reviewed | | |
| min_answers | ❌ not_reviewed | | |
| min_num_value | ❌ not_reviewed | | |
| min_num_value_n | ❌ not_reviewed | | |
| multiflexible_checkbox | ❌ not_reviewed | | |
| multiflexible_max | ❌ not_reviewed | | |
| multiflexible_min | ❌ not_reviewed | | |
| multiflexible_step | ❌ not_reviewed | | |

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
- **Not Reviewed**: 99 (83%)  
- **Researched**: 13 (11%)
- **Implemented**: 0 (0%)
- **Tested**: 7 (6%) - repeat_headings, answer_width, answer_width_bycolumn, category_separator, choice_input_columns, choice_title, commented_checkbox variants

## Current Focus
**Next Phase**: Research and implement A-C attributes (29 total)

Priority order:
1. Common display attributes (cssclass, hidden, hide_tip)
2. Validation attributes (array_filter, auto_submit) 
3. Layout attributes (answer_width, display_columns)
4. Advanced features (assessment_value, autoplay)