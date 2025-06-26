<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\PersistentWarningManager;

/**
 * Base test class for export functionality tests
 * Provides shared setup for export tests including Survey mocks
 */
abstract class BaseExportTest extends TestCase
{
    protected $mockData;
    /** @var \Survey */
    protected $mockSurvey;

    /** @var PersistentWarningManager */
    protected $warningManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up mock data using MockSurveyHelper
        $this->mockData = MockSurveyHelper::createMockSurveyData();
        
        // Create real Survey mock directly in test case (required for protected createMock method)
        $this->mockSurvey = $this->createMock(\Survey::class);
        $this->mockSurvey->method('getAllLanguages')->willReturn(['en', 'de']);
        $this->mockSurvey->method('getPrimaryKey')->willReturn(123456);
        
        // Configure mock properties for import/export functionality
        $this->mockSurvey->language = 'en';
        $this->mockSurvey->additional_languages = '';
        $this->mockSurvey->sid = 123456;
        
        // Ensure property access works for the mock
        $this->mockSurvey->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                switch($property) {
                    case 'sid': return 123456;
                    case 'language': return 'en';
                    case 'additional_languages': return '';
                    default: return null;
                }
            });

        $session = new \CHttpSession();
        $mockWarningManager = $this->getMockBuilder(PersistentWarningManager::class)
            ->setConstructorArgs([$session])
            ->getMock();



        $this->warningManager = $mockWarningManager;

        // Set global mock data for testable export classes to access
        MockSurveyHelper::setGlobalMockData($this->mockData);
    }
}
