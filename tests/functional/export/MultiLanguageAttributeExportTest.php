<?php

namespace tonisormisson\ls\structureimex\tests\functional\export;

use Question;
use QuestionAttribute;
use Survey;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

/**
 * Test export of question attributes in multiple languages
 * Reproduces issue where only one language is exported instead of all languages
 */
class MultiLanguageAttributeExportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Import the proper multi-language survey
        $this->testSurveyId = $this->importSurveyFromFile($this->getMultiLanguageSurveyPath());
    }
    
    public function testMultiLanguageAttributeExport()
    {
        // Create a question group
        $group = new \QuestionGroup();
        $group->sid = $this->testSurveyId;
        $group->group_name = 'Test Group';
        $group->group_order = 1;
        $group->save();
        
        // Create a question
        $questionId = $this->createTestQuestion($this->testSurveyId, $group->gid, 'TestQ1', \Question::QT_L_LIST, 'Test Question');
        
        // Set a global attribute first
        $this->setGlobalAttribute($questionId, 'hidden', '1');
        
        // Set language-specific attributes in BOTH et and en languages (as per the survey file)
        $this->setLanguageSpecificAttribute($questionId, 'em_validation_q_tip', 'et', 'Estonian validation tip');
        $this->setLanguageSpecificAttribute($questionId, 'other_replace_text', 'et', 'Estonian other text');
        
        // Now set English attributes  
        $this->setLanguageSpecificAttribute($questionId, 'em_validation_q_tip', 'en', 'English validation tip');
        $this->setLanguageSpecificAttribute($questionId, 'other_replace_text', 'en', 'English other text');
        
        
        
        // Export questions
        $survey = \Survey::model()->findByPk($this->testSurveyId);

        // Use reflection to set the path property before export happens in constructor
        $exportClass = new \ReflectionClass('\tonisormisson\ls\structureimex\export\ExportQuestions');
        $export = $exportClass->newInstanceWithoutConstructor();
        $pathProperty = $exportClass->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($export, \Yii::app()->runtimePath . '/');
        
        // Now call the constructor to trigger the export with correct path
        $constructor = $exportClass->getConstructor();
        $constructor->invoke($export, $survey);
        
        // Ensure export writer is properly closed
        if ($export->writer) {
            $export->writer->close();
        }
        
        // Get the filename and read the exported file
        $tempFile = $export->getFullFileName();
        $this->assertFileExists($tempFile, "Export file should exist");
        
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($tempFile);
        $sheet = $reader->getSheetIterator()->current();
        
        // Check header row to see if we have both language columns
        $headerRow = null;
        $questionRow = null;
        
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            if (count($cells) >= 3) {
                $firstCell = $cells[0]->getValue();
                
                if ($firstCell === 'type') {
                    // This is the header row
                    $headerRow = array_map(function($cell) { return $cell->getValue(); }, $cells);
                } elseif ($firstCell === 'Q' && isset($cells[2]) && $cells[2]->getValue() === 'TestQ1') {
                    // This is our question row
                    $questionRow = array_map(function($cell) { return $cell->getValue(); }, $cells);
                    break;
                }
            }
        }
        
        $reader->close();
        unlink($tempFile);
        
        // Verify we found both header and question rows
        $this->assertNotNull($headerRow, "Header row should be found");
        $this->assertNotNull($questionRow, "Question row should be found");
        
        // Check that header contains both language-specific options columns  
        $this->assertContains('options-et', $headerRow, "Header should contain options-et column");
        $this->assertContains('options-en', $headerRow, "Header should contain options-en column");
        
        // The actual data is in different columns due to missing relevance/mandatory/theme columns
        // For 2-language survey: type, subtype, code, value-en, help-en, script-en, value-et, help-et, script-et, relevance, mandatory, theme, options, options-en, options-et
        $globalOptions = isset($questionRow[12]) ? $questionRow[12] : '';
        $enOptions = isset($questionRow[13]) ? $questionRow[13] : '';  // English is column 13
        $etOptions = isset($questionRow[14]) ? $questionRow[14] : '';  // Estonian is column 14
        
        
        // Global options should contain the global attribute
        $this->assertNotEmpty($globalOptions, "Global options should not be empty");
        $globalData = json_decode($globalOptions, true);
        $this->assertIsArray($globalData, "Global options should be valid JSON");
        $this->assertArrayHasKey('hidden', $globalData, "Global options should contain hidden attribute");
        $this->assertEquals('1', $globalData['hidden'], "Hidden attribute should be '1'");
        
        // Estonian options should contain Estonian language-specific attributes
        $this->assertNotEmpty($etOptions, "Estonian options should not be empty");
        $etData = json_decode($etOptions, true);
        $this->assertIsArray($etData, "Estonian options should be valid JSON");
        $this->assertArrayHasKey('em_validation_q_tip', $etData, "Estonian options should contain em_validation_q_tip");
        $this->assertEquals('Estonian validation tip', $etData['em_validation_q_tip'], "Estonian validation tip should match");
        $this->assertArrayHasKey('other_replace_text', $etData, "Estonian options should contain other_replace_text");
        $this->assertEquals('Estonian other text', $etData['other_replace_text'], "Estonian other text should match");
        
        // English options should contain English language-specific attributes
        $this->assertNotEmpty($enOptions, "English options should not be empty");
        $enData = json_decode($enOptions, true);
        $this->assertIsArray($enData, "English options should be valid JSON");
        $this->assertArrayHasKey('em_validation_q_tip', $enData, "English options should contain em_validation_q_tip");
        $this->assertEquals('English validation tip', $enData['em_validation_q_tip'], "English validation tip should match");
        $this->assertArrayHasKey('other_replace_text', $enData, "English options should contain other_replace_text");
        $this->assertEquals('English other text', $enData['other_replace_text'], "English other text should match");
    }
    
    private function setLanguageSpecificAttribute($questionId, $attributeName, $language, $value)
    {
        // Delete existing attribute
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        // Create new attribute
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = $language;
        
        $result = $attribute->save();
        if (!$result) {
            throw new \Exception("Failed to set language-specific attribute $attributeName ($language): " . print_r($attribute->getErrors(), true));
        }
    }
    
    private function setGlobalAttribute($questionId, $attributeName, $value)
    {
        // Delete existing attribute
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND (language = \'\' OR language IS NULL)',
            'params' => [':qid' => $questionId, ':attr' => $attributeName]
        ]);
        
        // Create new attribute
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = ''; // Global attributes use empty string
        
        $result = $attribute->save();
        if (!$result) {
            throw new \Exception("Failed to set global attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
}
