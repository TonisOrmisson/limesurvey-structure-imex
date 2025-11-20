<?php

namespace tonisormisson\ls\structureimex\tests\functional\export;

use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

class MultiFlexExportTest extends DatabaseTestCase
{
    private int $groupId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $this->groupId = $this->createTestGroup($this->testSurveyId, 'MultiFlex Group', 1);
    }

    public function testTypeTSubQuestionsExportAsAnswers(): void
    {
        $survey = \Survey::model()->findByPk($this->testSurveyId);

        $questionId = $this->createTestQuestion(
            $this->testSurveyId,
            $this->groupId,
            'MF001',
            \Question::QT_COLON_ARRAY_NUMBERS,
            'Rate the combinations'
        );

        $sqExpression = 'self.sq_1_TOT == (self.sq_1_PER + self.sq_1_BUS)';
        $sqTip = 'Totals must equal the sum of PER and BUS.';
        $this->createTestAttribute($questionId, 'em_validation_sq', $sqExpression, '');
        $this->createTestAttribute($questionId, 'em_validation_sq_tip', $sqTip);

        $this->createMultiFlexSubQuestion(
            $survey,
            $questionId,
            'MF_ROW1',
            \Question::QT_COLON_ARRAY_NUMBERS,
            0,
            'Row Label 1'
        );

        $this->createMultiFlexSubQuestion(
            $survey,
            $questionId,
            'MF_COL1',
            \Question::QT_T_LONG_FREE_TEXT,
            1,
            'Column Label 1'
        );

        $exportFile = $this->exportQuestionsToTempFile($survey);

        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($exportFile);
        $sheet = $reader->getSheetIterator()->current();

        $header = null;
        $columnAsAnswer = false;
        $columnAsSubQuestion = false;
        $rowAsSubQuestion = false;
        $questionOptions = null;
        $questionLangOptions = null;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            if ($header === null) {
                $header = array_map(static fn($cell) => $cell->getValue(), $cells);
                continue;
            }

            $rowData = $this->mapRow($header, $cells);

            if (($rowData['code'] ?? null) === 'MF_COL1') {
                if (($rowData['type'] ?? null) === ExportQuestions::TYPE_ANSWER) {
                    $columnAsAnswer = true;
                    $this->assertEquals('Column Label 1', $rowData['value-en'] ?? '');
                }
                if (($rowData['type'] ?? null) === ExportQuestions::TYPE_SUB_QUESTION) {
                    $columnAsSubQuestion = true;
                }
            }

            if (($rowData['code'] ?? null) === 'MF_ROW1' && ($rowData['type'] ?? null) === ExportQuestions::TYPE_SUB_QUESTION) {
                $rowAsSubQuestion = true;
            }

            if (($rowData['code'] ?? null) === 'MF001' && ($rowData['type'] ?? null) === ExportQuestions::TYPE_QUESTION) {
                $questionOptions = $rowData['options'] ?? null;
                $questionLangOptions = $rowData['options-en'] ?? null;
            }
        }

        $reader->close();
        unlink($exportFile);

        $this->assertTrue($columnAsAnswer, 'Type T subquestion should be exported as an answer row');
        $this->assertFalse($columnAsSubQuestion, 'Type T subquestion must not be exported as a subquestion row');
        $this->assertTrue($rowAsSubQuestion, 'Non-T subquestions should remain subquestion rows');

        $this->assertNotNull($questionOptions, 'Global options JSON must exist for the question');
        $decodedOptions = json_decode((string)$questionOptions, true);
        $this->assertIsArray($decodedOptions, 'Global options JSON must decode to an array');
        $this->assertSame($sqExpression, $decodedOptions['em_validation_sq'] ?? null);

        $this->assertNotNull($questionLangOptions, 'Language options JSON must exist for the base language');
        $decodedLangOptions = json_decode((string)$questionLangOptions, true);
        $this->assertIsArray($decodedLangOptions, 'Language-specific options JSON must decode to an array');
        $this->assertSame($sqTip, $decodedLangOptions['em_validation_sq_tip'] ?? null);
    }

    private function createMultiFlexSubQuestion(\Survey $survey, int $parentQid, string $code, string $type, int $scaleId, string $label): void
    {
        $subQuestion = new \Question();
        $subQuestion->sid = $survey->sid;
        $subQuestion->gid = $this->groupId;
        $subQuestion->type = $type;
        $subQuestion->title = $code;
        $subQuestion->parent_qid = $parentQid;
        $subQuestion->scale_id = $scaleId;
        $subQuestion->question_order = $scaleId === 0 ? 1 : 2;
        $subQuestion->other = 'N';
        $subQuestion->mandatory = 'N';
        $subQuestion->relevance = '1';
        $subQuestion->modulename = '';

        if (!$subQuestion->save()) {
            throw new \RuntimeException('Failed to create multi-flex subquestion: ' . print_r($subQuestion->getErrors(), true));
        }

        if (class_exists('QuestionL10n')) {
            $l10n = new \QuestionL10n();
            $l10n->qid = $subQuestion->qid;
            $l10n->language = $survey->language ?? 'en';
            $l10n->question = $label;
            $l10n->help = '';
            if (!$l10n->save()) {
                throw new \RuntimeException('Failed to create subquestion l10n: ' . print_r($l10n->getErrors(), true));
            }
        }
    }

    private function exportQuestionsToTempFile(\Survey $survey): string
    {
        $exportClass = new \ReflectionClass(ExportQuestions::class);
        $export = $exportClass->newInstanceWithoutConstructor();

        $pathProperty = $exportClass->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($export, \Yii::app()->runtimePath . '/');

        $constructor = $exportClass->getConstructor();
        $constructor->invoke($export, $survey);

        if ($export->writer) {
            $export->writer->close();
        }

        return $export->getFullFileName();
    }

    private function mapRow(array $header, array $cells): array
    {
        $rowData = [];
        foreach ($header as $index => $columnName) {
            $rowData[$columnName] = isset($cells[$index]) ? ($cells[$index]->getValue() ?? null) : null;
        }
        return $rowData;
    }
}
