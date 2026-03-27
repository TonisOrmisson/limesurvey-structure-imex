<?php

namespace tonisormisson\ls\structureimex\export;

use Answer;
use Question;
use QuestionAttribute;
use QuestionGroup;
use Survey;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

class ImexQuestionsRowBuilder
{
    /**
     * @param string[] $languages
     */
    public function __construct(
        private Survey $survey,
        private array $languages
    ) {
    }

    /**
     * @return string[]
     */
    public function buildHeader(): array
    {
        $header = [
            ImportStructure::COLUMN_TYPE,
            ImportStructure::COLUMN_SUBTYPE,
            ImportStructure::COLUMN_CODE,
        ];

        foreach ($this->languages as $language) {
            $header[] = ImportStructure::COLUMN_VALUE . "-" . $language;
            $header[] = ImportStructure::COLUMN_HELP . "-" . $language;
            $header[] = ImportStructure::COLUMN_SCRIPT . "-" . $language;
        }

        $header[] = ImportStructure::COLUMN_RELEVANCE;
        $header[] = ImportStructure::COLUMN_MANDATORY;
        $header[] = ImportStructure::COLUMN_SAME_SCRIPT;
        $header[] = ImportStructure::COLUMN_THEME;
        $header[] = ImportStructure::COLUMN_OPTIONS;

        foreach ($this->languages as $language) {
            $header[] = ImportStructure::COLUMN_OPTIONS . "-" . $language;
        }

        return $header;
    }

    /**
     * @param mixed[] $row
     * @return array<string, mixed>
     */
    public function buildAssocRow(array $row): array
    {
        $assoc = [];
        $header = $this->buildHeader();
        foreach ($header as $index => $column) {
            $assoc[$column] = $row[$index] ?? '';
        }
        return $assoc;
    }

    /**
     * @return mixed[]
     */
    public function buildGroupRow(QuestionGroup $group): array
    {
        $row = [
            ExportQuestions::TYPE_GROUP,
            '',
            $group->gid,
        ];

        foreach ($this->languages as $language) {
            if (!isset($group->questiongroupl10ns[$language])) {
                continue;
            }
            $row[] = $group->questiongroupl10ns[$language]->group_name ?? '';
            $row[] = $group->questiongroupl10ns[$language]->description ?? '';
            $row[] = '';
        }

        $row[] = $group->grelevance ?? '';
        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';

        foreach ($this->languages as $language) {
            $row[] = '';
        }

        return $row;
    }

    /**
     * @return mixed[]
     */
    public function buildQuestionRow(Question $question, string $rowType): array
    {
        $row = [
            $rowType,
            ($rowType === ExportQuestions::TYPE_SUB_QUESTION ? '' : $question->type),
            $question->title,
        ];

        foreach ($this->languages as $language) {
            $l10n = $question->questionl10ns[$language] ?? null;
            $row[] = $l10n->question ?? '';
            $row[] = $l10n->help ?? '';

            if ((int) $question->same_script === 1) {
                if ($language === $this->survey->language) {
                    $row[] = $l10n->script ?? '';
                } else {
                    $row[] = '';
                }
            } else {
                $row[] = $l10n->script ?? '';
            }
        }

        $row[] = (string) $question->relevance;
        $row[] = $rowType === ExportQuestions::TYPE_SUB_QUESTION ? '' : (string) $question->mandatory;

        if ($rowType !== ExportQuestions::TYPE_SUB_QUESTION) {
            $row[] = (int) $question->same_script;
        } else {
            $row[] = '';
        }

        if ($rowType !== ExportQuestions::TYPE_SUB_QUESTION) {
            $questionTheme = $question->question_theme_name;
            $row[] = ($questionTheme != 'core') ? $questionTheme : '';
        } else {
            $row[] = '';
        }

        $attributes = $this->getQuestionAttributes($question);
        $allowUnknownThemeAttributes = !empty($question->question_theme_name);

        $globalAttributes = [];
        $languageSpecificAttributes = [];

        foreach ($attributes as $attribute) {
            $attributeName = (string) $attribute->attribute;
            $attributeValue = (string) $attribute->value;
            $attributeLanguage = (string) $attribute->language;

            if ($attributeName === 'question_template') {
                continue;
            }

            $isKnownAttribute = QuestionAttributeDefinition::isValidAttribute((string) $question->type, $attributeName);

            if (!$isKnownAttribute && !$allowUnknownThemeAttributes) {
                continue;
            }

            if (
                $isKnownAttribute
                && !QuestionAttributeDefinition::isNonDefaultValue((string) $question->type, $attributeName, $attributeValue)
            ) {
                continue;
            }

            if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
                if (empty($attributeLanguage)) {
                    $globalAttributes[$attributeName] = $attributeValue;
                }
            } else {
                if (!empty($attributeLanguage)) {
                    if (!isset($languageSpecificAttributes[$attributeLanguage])) {
                        $languageSpecificAttributes[$attributeLanguage] = [];
                    }
                    $languageSpecificAttributes[$attributeLanguage][$attributeName] = $attributeValue;
                }
            }
        }

        if (!empty($globalAttributes)) {
            $row[] = json_encode($globalAttributes);
        } else {
            $row[] = '';
        }

        foreach ($this->languages as $language) {
            if (!empty($languageSpecificAttributes[$language])) {
                $row[] = json_encode($languageSpecificAttributes[$language]);
            } else {
                $row[] = '';
            }
        }

        return $row;
    }

    /**
     * @return mixed[]
     */
    public function buildAnswerRow(Answer $answer): array
    {
        $row = [
            ExportQuestions::TYPE_ANSWER,
            '',
            $answer->code,
        ];

        foreach ($this->languages as $language) {
            if (!isset($answer->answerl10ns[$language])) {
                continue;
            }
            $row[] = $answer->answerl10ns[$language]->answer ?? '';
            $row[] = '';
            $row[] = '';
        }

        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';

        foreach ($this->languages as $language) {
            $row[] = '';
        }

        return $row;
    }

    /**
     * @return mixed[]
     */
    public function buildSubQuestionAsAnswerRow(Question $subQuestion): array
    {
        $row = [
            ExportQuestions::TYPE_ANSWER,
            '',
            $subQuestion->title,
        ];

        foreach ($this->languages as $language) {
            if (!isset($subQuestion->questionl10ns[$language])) {
                continue;
            }
            $row[] = $subQuestion->questionl10ns[$language]->question ?? '';
            $row[] = '';
            $row[] = '';
        }

        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';

        foreach ($this->languages as $language) {
            $row[] = '';
        }

        return $row;
    }

    /**
     * @param Question[] $subQuestions
     * @return array{0: Question[], 1: Question[]}
     */
    public function partitionSubQuestions(Question $question, array $subQuestions): array
    {
        if (!$this->questionHasMultiFlexAxisAnswers($question)) {
            return [[], $subQuestions];
        }

        $answerLike = [];
        $regular = [];
        foreach ($subQuestions as $subQuestion) {
            if ($this->isMultiFlexAxisSubQuestion($question, $subQuestion)) {
                $answerLike[] = $subQuestion;
            } else {
                $regular[] = $subQuestion;
            }
        }

        return [$answerLike, $regular];
    }

    private function questionHasMultiFlexAxisAnswers(Question $question): bool
    {
        return in_array(
            $question->type,
            [Question::QT_COLON_ARRAY_NUMBERS, Question::QT_SEMICOLON_ARRAY_TEXT],
            true
        );
    }

    private function isMultiFlexAxisSubQuestion(Question $parentQuestion, Question $subQuestion): bool
    {
        if (!$this->questionHasMultiFlexAxisAnswers($parentQuestion)) {
            return false;
        }

        return strtoupper((string) $subQuestion->type) === ExportQuestions::QT_LONG_FREE;
    }

    /**
     * @return QuestionAttribute[]
     */
    private function getQuestionAttributes(Question $question): array
    {
        $sql = "SELECT * FROM {{question_attributes}} WHERE qid = :qid AND value != ''";
        $command = \Yii::app()->db->createCommand($sql);
        $command->bindValue(':qid', $question->qid);
        $rows = $command->queryAll();

        $attributes = [];
        foreach ($rows as $row) {
            $attribute = new QuestionAttribute();
            $attribute->qaid = $row['qaid'];
            $attribute->qid = $row['qid'];
            $attribute->attribute = $row['attribute'];
            $attribute->value = $row['value'];
            $attribute->language = $row['language'];
            $attributes[] = $attribute;
        }

        return $attributes;
    }
}
