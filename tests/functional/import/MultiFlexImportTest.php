<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;
use Question;
use QuestionAttribute;
use QuestionL10n;

class MultiFlexImportTest extends DatabaseTestCase
{
    private int $surveyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
    }

    public function testAnswersBecomeColumnSubQuestions(): void
    {
        $rows = [
            $this->baseRow([
                'type' => 'G',
                'code' => 'G01',
                'value-en' => 'Matrix Group',
            ]),
            $this->baseRow([
                'type' => 'Q',
                'subtype' => \Question::QT_COLON_ARRAY_NUMBERS,
                'code' => 'MF001',
                'value-en' => 'Multi-flex rates',
                'options' => json_encode(['em_validation_sq' => 'SUM(MF001) > 0']),
                'options-en' => json_encode(['em_validation_sq_tip' => 'Please review totals.'])
            ]),
            $this->baseRow([
                'type' => 'sq',
                'subtype' => \Question::QT_COLON_ARRAY_NUMBERS,
                'code' => 'ROW1',
                'value-en' => 'Row label',
            ]),
            $this->baseRow([
                'type' => 'a',
                'code' => 'COL1',
                'value-en' => 'Column label',
            ]),
        ];

        $this->importRows($rows);

        $question = Question::model()->find('sid = :sid AND title = :title', [':sid' => $this->surveyId, ':title' => 'MF001']);
        $this->assertNotNull($question);

        $column = Question::model()->find(
            'sid = :sid AND parent_qid = :parent AND title = :title',
            [
                ':sid' => $this->surveyId,
                ':parent' => $question->qid,
                ':title' => 'COL1',
            ]
        );
        $this->assertNotNull($column, 'Column subquestion should exist');
        $this->assertEquals(\Question::QT_T_LONG_FREE_TEXT, $column->type);
        $this->assertEquals(1, (int)$column->scale_id);

        $columnText = QuestionL10n::model()->find(
            'qid = :qid AND language = :language',
            [':qid' => $column->qid, ':language' => 'en']
        );
        $this->assertNotNull($columnText);
        $this->assertEquals('Column label', $columnText->question);

        $validationAttr = QuestionAttribute::model()->find(
            'qid = :qid AND attribute = :attr AND language = ""',
            [':qid' => $question->qid, ':attr' => 'em_validation_sq']
        );
        $this->assertNotNull($validationAttr);
        $this->assertEquals('SUM(MF001) > 0', $validationAttr->value);

        $validationTip = QuestionAttribute::model()->find(
            'qid = :qid AND attribute = :attr AND language = :language',
            [':qid' => $question->qid, ':attr' => 'em_validation_sq_tip', ':language' => 'en']
        );
        $this->assertNotNull($validationTip);
        $this->assertEquals('Please review totals.', $validationTip->value);
    }

    private function importRows(array $rows): void
    {
        $survey = \Survey::model()->findByPk($this->surveyId);
        $importer = new ImportStructure($survey, $this->warningManager);

        $reflection = new \ReflectionClass($importer);
        $readerData = $reflection->getProperty('readerData');
        $readerData->setAccessible(true);
        $readerData->setValue($importer, $rows);

        $tmp = tempnam(sys_get_temp_dir(), 'imex');
        $fileProperty = $reflection->getProperty('fileName');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($importer, $tmp);

        $importer->process();

        if (is_file($tmp)) {
            unlink($tmp);
        }
    }

    private function baseRow(array $overrides): array
    {
        $defaults = [
            'type' => '',
            'subtype' => '',
            'code' => '',
            'value-en' => '',
            'help-en' => '',
            'script-en' => '',
            'relevance' => '1',
            'mandatory' => 'N',
            'same_script' => '',
            'theme' => '',
            'options' => '',
            'options-en' => '',
        ];

        return array_merge($defaults, $overrides);
    }
}
