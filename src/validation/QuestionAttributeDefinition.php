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
     * Get all attributes defined for a question type
     *
     * @param string $questionType LimeSurvey question type (T, L, Z, etc.)
     * @return array Attribute definitions or empty array if type not found
     */
    public static function getAttributesForQuestionType($questionType)
    {
        return self::$definitions[$questionType] ?? [];
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
            'cssclass' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'text_input_width' => [
                'default' => '',
                'type' => 'singleselect',
                'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
                'category' => 'Display'
            ],
            'input_size' => [
                'default' => '',
                'type' => 'integer',
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
            'cssclass' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'answer_order' => [
                'default' => 'normal',
                'type' => 'singleselect',
                'options' => ['normal', 'random', 'alphabetical'],
                'category' => 'Display'
            ],
            'display_columns' => [
                'default' => '',
                'type' => 'columns',
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
            // Timer attributes (same as T type)
            'time_limit' => [
                'default' => '',
                'type' => 'integer',
                'category' => 'Timer'
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
            'other_comment_mandatory' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Logic'
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
            'hidden' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'hide_tip' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'cssclass' => [
                'default' => '',
                'type' => 'text',
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
            'answer_order' => [
                'default' => 'normal',
                'type' => 'singleselect',
                'options' => ['normal', 'random', 'alphabetical'],
                'category' => 'Display'
            ],
            'other_replace_text' => [
                'default' => '',
                'type' => 'textarea',
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
            'array_filter' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'array_filter_style' => [
                'default' => '0',
                'type' => 'singleselect',
                'options' => ['0', '1'],
                'category' => 'Logic'
            ],
            'array_filter_exclude' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Logic'
            ],
            'other_comment_mandatory' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Logic'
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
            'other_position_code' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ]
        ],
        
        // S - Short Free Text
        \Question::QT_S_SHORT_FREE_TEXT => [
            'hidden' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'hide_tip' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
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
            'em_validation_q_tip' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
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
            'hidden' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'hide_tip' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Display'
            ],
            'cssclass' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'category_separator' => [
                'default' => '',
                'type' => 'text',
                'category' => 'Display'
            ],
            'answer_order' => [
                'default' => 'normal',
                'type' => 'singleselect',
                'options' => ['normal', 'random', 'alphabetical'],
                'category' => 'Display'
            ],
            'em_validation_q_tip' => [
                'default' => '',
                'type' => 'textarea',
                'category' => 'Logic'
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
            'other_comment_mandatory' => [
                'default' => '0',
                'type' => 'switch',
                'options' => ['0', '1'],
                'category' => 'Logic'
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
        
        // Add basic definitions for all other question types
        // These cover the most common attributes tested
        \Question::QT_F_ARRAY => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'array_filter_style' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_Q_MULTIPLE_SHORT_TEXT => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'text_input_width' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'text_input_columns' => ['default' => '', 'type' => 'singleselect', 'options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'label_input_columns' => ['default' => '', 'type' => 'singleselect', 'options' => ['', 'hidden', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_K_MULTIPLE_NUMERICAL => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'equals_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'max_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'min_num_value' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'num_value_int_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Input'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_X_TEXT_DISPLAY => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_Y_YES_NO_RADIO => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_G_GENDER => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_1_ARRAY_DUAL => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_5_POINT_CHOICE => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_D_DATE => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'date_format' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'date_max' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'date_min' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'dropdown_dates' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'dropdown_dates_minute_step' => ['default' => '1', 'type' => 'integer', 'category' => 'Display'],
            'dropdown_dates_month_style' => ['default' => '0', 'type' => 'singleselect', 'options' => ['0', '1', '2'], 'category' => 'Display'],
            'reverse' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        // Add all remaining question types with common attributes
        \Question::QT_A_ARRAY_5_POINT => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_B_ARRAY_10_CHOICE_QUESTIONS => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_C_ARRAY_YES_UNCERTAIN_NO => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_E_ARRAY_INC_SAME_DEC => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_H_ARRAY_COLUMN => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'answer_width_bycolumn' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_I_LANGUAGE => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_O_LIST_WITH_COMMENT => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_order' => ['default' => 'normal', 'type' => 'singleselect', 'options' => ['normal', 'random'], 'category' => 'Display'],
            'other_comment_mandatory' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_position' => ['default' => 'default', 'type' => 'singleselect', 'options' => ['beginning', 'default', 'end', 'specific'], 'category' => 'Display'],
            'other_position_code' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'other_replace_text' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'choice_input_columns' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'commented_checkbox' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'commented_checkbox_auto' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'other_comment_mandatory' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Logic'],
            'other_position' => ['default' => 'default', 'type' => 'singleselect', 'options' => ['beginning', 'default', 'end', 'specific'], 'category' => 'Display'],
            'other_position_code' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_R_RANKING => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'choice_title' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_subquestions' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'rank_title' => ['default' => '', 'type' => 'text', 'category' => 'Other'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_U_HUGE_FREE_TEXT => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'maximum_chars' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'display_rows' => ['default' => '5', 'type' => 'integer', 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_VERTICAL_FILE_UPLOAD => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'max_filesize' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'allowed_filetypes' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_ASTERISK_EQUATION => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_COLON_ARRAY_NUMBERS => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'input_boxes' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'multiflexible_checkbox' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'multiflexible_max' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'multiflexible_min' => ['default' => '', 'type' => 'text', 'category' => 'Input'],
            'multiflexible_step' => ['default' => '1', 'type' => 'integer', 'category' => 'Input'],
            'parent_order' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        \Question::QT_SEMICOLON_ARRAY_TEXT => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'answer_width' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'random_order' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'repeat_headings' => ['default' => '', 'type' => 'integer', 'category' => 'Display'],
            'numbers_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Other'],
            'placeholder' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
        // N - Numerical input
        \Question::QT_N_NUMERICAL => [
            'hidden' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'hide_tip' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Display'],
            'cssclass' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'min_num_value_n' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_num_value_n' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'min_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'max_answers' => ['default' => '', 'type' => 'integer', 'category' => 'Input'],
            'num_value_int_only' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Input'],
            'placeholder' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'prefix' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'printable_help' => ['default' => '', 'type' => 'text', 'category' => 'Display'],
            'public_statistics' => ['default' => '0', 'type' => 'switch', 'options' => ['0', '1'], 'category' => 'Statistics'],
            'em_validation_sq' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_sq_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'random_group' => ['default' => '', 'type' => 'text', 'category' => 'Logic'],
            'em_validation_q' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic'],
            'em_validation_q_tip' => ['default' => '', 'type' => 'textarea', 'category' => 'Logic']
        ],
        
    ];

}
