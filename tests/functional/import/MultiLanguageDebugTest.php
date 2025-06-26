<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

/**
 * Debug test for multi-language attribute import
 */
class MultiLanguageDebugTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
    }
    
    /**
     * Test basic global attribute import only
     */
    public function testBasicGlobalAttributeImport()
    {
        // Create simple CSV with just global attributes
        // Don't quote the JSON field to avoid CSV parsing issues
        $csvContent = implode("\n", [
            'type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options',
            'G,,TestGroup,"Test Group","","",1,,,',
            'Q,L,TestQ1,"Test Question","","",1,N,,"{""hidden"":""1"",""hide_tip"":""1""}"'
        ]);
        
        $csvFile = $this->writeTempCSV($csvContent);
        $survey = \Survey::model()->findByPk($this->testSurveyId);
        if (!$survey) {
            throw new \Exception("Survey {$this->testSurveyId} not found for plugin setup");
        }



        // Import the file
        $import = new \tonisormisson\ls\structureimex\import\ImportStructure($survey, $this->warningManager);
        $import->fileName = $csvFile;
        
        $prepareResult = $import->prepare();
        $this->assertTrue($prepareResult, "Import prepare should succeed");
        
        $processResult = $import->process();
        $errors = $import->getErrors();
        
        // Clean up
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
        
        $this->assertEmpty($errors, "Import should succeed without errors");
        
        // Find the imported question
        $question = \Question::model()->find('sid = :sid AND title = :title', [
            ':sid' => $this->testSurveyId,
            ':title' => 'TestQ1'
        ]);
        
        $this->assertNotNull($question, "Question should be imported");
        
        // Check if attributes exist
        $hiddenAttr = \QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $question->qid, ':attr' => 'hidden', ':lang' => '']
        ]);
        
        $this->assertNotNull($hiddenAttr, "Hidden attribute should exist");
        $this->assertEquals('1', $hiddenAttr->value, "Hidden attribute should be '1'");
    }
    
    private function writeTempCSV($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'debug_test_' . microtime(true) . '_') . '.csv';
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}
