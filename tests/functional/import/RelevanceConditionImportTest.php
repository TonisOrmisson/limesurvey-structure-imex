<?php

namespace tonisormisson\ls\structureimex\Tests\Functional\Import;

use Condition;
use Question;
use Survey;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\export\ImexQuestionsRowBuilder;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

class RelevanceConditionImportTest extends DatabaseTestCase
{
    public function testPartialStructureImportDoesNotTouchConditionsForQuestionsOutsideImport(): void
    {
        $surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $groupId = $this->createTestGroup($surveyId, 'Condition group', 1);
        $sourceOneId = $this->createTestQuestion($surveyId, $groupId, 'SRC1', Question::QT_T_LONG_FREE_TEXT, 'Source one');
        $sourceTwoId = $this->createTestQuestion($surveyId, $groupId, 'SRC2', Question::QT_T_LONG_FREE_TEXT, 'Source two');
        $targetId = $this->createTestQuestion($surveyId, $groupId, 'TARGET', Question::QT_T_LONG_FREE_TEXT, 'Target');

        $this->createCondition($targetId, 1, $sourceOneId, "{$surveyId}X{$groupId}X{$sourceOneId}", 'A');
        $this->createCondition($targetId, 2, $sourceTwoId, "{$surveyId}X{$groupId}X{$sourceTwoId}", 'B');
        \LimeExpressionManager::UpgradeConditionsToRelevance($surveyId, $targetId);

        $conditionsBefore = $this->conditionsForQuestion($targetId);
        $relevanceBefore = (string) Question::model()->findByPk($targetId)->relevance;

        $import = new ImportStructure(Survey::model()->findByPk($surveyId), $this->warningManager);
        $import->readerData = [
            $this->groupRow($groupId),
            $this->questionRow('NEWQ', 'New imported question', '1'),
        ];

        $this->assertNull($import->process());
        $this->assertSame($conditionsBefore, $this->conditionsForQuestion($targetId));
        $this->assertSame($relevanceBefore, (string) Question::model()->findByPk($targetId)->relevance);
    }

    public function testStructureImportRemovesOldConditionsWhenImportedQuestionHasRelevance(): void
    {
        $surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $groupId = $this->createTestGroup($surveyId, 'Condition group', 1);
        $sourceOneId = $this->createTestQuestion($surveyId, $groupId, 'SRC1', Question::QT_T_LONG_FREE_TEXT, 'Source one');
        $sourceTwoId = $this->createTestQuestion($surveyId, $groupId, 'SRC2', Question::QT_T_LONG_FREE_TEXT, 'Source two');
        $targetId = $this->createTestQuestion($surveyId, $groupId, 'TARGET', Question::QT_T_LONG_FREE_TEXT, 'Target');

        $this->createCondition($targetId, 1, $sourceOneId, "{$surveyId}X{$groupId}X{$sourceOneId}", 'A');
        $this->createCondition($targetId, 2, $sourceTwoId, "{$surveyId}X{$groupId}X{$sourceTwoId}", 'B');
        \LimeExpressionManager::UpgradeConditionsToRelevance($surveyId, $targetId);

        $importedRelevance = '(SRC1.NAOK == "A") or (SRC2.NAOK == "B")';
        $import = new ImportStructure(Survey::model()->findByPk($surveyId), $this->warningManager);
        $import->readerData = [
            $this->groupRow($groupId),
            $this->questionRow('TARGET', 'Target imported', $importedRelevance),
        ];

        $this->assertNull($import->process());
        $this->assertSame([], $this->conditionsForQuestion($targetId));
        $this->assertSame($importedRelevance, (string) Question::model()->findByPk($targetId)->relevance);
    }

    public function testExportedQuestionRelevanceMatchesLimeSurveyConditionTranslationForMultipleScenarios(): void
    {
        $surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $groupId = $this->createTestGroup($surveyId, 'Condition group', 1);
        $sourceOneId = $this->createTestQuestion($surveyId, $groupId, 'SRC1', Question::QT_T_LONG_FREE_TEXT, 'Source one');
        $sourceTwoId = $this->createTestQuestion($surveyId, $groupId, 'SRC2', Question::QT_T_LONG_FREE_TEXT, 'Source two');
        $targetId = $this->createTestQuestion($surveyId, $groupId, 'TARGET', Question::QT_T_LONG_FREE_TEXT, 'Target');

        $this->createCondition($targetId, 1, $sourceOneId, "{$surveyId}X{$groupId}X{$sourceOneId}", 'A');
        $this->createCondition($targetId, 2, $sourceTwoId, "{$surveyId}X{$groupId}X{$sourceTwoId}", 'B');
        \LimeExpressionManager::UpgradeConditionsToRelevance($surveyId, $targetId);
        $limeSurveyRelevance = (string) Question::model()->findByPk($targetId)->relevance;

        $survey = Survey::model()->findByPk($surveyId);
        $question = Question::model()->findByPk($targetId);
        $rowBuilder = new ImexQuestionsRowBuilder($survey, ['en']);
        $row = $rowBuilder->buildAssocRow($rowBuilder->buildQuestionRow($question, ExportQuestions::TYPE_QUESTION));

        $this->assertSame($limeSurveyRelevance, (string) $row['relevance']);
        $this->assertStringContainsString(' or ', (string) $row['relevance']);
    }

    private function createCondition(int $qid, int $scenario, int $cqid, string $cfieldname, string $value): void
    {
        $condition = new Condition();
        $condition->qid = $qid;
        $condition->scenario = $scenario;
        $condition->cqid = $cqid;
        $condition->cfieldname = $cfieldname;
        $condition->method = '==';
        $condition->value = $value;

        if (!$condition->save()) {
            throw new \Exception('Failed to create condition: ' . print_r($condition->getErrors(), true));
        }
    }

    private function conditionsForQuestion(int $qid): array
    {
        $conditions = Condition::model()->findAllByAttributes(['qid' => $qid], [
            'order' => 'scenario, cqid, cfieldname, method, value',
        ]);

        return array_map(static function (Condition $condition): array {
            return [
                'qid' => (int) $condition->qid,
                'scenario' => (int) $condition->scenario,
                'cqid' => (int) $condition->cqid,
                'cfieldname' => (string) $condition->cfieldname,
                'method' => (string) $condition->method,
                'value' => (string) $condition->value,
            ];
        }, $conditions);
    }

    private function groupRow(int $gid): array
    {
        return [
            'type' => 'G',
            'subtype' => '',
            'code' => $gid,
            'value-en' => 'Condition group',
            'help-en' => '',
            'script-en' => '',
            'relevance' => '1',
            'mandatory' => '',
            'same_script' => '',
            'theme' => '',
            'options' => '',
        ];
    }

    private function questionRow(string $code, string $text, string $relevance): array
    {
        return [
            'type' => 'Q',
            'subtype' => Question::QT_T_LONG_FREE_TEXT,
            'code' => $code,
            'value-en' => $text,
            'help-en' => '',
            'script-en' => '',
            'relevance' => $relevance,
            'mandatory' => 'N',
            'same_script' => '0',
            'theme' => '',
            'options' => '',
        ];
    }
}
