<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Quota;
use QuotaMember;
use QuotaLanguageSetting;
use Question;
use tonisormisson\ls\structureimex\import\ImportQuotas;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

class QuotaImportTest extends DatabaseTestCase
{
    protected $survey;
    protected $questionGroup;

    public function setUp(): void
    {
        parent::setUp();
        
        // Import test survey and set up basic structure
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $this->survey = \Survey::model()->findByPk($this->testSurveyId);
        
        // Ensure survey is inactive for import
        if ($this->survey && $this->survey->active === 'Y') {
            $this->survey->active = 'N';
            $this->survey->save();
        }

        // Create a basic question group for tests that need questions
        $groupData = $this->createTestSurveyWithQuestions();
        $this->questionGroup = \QuestionGroup::model()->findByPk($groupData['groups'][0]);
    }

    public function testImportBasicQuota()
    {
        
        $importData = [
            ['type' => 'Q', 'name' => 'Test Quota', 'value' => '100', 'active' => '1', 'autoload_url' => '0', 'message-en' => 'Test message'],
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = new ImportQuotas($this->createRealPlugin($this->testSurveyId));
        
        // Use the same approach as other tests - set fileName directly and bypass loadFile
        $import->fileName = $fileName;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, 'Import prepare should succeed');
        
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $quota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->testSurveyId,
            ':name' => 'Test Quota'
        ]);
        
        $this->assertNotNull($quota, 'Quota should be created');
        $this->assertEquals(100, $quota->qlimit);
        $this->assertEquals(1, $quota->active);
        $this->assertEquals(0, $quota->autoload_url);
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function testImportQuotaWithMembers()
    {
        $question1 = $this->createQuotaTestQuestion('gender', 'Gender Question');
        $question2 = $this->createQuotaTestQuestion('agegroup', 'Age Group Question');
        
        // Test with different questions (allowed) instead of same question multiple times (not allowed)
        $importData = [
            ['type' => 'Q', 'name' => 'Demo Quota', 'value' => '50', 'active' => '1', 'autoload_url' => '0'],
            ['type' => 'QM', 'name' => 'gender', 'value' => 'M', 'active' => '', 'autoload_url' => ''],
            ['type' => 'QM', 'name' => 'agegroup', 'value' => '25-34', 'active' => '', 'autoload_url' => ''],
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = $this->createImport();
        
        $import->fileName = $fileName;
        $this->assertTrue($import->prepare());
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $quota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->testSurveyId,
            ':name' => 'Demo Quota'
        ]);
        
        $this->assertNotNull($quota, 'Quota should be created');
        
        $members = QuotaMember::model()->findAll('quota_id = :quota_id', [':quota_id' => $quota->id]);
        $this->assertCount(2, $members, 'Should have 2 quota members for different questions');
        
        $memberCodes = array_map(function($m) { return $m->code; }, $members);
        $this->assertContains('M', $memberCodes);
        $this->assertContains('25-34', $memberCodes);
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function testImportDuplicateQuestionInSameQuotaFails()
    {
        $question = $this->createQuotaTestQuestion('gender', 'Gender Question');
        
        // Test with same question multiple times (should fail due to AND logic limitation)
        $importData = [
            ['type' => 'Q', 'name' => 'Invalid Quota', 'value' => '50', 'active' => '1', 'autoload_url' => '0'],
            ['type' => 'QM', 'name' => 'gender', 'value' => 'M', 'active' => '', 'autoload_url' => ''],
            ['type' => 'QM', 'name' => 'gender', 'value' => 'F', 'active' => '', 'autoload_url' => ''], // Same question - not allowed
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = $this->createImport();
        
        $import->fileName = $fileName;
        $this->assertTrue($import->prepare());
        $import->process();
        
        $this->assertNotEmpty($import->getErrors(), 'Import should have validation errors for duplicate question');
        
        // Check that the specific validation error is present
        $errors = $import->getErrors();
        $validationErrors = $errors['validation'] ?? [];
        $this->assertContains("Question 'gender' appears multiple times in quota 'Invalid Quota'", $validationErrors);
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function testImportQuotaLanguageSettings()
    {
        $importData = [
            [
                'type' => 'Q', 
                'name' => 'Multilang Quota', 
                'value' => '100', 
                'active' => '1', 
                'autoload_url' => '0',
                'message-en' => 'English message',
                'url-en' => 'https://example.com/en',
                'url_description-en' => 'English URL description'
            ],
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = $this->createImport();
        
        $import->fileName = $fileName;
        $this->assertTrue($import->prepare());
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $quota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->testSurveyId,
            ':name' => 'Multilang Quota'
        ]);
        
        $this->assertNotNull($quota, 'Quota should be created');
        
        $langSetting = QuotaLanguageSetting::model()->find('quotals_quota_id = :quota_id AND quotals_language = :lang', [
            ':quota_id' => $quota->id,
            ':lang' => 'en'
        ]);
        
        $this->assertNotNull($langSetting, 'Language setting should be created');
        $this->assertEquals('English message', $langSetting->quotals_message);
        $this->assertEquals('https://example.com/en', $langSetting->quotals_url);
        $this->assertEquals('English URL description', $langSetting->quotals_urldescrip);
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function testImportUpdateExistingQuota()
    {
        $existingQuota = new Quota();
        $existingQuota->sid = $this->testSurveyId;
        $existingQuota->name = 'Existing Quota';
        $existingQuota->qlimit = 50;
        $existingQuota->active = 0;
        $existingQuota->save();
        
        $importData = [
            ['type' => 'Q', 'name' => 'Existing Quota', 'value' => '200', 'active' => '1', 'autoload_url' => '1'],
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = $this->createImport();
        
        $import->fileName = $fileName;
        $this->assertTrue($import->prepare());
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $updatedQuota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->testSurveyId,
            ':name' => 'Existing Quota'
        ]);
        
        $this->assertNotNull($updatedQuota, 'Quota should exist');
        $this->assertEquals(200, $updatedQuota->qlimit, 'Quota limit should be updated');
        $this->assertEquals(1, $updatedQuota->active, 'Quota should be active');
        $this->assertEquals(1, $updatedQuota->autoload_url, 'Autoload URL should be updated');
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function testImportInvalidQuestionReference()
    {
        $importData = [
            ['type' => 'Q', 'name' => 'Test Quota', 'value' => '100', 'active' => '1', 'autoload_url' => '0'],
            ['type' => 'QM', 'name' => 'nonexistent_question', 'value' => 'A', 'active' => '', 'autoload_url' => ''],
        ];
        
        $fileName = $this->createExcelFileFromData($importData);
        $import = $this->createImport();
        
        $import->fileName = $fileName;
        $this->assertTrue($import->prepare());
        $import->process();
        
        $this->assertNotEmpty($import->getErrors(), 'Import should have errors for invalid question reference');
        
        // Check that the specific validation error is present
        $errors = $import->getErrors();
        $validationErrors = $errors['validation'] ?? [];
        $this->assertContains('Referenced questions not found in survey: nonexistent_question', $validationErrors);
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    private function createImport(): ImportQuotas
    {
        return new ImportQuotas($this->createRealPlugin($this->testSurveyId));
    }

    protected function createQuotaTestQuestion(string $code, string $question): Question
    {
        $qid = $this->createTestQuestion($this->testSurveyId, $this->questionGroup->gid, $code, 'L', $question);
        return Question::model()->findByPk($qid);
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
        
        // Mock the saveAs method to properly handle file saving
        $mockFile->method('saveAs')->willReturnCallback(function($destination) use ($filePath) {
            // Ensure destination directory exists
            $destDir = dirname($destination);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            return copy($filePath, $destination);
        });
        
        return $mockFile;
    }

    /**
     * Helper method for older test methods
     */
    private function createImportFile(array $data): void
    {
        $fileName = $this->createExcelFileFromData($data);
        $this->mockFile = $this->createMockUploadedFile($fileName);
    }

    /** @var \CUploadedFile */
    private $mockFile;

    /**
     * Create a real uploaded file by simulating the $_FILES superglobal
     */
    private function createRealUploadedFile(string $filePath): \CUploadedFile
    {
        // Create a temporary copy in a location that simulates an uploaded file
        $uploadDir = sys_get_temp_dir() . '/test_uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadedFile = $uploadDir . '/' . basename($filePath);
        copy($filePath, $uploadedFile);
        
        // Simulate $_FILES entry
        $_FILES['test_file'] = [
            'name' => basename($filePath),
            'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'tmp_name' => $uploadedFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($uploadedFile)
        ];
        
        // Create CUploadedFile instance
        return \CUploadedFile::getInstanceByName('test_file');
    }
}