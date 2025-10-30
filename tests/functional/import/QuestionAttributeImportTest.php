<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Question;
use QuestionAttribute;
use PHPUnit\Framework\Attributes\DataProvider;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test IMPORT of question attributes - global vs language-specific handling
 */
class QuestionAttributeImportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
    }
    
    /**
     * Data provider: Question types with attributes to test import
     */
    public static function questionTypeAttributeProvider()
    {
        return [
            // Global attributes
            [\Question::QT_L_LIST, 'hidden', '0', '1'],
            [\Question::QT_L_LIST, 'hide_tip', '0', '1'], 
            [\Question::QT_T_LONG_FREE_TEXT, 'maximum_chars', '', '500'],
            [\Question::QT_A_ARRAY_5_POINT, 'answer_width', '', '35'],

            // Language-specific attributes  
            [\Question::QT_L_LIST, 'em_validation_q_tip', '', 'Validation tip text'],
            [\Question::QT_L_LIST, 'other_replace_text', '', 'Other option'],
        ];
    }
    
    /**
     * @dataProvider questionTypeAttributeProvider
     */
    #[DataProvider('questionTypeAttributeProvider')]
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
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructure($survey, $this->warningManager);
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
