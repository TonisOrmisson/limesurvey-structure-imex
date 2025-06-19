<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

/**
 * Debug test for import functionality issues without crashes
 */
class ImportDebugTest extends DatabaseTestCase
{
    /**
     * Test that import can at least be instantiated and prepare called
     */
    public function testImportCanPrepareFile()
    {
        // Import a survey
        $blankSurveyPath = $this->getBlankSurveyPath();
        $surveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        $plugin = $this->createRealPlugin($surveyId);
        $survey = \Survey::model()->findByPk($surveyId);
        
        // Create simple CSV with group first, then question
        $csvContent = "type,subtype,code,value-en,help-en,script-en,relevance,mandatory,theme,options\n";
        $csvContent .= "G,,TestGroup,\"Test Group\",\"\",\"\",1,,,\n"; // Group row first
        $csvContent .= "Q,L,TestQ1,\"Test Question\",\"\",\"\",1,N,\"\",\"{\\\"hide_tip\\\":\\\"1\\\"}\"\n"; // Question row
        
        $tempFile = tempnam(sys_get_temp_dir(), 'debug_test_') . '.csv';
        file_put_contents($tempFile, $csvContent);
        
        // Create import instance
        $import = new \tonisormisson\ls\structureimex\import\ImportStructure($plugin, $survey);
        $import->fileName = $tempFile;
        
        $import->prepare();

        // Check if data was read
        $reflection = new \ReflectionClass($import);
        $readerDataProperty = $reflection->getProperty('readerData');
        $readerDataProperty->setAccessible(true);
        $readerData = $readerDataProperty->getValue($import);

        $import->process();

        // Add assertion to make test valid
        $this->assertNotNull($readerData, 'Reader data should be populated after prepare()');

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
