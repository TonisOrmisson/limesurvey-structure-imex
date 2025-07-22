<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Survey;
use Question;
use QuestionGroup;
use QuestionAttribute;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Functional test for validating that imported survey data is correctly stored in the database
 * This test focuses on verifying the database state after survey imports
 */
class ImportDataValidationTest extends DatabaseTestCase
{
    private $importedSurveyId;
    private $testGroupId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Import the blank survey for testing
        $blankSurveyPath = $this->getBlankSurveyPath();
        $this->importedSurveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        // Create a test group for questions
        $this->testGroupId = $this->createValidationTestGroup();
    }
    
    private function createValidationTestGroup(): int
    {
        $group = new QuestionGroup();
        $group->sid = $this->importedSurveyId;
        $group->group_name = 'Test Group';
        $group->description = 'Test group for functional tests';
        $group->group_order = 1;
        $group->language = 'en';
        $group->grelevance = '1';
        
        if (!$group->save()) {
            throw new \Exception('Failed to create test group: ' . print_r($group->getErrors(), true));
        }
        
        return $group->gid;
    }

    /**
     * Test that we can verify basic survey structure in database
     */
    public function testSurveyBasicStructure()
    {

        // Verify survey exists
        $survey = Survey::model()->findByPk($this->importedSurveyId);
        $this->assertNotNull($survey, 'Survey should exist in database');
        
        // Test basic survey properties
        $this->assertIsInt($survey->sid, 'Survey ID should be integer');
        $this->assertNotEmpty($survey->admin, 'Survey should have admin');
        $this->assertEquals('N', $survey->active, 'Survey should not be active by default');
        
        // Verify we can query questions for this survey
        $questions = Question::model()->findAll('sid = :sid', [':sid' => $this->importedSurveyId]);
        $this->assertIsArray($questions, 'Should be able to query questions');
        
        // Verify we can query groups for this survey
        $groups = QuestionGroup::model()->findAll('sid = :sid', [':sid' => $this->importedSurveyId]);
        $this->assertIsArray($groups, 'Should be able to query question groups');
    }

    /**
     * Test question type validation - verify different question types can be handled
     */
    public function testQuestionTypeHandling()
    {

        // Test that LimeSurvey supports the question types we want to test
        $supportedTypes = ['T', 'U', 'N', 'L', 'M', 'F', 'D', 'S', 'K'];
        
        foreach ($supportedTypes as $type) {
            // Create a test question of each type to verify the database supports it
            $question = new Question();
            $question->sid = $this->importedSurveyId;
            $question->gid = $this->testGroupId;
            $question->type = $type;
            $question->title = "TEST" . $type . substr(md5(uniqid()), 0, 8);
            $question->question_order = 1;
            $question->scale_id = 0;
            $question->parent_qid = 0;
            $question->mandatory = 'N';
            $question->other = 'N';
            $question->relevance = '1';
            $question->same_default = 0;
            $question->same_script = 0;
            
            $saved = $question->save();
            $this->assertTrue($saved, "Should be able to save question type {$type}");
            
            if ($saved) {
                // Verify we can read it back
                $savedQuestion = Question::model()->findByPk($question->qid);
                $this->assertNotNull($savedQuestion, "Should be able to read back question type {$type}");
                $this->assertEquals($type, $savedQuestion->type, "Question type should be preserved for {$type}");
                
                // Clean up
                $savedQuestion->delete();
            }
        }
    }

    /**
     * Test question attribute handling
     */
    public function testQuestionAttributeHandling()
    {

        // Create a test question
        $question = new Question();
        $question->sid = $this->importedSurveyId;
        $question->gid = $this->testGroupId;
        $question->type = 'T'; // Text question
        $question->title = "TESTATTR" . substr(md5(uniqid()), 0, 8);
        $question->question_order = 1;
        $question->scale_id = 0;
        $question->parent_qid = 0;
        $question->mandatory = 'N';
        $question->other = 'N';
        $question->relevance = '1';
        $question->same_default = 0;
        $question->same_script = 0;
        
        $this->assertTrue($question->save(), 'Should be able to save test question');
        
        // Test common question attributes
        $testAttributes = [
            'max_chars' => '100',
            'input_size' => '30',
            'regex_validation' => '^[a-zA-Z]+$',
            'hidden' => '0',
            'mandatory' => 'Y'
        ];
        
        foreach ($testAttributes as $attrName => $attrValue) {
            $attribute = new QuestionAttribute();
            $attribute->qid = $question->qid;
            $attribute->attribute = $attrName;
            $attribute->value = $attrValue;
            $attribute->language = 'en';
            
            $saved = $attribute->save();
            $this->assertTrue($saved, "Should be able to save attribute {$attrName}");
            
            if ($saved) {
                // Verify we can read it back
                $savedAttr = QuestionAttribute::model()->find(
                    'qid = :qid AND attribute = :attr',
                    [':qid' => $question->qid, ':attr' => $attrName]
                );
                $this->assertNotNull($savedAttr, "Should be able to read back attribute {$attrName}");
                $this->assertEquals($attrValue, $savedAttr->value, "Attribute value should be preserved for {$attrName}");
            }
        }
        
        // Clean up
        $question->delete();
    }

    /**
     * Test that LimeSurvey's question localization system works
     */
    public function testQuestionLocalizationSystem()
    {

        // Check if we can access the question localization table
        $db = $this->getDb();
        
        // Test that we can query the localization table - this should not fail
        $result = $db->createCommand('SELECT COUNT(*) FROM lime_question_l10ns')->queryScalar();
        $this->assertIsNumeric($result, 'Should be able to query question localization table');
        
        // Test basic localization structure
        $tables = $db->createCommand('SHOW TABLES LIKE "lime_question%"')->queryAll();
        $tableNames = array_values(array_map('array_values', $tables));
        $tableNames = array_merge(...$tableNames); // Flatten the array
        
        $this->assertContains('lime_questions', $tableNames, 'Should have main questions table');
        $this->assertContains('lime_question_l10ns', $tableNames, 'Should have question localization table');
    }

    /**
     * Test database table structure compatibility
     */
    public function testDatabaseTableStructure()
    {

        $db = $this->getDb();
        
        // Verify essential tables exist
        $requiredTables = [
            'lime_surveys',
            'lime_questions', 
            'lime_question_l10ns',
            'lime_question_attributes',
            'lime_groups',
            'lime_answers'
        ];
        
        foreach ($requiredTables as $table) {
            $exists = $db->createCommand()
                ->select('COUNT(*)')
                ->from('information_schema.tables')
                ->where('table_schema = :schema AND table_name = :table', [
                    ':schema' => $db->createCommand('SELECT DATABASE()')->queryScalar(),
                    ':table' => $table
                ])
                ->queryScalar();
                
            $this->assertEquals(1, $exists, "Table {$table} should exist");
        }
        
        // Verify key table structures
        $questionsCols = $db->createCommand('DESCRIBE lime_questions')->queryAll();
        $questionColNames = array_column($questionsCols, 'Field');
        
        $requiredQuestionCols = ['qid', 'sid', 'gid', 'type', 'title', 'question_order', 'mandatory'];
        foreach ($requiredQuestionCols as $col) {
            $this->assertContains($col, $questionColNames, "Questions table should have {$col} column");
        }
    }

    /**
     * Test that we can create questions with complex structures (for future import testing)
     */
    public function testComplexQuestionStructures()
    {

        // Test creating a question with subquestions (for array/multiple choice questions)
        $parentQuestion = new Question();
        $parentQuestion->sid = $this->importedSurveyId;
        $parentQuestion->gid = $this->testGroupId;
        $parentQuestion->type = 'M'; // Multiple choice
        $parentQuestion->title = "PARENT" . substr(md5(uniqid()), 0, 8);
        $parentQuestion->question_order = 1;
        $parentQuestion->scale_id = 0;
        $parentQuestion->parent_qid = 0;
        $parentQuestion->mandatory = 'N';
        $parentQuestion->other = 'N';
        $parentQuestion->relevance = '1';
        $parentQuestion->same_default = 0;
        $parentQuestion->same_script = 0;
        
        $this->assertTrue($parentQuestion->save(), 'Should be able to save parent question');
        
        // Create a subquestion
        $subQuestion = new Question();
        $subQuestion->sid = $this->importedSurveyId;
        $subQuestion->gid = $this->testGroupId;
        $subQuestion->type = 'M';
        $subQuestion->title = "SQ001";
        $subQuestion->question_order = 1;
        $subQuestion->scale_id = 0;
        $subQuestion->parent_qid = $parentQuestion->qid; // This makes it a subquestion
        $subQuestion->mandatory = 'N';
        $subQuestion->other = 'N';
        $subQuestion->relevance = '1';
        $subQuestion->same_default = 0;
        $subQuestion->same_script = 0;
        
        $this->assertTrue($subQuestion->save(), 'Should be able to save subquestion');
        
        // Verify the relationship
        $foundSubquestion = Question::model()->find(
            'sid = :sid AND parent_qid = :parent_qid',
            [':sid' => $this->importedSurveyId, ':parent_qid' => $parentQuestion->qid]
        );
        
        $this->assertNotNull($foundSubquestion, 'Should be able to find subquestion by parent');
        $this->assertEquals($subQuestion->qid, $foundSubquestion->qid, 'Found subquestion should match created one');
        
        // Clean up
        $subQuestion->delete();
        $parentQuestion->delete();
    }

}
