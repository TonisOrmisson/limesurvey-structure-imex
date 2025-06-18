<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;

/**
 * Test for handling curly quotes and other invalid JSON characters from spreadsheets
 */
class CurlyQuotesImportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        
        // Ensure survey is inactive for import testing
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if ($survey && $survey->active === 'Y') {
            $survey->active = 'N';
            $survey->save();
        }
    }
    
    /**
     * Test data provider with various invalid quote scenarios from spreadsheets
     */
    public function invalidQuotesProvider()
    {
        // Generate strings with actual UTF-8 curly quotes - these are the exact bytes that cause problems
        $leftQuote = "\xE2\x80\x9C";  // UTF-8 left double quote
        $rightQuote = "\xE2\x80\x9D"; // UTF-8 right double quote
        
        // This recreates the exact issue the user experienced from spreadsheets
        $curlyQuoteJson1 = '{' . $leftQuote . 'em_validation_q_tip' . $rightQuote . ':' . $leftQuote . 'CHANGED' . $rightQuote . ',' . $leftQuote . 'other_replace_text' . $rightQuote . ':' . $leftQuote . '874534' . $rightQuote . ',' . $leftQuote . 'hidden' . $rightQuote . ':' . $leftQuote . '0' . $rightQuote . '}';
        $curlyQuoteJson2 = '{' . $leftQuote . 'hide_tip' . $rightQuote . ':' . $leftQuote . '1' . $rightQuote . ',' . $leftQuote . 'cssclass' . $rightQuote . ':' . $leftQuote . 'test-class' . $rightQuote . '}';
        
        return [
            // Test case 1: UTF-8 curly quotes (most common from Excel/LibreOffice) - the exact issue user experienced
            [
                'description' => 'UTF-8 curly quotes from Excel',
                'invalidJson' => $curlyQuoteJson1,
                'expectedAttributes' => [
                    'em_validation_q_tip' => 'CHANGED',
                    'other_replace_text' => '874534', 
                    'hidden' => '0'
                ]
            ],
            
            // Test case 2: Mixed curly and straight quotes
            [
                'description' => 'Mixed quote types',
                'invalidJson' => $curlyQuoteJson2,
                'expectedAttributes' => [
                    'hide_tip' => '1',
                    'cssclass' => 'test-class'
                ]
            ],
            
            // Test case 3: Curly single quotes in values
            [
                'description' => 'Curly single quotes in values',
                'invalidJson' => '{"other_replace_text":"Don\'t worry","hide_tip":"1"}',
                'expectedAttributes' => [
                    'other_replace_text' => "Don't worry",
                    'hide_tip' => '1'
                ]
            ],
            
            // Test case 4: All types of curly quotes
            [
                'description' => 'All curly quote types',
                'invalidJson' => '{"em_validation_q_tip":"It\'s \"working\" now","hidden":"0"}',
                'expectedAttributes' => [
                    'em_validation_q_tip' => 'It\'s "working" now',
                    'hidden' => '0'
                ]
            ]
        ];
    }
    
    /**
     * @dataProvider invalidQuotesProvider
     */
    public function testInvalidQuotesImport($description, $invalidJson, $expectedAttributes)
    {
        // Create question with default values
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', 'L', 'Test Question');
        
        // Set initial empty values for all expected attributes
        foreach ($expectedAttributes as $attributeName => $expectedValue) {
            $this->setQuestionAttribute($questionId, $attributeName, '');
        }
        
        // Create import CSV with invalid JSON quotes
        $csvContent = $this->createImportCSVWithInvalidJson('L', $invalidJson);
        $csvFile = $this->writeTempCSV($csvContent);
        
        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
        $import->fileName = $csvFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed for: $description");
        
        $processResult = $import->process();
        
        $errors = $import->getErrors();
        if (!empty($errors)) {
            $this->fail("Import failed with errors for '$description': " . print_r($errors, true));
        }
        
        // Verify all expected attributes were imported correctly
        foreach ($expectedAttributes as $attributeName => $expectedValue) {
            $actualValue = $this->getQuestionAttribute($questionId, $attributeName);
            $this->assertEquals(
                $expectedValue, 
                $actualValue, 
                "Attribute '$attributeName' should be correctly imported for: $description. Expected '$expectedValue', got '$actualValue'"
            );
        }
        
        // Clean up temp file
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
    }
    
    /**
     * Test that valid JSON still works (regression test)
     */
    public function testValidJsonStillWorks()
    {
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', 'L', 'Test Question');
        $this->setQuestionAttribute($questionId, 'em_validation_q_tip', '');
        
        // Valid JSON with straight quotes
        $validJson = '{"em_validation_q_tip":"Valid JSON","hide_tip":"1"}';
        $csvContent = $this->createImportCSVWithInvalidJson('L', $validJson);
        $csvFile = $this->writeTempCSV($csvContent);
        
        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
        $import->fileName = $csvFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed for valid JSON");
        
        $processResult = $import->process();
        
        $errors = $import->getErrors();
        if (!empty($errors)) {
            $this->fail("Import failed with errors for valid JSON: " . print_r($errors, true));
        }
        
        // Verify attribute was imported correctly
        $actualValue = $this->getQuestionAttribute($questionId, 'em_validation_q_tip');
        $this->assertEquals('Valid JSON', $actualValue, "Valid JSON should still work correctly");
        
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
    }
    
    private function getOrCreateGroup()
    {
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $groups = $survey->groups;
        
        if (empty($groups)) {
            $group = new \QuestionGroup();
            $group->sid = $this->testSurveyId;
            $group->group_name = 'Test Group';
            $group->group_order = 1;
            $group->save();
            return $group->gid;
        } else {
            return $groups[0]->gid;
        }
    }
    
    private function setQuestionAttribute($questionId, $attributeName, $value)
    {
        // Get the survey language for proper language-specific attribute setting
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $language = $survey->language;
        
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = $language;
        $result = $attribute->save();
        
        if (!$result) {
            throw new \tonisormisson\ls\structureimex\exceptions\ImexException("Failed to set attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
    
    private function getQuestionAttribute($questionId, $attributeName)
    {
        // Get the survey language to check the correct language-specific attribute
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $language = $survey->language;
        
        $attribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        return $attribute ? $attribute->value : null;
    }
    
    private function createImportCSVWithInvalidJson($questionType, $invalidJsonOptions)
    {
        // Get the survey to determine the correct language
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $lang = $survey->language;
        
        $csvLines = [
            "type,subtype,code,value-{$lang},help-{$lang},script-{$lang},relevance,mandatory,theme,options",
            'G,,TestGroup,"Test Group","","",1,,,',
            "Q,$questionType,TestQ1,\"Test Question\",\"\",\"\",1,N,\"\",$invalidJsonOptions"
        ];
        
        return implode("\n", $csvLines);
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'curly_quotes_test_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}