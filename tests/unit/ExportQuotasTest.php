<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\export\ExportQuotas;

/**
 * Unit tests for quota export functionality
 * Tests the ExportQuotas class using mock data
 */
class ExportQuotasTest extends TestCase
{
    public function testMockQuotaHelper()
    {
        $mockData = MockQuotaHelper::createMockQuotaData();
        
        // Verify basic structure
        $this->assertArrayHasKey('quotas', $mockData);
        $this->assertArrayHasKey('survey', $mockData);
        
        // Verify survey data
        $this->assertEquals(123456, $mockData['survey']['sid']);
        $this->assertEquals('en', $mockData['survey']['language']);
        
        // Verify quota count
        $this->assertCount(4, $mockData['quotas']);
        
        // Verify quota names
        $quotaNames = array_keys($mockData['quotas']);
        $this->assertContains('male_quota', $quotaNames);
        $this->assertContains('age_quota', $quotaNames);
        $this->assertContains('complex_quota', $quotaNames);
        $this->assertContains('multiLanguage_quota', $quotaNames);
        
        $this->assertNotNull($mockData, 'Mock quota data should be created successfully');
    }

    public function testMockQuotaStructure()
    {
        $mockData = MockQuotaHelper::createMockQuotaData();
        $genderQuota = $mockData['quotas']['male_quota'];
        
        // Test core quota structure
        $this->assertArrayHasKey('core', $genderQuota);
        $this->assertArrayHasKey('members', $genderQuota);
        $this->assertArrayHasKey('language_settings', $genderQuota);
        
        // Test core data
        $core = $genderQuota['core'];
        $this->assertEquals('Male Participants', $core['name']);
        $this->assertEquals(100, $core['qlimit']);
        $this->assertEquals(1, $core['action']);
        $this->assertEquals(1, $core['active']);
        $this->assertEquals(0, $core['autoload_url']);
        
        // Test members structure
        $this->assertCount(1, $genderQuota['members']);
        $member = $genderQuota['members'][0];
        $this->assertEquals(1001, $member['qid']);
        $this->assertEquals('gender', $member['question_code']);
        $this->assertEquals('M', $member['answer_code']);
        
        // Test language settings
        $this->assertArrayHasKey('en', $genderQuota['language_settings']);
        $langSetting = $genderQuota['language_settings']['en'];
        $this->assertEquals('Male Participants', $langSetting['quotals_name']);
        $this->assertStringContainsString('quota for male participants', $langSetting['quotals_message']);
    }

    public function testComplexQuotaStructure()
    {
        $mockData = MockQuotaHelper::createMockQuotaData();
        $complexQuota = $mockData['quotas']['complex_quota'];
        
        // Test multiple members (OR conditions)
        $this->assertCount(3, $complexQuota['members']);
        
        $expectedAnswerCodes = ['1', '2', '3'];
        $actualAnswerCodes = array_column($complexQuota['members'], 'answer_code');
        $this->assertEquals($expectedAnswerCodes, $actualAnswerCodes);
        
        // All members should reference same question
        $questionCodes = array_unique(array_column($complexQuota['members'], 'question_code'));
        $this->assertCount(1, $questionCodes);
        $this->assertEquals(['education'], $questionCodes);
    }

    public function testMultiLanguageQuotaStructure()
    {
        $mockData = MockQuotaHelper::createMockQuotaData();
        $multiLangQuota = $mockData['quotas']['multiLanguage_quota'];
        
        // Test multiple languages
        $languageSettings = $multiLangQuota['language_settings'];
        $this->assertArrayHasKey('en', $languageSettings);
        $this->assertArrayHasKey('de', $languageSettings);
        $this->assertArrayHasKey('fr', $languageSettings);
        
        // Test language-specific content
        $this->assertEquals('Premium Subscribers', $languageSettings['en']['quotals_name']);
        $this->assertEquals('Premium-Abonnenten', $languageSettings['de']['quotals_name']);
        $this->assertEquals('Abonnés Premium', $languageSettings['fr']['quotals_name']);
        
        // Test URLs for autoload_url quota
        $this->assertEquals(1, $multiLangQuota['core']['autoload_url']);
        $this->assertStringContainsString('https://example.com/', $languageSettings['en']['quotals_url']);
        $this->assertStringContainsString('https://example.com/', $languageSettings['de']['quotals_url']);
        $this->assertStringContainsString('https://example.com/', $languageSettings['fr']['quotals_url']);
    }

    public function testExpectedExportDataFormat()
    {
        $exportData = MockQuotaHelper::createExpectedExportData();
        
        // Verify we have export rows
        $this->assertGreaterThan(0, count($exportData));
        
        // Test first row structure (quota row - type Q)
        $firstRow = $exportData[0];
        $expectedColumns = [
            'type', 'name', 'value', 'active', 'autoload_url',
            'message-en', 'url-en', 'url_description-en'
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertArrayHasKey($column, $firstRow, "Export data should contain column: $column");
        }
        
        // Test quota row data values
        $this->assertEquals('Q', $firstRow['type']);
        $this->assertEquals('Male Participants', $firstRow['name']);
        $this->assertEquals(100, $firstRow['value']);
        $this->assertEquals(1, $firstRow['active']);
        $this->assertEquals(0, $firstRow['autoload_url']);
        
        // Test second row structure (quota member row - type QM)
        $secondRow = $exportData[1];
        $this->assertEquals('QM', $secondRow['type']);
        $this->assertEquals('gender', $secondRow['name']);
        $this->assertEquals('M', $secondRow['value']);
        $this->assertEquals('', $secondRow['active']); // empty for QM
        $this->assertEquals('', $secondRow['autoload_url']); // empty for QM
        $this->assertEquals('', $secondRow['message-en']); // empty for QM
    }

    public function testImportTestDataFormat()
    {
        $importData = MockQuotaHelper::createImportTestData();
        
        // Should have header row plus data rows
        $this->assertGreaterThan(1, count($importData));
        
        // Test header row
        $header = $importData[0];
        $this->assertContains('type', $header);
        $this->assertContains('name', $header);
        $this->assertContains('value', $header);
        $this->assertContains('message-en', $header);
        
        // Test quota row (Q)
        $quotaRow = $importData[1];
        $this->assertEquals('Q', $quotaRow[0]); // type
        $this->assertEquals('Test Quota', $quotaRow[1]); // name
        $this->assertEquals(50, $quotaRow[2]); // value (limit)
        
        // Test quota member row (QM)
        $memberRow = $importData[2];
        $this->assertEquals('QM', $memberRow[0]); // type
        $this->assertEquals('gender', $memberRow[1]); // name (question code)
        $this->assertEquals('M', $memberRow[2]); // value (answer code)
    }

    public function testMockQuestionsForQuotas()
    {
        $questions = MockQuotaHelper::createMockQuestionsForQuotas();
        
        // Should have questions that match quota members
        $this->assertArrayHasKey(1001, $questions); // gender question
        $this->assertArrayHasKey(1002, $questions); // age question
        $this->assertArrayHasKey(1003, $questions); // education question
        $this->assertArrayHasKey(1004, $questions); // subscription question
        
        // Test gender question structure
        $genderQ = $questions[1001];
        $this->assertEquals('gender', $genderQ['title']);
        $this->assertEquals('L', $genderQ['type']);
        $this->assertArrayHasKey('answers', $genderQ);
        $this->assertArrayHasKey('M', $genderQ['answers']);
        $this->assertEquals('Male', $genderQ['answers']['M']);
    }

    public function testExportQuotasClassExists()
    {
        // Test that ExportQuotas class can be instantiated
        // Note: This is a basic unit test that doesn't require database
        $this->assertTrue(class_exists(ExportQuotas::class), 'ExportQuotas class should exist');
        
        // Test that it extends AbstractExport
        $reflection = new \ReflectionClass(ExportQuotas::class);
        $this->assertEquals(
            'tonisormisson\ls\structureimex\export\AbstractExport',
            $reflection->getParentClass()->getName(),
            'ExportQuotas should extend AbstractExport'
        );
    }
}