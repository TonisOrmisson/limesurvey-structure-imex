<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use LimeSurvey\Api\Command\Exception;
use tonisormisson\ls\structureimex\import\ImportStructure;

/**
 * Unit tests for ImportStructure business logic
 */
class ImportStructureTest extends BaseExportTest
{
    public function testImportStructureCreation()
    {
        $import = new ImportStructure($this->mockSurvey, $this->warningManager);
        
        $this->assertInstanceOf(ImportStructure::class, $import);
    }

    public function testSetClearSurveyContents()
    {
        $import = new ImportStructure($this->mockSurvey, $this->warningManager);
        
        // Test default value (should be false)
        $this->assertFalse($this->getClearSurveyContents($import));
        
        // Test setting to true
        $import->setClearSurveyContents(true);
        $this->assertTrue($this->getClearSurveyContents($import));
        
        // Test setting to false
        $import->setClearSurveyContents(false);
        $this->assertFalse($this->getClearSurveyContents($import));
    }

    public function testClearSurveyContentsWithActiveSurvey()
    {
        // Create a mock survey with proper sid property 
        $activeSurvey = $this->createMock(\Survey::class);
        $activeSurvey->method('getIsActive')->willReturn(true);
        
        // Use a stub to avoid ImportFromFile constructor validation
        $import = $this->getMockBuilder(ImportStructure::class)
            ->setConstructorArgs([$this->mockSurvey, $this->warningManager])
            ->onlyMethods([])
            ->getMock();
            
        $import->setClearSurveyContents(true);
        
        // Should throw exception when trying to clear active survey  
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot clear contents of an active survey");
        
        // Set the survey property using reflection to use our active mock
        $reflection = new \ReflectionClass($import);
        $surveyProperty = $reflection->getProperty('survey');
        $surveyProperty->setAccessible(true);
        $surveyProperty->setValue($import, $activeSurvey);
        
        // Use reflection to call private method
        $method = $reflection->getMethod('clearAllSurveyContents');
        $method->setAccessible(true);
        $method->invoke($import);
    }

    /**
     * Helper method to get private clearSurveyContents property using reflection
     */
    private function getClearSurveyContents(ImportStructure $import): bool
    {
        $reflection = new \ReflectionClass($import);
        $property = $reflection->getProperty('clearSurveyContents');
        $property->setAccessible(true);
        return $property->getValue($import);
    }

    public function testAttributeNames()
    {
        $import = new ImportStructure($this->mockSurvey, $this->warningManager);
        
        $attributes = $import->attributeNames();
        
        $this->assertIsArray($attributes);
        $this->assertNotEmpty($attributes);
    }

    public function testPrepareMethod()
    {
        // Create a temporary test file
        $testFile = sys_get_temp_dir() . '/test_import.xlsx';
        
        // Create minimal valid Excel file content
        $this->createTestExcelFile($testFile);
        
        $import = new ImportStructure($this->mockSurvey, $this->warningManager);
        $import->fileName = $testFile;

        $errors = $import->getErrors();
        if(count($errors) > 0) {
            throw new Exception("Import ended with errors: ". json_encode($errors));
        }
        // Test the prepare method returns boolean
        $result = $import->prepare();
        
        $this->assertIsBool($result);
        
        // Clean up
        if (file_exists($testFile)) {
            unlink($testFile);
        }
    }


    public function testLoadFileMethod()
    {
        // Create a temporary test file
        $testFile = sys_get_temp_dir() . '/test_load.xlsx';
        $this->createTestExcelFile($testFile);
        
        // Create mock uploaded file with property access
        $mockFile = $this->createMock(\CUploadedFile::class);
        $mockFile->method('getTempName')->willReturn($testFile);
        $mockFile->method('getName')->willReturn('test.xlsx');
        $mockFile->method('saveAs')->willReturnCallback(function($targetPath) use ($testFile) {
            return copy($testFile, $targetPath);
        });
        
        // Mock property access for 'name' property
        $mockFile->method('__get')->willReturnCallback(function($property) {
            if ($property === 'name') {
                return 'test.xlsx';
            }
            return null;
        });

        // Create import and set custom temp directory to bypass Yii app dependency
        $import = new ImportStructure($this->mockSurvey, $this->warningManager);
        $import->setCustomTempDir(__DIR__ . '/../runtime');
        
        // Test loadFile method
        $result = $import->loadFile($mockFile);
        $errors = $import->getErrors();
        if(count($errors) > 0) {
            throw new Exception("import ended with errors:" .json_encode($errors));
        }

        $this->assertTrue($result);
        
        // Clean up
        if (file_exists($testFile)) {
            unlink($testFile);
        }
    }
    
    private function createTestExcelFile($filePath)
    {
        // Create a minimal Excel file using OpenSpout
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($filePath);
        
        // Add test data with required language columns
        $row1 = \OpenSpout\Common\Entity\Row::fromValues(['type', 'code', 'title-en', 'value-en']);
        $row2 = \OpenSpout\Common\Entity\Row::fromValues(['G', 'group1', 'Test Group', '']);
        
        $writer->addRow($row1);
        $writer->addRow($row2);
        
        $writer->close();
    }
    
    private function createTestExcelFileWithQuestions($filePath)
    {
        // Create a more complex Excel file with groups and questions
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($filePath);
        
        // Add header with required language columns
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'type', 'code', 'title-en', 'question-en', 'relevance', 'value-en'
        ]));
        
        // Add test group
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'G', 'demographics', 'Demographics', '', '', ''
        ]));
        
        // Add test questions (use Q for question type)
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'Q', 'name', 'Your name', 'What is your name?', '1', ''
        ]));
        
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'Q', 'gender', 'Gender', 'What is your gender?', '1', ''
        ]));
        
        $writer->close();
    }
}
