<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

/**
 * Helper class to create mock quota data for unit testing
 * Provides realistic quota structures without requiring database access
 */
class MockQuotaHelper
{
    /**
     * Create mock quota data with various quota configurations
     * @return array Array of quota data structures
     */
    public static function createMockQuotaData(): array
    {
        return [
            'quotas' => [
                'male_quota' => self::createGenderQuota(),
                'age_quota' => self::createAgeQuota(), 
                'complex_quota' => self::createComplexQuota(),
                'multiLanguage_quota' => self::createMultiLanguageQuota()
            ],
            'survey' => [
                'sid' => 123456,
                'language' => 'en',
                'additional_languages' => 'de fr'
            ]
        ];
    }

    /**
     * Create a simple gender-based quota
     */
    private static function createGenderQuota(): array
    {
        return [
            'core' => [
                'name' => 'Male Participants',
                'qlimit' => 100,
                'action' => 1, // Terminate after related visible question
                'active' => 1,
                'autoload_url' => 0
            ],
            'members' => [
                [
                    'qid' => 1001,
                    'question_code' => 'gender',
                    'answer_code' => 'M'
                ]
            ],
            'language_settings' => [
                'en' => [
                    'quotals_name' => 'Male Participants',
                    'quotals_message' => 'Sorry, the quota for male participants has been reached.',
                    'quotals_url' => '',
                    'quotals_urldescrip' => ''
                ]
            ]
        ];
    }

    /**
     * Create an age-based quota with auto-redirect
     */
    private static function createAgeQuota(): array
    {
        return [
            'core' => [
                'name' => 'Young Adults 18-25',
                'qlimit' => 50,
                'action' => 2, // Soft terminate
                'active' => 1,
                'autoload_url' => 1
            ],
            'members' => [
                [
                    'qid' => 1002,
                    'question_code' => 'age_group',
                    'answer_code' => '1'
                ]
            ],
            'language_settings' => [
                'en' => [
                    'quotals_name' => 'Young Adults',
                    'quotals_message' => 'The quota for young adults has been reached. You will be redirected to a thank you page.',
                    'quotals_url' => 'https://example.com/thanks',
                    'quotals_urldescrip' => 'Thank you page'
                ]
            ]
        ];
    }

    /**
     * Create a quota with multiple conditions (OR logic)
     */
    private static function createComplexQuota(): array
    {
        return [
            'core' => [
                'name' => 'Education-based Quota',
                'qlimit' => 75,
                'action' => 3, // Terminate after visible and hidden questions
                'active' => 1,
                'autoload_url' => 0
            ],
            'members' => [
                [
                    'qid' => 1003,
                    'question_code' => 'education',
                    'answer_code' => '1'
                ],
                [
                    'qid' => 1003,
                    'question_code' => 'education', 
                    'answer_code' => '2'
                ],
                [
                    'qid' => 1003,
                    'question_code' => 'education',
                    'answer_code' => '3'
                ]
            ],
            'language_settings' => [
                'en' => [
                    'quotals_name' => 'Education-based Quota',
                    'quotals_message' => 'The quota for participants with your education level has been reached.',
                    'quotals_url' => '',
                    'quotals_urldescrip' => ''
                ]
            ]
        ];
    }

    /**
     * Create a multi-language quota
     */
    private static function createMultiLanguageQuota(): array
    {
        return [
            'core' => [
                'name' => 'Premium Subscribers',
                'qlimit' => 200,
                'action' => 1,
                'active' => 1,
                'autoload_url' => 1
            ],
            'members' => [
                [
                    'qid' => 1004,
                    'question_code' => 'subscription_type',
                    'answer_code' => 'premium'
                ]
            ],
            'language_settings' => [
                'en' => [
                    'quotals_name' => 'Premium Subscribers',
                    'quotals_message' => 'The quota for premium subscribers has been reached. Thank you for your interest!',
                    'quotals_url' => 'https://example.com/premium-thanks',
                    'quotals_urldescrip' => 'Premium thank you page'
                ],
                'de' => [
                    'quotals_name' => 'Premium-Abonnenten',
                    'quotals_message' => 'Die Quote für Premium-Abonnenten wurde erreicht. Vielen Dank für Ihr Interesse!',
                    'quotals_url' => 'https://example.com/premium-danke',
                    'quotals_urldescrip' => 'Premium-Dankesseite'
                ],
                'fr' => [
                    'quotals_name' => 'Abonnés Premium',
                    'quotals_message' => 'Le quota pour les abonnés premium a été atteint. Merci de votre intérêt!',
                    'quotals_url' => 'https://example.com/premium-merci',
                    'quotals_urldescrip' => 'Page de remerciement premium'
                ]
            ]
        ];
    }

    /**
     * Create expected export data structure for testing (hierarchical format)
     * @return array Expected CSV/Excel export format
     */
    public static function createExpectedExportData(): array
    {
        return [
            // Male Quota (Q) + Member (QM)
            [
                'type' => 'Q',
                'name' => 'Male Participants',
                'value' => 100,
                'active' => 1,
                'autoload_url' => 0,
                'message-en' => 'Sorry, the quota for male participants has been reached.',
                'url-en' => '',
                'url_description-en' => ''
            ],
            [
                'type' => 'QM',
                'name' => 'gender',
                'value' => 'M',
                'active' => '',
                'autoload_url' => '',
                'message-en' => '',
                'url-en' => '',
                'url_description-en' => ''
            ],
            // Age Quota (Q) + Member (QM)
            [
                'type' => 'Q',
                'name' => 'Young Adults 18-25',
                'value' => 50,
                'active' => 1,
                'autoload_url' => 1,
                'message-en' => 'The quota for young adults has been reached. You will be redirected to a thank you page.',
                'url-en' => 'https://example.com/thanks',
                'url_description-en' => 'Thank you page'
            ],
            [
                'type' => 'QM',
                'name' => 'age_group',
                'value' => '1',
                'active' => '',
                'autoload_url' => '',
                'message-en' => '',
                'url-en' => '',
                'url_description-en' => ''
            ],
            // Education Quota (Q) + Members (QM)
            [
                'type' => 'Q',
                'name' => 'Education-based Quota',
                'value' => 75,
                'active' => 1,
                'autoload_url' => 0,
                'message-en' => 'The quota for participants with your education level has been reached.',
                'url-en' => '',
                'url_description-en' => ''
            ],
            [
                'type' => 'QM',
                'name' => 'education',
                'value' => '1',
                'active' => '',
                'autoload_url' => '',
                'message-en' => '',
                'url-en' => '',
                'url_description-en' => ''
            ],
            [
                'type' => 'QM',
                'name' => 'education',
                'value' => '2',
                'active' => '',
                'autoload_url' => '',
                'message-en' => '',
                'url-en' => '',
                'url_description-en' => ''
            ],
            [
                'type' => 'QM',
                'name' => 'education',
                'value' => '3',
                'active' => '',
                'autoload_url' => '',
                'message-en' => '',
                'url-en' => '',
                'url_description-en' => ''
            ]
        ];
    }

    /**
     * Create mock questions data for quota testing
     * @return array Question data that matches quota conditions
     */
    public static function createMockQuestionsForQuotas(): array
    {
        return [
            1001 => [
                'qid' => 1001,
                'title' => 'gender',
                'question' => 'What is your gender?',
                'type' => 'L', // List radio
                'answers' => [
                    'M' => 'Male',
                    'F' => 'Female',
                    'O' => 'Other'
                ]
            ],
            1002 => [
                'qid' => 1002,
                'title' => 'age_group',
                'question' => 'What is your age group?',
                'type' => 'L',
                'answers' => [
                    '1' => '18-25 years',
                    '2' => '26-35 years', 
                    '3' => '36-45 years',
                    '4' => '46+ years'
                ]
            ],
            1003 => [
                'qid' => 1003,
                'title' => 'education',
                'question' => 'What is your highest education level?',
                'type' => 'L',
                'answers' => [
                    '1' => 'High School',
                    '2' => 'Bachelor Degree',
                    '3' => 'Master Degree',
                    '4' => 'PhD'
                ]
            ],
            1004 => [
                'qid' => 1004,
                'title' => 'subscription_type',
                'question' => 'What type of subscription do you have?',
                'type' => 'L',
                'answers' => [
                    'basic' => 'Basic Plan',
                    'premium' => 'Premium Plan',
                    'enterprise' => 'Enterprise Plan'
                ]
            ]
        ];
    }

    /**
     * Create import test data (CSV rows)
     * @return array CSV-style data for import testing
     */
    public static function createImportTestData(): array
    {
        return [
            // Header row
            [
                'type', 'name', 'value', 'active', 'autoload_url',
                'message-en', 'url-en', 'url_description-en'
            ],
            // Test Quota (Q) + Member (QM)
            [
                'Q', 'Test Quota', 50, 1, 0,
                'Quota reached.', '', ''
            ],
            [
                'QM', 'gender', 'M', '', '',
                '', '', ''
            ],
            // Age Quota (Q) + Members (QM)
            [
                'Q', 'Age Quota', 100, 1, 1,
                'Age quota reached.', 'https://example.com/thanks', 'Thanks'
            ],
            [
                'QM', 'age_group', '1', '', '',
                '', '', ''
            ],
            [
                'QM', 'age_group', '2', '', '',
                '', '', ''
            ]
        ];
    }
}