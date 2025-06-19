<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;
use QuestionL10n;
use Survey;
use QuestionGroup;

/**
 * CRITICAL TEST: Verify that import actually changes the database
 * 
 * This test creates real questions, exports them, modifies the export file,
 * imports it back, and verifies the database was actually changed.
 */
class RealDatabaseImportTest extends DatabaseTestCase
{
    private $plugin;
    protected $testSurveyId;
    private $testQuestionId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Import a real survey
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        
        // Create a real plugin instance
        $this->plugin = $this->createRealPlugin($this->testSurveyId);
        
        // Create a test question in the database
        $this->testQuestionId = $this->createTestQuestionInDatabase();
        
    }

    /**
     * TEST: Verify that importing attributes actually changes the database
     */
    public function testImportActuallyChangesDatabase()
    {
        // Step 1: Verify initial state in database
        $initialHideTip = $this->getQuestionAttributeFromDatabase($this->testQuestionId, 'hide_tip');
        $initialAnswerOrder = $this->getQuestionAttributeFromDatabase($this->testQuestionId, 'answer_order');
        

        // Check if attributes exist at all
        $allAttributes = QuestionAttribute::model()->findAll([
            'condition' => 'qid = :qid',
            'params' => [':qid' => $this->testQuestionId]
        ]);

        $this->assertNotNull($initialHideTip, 'hide_tip attribute should exist in database');
        $this->assertEquals('0', $initialHideTip, 'Initial hide_tip should be 0');
        $this->assertEquals('normal', $initialAnswerOrder, 'Initial answer_order should be normal');
        
        // Step 2: Create a CSV import file with different values
        $csvContent = $this->createImportCSVWithChangedAttributes();
        $csvFile = $this->writeCSVToTempFile($csvContent);
        
        // Step 3: Import the file
        $this->performActualImport($csvFile);
        
        // Step 4: Verify database was actually changed
        $newHideTip = $this->getQuestionAttributeFromDatabase($this->testQuestionId, 'hide_tip');
        $newAnswerOrder = $this->getQuestionAttributeFromDatabase($this->testQuestionId, 'answer_order');
        
        // CRITICAL ASSERTIONS: Database must be changed
        $this->assertEquals('1', $newHideTip, 'hide_tip should be changed to 1 in database');
        $this->assertEquals('random', $newAnswerOrder, 'answer_order should be changed to random in database');
        
        // Clean up
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
    }

    /**
     * Create a test question in the database with known attributes
     */
    private function createTestQuestionInDatabase()
    {
        $survey = Survey::model()->findByPk($this->testSurveyId);
        
        // Get or create a question group
        $groups = $survey->groups;
        if (empty($groups)) {
            $group = new QuestionGroup();
            $group->sid = $survey->sid;
            $group->group_name = 'Test Group';
            $group->group_order = 1;
            $group->save();
        } else {
            $group = $groups[0];
        }
        
        // Create question
        $question = new Question();
        $question->sid = $survey->sid;
        $question->gid = $group->gid;
        $question->type = 'L'; // List (Radio)
        $question->title = 'TestQImport';
        $question->mandatory = 'N';
        $question->question_order = 1;
        $result = $question->save();
        

        // Create localized content if needed
        if (class_exists('QuestionL10n')) {
            $questionL10n = new QuestionL10n();
            $questionL10n->qid = $question->qid;
            $questionL10n->language = $survey->language ?? 'en';
            $questionL10n->question = 'Test import question';
            $questionL10n->help = '';
            $questionL10n->save();
        }
        
        // Set initial attribute values
        $this->setQuestionAttributeInDatabase($question->qid, 'hide_tip', '0');
        $this->setQuestionAttributeInDatabase($question->qid, 'answer_order', 'normal');
        
        return $question->qid;
    }
    
    /**
     * Get question attribute value from database
     */
    private function getQuestionAttributeFromDatabase($qid, $attributeName)
    {
        $attribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attribute',
            'params' => [':qid' => $qid, ':attribute' => $attributeName]
        ]);
        
        return $attribute ? $attribute->value : null;
    }
    
    /**
     * Set question attribute value in database
     */
    private function setQuestionAttributeInDatabase($qid, $attributeName, $value)
    {
        // Delete existing
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attribute',
            'params' => [':qid' => $qid, ':attribute' => $attributeName]
        ]);
        
        // Create new
        $attribute = new QuestionAttribute();
        $attribute->qid = $qid;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->save();
    }
    
    /**
     * Create CSV content with changed attribute values
     */
    private function createImportCSVWithChangedAttributes()
    {
        // Get the survey language
        $survey = Survey::model()->findByPk($this->testSurveyId);
        $lang = $survey->language ?? 'en';
        
        // Create a CSV with proper language-specific columns
        $csvLines = [
            // Header with language-specific columns
            "type,subtype,code,value-{$lang},help-{$lang},script-{$lang},relevance,mandatory,theme,options",
            // Group first (required for questions)
            "G,,TestGroup,\"Test Group\",\"\",\"\",1,,,",
            // Question with changed attributes
            "Q,L,TestQImport,\"Test import question\",\"\",\"\",1,N,\"\",\"{\"\"hide_tip\"\":\"\"1\"\",\"\"answer_order\"\":\"\"random\"\"}\""
        ];
        
        return implode("\n", $csvLines);
    }
    
    /**
     * Write CSV content to temporary file
     */
    private function writeCSVToTempFile($csvContent)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
        file_put_contents($tempFile, $csvContent);
        return $tempFile;
    }
    
    /**
     * Perform the actual import using the plugin
     */
    private function performActualImport($csvFile)
    {
        // This should simulate what happens when user imports a file
        $survey = Survey::model()->findByPk($this->testSurveyId);
        
        // Use the import class directly
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($this->plugin, $survey);
        
        // Set the file
        $import->fileName = $csvFile;
        
        // Prepare first to validate structure
        $prepareResult = $import->prepare();
        if (!$prepareResult) {
            $errors = $import->getErrors();
            if (!empty($errors)) {
                $errorMessage = 'Import prepare failed with errors: ';
                foreach ($errors as $field => $fieldErrors) {
                    foreach ((array)$fieldErrors as $error) {
                        $errorMessage .= "$field: $error; ";
                    }
                }
                $this->fail($errorMessage);
            }
        }
        
        // Process the import
        $result = $import->process();
        
        // Check for errors
        $errors = $import->getErrors();
        if (!empty($errors)) {
            $errorMessage = 'Import failed with errors: ';
            foreach ($errors as $field => $fieldErrors) {
                foreach ((array)$fieldErrors as $error) {
                    $errorMessage .= "$field: $error; ";
                }
            }
            $this->fail($errorMessage);
        }
    }
}
