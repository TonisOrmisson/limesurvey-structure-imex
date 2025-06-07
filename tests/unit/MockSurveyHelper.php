<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

/**
 * Helper class for creating mock survey data for tests
 * 
 * This class provides reusable mock data for testing the StructureImEx plugin
 * including surveys, question groups, questions, and attributes.
 */
class MockSurveyHelper
{
    private static $defaultSurveyId = 123456;

    /**
     * Create a complete mock survey with groups, questions, and attributes
     */
    public static function createMockSurveyData(int $surveyId = null): array
    {
        $surveyId = $surveyId ?? self::$defaultSurveyId;
        
        return [
            'survey' => self::createMockSurvey($surveyId),
            'groups' => self::createMockGroups($surveyId),
            'questions' => self::createMockQuestions($surveyId),
            'attributes' => self::createMockAttributes(),
            'languages' => ['en', 'de']
        ];
    }

    /**
     * Create a mock survey object
     */
    public static function createMockSurvey(int $surveyId = null): object
    {
        $surveyId = $surveyId ?? self::$defaultSurveyId;
        
        $survey = new class {
            public $sid;
            public $active = 'N';
            public $language = 'en';
            public $primaryKey;
            
            public function getPrimaryKey() {
                return $this->primaryKey;
            }
            
            public function getAllLanguages() {
                return ['en', 'de'];
            }
        };
        
        $survey->sid = $surveyId;
        $survey->primaryKey = $surveyId;
        return $survey;
    }

    /**
     * Create mock question groups with multilingual content
     */
    public static function createMockGroups(int $surveyId = null): array
    {
        $surveyId = $surveyId ?? self::$defaultSurveyId;
        $groups = [];
        
        // Group 1 - Demographics 
        $group1 = new class {
            public $gid = 1;
            public $sid;
            public $grelevance = '1';
            public $group_order = 1;
            public $questiongroupl10ns = [];
        };
        $group1->sid = $surveyId;
        $group1->questiongroupl10ns['en'] = (object)[
            'group_name' => 'Demographics',
            'description' => 'Basic demographic questions'
        ];
        $group1->questiongroupl10ns['de'] = (object)[
            'group_name' => 'Demografie',
            'description' => 'Grundlegende demografische Fragen'
        ];
        $groups[] = $group1;
        
        // Group 2 - Preferences
        $group2 = new class {
            public $gid = 2;
            public $sid;
            public $grelevance = '1';
            public $group_order = 2;
            public $questiongroupl10ns = [];
        };
        $group2->sid = $surveyId;
        $group2->questiongroupl10ns['en'] = (object)[
            'group_name' => 'Preferences',
            'description' => 'User preference questions'
        ];
        $group2->questiongroupl10ns['de'] = (object)[
            'group_name' => 'Präferenzen',
            'description' => 'Benutzerpräferenz Fragen'
        ];
        $groups[] = $group2;
        
        return $groups;
    }

    /**
     * Create mock questions with different types and multilingual content
     */
    public static function createMockQuestions(int $surveyId = null): array
    {
        $surveyId = $surveyId ?? self::$defaultSurveyId;
        $questions = [];
        
        // Text question
        $q1 = new class {
            public $qid = 1;
            public $sid;
            public $gid = 1;
            public $type = 'T';
            public $title = 'Q001';
            public $other = 'N';
            public $mandatory = 'N';
            public $question_order = 1;
            public $scale_id = 0;
            public $parent_qid = 0;
            public $relevance = '1';
            public $modulename = '';
            public $question_theme_name = 'core';
            public $questionl10ns = [];
        };
        $q1->sid = $surveyId;
        $q1->questionl10ns['en'] = (object)[
            'question' => 'What is your name?',
            'help' => 'Please enter your full name',
            'script' => ''
        ];
        $q1->questionl10ns['de'] = (object)[
            'question' => 'Wie ist Ihr Name?',
            'help' => 'Bitte geben Sie Ihren vollständigen Namen ein',
            'script' => ''
        ];
        $questions[] = $q1;
        
        // Multiple choice question
        $q2 = new class {
            public $qid = 2;
            public $sid;
            public $gid = 1;
            public $type = 'L';
            public $title = 'Q002';
            public $other = 'N';
            public $mandatory = 'Y';
            public $question_order = 2;
            public $scale_id = 0;
            public $parent_qid = 0;
            public $relevance = '1';
            public $modulename = '';
            public $question_theme_name = 'core';
            public $questionl10ns = [];
        };
        $q2->sid = $surveyId;
        $q2->questionl10ns['en'] = (object)[
            'question' => 'What is your age group?',
            'help' => 'Select your age range',
            'script' => 'console.log("Age question loaded");'
        ];
        $q2->questionl10ns['de'] = (object)[
            'question' => 'Wie alt sind Sie?',
            'help' => 'Wählen Sie Ihre Altersgruppe',
            'script' => 'console.log("Altersfrage geladen");'
        ];
        $questions[] = $q2;
        
        // Numerical question
        $q3 = new class {
            public $qid = 3;
            public $sid;
            public $gid = 2;
            public $type = 'N';
            public $title = 'Q003';
            public $other = 'N';
            public $mandatory = 'N';
            public $question_order = 1;
            public $scale_id = 0;
            public $parent_qid = 0;
            public $relevance = 'Q002.NAOK == "A3"';
            public $modulename = '';
            public $question_theme_name = 'core';
            public $questionl10ns = [];
        };
        $q3->sid = $surveyId;
        $q3->questionl10ns['en'] = (object)[
            'question' => 'How many years of experience do you have?',
            'help' => 'Enter a number between 0 and 50',
            'script' => ''
        ];
        $q3->questionl10ns['de'] = (object)[
            'question' => 'Wie viele Jahre Erfahrung haben Sie?',
            'help' => 'Geben Sie eine Zahl zwischen 0 und 50 ein',
            'script' => ''
        ];
        $questions[] = $q3;
        
        return $questions;
    }

    /**
     * Create mock question attributes for testing attribute export/import
     */
    public static function createMockAttributes(): array
    {
        $attributes = [];
        
        // Attributes for Q002 (multiple choice)
        $attr1 = new class {
            public $qid = 2;
            public $attribute = 'random_order';
            public $value = '1';
        };
        $attributes[] = $attr1;
        
        $attr2 = new class {
            public $qid = 2;
            public $attribute = 'other_option';
            public $value = 'Y';
        };
        $attributes[] = $attr2;
        
        // Attributes for Q003 (numerical)
        $attr3 = new class {
            public $qid = 3;
            public $attribute = 'min_answers';
            public $value = '0';
        };
        $attributes[] = $attr3;
        
        $attr4 = new class {
            public $qid = 3;
            public $attribute = 'max_answers';
            public $value = '50';
        };
        $attributes[] = $attr4;
        
        return $attributes;
    }

    /**
     * Create mock plugin instance for testing (without extending real plugin)
     */
    public static function createMockPlugin($survey): object
    {
        return new class($survey) {
            private $survey;
            
            public function __construct($survey) {
                $this->survey = $survey;
            }
            
            public function getSurvey() {
                return $this->survey;
            }
            
            public function getImportUnknownAttributes() {
                return false;
            }
            
            public function get($key, $scope = null, $id = null, $default = null) {
                return $default;
            }
        };
    }

    /**
     * Set global mock data for TestableExportQuestions to access
     */
    public static function setGlobalMockData(array $mockData): void
    {
        global $mockGroups, $mockQuestions, $mockAttributes;
        
        $mockGroups = $mockData['groups'];
        $mockQuestions = $mockData['questions'];
        $mockAttributes = $mockData['attributes'];
    }

    /**
     * Create a simple survey with minimal data for basic tests
     */
    public static function createSimpleMockSurvey(int $surveyId = null): array
    {
        $surveyId = $surveyId ?? self::$defaultSurveyId;
        
        $survey = self::createMockSurvey($surveyId);
        
        // Single group
        $group = new class {
            public $gid = 1;
            public $sid;
            public $grelevance = '1';
            public $group_order = 1;
            public $questiongroupl10ns = [];
        };
        $group->sid = $surveyId;
        $group->questiongroupl10ns['en'] = (object)[
            'group_name' => 'Test Group',
            'description' => 'Test group description'
        ];
        
        // Single question
        $question = new class {
            public $qid = 1;
            public $sid;
            public $gid = 1;
            public $type = 'T';
            public $title = 'Q001';
            public $other = 'N';
            public $mandatory = 'N';
            public $question_order = 1;
            public $scale_id = 0;
            public $parent_qid = 0;
            public $relevance = '1';
            public $modulename = '';
            public $question_theme_name = 'core';
            public $questionl10ns = [];
        };
        $question->sid = $surveyId;
        $question->questionl10ns['en'] = (object)[
            'question' => 'Test question?',
            'help' => 'Test help text',
            'script' => ''
        ];
        
        return [
            'survey' => $survey,
            'groups' => [$group],
            'questions' => [$question],
            'attributes' => [],
            'languages' => ['en']
        ];
    }
}
