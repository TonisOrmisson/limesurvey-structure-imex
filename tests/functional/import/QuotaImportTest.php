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
        
        $mockFile = $this->createMockUploadedFile($fileName);
        $this->assertTrue($import->loadFile($mockFile));
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
    }

    public function testImportQuotaWithMembers()
    {
        $question = $this->createQuotaTestQuestion('gender', 'Gender Question');
        
        $importData = [
            ['type' => 'Q', 'name' => 'Gender Quota', 'value' => '50', 'active' => '1', 'autoload_url' => '0'],
            ['type' => 'QM', 'name' => 'gender', 'value' => 'M', 'active' => '', 'autoload_url' => ''],
            ['type' => 'QM', 'name' => 'gender', 'value' => 'F', 'active' => '', 'autoload_url' => ''],
        ];
        
        $this->createImportFile($importData);
        $import = $this->createImport();
        
        $this->assertTrue($import->loadFile($this->mockFile));
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $quota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->survey->sid,
            ':name' => 'Gender Quota'
        ]);
        
        $this->assertNotNull($quota, 'Quota should be created');
        
        $members = QuotaMember::model()->findAll('quota_id = :quota_id', [':quota_id' => $quota->id]);
        $this->assertCount(2, $members, 'Should have 2 quota members');
        
        $memberCodes = array_map(function($m) { return $m->code; }, $members);
        $this->assertContains('M', $memberCodes);
        $this->assertContains('F', $memberCodes);
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
        
        $this->createImportFile($importData);
        $import = $this->createImport();
        
        $this->assertTrue($import->loadFile($this->mockFile));
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $quota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->survey->sid,
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
    }

    public function testImportUpdateExistingQuota()
    {
        $existingQuota = new Quota();
        $existingQuota->sid = $this->survey->sid;
        $existingQuota->name = 'Existing Quota';
        $existingQuota->qlimit = 50;
        $existingQuota->active = 0;
        $existingQuota->save();
        
        $importData = [
            ['type' => 'Q', 'name' => 'Existing Quota', 'value' => '200', 'active' => '1', 'autoload_url' => '1'],
        ];
        
        $this->createImportFile($importData);
        $import = $this->createImport();
        
        $this->assertTrue($import->loadFile($this->mockFile));
        $import->process();
        
        $this->assertEmpty($import->getErrors(), 'Import should not have errors: ' . print_r($import->getErrors(), true));
        
        $updatedQuota = Quota::model()->find('sid = :sid AND name = :name', [
            ':sid' => $this->survey->sid,
            ':name' => 'Existing Quota'
        ]);
        
        $this->assertNotNull($updatedQuota, 'Quota should exist');
        $this->assertEquals(200, $updatedQuota->qlimit, 'Quota limit should be updated');
        $this->assertEquals(1, $updatedQuota->active, 'Quota should be active');
        $this->assertEquals(1, $updatedQuota->autoload_url, 'Autoload URL should be updated');
    }

    public function testImportInvalidQuestionReference()
    {
        $importData = [
            ['type' => 'Q', 'name' => 'Test Quota', 'value' => '100', 'active' => '1', 'autoload_url' => '0'],
            ['type' => 'QM', 'name' => 'nonexistent_question', 'value' => 'A', 'active' => '', 'autoload_url' => ''],
        ];
        
        $this->createImportFile($importData);
        $import = $this->createImport();
        
        $this->assertTrue($import->loadFile($this->mockFile));
        $import->process();
        
        $this->assertNotEmpty($import->getErrors(), 'Import should have errors for invalid question reference');
        $this->assertEquals(1, $import->failedModelsCount, 'Should have 1 failed model');
    }

    private function createImport(): ImportQuotas
    {
        return new ImportQuotas($this->plugin);
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
}