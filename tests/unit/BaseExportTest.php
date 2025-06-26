<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base test class for export functionality tests
 * Provides shared setup for export tests including Survey mocks
 */
abstract class BaseExportTest extends TestCase
{
    protected $mockData;
    protected $mockSurvey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up mock data using MockSurveyHelper
        $this->mockData = MockSurveyHelper::createMockSurveyData();
        
        // Create real Survey mock directly in test case (required for protected createMock method)
        $this->mockSurvey = $this->createMock(\Survey::class);
        $this->mockSurvey->method('getAllLanguages')->willReturn(['en', 'de']);
        $this->mockSurvey->method('getPrimaryKey')->willReturn(123456);
        
        // Configure mock properties for quota export functionality
        $this->mockSurvey->language = 'en';
        $this->mockSurvey->additional_languages = '';
        $this->mockSurvey->sid = 123456;
        
        // Set global mock data for testable export classes to access
        MockSurveyHelper::setGlobalMockData($this->mockData);
    }
}