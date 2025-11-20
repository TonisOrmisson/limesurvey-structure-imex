<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use OpenSpout\Common\Entity\Row;
use tonisormisson\ls\structureimex\export\ExportRelevances;

class ExportRelevancesTest extends BaseExportTest
{
    public function testExportsRelevanceRowsWhenGroupLanguageMissing(): void
    {
        $survey = $this->createMock(\Survey::class);
        $survey->method('getAllLanguages')->willReturn(['en']);
        $survey->method('getPrimaryKey')->willReturn(123456);
        $survey->language = 'en';
        $survey->sid = 123456;

        $group = $this->createMock(\QuestionGroup::class);
        $group->method('getPrimaryTitle')->willReturn('Demographics');
        $group->language = null;
        $group->grelevance = '';

        $question = $this->createMock(\Question::class);
        $question->title = 'Q001';
        $question->relevance = '';

        $subQuestion = $this->createMock(\Question::class);
        $subQuestion->title = 'SQ001';
        $subQuestion->relevance = 'gender.NAOK == "M"';

        $question->method('__get')->willReturnCallback(static function ($name) use ($subQuestion) {
            return match ($name) {
                'subquestions' => [$subQuestion],
                'title' => 'Q001',
                'relevance' => '',
                default => null,
            };
        });
        $group->method('__get')->willReturnCallback(static function ($name) use ($question) {
            return $name === 'questions' ? [$question] : null;
        });
        $survey->method('__get')->willReturnCallback(static function ($name) use ($group) {
            return match ($name) {
                'groups' => [$group],
                'language' => 'en',
                'sid' => 123456,
                default => null,
            };
        });

        $subQuestion->method('__get')->willReturnCallback(static function ($name) {
            return match ($name) {
                'title' => 'SQ001',
                'relevance' => 'gender.NAOK == "M"',
                default => null,
            };
        });

        $question->subquestions = [$subQuestion];
        $group->questions = [$question];

        $this->assertSame('en', $survey->language);
        $this->assertNull($group->language);
        $this->assertNotSame($survey->language, $group->language);

        $export = new TestableExportRelevances($survey);
        $export->runExport();

        $rows = $export->getSheetRows('relevances');

        $this->assertGreaterThan(1, count($rows), 'Relevance export should produce data rows');
        $this->assertSame(['Demographics', null, null, '1'], $rows[1]);
        $this->assertSame([null, 'Q001', null, '1'], $rows[2]);
        $this->assertSame([null, 'SQ001', 'Q001', 'gender.NAOK == "M"'], $rows[3]);
    }
}

class TestableExportRelevances extends ExportRelevances
{
    private TestCollectingWriter $testWriter;

    public function __construct($survey)
    {
        $this->survey = $survey;
        $this->path = sys_get_temp_dir() . '/';
        $this->fileName = 'relevances_test.xlsx';
        $this->sheetName = 'relevances';
        $this->languages = $survey->getAllLanguages();
        $this->applicationMajorVersion = 3;
        $this->initStyles();

        $this->testWriter = new TestCollectingWriter();
        $this->writer = $this->testWriter;
    }

    public function runExport(): void
    {
        $this->writer->openToFile(null);
        $this->writeHeaders();
        $this->writeData();
        $this->writer->close();
    }

    public function getSheetRows(string $sheetName): array
    {
        return $this->testWriter->getSheetRows($sheetName);
    }
}

class TestCollectingWriter
{
    /** @var array<int, TestCollectingSheet> */
    private array $sheets = [];
    private int $currentIndex = 0;
    /** @var array<string, array<int, array<int, mixed>>> */
    private array $rowsBySheet = [];

    public function openToFile($path): void
    {
    }

    public function close(): void
    {
    }

    public function getCurrentSheet(): TestCollectingSheet
    {
        if (!isset($this->sheets[$this->currentIndex])) {
            $this->sheets[$this->currentIndex] = new TestCollectingSheet($this);
        }
        return $this->sheets[$this->currentIndex];
    }

    public function addNewSheetAndMakeItCurrent(): TestCollectingSheet
    {
        $this->currentIndex = count($this->sheets);
        $sheet = new TestCollectingSheet($this);
        $this->sheets[$this->currentIndex] = $sheet;
        return $sheet;
    }

    public function addRow(Row $row): void
    {
        $sheetName = $this->getCurrentSheet()->getName();
        $this->ensureSheetEntry($sheetName);
        $this->rowsBySheet[$sheetName][] = array_map(static function ($cell) {
            return $cell->getValue();
        }, $row->getCells());
    }

    /**
     * @param Row[] $rows
     */
    public function addRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }

    public function ensureSheetEntry(string $name): void
    {
        if (!array_key_exists($name, $this->rowsBySheet)) {
            $this->rowsBySheet[$name] = [];
        }
    }

    public function registerSheet(string $name): void
    {
        $this->ensureSheetEntry($name);
    }

    public function getSheetRows(string $sheetName): array
    {
        return $this->rowsBySheet[$sheetName] ?? [];
    }
}

class TestCollectingSheet
{
    private string $name = '';

    public function __construct(private TestCollectingWriter $writer)
    {
    }

    public function setName($name): void
    {
        $this->name = $name;
        $this->writer->registerSheet($name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
