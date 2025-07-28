<?php

namespace tonisormisson\ls\structureimex\validation;

use Question;

/**
 * Defines question attributes for each LimeSurvey question type
 * 
 * This class provides a comprehensive mapping of:
 * - Which attributes are valid for each question type  
 * - Default values for each attribute
 * - Validation rules for attribute values
 * 
 * Based on LimeSurvey core XML configuration files in:
 * /application/views/survey/questions/answer/[type]/config.xml
 */
class QuestionAttributeDefinition
{
    /**
     * Universal attributes that apply to all question types
     */
    private static $universalAttributes = [
        'hide_tip' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Display'
        ],
        'hidden' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Display'
        ],
        'page_break' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Other'
        ],
        'statistics_showgraph' => [
            'default' => '1',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Statistics'
        ],
        'statistics_graphtype' => [
            'default' => '0',
            'type' => 'singleselect',
            'options' => ['0', '1', '2', '3', '4', '5'],
            'category' => 'Statistics'
        ],
        'save_as_default' => [
            'default' => 'N',
            'type' => 'switch',
            'options' => ['N', 'Y'],
            'category' => 'Other'
        ],
        'cssclass' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Display'
        ],
        'random_group' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Logic'
        ],
        'em_validation_q' => [
            'default' => '',
            'type' => 'textarea',
            'category' => 'Logic'
        ],
        'em_validation_q_tip' => [
            'default' => '',
            'type' => 'textarea',
            'category' => 'Logic'
        ],
        'public_statistics' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Statistics'
        ],
        'scale_export' => [
            'default' => '0',
            'type' => 'singleselect',
            'options' => ['0', '1', '2', '3'],
            'category' => 'Other'
        ],
        'array_filter_style' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Logic'
        ],
        'time_limit_action' => [
            'default' => '1',
            'type' => 'singleselect',
            'options' => ['1', '2', '3'],
            'category' => 'Timer'
        ],
        'time_limit_disable_next' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Timer'
        ],
        'time_limit_disable_prev' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Timer'
        ],
        'clear_default' => [
            'default' => 'N',
            'type' => 'switch',
            'options' => ['N', 'Y'],
            'category' => 'Logic'
        ],
        'random_order' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Display'
        ],
        'other_numbers_only' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Logic'
        ],
        'other_position' => [
            'default' => 'default',
            'type' => 'singleselect',
            'options' => ['beginning', 'default', 'end', 'specific'],
            'category' => 'Display'
        ],
        'numbers_only' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Other'
        ],
        'answer_order' => [
            'default' => 'normal',
            'type' => 'singleselect',
            'options' => ['normal', 'random', 'alphabetical'],
            'category' => 'Display'
        ],
        'other_comment_mandatory' => [
            'default' => '0',
            'type' => 'switch',
            'options' => ['0', '1'],
            'category' => 'Logic'
        ],
        'suffix' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Display'
        ],
        'prefix' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Display'
        ],
        'placeholder' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Display'
        ],
        'printable_help' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Display'
        ],
        'array_filter' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Logic'
        ],
        'array_filter_exclude' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Logic'
        ],
        'exclude_all_others' => [
            'default' => '',
            'type' => 'text',
            'category' => 'Logic'
        ],
        'input_size' => [
            'default' => '',
            'type' => 'integer',
            'category' => 'Display'
        ]
    ];


    /**
     * Get all attributes defined for a question type
     *
     * @param string $questionType LimeSurvey question type (T, L, Z, etc.)
     * @return array Attribute definitions or empty array if type not found
     */
    public static function getAttributesForQuestionType($questionType)
    {
        // Return empty array for invalid question types
        if (!isset(self::$definitions[$questionType])) {
            return [];
        }
        
        $typeSpecific = self::$definitions[$questionType];
        // Merge universal attributes with type-specific ones
        return array_merge(self::$universalAttributes, $typeSpecific);
    }

    /**
     * Get default value for a specific attribute and question type
     *
     * @param string $questionType LimeSurvey question type
     * @param string $attributeName Attribute name
     * @return string|null Default value or null if not found
     */
    public static function getDefaultValue($questionType, $attributeName)
    {
        $attributes = self::getAttributesForQuestionType($questionType);
        return $attributes[$attributeName]['default'] ?? null;
    }

    /**
     * Check if an attribute is valid for a question type
     *
     * @param string $questionType LimeSurvey question type
     * @param string $attributeName Attribute name
     * @return bool True if attribute is valid for question type
     */
    public static function isValidAttribute($questionType, $attributeName)
    {
        $attributes = self::getAttributesForQuestionType($questionType);
        return isset($attributes[$attributeName]);
    }

    /**
     * Get list of attribute names for a question type
     *
     * @param string $questionType LimeSurvey question type
     * @return array List of valid attribute names
     */
    public static function getAttributeNames($questionType)
    {
        $attributes = self::getAttributesForQuestionType($questionType);
        return array_keys($attributes);
    }

    /**
     * Check if a value differs from the default for an attribute
     *
     * @param string $questionType LimeSurvey question type
     * @param string $attributeName Attribute name
     * @param mixed $value Current value
     * @return bool True if value is different from default
     */
    public static function isNonDefaultValue(string $questionType, string $attributeName, mixed $value)
    {
        $defaultValue = self::getDefaultValue($questionType, $attributeName);

        if ($defaultValue === null) {
            return false; // Unknown attribute, don't export
        }

        // Handle empty string defaults vs null/empty values
        if ($defaultValue === '' && ($value === '' || $value === null)) {
            return false;
        }

        // Direct comparison for most cases
        return (string)$value !== (string)$defaultValue;
    }

    /**
     * Get all supported question types
     *
     * @return array List of supported question type codes
     */
    public static function getSupportedQuestionTypes()
    {
        return array_keys(self::$definitions);
    }

    /**
     * Validate attribute value against its definition
     *
     * @param string $questionType LimeSurvey question type
     * @param string $attributeName Attribute name
     * @param string $value Value to validate
     * @return bool True if value is valid
     */
    public static function validateAttributeValue($questionType, $attributeName, $value)
    {
        $attributes = self::getAttributesForQuestionType($questionType);
        $attributeDefinition = $attributes[$attributeName] ?? null;

        if (!$attributeDefinition) {
            return false; // Unknown attribute
        }

        $type = $attributeDefinition['type'];

        switch ($type) {
            case 'switch':
                return in_array($value, ['0', '1']);

            case 'integer':
                return $value === '' || (is_numeric($value) && (int)$value == $value);

            case 'singleselect':
                $options = $attributeDefinition['options'] ?? [];
                return in_array($value, $options);

            case 'text':
            case 'textarea':
                return true; // Text values are generally valid

            default:
                return true; // Unknown types are allowed
        }
    }

    /**
     * Question type attribute definitions
     * 
     * Structure: [questionType => [attributeName => [default, validation, ...]]]
     */
    private static $definitions = [
        // T - Long free text
        \Question::QT_T_LONG_FREE_TEXT => [
            'assessment_value' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'location_mapheight' => ['default' => '300', 'type' => 'text', 'category' => 'Location'],
            'location_mapwidth' => ['default' => '500', 'type' => 'text', 'category' => 'Location'],
            'location_mapzoom' => ['default' => '11', 'type' => 'text', 'category' => 'Location'],
            'statistics_showmap' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'text_input_width' => [
                'default' => '',
                'type' => 'singleselect',
                'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
                'category' => 'Display'
            ],
            'display_rows' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Display'
            ],
            'maximum_chars' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Input'
            ],
            'page_break' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Other'
            ],
            'statistics_showgraph' => [
                'default' => '1',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'statistics_graphtype' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1', '2', '3', '4', '5'],
                'category' => 'Statistics'
            ],
            // Timer attributes
            'time_limit' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_countdown_message' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_timer_style' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_message_delay' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_message' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_message_style' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_warning' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_warning_display_time' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_warning_message' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_warning_style' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_warning_2' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_warning_2_display_time' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            'time_limit_warning_2_message' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            'time_limit_warning_2_style' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Timer'
            ],
            // General attributes (always available)
            'random_group' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'em_validation_q' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'em_validation_q_tip' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'numbers_only' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Other'
            ]
        ],
        
        // L - List (Radio)
        \Question::QT_L_LIST => [
            'answer_width' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Display'
            ],
            'display_columns' => [
                'default' => '',
                'type' => 'columns',
                'category' => 'Display'
            ],
            'fix_height' => [
                'default' => '200',
                'type' => 'integer',
                'category' => 'Display'
            ],
            'exclude_all_others' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'page_break' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Other'
            ],
            'statistics_showgraph' => [
                'default' => '1',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'statistics_graphtype' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1', '2', '3', '4', '5'],
                'category' => 'Statistics'
            ],
            // Timer attributes (same as T type)
            'time_limit' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
            ],
            // General attributes (always available)
            'random_group' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'em_validation_q' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'em_validation_q_tip' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'assessment_value' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'scale_export' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'other_position_code' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'other_replace_text' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ]
        ],
        
        
        // M - Multiple Choice
        \Question::QT_M_MULTIPLE_CHOICE => [
            'display_columns' => [
                'default' => '',
                'type' => 'columns',
                'category' => 'Display'
            ],
            'min_answers' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Input'
            ],
            'max_answers' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Input'
            ],
            'other_replace_text' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'em_validation_q_tip' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'em_validation_q' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
            ],
            'random_group' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'other_position_code' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            // Exclusive option attributes
            'exclude_all_others' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'exclude_all_others_auto' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Logic'
            ]
        ],
        
        // S - Short Free Text
        \Question::QT_S_SHORT_FREE_TEXT => [
            'assessment_value' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'text_input_width' => [
                'default' => '',
                'type' => 'singleselect',
                'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
                'category' => 'Display'
            ],
            'maximum_chars' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Input'
            ],
            'cssclass' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            // Location attributes for map functionality
            'location_city' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Location'
            ],
            'location_country' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Location'
            ],
            'location_defaultcoordinates' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Location'
            ],
            'location_mapheight' => [
                'default' => '300',
                'type' => 'text',
                'category' => 'Location'
            ],
            'location_mapservice' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1', '100'],
                'category' => 'Location'
            ],
            'location_mapwidth' => [
                'default' => '500',
                'type' => 'text',
                'category' => 'Location'
            ],
            'location_mapzoom' => [
                'default' => '11',
                'type' => 'text',
                'category' => 'Location'
            ],
            'location_nodefaultfromip' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Location'
            ],
            'location_postal' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Location'
            ],
            'statistics_showmap' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Statistics'
            ],
            'location_state' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Location'
            ],
            'numbers_only' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Other'
            ]
        ],
        
        // ! - List Dropdown
        \Question::QT_EXCLAMATION_LIST_DROPDOWN => [
            'category_separator' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'dropdown_prefix' => [
                'default' => '0',
                'type' => 'buttongroup',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'dropdown_size' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'show_search' => [
                'default' => 'false',
                'type' => 'buttongroup',
                'options' => ['false', 'true'],
                'category' => 'Display'
            ],
            'show_tick' => [
                'default' => 'false',
                'type' => 'buttongroup',
                'options' => ['false', 'true'],
                'category' => 'Display'
            ],
            'other_position_code' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'other_replace_text' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            // U-V-W attributes
            'width_entry' => ['default' => 'false', 'type' => 'buttongroup', 'category' => 'Display']
        ],
        
        \Question::QT_X_TEXT_DISPLAY => [
            'assessment_value' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'text_input_width' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'location_mapheight' => ['default' => '300', 'type' => 'text', 'category' => 'Location'],
            'location_mapwidth' => ['default' => '500', 'type' => 'text', 'category' => 'Location'],
            'location_mapzoom' => ['default' => '11', 'type' => 'text', 'category' => 'Location'],
            'statistics_showmap' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        \Question::QT_Q_MULTIPLE_SHORT_TEXT => [
            'text_input_width' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'text_input_columns' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'label_input_columns' => ['default' => '', 'type' => 'singleselect', 'options' => ['', 'hidden', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        \Question::QT_Y_YES_NO_RADIO => [
            'display_type' => ['default' => '0', 'type' => 'buttongroup', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_G_GENDER => [
            'display_type' => ['default' => '0', 'type' => 'buttongroup', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_5_POINT_CHOICE => [
            'slider_rating' => ['default' => '0', 'type' => 'singleselect', 'options' => ['0', '1', '2'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_D_DATE => [
            'date_format' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'date_max' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'date_min' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'dropdown_dates' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'dropdown_dates_minute_step' => ['default' => '1', 'type' => 'integer', 'category' => 'Display'],
            'dropdown_dates_month_style' => ['default' => '0', 'type' => 'singleselect', 'options' => ['0', '1', '2'], 'category' => 'Display'],
            'reverse' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        // Add all remaining question types with common attributes
        \Question::QT_A_ARRAY_5_POINT => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_B_ARRAY_10_CHOICE_QUESTIONS => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_C_ARRAY_YES_UNCERTAIN_NO => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_E_ARRAY_INC_SAME_DEC => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_H_ARRAY_COLUMN => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'answer_width_bycolumn' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_I_LANGUAGE => [
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS => [
            'choice_input_columns' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'exclude_all_others' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'commented_checkbox' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'commented_checkbox_auto' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'other_comment_mandatory' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_position' => ['default' => 'default', 'type' => 'singleselect', 'options' => ['beginning', 'default', 'end', 'specific'], 'category' => 'Display'],
            'other_position_code' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'other_replace_text' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_R_RANKING => [
            'choice_title' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_subquestions' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'rank_title' => ['default' => '', 'type' => 'text', 'category' => 'Other'],
            'samechoiceheight' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'samelistheight' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'showpopups' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_U_HUGE_FREE_TEXT => [
            'maximum_chars' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'display_rows' => ['default' => '5', 'type' => 'integer', 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_VERTICAL_FILE_UPLOAD => [
            'max_filesize' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'allowed_filetypes' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'min_num_of_files' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_num_of_files' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'show_title' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'show_comment' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'show_filename' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_ASTERISK_EQUATION => [
            'equation' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_COLON_ARRAY_NUMBERS => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'input_boxes' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'multiflexible_checkbox' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'multiflexible_max' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'multiflexible_min' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'multiflexible_step' => ['default' => '1', 'type' => 'integer', 'category' => 'Input'],
            'parent_order' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_SEMICOLON_ARRAY_TEXT => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        // N - Numerical input
        \Question::QT_N_NUMERICAL => [
            'text_input_width' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'min_num_value_n' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_num_value_n' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'num_value_int_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Input'],
            'public_statistics' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'scale_export' => ['default' => '0', 'type' => 'singleselect', 'options' => ['0', '1', '2', '3'], 'category' => 'Other'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            // U-V-W attributes
            'use_dropdown' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display']
        ],
        
        \Question::QT_F_ARRAY => [
            'assessment_value' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'array_filter_style' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            // U-V-W attributes
            'use_dropdown' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display']
        ],
        
        \Question::QT_1_ARRAY_DUAL => [
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            // U-V-W attributes
            'use_dropdown' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display']
        ],
        
        \Question::QT_O_LIST_WITH_COMMENT => [
            'answer_order' => ['default' => 'normal', 'type' => 'singleselect', 'options' => ['normal', 'random'], 'category' => 'Display'],
            'other_comment_mandatory' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_position' => ['default' => 'default', 'type' => 'singleselect', 'options' => ['beginning', 'default', 'end', 'specific'], 'category' => 'Display'],
            'other_position_code' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'other_replace_text' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            // U-V-W attributes
            'use_dropdown' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display']
        ],
        
        \Question::QT_K_MULTIPLE_NUMERICAL => [
            'text_input_width' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'equals_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'max_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'min_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'num_value_int_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Input'],
            'slider_layout' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Slider'],
            'slider_max' => ['default' => '', 'type' => 'text', 'category' => 'Slider'],
            'slider_min' => ['default' => '', 'type' => 'text', 'category' => 'Slider'],
            'slider_orientation' => ['default' => '0', 'type' => 'singleselect', 'options' => ['0', '1'], 'category' => 'Slider'],
            'slider_showminmax' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Slider'],
            'slider_separator' => ['default' => '|', 'type' => 'text', 'category' => 'Slider'],
            'slider_default_set' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Slider'],
            'slider_custom_handle' => ['default' => 'f1ae', 'type' => 'text', 'category' => 'Slider'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            // U-V-W attributes
            'value_range_allows_missing' => ['default' => '1', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Input']
        ],
        
        
    ];

}
