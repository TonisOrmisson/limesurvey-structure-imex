<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Question;
use QuestionAttribute;
use PHPUnit\Framework\Attributes\DataProvider;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

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
    public static function invalidQuotesProvider()
    {
        // Generate strings with actual UTF-8 curly quotes - these are the exact bytes that cause problems
        $leftQuote = "\xE2\x80\x9C";  // UTF-8 left double quote
        $rightQuote = "\xE2\x80\x9D"; // UTF-8 right double quote


        return [
            // Test case 1: UTF-8 curly quotes - language-specific attributes only
            [
                'description' => 'UTF-8 curly quotes from Excel',
                'invalidJson' => '{' . $leftQuote . 'em_validation_q_tip' . $rightQuote . ':' . $leftQuote . 'CHANGED' . $rightQuote . ',' . $leftQuote . 'other_replace_text' . $rightQuote . ':' . $leftQuote . '874534' . $rightQuote . '}',
                'expectedAttributes' => [
                    'em_validation_q_tip' => 'CHANGED',
                    'other_replace_text' => '874534'
                ]
            ],
            
            // Test case 2: Mixed curly and straight quotes - global attributes only
            [
                'description' => 'Mixed quote types',
                'invalidJson' => '{' . $leftQuote . 'hide_tip' . $rightQuote . ':' . $leftQuote . '1' . $rightQuote . ',' . $leftQuote . 'cssclass' . $rightQuote . ':' . $leftQuote . 'test-class' . $rightQuote . '}',
                'expectedAttributes' => [
                    'hide_tip' => '1',
                    'cssclass' => 'test-class'
                ]
            ],
            
            // Test case 3: Curly single quotes in values - language-specific
            [
                'description' => 'Curly single quotes in values',
                'invalidJson' => '{"other_replace_text":"Don\'t worry"}',
                'expectedAttributes' => [
                    'other_replace_text' => "Don't worry"
                ]
            ],
            
            // Test case 4: All types of curly quotes - language-specific
            [
                'description' => 'All curly quote types',
                'invalidJson' => '{"em_validation_q_tip":"It\'s \"working\" now"}',
                'expectedAttributes' => [
                    'em_validation_q_tip' => 'It\'s "working" now'
                ]
            ]
        ];
    }
    
    /**
     * @dataProvider invalidQuotesProvider
     */
    #[DataProvider('invalidQuotesProvider')]
    public function testInvalidQuotesImport($description, $invalidJson, $expectedAttributes)
    {
        // Create question with default values
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', 'L', 'Test Question');
        
        // Set initial empty values for all expected attributes
        foreach ($expectedAttributes as $attributeName => $expectedValue) {
            $this->setQuestionAttribute($questionId, $attributeName, '');
        }
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if (!$survey) {
            throw new \Exception("Survey {$this->testSurveyId} not found for plugin setup");
        }

        // Create import CSV with invalid JSON quotes
        $csvContent = $this->createImportCSVWithInvalidJson('L', $invalidJson);
        $csvFile = $this->writeTempCSV($csvContent);
        

        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructure($survey, $this->warningManager, true);
        $import->fileName = $csvFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed for: $description");

        $processResult = $import->process();
        
        $errors = $import->getErrors();
        
        // Debug: check if import had errors
        $this->assertEmpty($errors, "Import should succeed without errors for: $description. Errors: " . print_r($errors, true));

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
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if (!$survey) {
            throw new \Exception("Survey {$this->testSurveyId} not found for plugin setup");
        }

        // Valid JSON with straight quotes - only language-specific attribute
        $validJson = '{"em_validation_q_tip":"Valid JSON"}';
        $csvContent = $this->createImportCSVWithInvalidJson('L', $validJson);
        $csvFile = $this->writeTempCSV($csvContent);
        


        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructure($survey, $this->warningManager, true);
        $import->fileName = $csvFile;

        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed for valid JSON");
        $processResult = $import->process();
        
        $errors = $import->getErrors();

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
        // Determine correct language based on attribute type
        $language = $this->getCorrectLanguageForAttribute($attributeName);
        
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
        // Determine correct language based on attribute type
        $language = $this->getCorrectLanguageForAttribute($attributeName);
        
        $attribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        return $attribute ? $attribute->value : null;
    }
    
    /**
     * Get the correct language for an attribute based on whether it's global or language-specific
     */
    private function getCorrectLanguageForAttribute($attributeName)
    {
        $languageSpecificAttributes = [
            'em_validation_q_tip',
            'other_replace_text',
            'prefix',
            'suffix',
            'validation_message',
            'choice_help'
        ];
        
        if (in_array($attributeName, $languageSpecificAttributes)) {
            // Language-specific attributes use the survey language
            $survey = \Survey::model()->findByPk($this->testSurveyId);
            return $survey->language;
        } else {
            // Global attributes use empty language
            return '';
        }
    }
    
    private function createImportCSVWithInvalidJson($questionType, $invalidJsonOptions)
    {
        // Get the survey to determine the correct language
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $lang = $survey->language;
        
        // Determine whether this JSON contains global or language-specific attributes
        $isLanguageSpecific = $this->containsLanguageSpecificAttributes($invalidJsonOptions);
        
        // Properly escape JSON for CSV - if it contains commas, it must be quoted and internal quotes escaped
        $escapedJsonOptions = $invalidJsonOptions;
        if (strpos($invalidJsonOptions, ',') !== false) {
            // CSV requires quoting fields that contain commas and escaping internal quotes
            $escapedJsonOptions = '"' . str_replace('"', '""', $invalidJsonOptions) . '"';
        }
        
        if ($isLanguageSpecific) {
            // Use language-specific options column for language-specific attributes
            $csvLines = [
                "type,subtype,code,value-{$lang},help-{$lang},script-{$lang},relevance,mandatory,theme,options,options-{$lang}",
                'G,,TestGroup,"Test Group","","",1,,,,',
                "Q,$questionType,TestQ1,\"Test Question\",\"\",\"\",1,N,\"\",\"\",$escapedJsonOptions"
            ];
        } else {
            // Use global options column for global attributes
            $csvLines = [
                "type,subtype,code,value-{$lang},help-{$lang},script-{$lang},relevance,mandatory,theme,options",
                'G,,TestGroup,"Test Group","","",1,,,',
                "Q,$questionType,TestQ1,\"Test Question\",\"\",\"\",1,N,\"\",$escapedJsonOptions"
            ];
        }
        
        return implode("\n", $csvLines);
    }
    
    /**
     * Check if the JSON contains language-specific attributes
     */
    private function containsLanguageSpecificAttributes($jsonString)
    {
        $languageSpecificAttributes = [
            'em_validation_q_tip',
            'other_replace_text',
            'prefix',
            'suffix',
            'validation_message',
            'choice_help'
        ];
        
        foreach ($languageSpecificAttributes as $attr) {
            if (strpos($jsonString, $attr) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'curly_quotes_test_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}
