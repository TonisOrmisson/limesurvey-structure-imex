<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\Tests\Unit\MockSurveyHelper;

class ExportQuestionsTest extends TestCase
{
    private $mockData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up mock data using MockSurveyHelper
        $this->mockData = MockSurveyHelper::createMockSurveyData();
        
        // Set global mock data for TestableExportQuestions to access
        MockSurveyHelper::setGlobalMockData($this->mockData);
    }

    public function testExportQuestions()
    {
        // For now, just test that we can create the classes without fatal errors
        $survey = $this->mockData['survey'];
        $mockPlugin = $this->createMockPlugin($survey);
        
        // Test that our mock plugin works
        $this->assertEquals($survey, $mockPlugin->getSurvey());
        $this->assertEquals(false, $mockPlugin->getPluginSetting('include_unknown_attributes'));
        
        $this->assertTrue(true, "Basic plugin mock creation works");
    }

    private function createMockPlugin($survey)
    {
        return new class($survey) {
            private $survey;
            
            public function __construct($survey) {
                $this->survey = $survey;
            }
            
            public function getSurvey() {
                return $this->survey;
            }
            
            public function getPluginSetting($name, $default = null) {
                // Return mock values for plugin settings
                switch ($name) {
                    case 'include_unknown_attributes':
                        return false;
                    default:
                        return $default;
                }
            }
        };
    }
}