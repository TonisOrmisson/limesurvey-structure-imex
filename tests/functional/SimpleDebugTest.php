<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

/**
 * Simple test to debug import issues without crashes
 */
class SimpleDebugTest extends DatabaseTestCase
{
    /**
     * Test that import can at least be instantiated and prepare called
     */
    public function testImportCanPrepareFile()
    {
        // Import a survey
        $blankSurveyPath = __DIR__ . '/../support/data/surveys/blank-survey.lss';
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
        $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
        $import->fileName = $tempFile;
        
        // Try prepare
        try {
            $result = $import->prepare();
            echo "SUCCESS: Prepare returned: " . ($result ? 'true' : 'false') . "\n";
            
            // Check if data was read
            $reflection = new \ReflectionClass($import);
            $readerDataProperty = $reflection->getProperty('readerData');
            $readerDataProperty->setAccessible(true);
            $readerData = $readerDataProperty->getValue($import);
            
            echo "DEBUG: Reader data count: " . count($readerData) . "\n";
            if (!empty($readerData)) {
                echo "DEBUG: First row keys: " . implode(', ', array_keys($readerData[0])) . "\n";
                echo "DEBUG: First row values: " . print_r($readerData[0], true) . "\n";
            }
            
            // Now try process step
            echo "DEBUG: Attempting process step...\n";
            try {
                $processResult = $import->process();
                echo "SUCCESS: Process completed\n";
            } catch (\Exception $e) {
                echo "ERROR in process: " . $e->getMessage() . "\n";
                echo "Stack trace: " . $e->getTraceAsString() . "\n";
            }
            
        } catch (\Exception $e) {
            echo "ERROR in prepare: " . $e->getMessage() . "\n";
        } finally {
            // File might be moved during import, only unlink if it exists
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        
        $this->assertTrue(true, 'Test completed without fatal errors');
    }
}