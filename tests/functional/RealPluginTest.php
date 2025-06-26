<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use tonisormisson\ls\structureimex\StructureImEx;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\import\ImportStructure;
use Survey;

/**
 * Test that verifies the real StructureImEx plugin can be instantiated and works correctly
 * This proves that we don't need to mock our own plugin - we can test it directly
 */
class RealPluginTest extends DatabaseTestCase
{
    private $importedSurveyId;
    private $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for file operations
        $this->createRuntimeDirectories();
        
        // Import a survey for testing
        $blankSurveyPath = __DIR__ . '/../support/data/surveys/blank-survey.lss';
        $this->importedSurveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        // Create real plugin instance
        $this->plugin = $this->createRealPlugin($this->importedSurveyId);
    }

    protected function tearDown(): void
    {
        // Clean up runtime directories
        $this->cleanupRuntimeDirectories();
        
        parent::tearDown();
    }

    /**
     * Test that we can create a real StructureImEx plugin instance
     */
    public function testCanCreateRealPlugin()
    {

        // Verify plugin instance
        $this->assertInstanceOf(StructureImEx::class, $this->plugin, 'Should create real StructureImEx instance');
        
        // Verify plugin has survey set
        $survey = $this->plugin->getSurvey();
        $this->assertNotNull($survey, 'Plugin should have survey set');
        $this->assertInstanceOf(Survey::class, $survey, 'Plugin survey should be Survey instance');
        $this->assertEquals($this->importedSurveyId, $survey->sid, 'Plugin should have correct survey');
    }

    /**
     * Test that we can create export classes with real plugin
     */
    public function testCanCreateExporterWithRealPlugin()
    {

        // Test creating ExportQuestions
        $exporter = new ExportQuestions($this->plugin);
        $this->assertInstanceOf(ExportQuestions::class, $exporter, 'Should create ExportQuestions with real plugin');
        
        // Verify exporter has access to survey
        $survey = $this->plugin->getSurvey();
        $this->assertNotNull($survey, 'Exporter should have access to survey through plugin');
    }

    /**
     * Test that we can create import classes with real plugin
     */
    public function testCanCreateImporterWithRealPlugin()
    {

        // Test creating ImportStructure
        $importer = new ImportStructure($this->plugin->getSurvey(), $this->warningManager);
        $this->assertInstanceOf(ImportStructure::class, $importer, 'Should create ImportStructure with real plugin');
        
        // Verify importer has access to survey
        $survey = $this->plugin->getSurvey();
        $this->assertNotNull($survey, 'Importer should have access to survey through plugin');
        $this->assertEquals($this->importedSurveyId, $survey->sid, 'Importer should have correct survey');
    }

    /**
     * Test plugin settings functionality
     */
    public function testPluginSettings()
    {

        // Test getImportUnknownAttributes (default should be false)
        $importUnknown = $this->plugin->getImportUnknownAttributes();
        $this->assertIsBool($importUnknown, 'getImportUnknownAttributes should return boolean');
        $this->assertFalse($importUnknown, 'Default should be false');
    }

    /**
     * Test that plugin can access LimeSurvey's plugin manager
     */
    public function testPluginManagerIntegration()
    {

        // Verify plugin manager is available
        $pluginManager = \Yii::app()->getPluginManager();
        $this->assertNotNull($pluginManager, 'Plugin manager should be available');
        
        // Verify our plugin can be instantiated through plugin manager
        $plugin = new StructureImEx($pluginManager, 999);
        $this->assertInstanceOf(StructureImEx::class, $plugin, 'Should create plugin through plugin manager');
    }

    /**
     * Test warning manager functionality
     */
    public function testWarningManager()
    {

        // Test warning manager creation
        $warningManager = $this->plugin->getWarningManager();
        $this->assertNotNull($warningManager, 'Should be able to get warning manager');
        $this->assertInstanceOf('tonisormisson\ls\structureimex\PersistentWarningManager', $warningManager, 'Should be correct warning manager type');
        
        // Test that subsequent calls return same instance
        $warningManager2 = $this->plugin->getWarningManager();
        $this->assertSame($warningManager, $warningManager2, 'Should return same warning manager instance');
    }

    // Helper methods

    private function createRuntimeDirectories(): void
    {
        // Create tmp/runtime directory structure that the plugin expects
        $directories = [
            'tmp',
            'tmp/runtime'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function cleanupRuntimeDirectories(): void
    {
        // Clean up any files created during testing
        if (is_dir('tmp/runtime')) {
            $files = glob('tmp/runtime/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Remove directories (in reverse order)
        $directories = ['tmp/runtime', 'tmp'];
        foreach ($directories as $dir) {
            if (is_dir($dir) && count(scandir($dir)) == 2) { // Only . and ..
                rmdir($dir);
            }
        }
    }


}
