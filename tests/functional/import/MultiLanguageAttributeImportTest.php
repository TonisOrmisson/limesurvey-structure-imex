<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;

/**
 * Test multi-language attribute import functionality
 * 
 * Verifies that the import can properly handle both global "options" column
 * and language-specific "options-{lang}" columns for round-trip compatibility.
 */
class MultiLanguageAttributeImportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();

        // Import the multi-language survey for testing
        $this->testSurveyId = $this->importSurveyFromFile($this->getMultiLanguageSurveyPath());
    }
    
    /**
     * Test that language-specific columns are detected and processed correctly
     */
    public function testLanguageSpecificColumnDetection()
    {
        // Create a test question
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'TestQ1', \Question::QT_L_LIST, 'Test Question');
        
        // Create CSV with both global and language-specific options
        $csvContent = $this->createMultiLanguageCSV();
        $csvFile = $this->writeTempCSV($csvContent);
        
        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin);
        $import->fileName = $csvFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed");
        
        $processResult = $import->process();
        $errors = $import->getErrors();
        
        // Clean up
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
        
        $this->assertEmpty($errors, "Import should succeed without errors: " . print_r($errors, true));
        
        // Verify that global attributes were saved correctly
        $hiddenAttr = $this->getQuestionAttribute($questionId, 'hidden', '');
        $this->assertEquals('1', $hiddenAttr, "Global 'hidden' attribute should be '1'");
        
        // Verify that language-specific attributes were saved correctly for each language
        $enValidationTip = $this->getQuestionAttribute($questionId, 'em_validation_q_tip', 'en');
        $this->assertEquals('English validation tip', $enValidationTip, "English validation tip should be saved correctly");
        
        $etValidationTip = $this->getQuestionAttribute($questionId, 'em_validation_q_tip', 'et');
        $this->assertEquals('Estonian validation tip', $etValidationTip, "Estonian validation tip should be saved correctly");
        
        // Verify that language-specific attributes have different values per language
        $this->assertNotEquals($enValidationTip, $etValidationTip, "Language-specific attributes should have different values per language");
    }
    
    /**
     * Test round-trip compatibility (export → import → export should yield same result)
     */
    public function testRoundTripCompatibility()
    {
        // Create a question with mixed global and language-specific attributes
        $questionId = $this->createTestQuestion($this->testSurveyId, $this->getOrCreateGroup(), 'RoundTrip', \Question::QT_L_LIST, 'Round Trip Question');
        
        // Set up mixed attributes
        $this->setGlobalAttribute($questionId, 'hidden', '1');
        $this->setGlobalAttribute($questionId, 'hide_tip', '1');
        $this->setLanguageSpecificAttribute($questionId, 'em_validation_q_tip', 'en', 'Original English tip');
        $this->setLanguageSpecificAttribute($questionId, 'em_validation_q_tip', 'et', 'Original Estonian tip');
        $this->setLanguageSpecificAttribute($questionId, 'other_replace_text', 'en', 'Other (English)');
        $this->setLanguageSpecificAttribute($questionId, 'other_replace_text', 'et', 'Muu (Estonian)');

        // Export the survey
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $exportClass = new \ReflectionClass('\\tonisormisson\\ls\\structureimex\\export\\ExportQuestions');
        $export = $exportClass->newInstanceWithoutConstructor();
        $pathProperty = $exportClass->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($export, \Yii::app()->runtimePath . '/');
        
        $constructor = $exportClass->getConstructor();
        $constructor->invoke($export, $plugin);
        
        $exportFile = $export->getFullFileName();
        $this->assertFileExists($exportFile, "Export file should be created");
        
        // Delete the original question
        Question::model()->deleteByPk($questionId);

        // Import the exported file back
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin);
        $import->fileName = $exportFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Re-import prepare should succeed");
        
        $processResult = $import->process();
        $errors = $import->getErrors();
        $this->assertEmpty($errors, "Re-import should succeed without errors: " . print_r($errors, true));
        
        // Find the re-imported question
        $reimportedQuestion = Question::model()->find('sid = :sid AND title = :title', [
            ':sid' => $this->testSurveyId,
            ':title' => 'RoundTrip'
        ]);


        $this->assertNotNull($reimportedQuestion, "Re-imported question should exist");

        // Verify that all attributes were preserved correctly
        $this->assertEquals('1', $this->getQuestionAttribute($reimportedQuestion->qid, 'hidden', ''), "Global 'hidden' should be preserved");
        $this->assertEquals('1', $this->getQuestionAttribute($reimportedQuestion->qid, 'hide_tip', ''), "Global 'hide_tip' should be preserved");

        $this->assertEquals('Original English tip', $this->getQuestionAttribute($reimportedQuestion->qid, 'em_validation_q_tip', 'en'), "English validation tip should be preserved");
        $this->assertEquals('Original Estonian tip', $this->getQuestionAttribute($reimportedQuestion->qid, 'em_validation_q_tip', 'et'), "Estonian validation tip should be preserved");

        $this->assertEquals('Other (English)', $this->getQuestionAttribute($reimportedQuestion->qid, 'other_replace_text', 'en'), "English other text should be preserved");
        $this->assertEquals('Muu (Estonian)', $this->getQuestionAttribute($reimportedQuestion->qid, 'other_replace_text', 'et'), "Estonian other text should be preserved");
        // Clean up
        if (file_exists($exportFile)) {
            unlink($exportFile);
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
    
    private function setGlobalAttribute($questionId, $attributeName, $value)
    {
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND (language = \'\' OR language IS NULL)',
            'params' => [':qid' => $questionId, ':attr' => $attributeName]
        ]);
        
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = '';
        
        if (!$attribute->save()) {
            throw new \Exception("Failed to save global attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
    
    private function setLanguageSpecificAttribute($questionId, $attributeName, $language, $value)
    {
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = $language;
        
        if (!$attribute->save()) {
            throw new \Exception("Failed to save language-specific attribute $attributeName ($language): " . print_r($attribute->getErrors(), true));
        }
    }
    
    private function getQuestionAttribute($questionId, $attributeName, $language)
    {
        $attribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        return $attribute ? $attribute->value : null;
    }
    
    private function createMultiLanguageCSV()
    {
        $csvLines = [
            // Header with both global and language-specific options columns
            'type,subtype,code,value-en,help-en,script-en,value-et,help-et,script-et,relevance,mandatory,theme,options,options-en,options-et',
            
            // Group
            'G,,TestGroup,"Test Group","","","Test Grupp","","",1,,,,,',
            
            // Question with mixed global and language-specific attributes
            'Q,L,TestQ1,"Test Question","","","Test küsimus","","",1,N,"",' .
            '"{""hidden"":""1"",""hide_tip"":""1""}",' . // Global attributes
            '"{""em_validation_q_tip"":""English validation tip"",""other_replace_text"":""Other (English)""}",' . // English-specific
            '"{""em_validation_q_tip"":""Estonian validation tip"",""other_replace_text"":""Muu (Estonian)""}"'  // Estonian-specific
        ];
        
        return implode("\n", $csvLines);
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'multilang_test_' . microtime(true) . '_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}
