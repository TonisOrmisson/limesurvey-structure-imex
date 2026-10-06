<?php

namespace tonisormisson\ls\structureimex\Tests\Functional\import;

use PHPUnit\Framework\Attributes\DataProvider;
use Question;
use QuestionAttribute;
use QuestionGroup;
use Survey;
use tonisormisson\ls\structureimex\export\ImexQuestionsRowBuilder;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

class OrderingAttributeTest extends DatabaseTestCase
{
    public static function orderingTypes(): array
    {
        return [
            ['L', 'answer_order'], ['!', 'answer_order'], ['O', 'answer_order'], ['R', 'answer_order'],
            ['M', 'subquestion_order'], ['P', 'subquestion_order'],
            ['F', 'random_order'], ['Q', 'random_order'], ['K', 'random_order'],
        ];
    }

    #[DataProvider('orderingTypes')]
    public function testLegacyOrderingExportAndImport(string $type, string $canonical): void
    {
        $sid = $this->importSurveyFromFile(__DIR__ . '/../../support/data/surveys/blank-survey.lss');
        $gid = $this->createTestGroup($sid, 'Ordering', 1);
        $qid = $this->createTestQuestion($sid, $gid, 'OrderQ', $type, 'Ordering question');
        $survey = Survey::model()->findByPk($sid);
        $builder = new ImexQuestionsRowBuilder($survey, [$survey->language]);
        $this->createTestAttribute($qid, 'random_order', '1', '');

        $question = Question::model()->findByPk($qid);
        $row = $builder->buildAssocRow($builder->buildQuestionRow($question, 'Q'));
        $options = json_decode($row['options'], true);
        $this->assertSame($canonical === 'random_order' ? '1' : 'random', $options[$canonical]);
        if ($canonical !== 'random_order') {
            $this->assertArrayNotHasKey('random_order', $options);
        }

        // Import old spreadsheets with validation both on and off, then explicitly turn ordering off.
        $cases = [
            [['random_order' => '1'], false, $canonical === 'random_order' ? '1' : 'random'],
            [['random_order' => '0'], true, $canonical === 'random_order' ? '0' : 'normal'],
        ];
        if ($canonical !== 'random_order') {
            $cases[] = [['random_order' => '1', $canonical => 'normal'], false, 'normal'];
            $cases[] = [[$canonical => 'random_alphabetical'], false, 'random_alphabetical'];
        }
        foreach ($cases as [$input, $allowUnknown, $expected]) {
            if ($canonical !== 'random_order') {
                QuestionAttribute::model()->deleteAllByAttributes(['qid' => $qid, 'attribute' => 'random_order']);
                $this->createTestAttribute($qid, 'random_order', '1', '');
            }
            $row['options'] = json_encode($input);
            $temp = tempnam(sys_get_temp_dir(), 'imex-order-');
            $file = $temp . '.csv';
            rename($temp, $file);
            try {
                $stream = fopen($file, 'w');
                fputcsv($stream, $builder->buildHeader(), ',', '"', '');
                fputcsv($stream, $builder->buildGroupRow(QuestionGroup::model()->findByPk($gid)), ',', '"', '');
                fputcsv($stream, array_values($row), ',', '"', '');
                fclose($stream);
                $import = new ImportStructure($survey, $this->warningManager, $allowUnknown);
                $import->fileName = $file;
                $this->assertTrue($import->prepare(), json_encode($import->getErrors()));
                $import->process();
                $this->assertEmpty($import->getErrors());
            } finally {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $stored = QuestionAttribute::model()->findByAttributes(['qid' => $qid, 'attribute' => $canonical]);
            $this->assertNotNull($stored);
            $this->assertSame($expected, $stored->value);
            if ($canonical !== 'random_order') {
                $this->assertNull(QuestionAttribute::model()->findByAttributes(['qid' => $qid, 'attribute' => 'random_order']));
            }

            // Export after import: canonical default values are omitted, including conflicting legacy input.
            $question = Question::model()->findByPk($qid);
            $exportedRow = $builder->buildAssocRow($builder->buildQuestionRow($question, 'Q'));
            $exported = json_decode($exportedRow['options'], true) ?: [];
            if (in_array($expected, ['0', 'normal'], true)) {
                $this->assertArrayNotHasKey($canonical, $exported);
            } else {
                $this->assertSame($expected, $exported[$canonical]);
            }
        }
    }
}
