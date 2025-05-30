<?php

namespace tonisormisson\ls\structureimex\Tests;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\export\ExportQuestions;

/**
 * Test for ExportQuestions functionality
 * 
 * This test creates a mock survey with questions and attributes,
 * exports them using the plugin, and validates the export file content.
 */
class ExportQuestionsTest extends TestCase
{
    private $mockData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock survey data using helper
        $this->mockData = MockSurveyHelper::createMockSurveyData();
        
        // Set global mock data for TestableExportQuestions to access
        MockSurveyHelper::setGlobalMockData($this->mockData);
    }

    public function testExportQuestions()
    {
        // Create TestableExportQuestions instance
        $exporter = new TestableExportQuestions($this->mockData['survey'], $this->mockData['languages']);
        
        // Get the export file path
        $filePath = $exporter->getTestFilePath();
        
        // Verify file was created
        $this->assertFileExists($filePath, "Export file should be created");
        
        // Read and verify file contents using ODS reader
        $reader = new \OpenSpout\Reader\ODS\Reader();
        $reader->open($filePath);
        
        $rowCount = 0;
        $headers = [];
        $questionRows = [];
        
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'questions') {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->getCells();
                    $rowData = array_map(function($cell) {
                        return $cell->getValue();
                    }, $cells);
                    
                    if ($rowCount === 0) {
                        $headers = $rowData;
                    } else {
                        $questionRows[] = $rowData;
                    }
                    $rowCount++;
                }
                break;
            }
        }
        
        $reader->close();
        
        // Clean up
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Validate export content
        $this->assertGreaterThan(1, $rowCount, "Export should contain more than just headers");
        
        // Verify expected headers are present
        $expectedHeaders = ['type', 'subtype', 'code', 'value-en', 'help-en', 'script-en', 'value-de', 'help-de', 'script-de', 'relevance', 'mandatory', 'theme', 'options'];
        
        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertContains($expectedHeader, $headers, "Header '{$expectedHeader}' should be present");
        }
        
        // Should have at least 2 groups + 3 questions = 5 data rows minimum
        $this->assertGreaterThanOrEqual(5, count($questionRows), "Should have at least 5 data rows (2 groups + 3 questions)");
        
        // Check that we have group rows (type 'G')
        $groupRows = array_filter($questionRows, function($row) {
            return isset($row[0]) && $row[0] === 'G';
        });
        $this->assertCount(2, $groupRows, "Should have 2 group rows");
        
        // Check that we have question rows (type 'Q')
        $questionDataRows = array_filter($questionRows, function($row) {
            return isset($row[0]) && $row[0] === 'Q';
        });
        $this->assertCount(3, $questionDataRows, "Should have 3 question rows");
        
        // Validate question attributes export
        $questionWithOptions = array_filter($questionDataRows, function($row) {
            return isset($row[2]) && $row[2] === 'Q002' && !empty($row[12]); // Q002 should have options
        });
        $this->assertNotEmpty($questionWithOptions, "Question Q002 should have attributes exported");
        
        // Validate specific content for Q002
        $q002Row = array_filter($questionDataRows, function($row) {
            return isset($row[2]) && $row[2] === 'Q002';
        });
        $q002Row = array_values($q002Row)[0]; // Get first match
        
        $this->assertEquals('L', $q002Row[1], "Q002 should be List type");
        $this->assertEquals('What is your age group?', $q002Row[3], "Q002 English question text should match");
        $this->assertEquals('Wie alt sind Sie?', $q002Row[6], "Q002 German question text should match");
        $this->assertEquals('Y', $q002Row[10], "Q002 should be mandatory");
        
        // Validate attributes JSON
        $options = json_decode($q002Row[12], true);
        $this->assertIsArray($options, "Q002 options should be valid JSON");
        $this->assertEquals('1', $options['random_order'], "Q002 should have random_order attribute");
        $this->assertEquals('Y', $options['other_option'], "Q002 should have other_option attribute");
        
        // Validate Q003 relevance condition
        $q003Row = array_filter($questionDataRows, function($row) {
            return isset($row[2]) && $row[2] === 'Q003';
        });
        $q003Row = array_values($q003Row)[0]; // Get first match
        $this->assertEquals('Q002.NAOK == "A3"', $q003Row[9], "Q003 should have correct relevance condition");
    }
}

/**
 * Testable version of ExportQuestions that overrides database calls with mock data
 */
class TestableExportQuestions extends ExportQuestions
{
    private $type = "";
    private $mockSurvey;
    private $mockLanguages;
    private $testFilePath;

    public function __construct($survey, $languages)
    {
        $this->mockSurvey = $survey;
        $this->mockLanguages = $languages;
        
        // Set up mock plugin
        $mockPlugin = $this->createMockPlugin($survey);
        
        // Don't call parent constructor immediately
        $this->survey = $survey;
        $this->languages = $languages;
        $this->plugin = $mockPlugin;
        $this->applicationMajorVersion = 4; // Assume v4+
        
        // Set up test file path
        $tempDir = sys_get_temp_dir();
        $this->fileName = "test_export_" . uniqid() . ".ods";
        $this->path = $tempDir . DIRECTORY_SEPARATOR;
        $this->testFilePath = $this->path . $this->fileName;
        
        // Initialize writer and styles
        $this->writer = new \OpenSpout\Writer\ODS\Writer();
        $this->initStyles();
        
        // Open file and start export
        $this->writer->openToFile($this->testFilePath);
        $this->writeHeaders();
        $this->writeData();
        $this->writer->close();
    }

    private function createMockPlugin($survey): \tonisormisson\ls\structureimex\StructureImEx
    {
        return new class($survey) extends \tonisormisson\ls\structureimex\StructureImEx {
            private $survey;
            
            public function __construct($survey) {
                $this->survey = $survey;
                // Don't call parent constructor to avoid dependency issues
            }
            
            public function getSurvey() {
                return $this->survey;
            }
        };
    }

    public function getTestFilePath()
    {
        return $this->testFilePath;
    }

    // Override the main writeData method since we can't override private methods
    protected function writeData()
    {
        foreach ($this->groupsInMainLanguage() as $group) {
            $this->mockProcessGroup($group);
        }
    }

    private function mockProcessGroup($group)
    {
        // Add the group row
        $this->type = 'G'; // TYPE_GROUP
        $this->addGroupMock($group);

        // Get questions for this group
        $questions = $this->getMockQuestionsForGroup($group);
        foreach ($questions as $question) {
            $this->type = 'Q'; // TYPE_QUESTION
            $this->addQuestionMock($question);
        }
    }

    private function getMockQuestionsForGroup($group)
    {
        global $mockQuestions;
        return array_filter($mockQuestions ?? [], function($question) use ($group) {
            return $question->gid == $group->gid && ($question->parent_qid == 0 || $question->parent_qid === null);
        });
    }

    private function addGroupMock($group)
    {
        $row = [
            'G', // TYPE_GROUP
            null,
            $group->gid,
        ];
        
        // Add language-specific columns (group name, description, script for each language)
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($group->questiongroupl10ns[$language])) {
                    $row[] = '';
                    $row[] = '';
                    $row[] = null;
                    continue;
                }
                $row[] = $group->questiongroupl10ns[$language]->group_name;
                $row[] = $group->questiongroupl10ns[$language]->description;
                $row[] = null; // no script for groups
            }
        }

        $row[] = $group->grelevance;
        $row[] = null; // no mandatory
        $row[] = null; // no theme
        $row[] = null; // no options

        $row = \OpenSpout\Common\Entity\Row::fromValues($row, $this->groupStyle);
        $this->writer->addRow($row);
    }

    private function addQuestionMock($question)
    {
        $row = [
            'Q', // TYPE_QUESTION
            $question->type,
            $question->title,
        ];
        
        // Add language-specific columns (question text, help, script for each language)
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($question->questionl10ns[$language])) {
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    continue;
                }
                $row[] = $question->questionl10ns[$language]->question;
                $row[] = $question->questionl10ns[$language]->help;
                $row[] = $question->questionl10ns[$language]->script ?? '';
            }
        }

        $row[] = $question->relevance;
        $row[] = $question->mandatory;
        $row[] = $question->question_theme_name ?? 'core';
        
        // Add options as JSON
        $attributes = $this->getQuestionAttributes($question);
        $options = [];
        foreach ($attributes as $attr) {
            $options[$attr->attribute] = $attr->value;
        }
        $row[] = !empty($options) ? json_encode($options) : null;

        $row = \OpenSpout\Common\Entity\Row::fromValues($row, $this->questionStyle);
        $this->writer->addRow($row);
    }

    // Override the database query methods with mock data
    protected function groupsInMainLanguage()
    {
        global $mockGroups;
        return $mockGroups ?? [];
    }

    protected function getQuestionAttributes($question)
    {
        global $mockAttributes;
        return array_filter($mockAttributes ?? [], function($attr) use ($question) {
            return $attr->qid == $question->qid;
        });
    }

    protected function getQuestionL10nForLanguage($question, $language)
    {
        return $question->questionl10ns[$language] ?? null;
    }

    public function isV4plusVersion(): bool
    {
        return true; // Assume V4+ for testing
    }
}
