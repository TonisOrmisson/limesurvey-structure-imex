<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;

/**
 * CRITICAL TEST: Verify import functionality actually changes database
 * 
 * This test creates a question, sets initial attributes, then imports 
 * a CSV with different attribute values to verify the database is changed.
 */
class ImportDatabaseTest extends DatabaseTestCase
{
    protected $testSurveyId;
    protected $testQuestionId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Import a survey and create a test question
        $blankSurveyPath = __DIR__ . '/../support/data/surveys/blank-survey.lss';
        $this->testSurveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        // Create a test question
        $question = $this->createQuestionWithGroup($this->testSurveyId);
        $this->testQuestionId = $question->qid;
        
        // Set initial attribute values
        $this->setQuestionAttribute($this->testQuestionId, 'hide_tip', '0');
        $this->setQuestionAttribute($this->testQuestionId, 'answer_order', 'normal');
    }
    
    /**
     * Test that import actually changes question attributes in database
     */
    public function testImportChangesQuestionAttributes()
    {
        // Step 1: Verify initial state
        $initialHideTip = $this->getQuestionAttribute($this->testQuestionId, 'hide_tip');
        $initialAnswerOrder = $this->getQuestionAttribute($this->testQuestionId, 'answer_order');
        
        $this->assertEquals('0', $initialHideTip, 'Initial hide_tip should be 0');
        $this->assertEquals('normal', $initialAnswerOrder, 'Initial answer_order should be normal');
        echo "SUCCESS: Initial attributes verified\n";
        
        // Step 2: Create import CSV with changed values
        $csvContent = $this->createImportCSV();
        $csvFile = $this->writeTempCSV($csvContent);
        // Step 3: Perform import
        try {
            $this->performImport($csvFile);
        } catch (\Exception $e) {
            echo "ERROR: Import failed with exception: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            $this->fail('Import should not throw exceptions: ' . $e->getMessage());
        }
        
        // Step 4: CRITICAL - Verify database was actually changed
        $newHideTip = $this->getQuestionAttribute($this->testQuestionId, 'hide_tip');
        $newAnswerOrder = $this->getQuestionAttribute($this->testQuestionId, 'answer_order');
        
        echo "DEBUG: After import - hide_tip: '$newHideTip', answer_order: '$newAnswerOrder'\n";
        
        // CRITICAL ASSERTIONS - These must pass for import to be working
        $this->assertEquals('1', $newHideTip, 'CRITICAL: hide_tip should be changed to 1 after import');
        $this->assertEquals('random', $newAnswerOrder, 'CRITICAL: answer_order should be changed to random after import');
        
        // Clean up
        unlink($csvFile);
        
        echo "SUCCESS: Import actually changed database attributes!\n";
    }
    
    /**
     * Create a question with a group for testing
     */
    protected function createQuestionWithGroup($surveyId)
    {
        $survey = \Survey::model()->findByPk($surveyId);
        
        // Create a question group
        $group = new \QuestionGroup();
        $group->sid = $survey->sid;
        $group->group_name = 'Test Group';
        $group->group_order = 1;
        $group->save();
        
        // Create question using the parent method which handles L10n properly
        $question = new Question();
        $question->sid = $survey->sid;
        $question->gid = $group->gid;
        $question->type = 'L';
        $question->title = 'TestQ1';
        $question->mandatory = 'N';
        $question->question_order = 1;
        $question->relevance = '1';
        $question->parent_qid = 0;
        $question->scale_id = 0;
        $question->other = 'N';
        
        if (!$question->save()) {
            throw new \Exception('Failed to create question: ' . print_r($question->getErrors(), true));
        }
        
        // Create question localization
        if (class_exists('QuestionL10n')) {
            $questionL10n = new \QuestionL10n();
            $questionL10n->qid = $question->qid;
            $questionL10n->language = $survey->language ?? 'en';
            $questionL10n->question = 'Test Question';
            $questionL10n->help = '';
            $questionL10n->save();
        }
        
        return $question;
    }
    
    /**
     * Set question attribute value
     */
    private function setQuestionAttribute($qid, $attributeName, $value)
    {
        // Delete existing attribute
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr',
            'params' => [':qid' => $qid, ':attr' => $attributeName]
        ]);
        
        // Create new attribute
        $attribute = new QuestionAttribute();
        $attribute->qid = $qid;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $result = $attribute->save();
        
        if (!$result) {
            throw new \Exception("Failed to set attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
    
    /**
     * Get question attribute value
     */
    private function getQuestionAttribute($qid, $attributeName)
    {
        $attribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr',
            'params' => [':qid' => $qid, ':attr' => $attributeName]
        ]);
        
        return $attribute ? $attribute->value : null;
    }
    
    /**
     * Create import CSV content with changed attribute values
     */
    private function createImportCSV()
    {
        $csvLines = [
            // Header
            'type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options',
            // Group row first (required)
            'G,,TestGroup,"Test Group","","",1,,,',
            // Question with changed attributes - JSON format
            'Q,L,TestQ1,"Test Question","","",1,N,"","{\"hide_tip\":\"1\",\"answer_order\":\"random\"}"'
        ];
        
        return implode("\n", $csvLines);
    }
    
    /**
     * Write CSV content to temporary file
     */
    private function writeTempCSV($content)
    {
        // Use unique filename with microtime to avoid any caching issues
        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_' . microtime(true) . '_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
    
    /**
     * Perform the actual import
     */
    private function performImport($csvFile)
    {
        // Create completely fresh plugin and survey instances to avoid state issues
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        
        // Force survey model to reload from database to avoid stale data
        $survey->refresh();
        
        // Create completely new import instance
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
        
        // Set fileName directly and call prepare to read the file
        $import->fileName = $csvFile;
        echo "DEBUG: About to prepare file: $csvFile\n";

        $prepareResult = $import->prepare();
        if (!$prepareResult) {
            throw new \Exception('Failed to prepare import file');
        }
        echo "DEBUG: Prepare succeeded, about to process\n";
        
        // Perform import
        $result = $import->process();
        echo "DEBUG: Process completed\n";
        
        // Check for errors
        $errors = $import->getErrors();
        if (!empty($errors)) {
            $errorMessage = 'Import had errors: ';
            foreach ($errors as $field => $fieldErrors) {
                foreach ((array)$fieldErrors as $error) {
                    $errorMessage .= "$field: $error; ";
                }
            }
            throw new \Exception($errorMessage);
        }

        return $result;
    }
    
    /**
     * Create a mock CUploadedFile from a real file
     */
    private function createMockUploadedFile($filePath)
    {
        // Create a proper CUploadedFile instance
        $uploadedFile = new \CUploadedFile(
            basename($filePath),  // name
            $filePath,           // tempName
            'text/csv',          // type
            filesize($filePath), // size
            UPLOAD_ERR_OK        // error
        );
        
        return $uploadedFile;
    }
}
