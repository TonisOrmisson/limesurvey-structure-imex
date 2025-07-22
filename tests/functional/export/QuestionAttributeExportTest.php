<?php

namespace tonisormisson\ls\structureimex\tests\functional\export;

use Question;
use QuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test EXPORT of question attributes - global vs language-specific separation
 */
class QuestionAttributeExportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
    }
    
    /**
     * Data provider: Question types with attributes to test export
     */
    public function questionTypeAttributeProvider()
    {
        return [
            // Global attributes (should go to 'options' column)
            [\Question::QT_L_LIST, 'hidden', '0', '1'],
            [\Question::QT_L_LIST, 'hide_tip', '0', '1'],
            [\Question::QT_T_LONG_FREE_TEXT, 'maximum_chars', '', '500'],
            
            // Language-specific attributes (should go to 'options-{lang}' columns)
            [\Question::QT_L_LIST, 'em_validation_q_tip', '', 'Validation tip text'],
            [\Question::QT_L_LIST, 'other_replace_text', '', 'Other option'],
        ];
    }
    
    /**
     * @dataProvider questionTypeAttributeProvider
     */
    public function testAttributeExport($questionType, $attributeName, $defaultValue, $changedValue)
    {
        // Create question with the changed attribute value
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', $questionType, 'Test Question');
        $this->setQuestionAttribute($questionId, $attributeName, $changedValue);
        
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
        
        // Get the filename in the test runtime directory
        $tempFile = $export->getFullFileName();
        
        // Ensure file exists and is readable
        $this->assertFileExists($tempFile, "Export file should exist");
        
        // Read the exported file and check if attribute is exported correctly
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($tempFile);
        $sheet = $reader->getSheetIterator()->current();
        
        $found = false;
        $exportedGlobalOptions = null;
        $exportedLanguageOptions = [];
        
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            if (count($cells) >= 3) {
                $type = $cells[0]->getValue();
                $code = $cells[2]->getValue();
                
                if ($type === 'Q' && $code === 'TestQ1') {
                    // Debug: count total columns
                    $totalColumns = count($cells);
                    
                    // For single-language survey: options should be at 0-based index 10
                    // For multi-language survey: options should be at 0-based index 13  
                    // Detect which by column count - single language has fewer columns
                    if ($totalColumns <= 12) {
                        // Single language survey
                        if ($totalColumns > 10) {
                            $exportedGlobalOptions = $cells[10]->getValue();
                        }
                        // Language-specific options start at index 11
                        for ($i = 11; $i < $totalColumns; $i++) {
                            $exportedLanguageOptions[] = $cells[$i]->getValue();
                        }
                    } else {
                        // Multi-language survey  
                        if ($totalColumns > 13) {
                            $exportedGlobalOptions = $cells[13]->getValue();
                        }
                        // Language-specific options start at index 14, 15, etc
                        for ($i = 14; $i < $totalColumns; $i++) {
                            $exportedLanguageOptions[] = $cells[$i]->getValue();
                        }
                    }
                    $found = true;
                    break;
                }
            }
        }
        
        $reader->close();
        unlink($tempFile);
        
        $this->assertTrue($found, "Question TestQ1 should be found in export");
        
        // Check if the attribute is in the correct column based on its type
        if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
            // Global attribute should be in the options column
            $this->assertNotEmpty($exportedGlobalOptions, "Global options column should not be empty for global attribute $attributeName");
            $optionsArray = json_decode($exportedGlobalOptions, true);
            $this->assertIsArray($optionsArray, "Global options should be valid JSON");
            $this->assertArrayHasKey($attributeName, $optionsArray, "Global attribute $attributeName should be in options column");
            $this->assertEquals($changedValue, $optionsArray[$attributeName], "Global attribute value should match");
        } else {
            // Language-specific attribute should be in one of the language options columns
            $found = false;
            foreach ($exportedLanguageOptions as $langOptions) {
                if (!empty($langOptions)) {
                    $optionsArray = json_decode($langOptions, true);
                    if (is_array($optionsArray) && isset($optionsArray[$attributeName])) {
                        $this->assertEquals($changedValue, $optionsArray[$attributeName], "Language-specific attribute value should match");
                        $found = true;
                        break;
                    }
                }
            }
            $this->assertTrue($found, "Language-specific attribute $attributeName should be found in language options columns");
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
        // Determine if this is a global or language-specific attribute
        $isLanguageSpecific = QuestionAttributeLanguageManager::isLanguageSpecific($attributeName);
        
        if ($isLanguageSpecific) {
            // Language-specific attribute - set for survey language
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
        } else {
            // Global attribute - set with empty language
            QuestionAttribute::model()->deleteAll([
                'condition' => 'qid = :qid AND attribute = :attr AND (language = \'\' OR language IS NULL)',
                'params' => [':qid' => $questionId, ':attr' => $attributeName]
            ]);
            
            $attribute = new QuestionAttribute();
            $attribute->qid = $questionId;
            $attribute->attribute = $attributeName;
            $attribute->value = $value;
            $attribute->language = ''; // Global attributes use empty string
        }
        
        $result = $attribute->save();
        
        if (!$result) {
            throw new \tonisormisson\ls\structureimex\exceptions\ImexException("Failed to set attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
}
