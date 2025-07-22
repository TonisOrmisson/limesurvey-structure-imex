<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use QuotaMember;
use Question;
use tonisormisson\ls\structureimex\import\ImportQuotas;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test quota duplicate question validation
 */
class QuotaDuplicateQuestionTest extends DatabaseTestCase
{
    protected $survey;
    protected $testQuestion;

    public function setUp(): void
    {
        parent::setUp();
        
        // Import test survey
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $this->survey = \Survey::model()->findByPk($this->testSurveyId);
        
        // Ensure survey is inactive for import
        if ($this->survey && $this->survey->active === 'Y') {
            $this->survey->active = 'N';
            $this->survey->save();
        }
        
        // Create test question
        $groupData = $this->createTestSurveyWithQuestions();
        $this->testQuestion = Question::model()->findByPk($groupData['questions'][0]);
    }

    /**
     * Test that duplicate question references in same quota are rejected
     */
    public function testDuplicateQuestionInSameQuotaFails()
    {
        $quotaData = [
            ['type' => 'Q', 'name' => 'testQuota', 'value' => '10', 'active' => '1'],
            ['type' => 'QM', 'name' => $this->testQuestion->title, 'value' => 'Y', 'active' => ''],
            ['type' => 'QM', 'name' => $this->testQuestion->title, 'value' => 'N', 'active' => ''], // Same question, different value
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertFalse($result, 'Import should fail due to duplicate question reference');
    }

    /**
     * Test that duplicate question references get proper error message
     */
    public function testDuplicateQuestionErrorMessage()
    {
        $quotaData = [
            ['type' => 'Q', 'name' => 'GenderQuota', 'value' => '50', 'active' => '1'],
            ['type' => 'QM', 'name' => $this->testQuestion->title, 'value' => 'M', 'active' => ''],
            ['type' => 'QM', 'name' => $this->testQuestion->title, 'value' => 'F', 'active' => ''], // Duplicate question
        ];
        
        $fileName = $this->createExcelFileFromData($quotaData);

        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if (!$survey) {
            throw new \Exception("Survey {$this->testSurveyId} not found for plugin setup");
        }

        $import = new ImportQuotas($survey, $this->warningManager);
        
        $import->fileName = $fileName;
        $import->prepare();
        $import->process();
        
        $errors = $import->getErrors();
        $this->assertNotEmpty($errors, 'Import should have validation errors');
        
        // Check for specific duplicate question error
        $hasValidationError = false;
        foreach ($errors as $field => $fieldErrors) {
            foreach ((array)$fieldErrors as $error) {
                if (strpos($error, 'appears multiple times in quota') !== false) {
                    $hasValidationError = true;
                    $this->assertStringContainsString($this->testQuestion->title, $error);
                    $this->assertStringContainsString('GenderQuota', $error);
                    break 2;
                }
            }
        }
        
        $this->assertTrue($hasValidationError, 'Should have specific duplicate question validation error');
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Test that same question can be used in different quotas
     */
    public function testSameQuestionInDifferentQuotasIsAllowed()
    {
        // Get the actual question title that exists in the survey
        $question = Question::model()->find('sid = :sid', [':sid' => $this->testSurveyId]);
        $questionTitle = $question->title;
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'QuotaA', 'value' => '10', 'active' => '1'],
            ['type' => 'QM', 'name' => $questionTitle, 'value' => 'Y', 'active' => ''],
            ['type' => 'Q', 'name' => 'QuotaB', 'value' => '20', 'active' => '1'],
            ['type' => 'QM', 'name' => $questionTitle, 'value' => 'N', 'active' => ''], // Same question, different quota
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertTrue($result, 'Import should succeed - same question allowed in different quotas');
        
        // Verify both quota members were created
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND qid = :qid';
        $criteria->params = [':sid' => $this->testSurveyId, ':qid' => $question->qid];
        
        $quotaMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(2, $quotaMembers, 'Should have 2 quota members for same question in different quotas');
        
        $memberCodes = array_map(function($m) { return $m->code; }, $quotaMembers);
        $this->assertContains('Y', $memberCodes);
        $this->assertContains('N', $memberCodes);
    }

    /**
     * Test multiple questions in same quota is allowed
     */
    public function testMultipleQuestionsInSameQuotaIsAllowed()
    {
        // Get existing questions and create a second one if needed
        $questions = Question::model()->findAll('sid = :sid', [':sid' => $this->testSurveyId]);
        $firstQuestion = $questions[0];
        
        // Create a second question for this test
        $secondQuestionId = $this->createTestQuestion(
            $this->testSurveyId, 
            $firstQuestion->gid, 
            'Q002', 
            'L', 
            'Second Question'
        );
        $secondQuestion = Question::model()->findByPk($secondQuestionId);
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'MultiQuestionQuota', 'value' => '100', 'active' => '1'],
            ['type' => 'QM', 'name' => $firstQuestion->title, 'value' => 'Y', 'active' => ''],
            ['type' => 'QM', 'name' => $secondQuestion->title, 'value' => 'A', 'active' => ''], // Different question
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertTrue($result, 'Import should succeed - multiple different questions in same quota allowed');
        
        // Verify both quota members were created
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid';
        $criteria->params = [':sid' => $this->survey->sid];
        
        $quotaMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(2, $quotaMembers, 'Should have 2 quota members for different questions in same quota');
    }

    /**
     * Helper method to import quota data
     */
    private function importQuotas(array $quotaData): bool
    {
        $fileName = $this->createExcelFileFromData($quotaData);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if (!$survey) {
            throw new \Exception("Survey {$this->testSurveyId} not found for plugin setup");
        }


        $import = new ImportQuotas($survey, $this->warningManager);
        
        $import->fileName = $fileName;
        
        if (!$import->prepare()) {
            return false;
        }
        
        $import->process();
        $errors = $import->getErrors();
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        
        return empty($errors);
    }

    /**
     * Create Excel file from data array
     */
    private function createExcelFileFromData(array $data): string
    {
        $fileName = sys_get_temp_dir() . '/quota_test_' . uniqid() . '.xlsx';
        
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($fileName);
        
        // Get headers from first row
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $headerRow = \OpenSpout\Common\Entity\Row::fromValues($headers);
            $writer->addRow($headerRow);
            
            foreach ($data as $rowData) {
                $values = [];
                foreach ($headers as $header) {
                    $values[] = $rowData[$header] ?? '';
                }
                $dataRow = \OpenSpout\Common\Entity\Row::fromValues($values);
                $writer->addRow($dataRow);
            }
        }
        
        $writer->close();
        return $fileName;
    }

    /**
     * Create mock uploaded file
     */
    private function createMockUploadedFile(string $filePath): \CUploadedFile
    {
        $mockFile = $this->getMockBuilder(\CUploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $mockFile->method('getTempName')->willReturn($filePath);
        $mockFile->method('getName')->willReturn(basename($filePath));
        $mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
        $mockFile->method('getSize')->willReturn(filesize($filePath));
        
        return $mockFile;
    }
}
