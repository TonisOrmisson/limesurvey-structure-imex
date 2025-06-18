<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

/**
 * Test that unknown attributes (like other_replace_text3) can be imported
 * when the plugin setting allows it
 */
class UnknownAttributeImportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
    }
    
    /**
     * Test that unknown attributes like other_replace_text3 should be importable
     * This reproduces the manual testing error: "Skipping invalid attributes for question 'k3' (type 'L'): other_replace_text3"
     */
    public function testUnknownAttributeImportShouldNotFail()
    {
        // Create CSV with unknown attribute other_replace_text3 for question type L
        $csvContent = implode("\n", [
            'type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options',
            'G,,TestGroup,"Test Group","","",1,,,',
            'Q,L,k3,"Test Question","","",1,N,,"{""other_replace_text3"":""Custom other text""}"'
        ]);
        
        $csvFile = $this->writeTempCSV($csvContent);
        
        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        
        // Enable unknown attribute import for this test
        $plugin->setSetting('importUnknownAttributes', true, 'Survey', $this->testSurveyId);
        
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
        
        // This should NOT fail - unknown attributes should be importable
        $this->assertEmpty($errors, "Import should succeed without errors, unknown attributes should be allowed: " . print_r($errors, true));
        
        // Find the imported question
        $question = \Question::model()->find('sid = :sid AND title = :title', [
            ':sid' => $this->testSurveyId,
            ':title' => 'k3'
        ]);
        
        $this->assertNotNull($question, "Question should be imported");
        
        // Check if the unknown attribute was saved
        $otherReplaceText3Attr = \QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $question->qid, ':attr' => 'other_replace_text3', ':lang' => '']
        ]);
        
        $this->assertNotNull($otherReplaceText3Attr, "Unknown attribute 'other_replace_text3' should be imported and saved");
        $this->assertEquals('Custom other text', $otherReplaceText3Attr->value, "Unknown attribute should have correct value");
    }
    
    /**
     * Test that standard attributes work normally
     */
    public function testStandardAttributeImportStillWorks()
    {
        // Create CSV with standard attributes for question type L
        $csvContent = implode("\n", [
            'type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options',
            'G,,TestGroup,"Test Group","","",1,,,',
            'Q,L,k1,"Test Question","","",1,N,,"{""hidden"":""1"",""hide_tip"":""1""}"'
        ]);
        
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
        
        // Clean up
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
        
        $this->assertEmpty($errors, "Import should succeed without errors: " . print_r($errors, true));
        
        // Find the imported question
        $question = \Question::model()->find('sid = :sid AND title = :title', [
            ':sid' => $this->testSurveyId,
            ':title' => 'k1'
        ]);
        
        $this->assertNotNull($question, "Question should be imported");
        
        // Check if standard attributes exist
        $hiddenAttr = \QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $question->qid, ':attr' => 'hidden', ':lang' => '']
        ]);
        
        $this->assertNotNull($hiddenAttr, "Standard 'hidden' attribute should exist");
        $this->assertEquals('1', $hiddenAttr->value, "Standard attribute should have correct value");
    }
    
    /**
     * Test that unknown attributes are rejected when the setting is disabled (default behavior)
     */
    public function testUnknownAttributeRejectedByDefault()
    {
        // Create CSV with unknown attribute other_replace_text3 for question type L
        $csvContent = implode("\n", [
            'type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options',
            'G,,TestGroup,"Test Group","","",1,,,',
            'Q,L,k4,"Test Question","","",1,N,,"{""other_replace_text3"":""Custom other text""}"'
        ]);
        
        $csvFile = $this->writeTempCSV($csvContent);
        
        // Import the file
        $plugin = $this->createRealPlugin($this->testSurveyId);
        
        // Explicitly disable unknown attribute import (default behavior)
        $plugin->setSetting('importUnknownAttributes', false, 'Survey', $this->testSurveyId);
        
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
        
        // The import should succeed, but with warnings about unknown attributes
        $this->assertEmpty($errors, "Import should succeed without errors: " . print_r($errors, true));
        
        // Find the imported question
        $question = \Question::model()->find('sid = :sid AND title = :title', [
            ':sid' => $this->testSurveyId,
            ':title' => 'k4'
        ]);
        
        $this->assertNotNull($question, "Question should be imported");
        
        // Check that the unknown attribute was NOT saved
        $otherReplaceText3Attr = \QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $question->qid, ':attr' => 'other_replace_text3', ':lang' => '']
        ]);
        
        $this->assertNull($otherReplaceText3Attr, "Unknown attribute 'other_replace_text3' should NOT be imported when setting is disabled");
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'unknown_attr_test_' . microtime(true) . '_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}