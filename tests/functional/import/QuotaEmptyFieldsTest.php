<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Quota;
use QuotaLanguageSetting;
use Question;
use tonisormisson\ls\structureimex\import\ImportQuotas;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test quota empty fields handling during import
 */
class QuotaEmptyFieldsTest extends DatabaseTestCase
{
    protected $survey;
    protected $existingQuota;

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
        
        // Create existing quota with content
        $this->createExistingQuotaWithContent();
    }

    /**
     * Create an existing quota with language settings that have content
     */
    private function createExistingQuotaWithContent(): void
    {
        // Create quota
        $this->existingQuota = new Quota();
        $this->existingQuota->sid = $this->survey->sid;
        $this->existingQuota->name = 'ExistingQuota';
        $this->existingQuota->qlimit = 50;
        $this->existingQuota->active = 1;
        $this->existingQuota->autoload_url = 1; // Auto-redirect is on
        $this->existingQuota->save();
        
        // Create existing language setting with content
        $langSetting = new QuotaLanguageSetting();
        $langSetting->quotals_quota_id = $this->existingQuota->id;
        $langSetting->quotals_language = 'en';
        $langSetting->quotals_message = 'Existing quota message';
        $langSetting->quotals_url = 'https://existing.example.com';
        $langSetting->quotals_urldescrip = 'Existing URL description';
        $langSetting->save();
        
        $this->assertNotNull($this->existingQuota->id, 'Existing quota should be created');
        $this->assertNotNull($langSetting->quotals_id, 'Existing language setting should be created');
    }

    /**
     * Test that importing empty quota message clears existing content
     * This should fail initially because empty values are not properly clearing existing content
     */
    public function testImportEmptyQuotaMessageClearsExisting()
    {
        $quotaData = [
            [
                'type' => 'Q', 
                'name' => 'ExistingQuota', 
                'value' => '100', 
                'active' => '1',
                'autoload_url' => '1',
                'message-en' => '', // Empty message should clear existing
                'url-en' => 'https://new.example.com', // Keep URL since autoload is on
                'url_description-en' => '' // Empty description should clear existing
            ],
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertTrue($result, 'Import should succeed');
        
        // Check that the language setting was updated
        $langSetting = QuotaLanguageSetting::model()->find('quotals_quota_id = :quota_id AND quotals_language = :lang', [
            ':quota_id' => $this->existingQuota->id,
            ':lang' => 'en'
        ]);
        
        $this->assertNotNull($langSetting, 'Language setting should exist');
        $this->assertEquals('', $langSetting->quotals_message, 'Message should be cleared to empty');
        $this->assertEquals('https://new.example.com', $langSetting->quotals_url, 'URL should be updated');
        $this->assertEquals('', $langSetting->quotals_urldescrip, 'URL description should be cleared to empty');
    }

    /**
     * Test that importing empty URL when autoload_url is on should fail validation
     */
    public function testImportEmptyUrlWithAutoloadOnFails()
    {
        $quotaData = [
            [
                'type' => 'Q', 
                'name' => 'ExistingQuota', 
                'value' => '100', 
                'active' => '1',
                'autoload_url' => '1', // Auto-redirect is on
                'message-en' => 'Quota reached',
                'url-en' => '', // Empty URL with autoload on should fail
                'url_description-en' => 'Click here'
            ],
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertFalse($result, 'Import should fail when URL is empty but autoload_url is enabled');
    }

    /**
     * Test that importing empty URL when autoload_url is off should succeed
     */
    public function testImportEmptyUrlWithAutoloadOffSucceeds()
    {
        $quotaData = [
            [
                'type' => 'Q', 
                'name' => 'ExistingQuota', 
                'value' => '100', 
                'active' => '1',
                'autoload_url' => '0', // Auto-redirect is off
                'message-en' => 'Quota reached',
                'url-en' => '', // Empty URL with autoload off should be fine
                'url_description-en' => ''
            ],
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertTrue($result, 'Import should succeed when URL is empty and autoload_url is disabled');
        
        // Check that URL was cleared
        $langSetting = QuotaLanguageSetting::model()->find('quotals_quota_id = :quota_id AND quotals_language = :lang', [
            ':quota_id' => $this->existingQuota->id,
            ':lang' => 'en'
        ]);
        
        $this->assertNotNull($langSetting, 'Language setting should exist');
        $this->assertEquals('', $langSetting->quotals_url, 'URL should be cleared to empty');
    }

    /**
     * Test that all empty language fields are properly cleared
     */
    public function testImportAllEmptyLanguageFieldsCleared()
    {
        $quotaData = [
            [
                'type' => 'Q', 
                'name' => 'ExistingQuota', 
                'value' => '100', 
                'active' => '1',
                'autoload_url' => '0', // Turn off autoload so empty URL is allowed
                'message-en' => '', // Clear message
                'url-en' => '', // Clear URL
                'url_description-en' => '' // Clear description
            ],
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertTrue($result, 'Import should succeed');
        
        // Check that all fields were cleared
        $langSetting = QuotaLanguageSetting::model()->find('quotals_quota_id = :quota_id AND quotals_language = :lang', [
            ':quota_id' => $this->existingQuota->id,
            ':lang' => 'en'
        ]);
        
        $this->assertNotNull($langSetting, 'Language setting should exist');
        $this->assertEquals('', $langSetting->quotals_message, 'Message should be empty');
        $this->assertEquals('', $langSetting->quotals_url, 'URL should be empty');
        $this->assertEquals('', $langSetting->quotals_urldescrip, 'URL description should be empty');
    }

    /**
     * Test that fields with whitespace are treated as empty
     */
    public function testImportWhitespaceFieldsTreatedAsEmpty()
    {
        $quotaData = [
            [
                'type' => 'Q', 
                'name' => 'ExistingQuota', 
                'value' => '100', 
                'active' => '1',
                'autoload_url' => '0',
                'message-en' => '   ', // Only whitespace should be treated as empty
                'url-en' => "\t\n", // Tabs and newlines should be treated as empty
                'url_description-en' => ' ' // Single space should be treated as empty
            ],
        ];
        
        $result = $this->importQuotas($quotaData);
        $this->assertTrue($result, 'Import should succeed');
        
        // Check that whitespace was treated as empty
        $langSetting = QuotaLanguageSetting::model()->find('quotals_quota_id = :quota_id AND quotals_language = :lang', [
            ':quota_id' => $this->existingQuota->id,
            ':lang' => 'en'
        ]);
        
        $this->assertNotNull($langSetting, 'Language setting should exist');
        $this->assertEquals('', $langSetting->quotals_message, 'Whitespace message should be treated as empty');
        $this->assertEquals('', $langSetting->quotals_url, 'Whitespace URL should be treated as empty');
        $this->assertEquals('', $langSetting->quotals_urldescrip, 'Whitespace description should be treated as empty');
    }

    /**
     * Helper method to import quota data and return success status
     */
    private function importQuotas(array $quotaData): bool
    {
        $fileName = $this->createExcelFileFromData($quotaData);
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $import = new ImportQuotas($plugin);
        
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

}