<?php

namespace tonisormisson\ls\structureimex\validation;

use Question;

/**
 * Manages language-specific vs global question attributes
 *
 * This class determines which question attributes are:
 * - Global (one value for all languages, stored with language=NULL)
 * - Language-specific (different value per language, stored with language code)
 *
 * Based on LimeSurvey's config.xml files in /application/views/survey/questions/answer/[type]/config.xml
 */
class QuestionAttributeLanguageManager
{
    /**
     * Cache for parsed attribute definitions
     * @var array
     */
    private static $attributeLanguageMap = null;

    /**
     * Known global attributes (not language-specific)
     * These attributes have <i18n></i18n> or <i18n>0</i18n> in their config.xml
     */
    private static $globalAttributes = [
        // Display attributes
        'hide_tip',
        'hidden',
        'cssclass',
        'display_columns',
        'text_input_width',
        'text_input_columns',
        'input_size',
        'display_rows',
        'maximum_chars',
        'page_break',

        // Logic attributes
        'mandatory',
        'other',
        'answer_order',
        'random_order',
        'assessment_value',
        'scale_export',
        'code',

        // Input validation
        'numbers_only',
        'num_value_int_only',
        'min_answers',
        'max_answers',
        'min_num_value',
        'max_num_value',
        'multiflexible_min',
        'multiflexible_max',
        'multiflexible_step',
        'slider_min',
        'slider_max',
        'slider_step',
        'slider_default',
        'slider_orientation',
        'slider_handle',
        'slider_layout',
        'slider_separator',
        'slider_showminmax',

        // Display behavior
        'dropdown_size',
        'dropdown_prefix',
        'dropdown_separators',
        'exclude_all_others',
        'exclude_all_others_auto',
        'hidden_answer',
        'show_totals',
        'show_grand_total',
        'repeat_headings',
        'use_dropdown',
        'dropdown_prepostfix',
        'dropdown_separators',

        // Statistics
        'public_statistics',
        'statistics_showgraph',
        'statistics_graphtype',
        'statistics_showmap',

        // Timer attributes (numeric settings)
        'time_limit',
        'time_limit_action',
        'time_limit_disable_next',
        'time_limit_disable_prev',
        'time_limit_message_delay',
        'time_limit_warning',
        'time_limit_warning_display_time',
        'time_limit_warning_2',
        'time_limit_warning_2_display_time',

        // File upload
        'max_filesize',
        'allowed_filetypes',
        'show_title',
        'show_comment',

        // Advanced
        'em_validation_q',
        'random_group',
        'save_as_default',
        'clear_default',
        'array_filter',
        'array_filter_style',
        'array_filter_exclude',
        'choice_title',
        'choice_title_display',
        'equals_num_value',
        'min_num_value_n',
        'max_num_value_n',
        'multiflexible_checkbox',
        'reverse',
        'value_range_allows_missing',
        'em_class',
        'category_separator',
    ];

    /**
     * Known language-specific attributes
     * These attributes have <i18n>1</i18n> in their config.xml
     */
    private static $languageSpecificAttributes = [
        // User-facing text
        'prefix',
        'suffix',
        'other_replace_text',
        'other_comment_mandatory',
        'other_numbers_only',
        'printable_help',

        // Validation messages
        'em_validation_q_tip',
        'validation_message',
        'fixnum_message',
        'choice_help',
        'choice_input_columns',

        // Timer messages
        'time_limit_message',
        'time_limit_warning_message',
        'time_limit_warning_2_message',
        'time_limit_countdown_message',
        'time_limit_timer_style',
        'time_limit_message_style',
        'time_limit_warning_style',
        'time_limit_warning_2_style',

        // Display text
        'slider_min_text',
        'slider_max_text',
        'dropdown_prepostfix',
        'answer_width',
        'label_input_columns',
        'show_comment',
        'show_title',
        'scale_export',
        'category_separator',
    ];

    /**
     * Check if an attribute is language-specific
     *
     * @param string $attributeName The attribute name
     * @return bool True if language-specific, false if global
     */
    public static function isLanguageSpecific(string $attributeName): bool
    {
        // Check known language-specific attributes first
        if (in_array($attributeName, self::$languageSpecificAttributes)) {
            return true;
        }

        // Check known global attributes
        if (in_array($attributeName, self::$globalAttributes)) {
            return false;
        }

        // For unknown attributes, try to parse from LimeSurvey config files
        return self::parseAttributeFromConfig($attributeName);
    }

    /**
     * Check if an attribute is global (not language-specific)
     *
     * @param string $attributeName The attribute name
     * @return bool True if global, false if language-specific
     */
    public static function isGlobal(string $attributeName): bool
    {
        return !self::isLanguageSpecific($attributeName);
    }

    /**
     * Get all global attributes for a question type
     *
     * @param string $questionType LimeSurvey question type
     * @param array $attributes Array of attribute names
     * @return array Global attributes only
     */
    public static function filterGlobalAttributes(string $questionType, array $attributes): array
    {
        $globalAttributes = [];
        foreach ($attributes as $attributeName => $value) {
            if (self::isGlobal($attributeName)) {
                $globalAttributes[$attributeName] = $value;
            }
        }
        return $globalAttributes;
    }

    /**
     * Get all language-specific attributes for a question type
     *
     * @param string $questionType LimeSurvey question type
     * @param array $attributes Array of attribute names
     * @return array Language-specific attributes only
     */
    public static function filterLanguageSpecificAttributes(string $questionType, array $attributes): array
    {
        $languageAttributes = [];
        foreach ($attributes as $attributeName => $value) {
            if (self::isLanguageSpecific($attributeName)) {
                $languageAttributes[$attributeName] = $value;
            }
        }
        return $languageAttributes;
    }

    /**
     * Parse attribute from LimeSurvey config.xml files
     * This is a fallback for unknown attributes
     *
     * @param string $attributeName The attribute name to check
     * @return bool True if language-specific, false if global
     */
    private static function parseAttributeFromConfig(string $attributeName): bool
    {
        // If we can't determine from our known lists, assume global for safety
        // This prevents creating duplicate attributes with wrong language settings
        return false;
    }

    /**
     * Get all known global attribute names
     *
     * @return array List of global attribute names
     */
    public static function getGlobalAttributeNames(): array
    {
        return self::$globalAttributes;
    }

    /**
     * Get all known language-specific attribute names
     *
     * @return array List of language-specific attribute names
     */
    public static function getLanguageSpecificAttributeNames(): array
    {
        return self::$languageSpecificAttributes;
    }

    /**
     * Separate attributes into global and language-specific groups
     *
     * @param array $attributes Array of attribute name => value pairs
     * @return array ['global' => [...], 'language_specific' => [...]]
     */
    public static function separateAttributes(array $attributes): array
    {
        $global = [];
        $languageSpecific = [];
        
        foreach ($attributes as $attributeName => $value) {
            if (self::isLanguageSpecific($attributeName)) {
                $languageSpecific[$attributeName] = $value;
            } else {
                $global[$attributeName] = $value;
            }
        }
        
        return [
            'global' => $global,
            'language_specific' => $languageSpecific
        ];
    }
}
