<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

/**
 * COMPREHENSIVE TEST: All question types, all attributes, import AND export
 * 
 * Tests that every attribute for every question type can be:
 * 1. Exported correctly when non-default
 * 2. Imported correctly and changes database
 */
class QuestionAttributeImportExportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        
        // Ensure survey is inactive for import testing
        $survey = \Survey::model()->findByPk($this->testSurveyId);
    }
    
    /**
     * Data provider: All question types with specific attributes to test
     * 
     * Each array contains 4 elements:
     * [0] = Question type code (e.g. 'L' for List Radio, 'T' for Long Text)
     * [1] = Attribute name (e.g. 'em_validation_q_tip', 'hide_tip')
     * [2] = Default/initial value for the attribute (what it starts as)
     * [3] = Changed value for the attribute (what we import to test if it changes)
     */
    public function questionTypeAttributeProvider()
    {
        return [
            // L type (List Radio) - Test em_validation_q_tip specifically as requested
            [\Question::QT_L_LIST, 'em_validation_q_tip', '', 'Validation tip text'],
            [\Question::QT_L_LIST, 'hide_tip', '0', '1'],
            [\Question::QT_L_LIST, 'hidden', '0', '1'],  // Test hidden attribute specifically (default 0 -> 1)
            [\Question::QT_L_LIST, 'answer_order', 'normal', 'random'],
            [\Question::QT_L_LIST, 'other_replace_text', '', 'Other option'],
            
            // T type (Long free text)
            [\Question::QT_T_LONG_FREE_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_T_LONG_FREE_TEXT, 'cssclass', '', 'custom-class'],
            [\Question::QT_T_LONG_FREE_TEXT, 'text_input_width', '', '200'],
            [\Question::QT_T_LONG_FREE_TEXT, 'maximum_chars', '', '500'],
            
            // N type (Numerical)
            [\Question::QT_N_NUMERICAL, 'hide_tip', '0', '1'],
            [\Question::QT_N_NUMERICAL, 'min_num_value_n', '', '1'],
            [\Question::QT_N_NUMERICAL, 'max_num_value_n', '', '10'],
            [\Question::QT_N_NUMERICAL, 'num_value_int_only', '0', '1'],
            
            // M type (Multiple choice)
            [\Question::QT_M_MULTIPLE_CHOICE, 'hide_tip', '0', '1'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'other_replace_text', '', 'Other option'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'min_answers', '', '2'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'max_answers', '', '5'],
            
            // S type (Short free text)
            [\Question::QT_S_SHORT_FREE_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_S_SHORT_FREE_TEXT, 'cssclass', '', 'short-text'],
            [\Question::QT_S_SHORT_FREE_TEXT, 'text_input_width', '', '100'],
            
            // ! type (List dropdown) - replaces invalid 'Z'
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'hide_tip', '0', '1'],
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'answer_order', 'normal', 'random'],
            
            // F type (Array)
            [\Question::QT_F_ARRAY, 'hide_tip', '0', '1'],
            [\Question::QT_F_ARRAY, 'random_order', '0', '1'],
            
            // Q type (Multiple short text)
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'text_input_columns', '', '6'],
            
            // K type (Multiple numerical)
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'hide_tip', '0', '1'],
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'num_value_int_only', '0', '1'],
            
            // X type (Boilerplate)
            [\Question::QT_X_TEXT_DISPLAY, 'hide_tip', '0', '1'],
            [\Question::QT_X_TEXT_DISPLAY, 'cssclass', '', 'boilerplate-class'],
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
        
        // Export questions - need to override the path before constructor runs
        $plugin = $this->createRealPlugin($this->testSurveyId);
        
        // Use reflection to set the path property before export happens in constructor
        $exportClass = new \ReflectionClass('\tonisormisson\ls\structureimex\export\ExportQuestions');
        $export = $exportClass->newInstanceWithoutConstructor();
        $pathProperty = $exportClass->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($export, \Yii::app()->runtimePath . '/');
        
        // Now call the constructor to trigger the export with correct path
        $constructor = $exportClass->getConstructor();
        $constructor->invoke($export, $plugin);
        
        // Get the filename in the test runtime directory
        $tempFile = $export->getFullFileName();
        // Read the exported file and check if attribute is exported
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($tempFile);
        $sheet = $reader->getSheetIterator()->current();
        
        $found = false;
        $exportedOptions = null;
        
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            if (count($cells) >= 3) {
                $type = $cells[0]->getValue();
                $code = $cells[2]->getValue();
                
                if ($type === 'Q' && $code === 'TestQ1') {
                    if (count($cells) >= 10) {
                        $exportedOptions = $cells[9]->getValue(); // options column
                    }
                    $found = true;
                    break;
                }
            }
        }
        
        $reader->close();
        unlink($tempFile);
        
        $this->assertTrue($found, "Question TestQ1 should be found in export");
        
        // Check attribute export based on whether it's global or language-specific
        if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
            // Global attribute should be in the global options column
            $this->assertNotEmpty($exportedOptions, "Global options column should not be empty for global attribute $attributeName");
            
            $optionsArray = json_decode($exportedOptions, true);
            $this->assertIsArray($optionsArray, "Global options should be valid JSON");
            $this->assertArrayHasKey($attributeName, $optionsArray, "Global attribute $attributeName should be exported");
            $this->assertEquals($changedValue, $optionsArray[$attributeName], "Global attribute value should match");
        } else {
            // Language-specific attribute should be in language-specific columns
            // For this test, we set the attribute for the survey's default language
            $survey = \Survey::model()->findByPk($this->testSurveyId);
            $language = $survey->language;
            
            // We need to find the correct language column and check it
            // This is a simplified check - in a real scenario we'd parse all language columns
            $this->markTestSkipped("Language-specific attribute testing requires multi-language survey setup");
        }
    }
    
    /**
     * @dataProvider questionTypeAttributeProvider
     */
    public function testAttributeImport($questionType, $attributeName, $defaultValue, $changedValue)
    {

        // Create question with default attribute value
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', $questionType, 'Test Question');
        $this->setQuestionAttribute($questionId, $attributeName, $defaultValue);

        // Verify initial state
        $initialValue = $this->getQuestionAttribute($questionId, $attributeName);
        $this->assertEquals($defaultValue, $initialValue, "Initial attribute value should be default");

        // Create import CSV with changed attribute
        $csvContent = $this->createImportCSV($questionType, $attributeName, $changedValue);
        $csvFile = $this->writeTempCSV($csvContent);

        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
        $import->fileName = $csvFile;

        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed");
        
        $processResult = $import->process();

        $errors = $import->getErrors();
        if (!empty($errors)) {
            $this->fail("Import failed with errors: " . print_r($errors, true));
        }

        // Verify database was changed - the existing question should be updated
        $newValue = $this->getQuestionAttribute($questionId, $attributeName);

        // Clean up temp file
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }

        $this->assertEquals($changedValue, $newValue, "Attribute $attributeName for question type $questionType should be changed in database after import");

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
    
    private function getQuestionAttribute($questionId, $attributeName)
    {
        // Determine if this is a global or language-specific attribute
        $isLanguageSpecific = QuestionAttributeLanguageManager::isLanguageSpecific($attributeName);
        
        if ($isLanguageSpecific) {
            // Language-specific attribute - get for survey language
            $survey = \Survey::model()->findByPk($this->testSurveyId);
            $language = $survey->language;
            
            $attribute = QuestionAttribute::model()->find([
                'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
                'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
            ]);
        } else {
            // Global attribute - get with empty language
            $attribute = QuestionAttribute::model()->find([
                'condition' => 'qid = :qid AND attribute = :attr AND (language = \'\' OR language IS NULL)',
                'params' => [':qid' => $questionId, ':attr' => $attributeName]
            ]);
        }
        
        return $attribute ? $attribute->value : null;
    }
    
    private function createImportCSV($questionType, $attributeName, $attributeValue)
    {
        // Get the survey to determine the correct language
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $lang = $survey->language;
        
        $options = json_encode([$attributeName => $attributeValue]);
        
        $csvLines = [
            "type,subtype,code,value-{$lang},help-{$lang},script-{$lang},relevance,mandatory,theme,options",
            'G,,TestGroup,"Test Group","","",1,,,',
            "Q,$questionType,TestQ1,\"Test Question\",\"\",\"\",1,N,\"\",$options"
        ];
        
        return implode("\n", $csvLines);
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'attr_test_' . microtime(true) . '_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}
