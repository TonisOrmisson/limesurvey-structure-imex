<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use QuotaMember;
use Question;
use tonisormisson\ls\structureimex\import\ImportQuotas;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test quota member sid (survey ID) assignment
 */
class QuotaMemberSidTest extends DatabaseTestCase
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
     * Test that quota members are created with correct survey ID
     */
    public function testQuotaMemberHasCorrectSurveyId()
    {
        $quotaData = [
            ['type' => 'Q', 'name' => 'testQuota', 'value' => '10', 'active' => '1'],
            ['type' => 'QM', 'name' => $this->testQuestion->title, 'value' => 'Y', 'active' => ''],
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertTrue($result, 'Import should succeed');
        
        // Find the created quota member
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND code = :code';
        $criteria->params = [':sid' => $this->survey->sid, ':code' => 'Y'];
        
        $quotaMember = QuotaMember::model()->find($criteria);
        
        $this->assertNotNull($quotaMember, 'QuotaMember should be created');
        $this->assertEquals($this->survey->sid, $quotaMember->sid, 'QuotaMember should have correct survey ID');
        $this->assertNotEmpty($quotaMember->sid, 'QuotaMember sid should not be empty');
        $this->assertNotNull($quotaMember->sid, 'QuotaMember sid should not be null');
    }
    
    /**
     * Test that quota members without survey ID are rejected
     */
    public function testQuotaMemberValidationRequiresSid()
    {
        $this->createTestSurveyWithQuestions();
        
        // Create a quota member manually without survey ID to test validation
        $quotaMember = new QuotaMember();
        $quotaMember->qid = $this->testQuestion->qid;
        $quotaMember->code = 'Y';
        $quotaMember->quota_id = 1;
        // Deliberately omit sid to test validation
        
        $result = $quotaMember->save();
        
        // This should fail because sid is required for proper functioning
        $this->assertFalse($result, 'QuotaMember save should fail without sid');
    }
    
    /**
     * Test that existing quota members keep their survey ID when updated
     */
    public function testExistingQuotaMemberKeepsSurveyId()
    {
        $this->createTestSurveyWithQuestions();
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'testQuota', 'value' => '10', 'active' => '1'],
            ['type' => 'QM', 'name' => 'Q1', 'value' => 'Y', 'active' => ''],
        ];
        
        // First import
        $result1 = $this->importQuotas($quotaData);
        $this->assertTrue($result1, 'First import should succeed');
        
        // Get the created quota member
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND code = :code';
        $criteria->params = [':sid' => $this->survey->sid, ':code' => 'Y'];
        
        $quotaMember = QuotaMember::model()->find($criteria);
        $originalSid = $quotaMember->sid;
        
        // Second import (should update existing)
        $result2 = $this->importQuotas($quotaData);
        $this->assertTrue($result2, 'Second import should succeed');
        
        // Verify survey ID is still correct
        $quotaMember->refresh();
        $this->assertEquals($originalSid, $quotaMember->sid, 'QuotaMember should keep correct survey ID after update');
        $this->assertEquals($this->survey->sid, $quotaMember->sid, 'QuotaMember should still have correct survey ID');
    }

    /**
     * Helper method to import quota data
     */
    private function importQuotas(array $quotaData): bool
    {
        $fileName = $this->createExcelFileFromData($quotaData);
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $import = new ImportQuotas($plugin);
        
        $mockFile = $this->createMockUploadedFile($fileName);
        
        if (!$import->loadFile($mockFile)) {
            return false;
        }
        
        $import->process();
        $errors = $import->getErrors();
        
        if (!empty($errors)) {
            $this->fail('Import errors: ' . print_r($errors, true));
            return false;
        }
        
        return true;
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