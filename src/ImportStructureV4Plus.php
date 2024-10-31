<?php

namespace tonisormisson\ls\structureimex;

use Answer;
use AnswerL10n;
use CDbCriteria;
use LSActiveRecord;
use Question;
use QuestionAttribute;
use QuestionGroup;
use QuestionGroupL10n;
use QuestionL10n;
use Exception;
use tonisormisson\ls\structureimex\exceptions\InvalidModelTypeException;


class ImportStructureV4Plus extends ImportFromFile
{

    /** @var ?LSActiveRecord */
    public $currentModel;


    /** @var ?Question $question current question (main/parent) */
    private ?Question $question;

    /** @var ?Question $subQuestion current subQuestion (main/parent) */
    private ?Question $subQuestion;

    /** @var ?QuestionGroup $questionGroup current questionGroup */
    private ?QuestionGroup $questionGroup;

    private int $groupOrder = 1;

    private int $questionOrder = 1;
    private int $subQuestionOrder = 1;
    private int $answerOrder = 1;

    /** @var string[] */
    private array $languages = [];


    const COLUMN_TYPE = 'type';
    const COLUMN_SUBTYPE = 'subtype';
    const COLUMN_CODE = 'code';
    const COLUMN_RELEVANCE = 'relevance';
    const COLUMN_THEME = 'theme';
    const COLUMN_OPTIONS = 'options';
    const COLUMN_VALUE = 'value';
    const COLUMN_HELP = 'help';
    const COLUMN_MANDATORY = 'mandatory';

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function importModel($attributes): void
    {
        $this->questionCodeColumn = static::COLUMN_CODE;
        $this->rowAttributes = $attributes;
        $this->initType();

        switch ($this->type) {
            case ExportQuestions::TYPE_GROUP:
                $this->saveGroups();
                return;
            case ExportQuestions::TYPE_QUESTION:
                $this->saveQuestions();
                return;
            case ExportQuestions::TYPE_ANSWER:
                $this->saveAnswers();
                return;
            case ExportQuestions::TYPE_SUB_QUESTION:
                $this->saveSubQuestions();
                return;

        }
        $this->currentModel = null;
    }

    protected function beforeProcess(): void
    {
        $this->validateStructure();
    }

    /**
     * @throws Exception
     */
    protected function initType()
    {
        switch (strtolower($this->rowAttributes[self::COLUMN_TYPE])) {
            case strtolower(ExportQuestions::TYPE_QUESTION):
                $this->type = ExportQuestions::TYPE_QUESTION;
                $this->hasSurveyColumn = true;
                break;
            case strtolower(ExportQuestions::TYPE_SUB_QUESTION):
                $this->type = ExportQuestions::TYPE_SUB_QUESTION;
                $this->hasSurveyColumn = true;
                break;
            case  strtolower(ExportQuestions::TYPE_GROUP):
                $this->type = ExportQuestions::TYPE_GROUP;
                $this->hasSurveyColumn = true;
                break;
            case  strtolower(ExportQuestions::TYPE_ANSWER):
                $this->hasSurveyColumn = false;
                $this->type = ExportQuestions::TYPE_ANSWER;
                break;
            default:
                throw new Exception('Invalid Type: ' . $this->rowAttributes[self::COLUMN_TYPE]);
        }
    }

    /**
     * @return QuestionGroup|null
     * @throws Exception
     */
    protected function findGroup($language): ?QuestionGroup
    {
        if ($this->type != ExportQuestions::TYPE_GROUP) {
            throw new Exception('Not a group!');
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $result = null;

        // if the file is an export file, it will possibly contain group id
        if (!empty($this->rowAttributes[self::COLUMN_CODE])) {
            $gidCriteria = clone $criteria;
            $gidCriteria->addCondition('gid=:gid');
            $gidCriteria->params[':gid'] = $this->rowAttributes[self::COLUMN_CODE];
            $result = QuestionGroup::model()->find($gidCriteria);
        }
        if ($result instanceof QuestionGroup) {
            return $result;
        }

        // otherwise try to look by name and hope it has not been changed
        $languageValueKey = self::COLUMN_VALUE . "-" . $language;
        $criteria->addCondition('questiongroupl10ns.group_name=:name');
        $criteria->params[':name'] = $this->rowAttributes[$languageValueKey];

        $result = QuestionGroup::model()->with('questiongroupl10ns')->find($criteria);
        return $result;
    }

    /**
     * @param int $gid
     * @param string $language
     * @return QuestionGroupL10n|null
     * @throws Exception
     */
    protected function findGroupL10n($gid, $language): ?QuestionGroupL10n
    {
        if ($this->type != ExportQuestions::TYPE_GROUP) {
            throw new Exception('Not a group!');
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('gid=:gid');
        $criteria->params[':gid'] = $gid;

        $result = QuestionGroupL10n::model()->find($criteria);
        return $result;
    }

    /**
     * @throws Exception
     */
    private function saveGroups()
    {
        $i = 1;
        $this->questionGroup = null;
        $this->setGroupsInitialOrder();

        $language = $this->survey->language;
        $this->currentModel = $this->findGroup($language);

        if (!($this->currentModel instanceof QuestionGroup)) {
            $this->currentModel = new QuestionGroup();
        }

        $this->currentModel->setAttributes([
            'sid' => (int)$this->survey->sid,
            'grelevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'group_order' => $this->groupOrder,
        ]);
        // relevance not in LS model rules!!
        $this->currentModel->grelevance = $this->rowAttributes[self::COLUMN_RELEVANCE];

        // LS misses the validaion rule for 'sid' !!! so we must define this separately
        $this->currentModel->sid = $this->survey->sid;

        $result = $this->currentModel->save();
        if (!$result) {
            throw new Exception('Error saving group : ' . serialize($this->currentModel->getErrors()));
        }

        $this->questionGroup = $this->currentModel;

        foreach ($this->languages as $language) {
            $this->currentModel = $this->findGroupL10n($this->questionGroup->gid, $language);
            $languageValueKey = self::COLUMN_VALUE . "-" . $language;
            $languageHelpKey = self::COLUMN_HELP . "-" . $language;

            if (!($this->currentModel instanceof QuestionGroupL10n)) {
                $this->currentModel = new QuestionGroupL10n();
            }

            $this->currentModel->setAttributes([
                'gid' => (int)$this->questionGroup->gid,
                'group_name' => $this->rowAttributes[$languageValueKey],
                'description' => $this->rowAttributes[$languageHelpKey],
                'language' => $language,
            ]);

            $result = $this->currentModel->save();
            if (!$result) {
                throw new Exception("Error saving group language $language : " . serialize($this->currentModel->getErrors()));
            }
        }

        $this->groupOrder++;
        $this->questionOrder = 1;
    }

    private function setGroupsInitialOrder()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->order = "group_order DESC";
        $lastGroup = QuestionGroup::model()->find($criteria);

        if ($lastGroup instanceof QuestionGroup) {
            $this->groupOrder = $lastGroup->group_order + 1;
        }

    }

    /**
     * @throws Exception
     */
    private function saveQuestions()
    {
        $i = 1;
        $this->question = null;

        $this->currentModel = $this->findQuestion();
        if (!($this->currentModel instanceof Question)) {
            $this->currentModel = new Question();
        }

        $mandatory = "Y";
        if (in_array(strtoupper($this->rowAttributes[self::COLUMN_MANDATORY]), ['Y', 'N'])) {
            $mandatory = strtoupper($this->rowAttributes[self::COLUMN_MANDATORY]);
        }

        // Set Survey ID before other attributes so that validation works
        $this->currentModel->sid = $this->survey->sid;

        $attributes = [
            'type' => $this->rowAttributes[self::COLUMN_SUBTYPE],
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'question_order' => $this->questionOrder,
            'mandatory' => $mandatory,
        ];

        $questionTheme = "";
        if (!empty($this->rowAttributes[self::COLUMN_THEME])) {
            $questionTheme = $this->rowAttributes[self::COLUMN_THEME];
        }
        if (empty($questionTheme) && isset($this->rowAttributes[self::COLUMN_OPTIONS])) {
            $attributeInput = $this->rowAttributes[self::COLUMN_OPTIONS];
            $attributeArray = (array)json_decode((string)$attributeInput);
            if (!empty($attributeArray['question_template'])) {
                $questionTheme = $attributeArray['question_template'];
            }
        }
        if (!empty($questionTheme)) {
            $attributes['question_theme_name'] = $questionTheme;
        }

        $this->currentModel->setAttributes($attributes);

        $result = $this->currentModel->save();
        if (!$result) {
            throw new Exception("Error saving baseQuestion nr $i: " . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
        }
        $this->saveQuestionAttributes();

        $this->question = $this->currentModel;

        foreach ($this->languages as $language) {
            $this->currentModel = $this->findQuestionL10n($this->question->qid, $language);

            $languageValueKey = self::COLUMN_VALUE . "-" . $language;
            $languageHelpKey = self::COLUMN_HELP . "-" . $language;

            if (!($this->currentModel instanceof QuestionL10n)) {
                $this->currentModel = new QuestionL10n();
            }

            $this->currentModel->setAttributes([
                'qid' => $this->question->qid,
                'question' => $this->rowAttributes[$languageValueKey],
                'help' => $this->rowAttributes[$languageHelpKey],
                'language' => $language,
            ]);

            $result = $this->currentModel->save();

            if (!$result) {
                throw new Exception("Error saving baseQuestion nr $i language '$language': " . serialize($this->rowAttributes[$languageValueKey]) . serialize($this->currentModel->getErrors()));
            }
        }
        $this->questionOrder++;
        $this->subQuestionOrder = 1;
        $this->answerOrder = 1;
    }

    /**
     * @throws Exception
     */
    private function saveQuestionAttributes()
    {
        if (!isset($this->rowAttributes[self::COLUMN_OPTIONS])) {
            return;
        }

        $attributeInput = $this->rowAttributes[self::COLUMN_OPTIONS];
        $attributeArray = (array)json_decode($attributeInput);
        if (empty($attributeArray)) {
            return;
        }
        // Filter the attributes to only those that need to be validated, unless the
        // importUnknownAttributes setting is set.
        if (!$this->plugin->getImportUnknownAttributes()) {
            $this->validateAttributes($attributeArray);
        }
        $myAttributes = new MyQuestionAttribute();
        $myAttributes->setAttributes($attributeArray, false);
        $myAttributes->validate();
        foreach ($myAttributes->attributes as $attributeName => $value) {
            if (is_null($value)) {
                continue;
            }
            $this->saveQuestionAttribute($attributeName, $value);
        }
    }

    private function validateAttributes($attributeArray)
    {
        $allowedAttributes = (new MyQuestionAttribute())->attributeNames();
        if (empty($attributeArray)) {
            return;
        }
        foreach ($attributeArray as $attributeName => $value) {
            if (!in_array($attributeName, $allowedAttributes)) {
                throw new \Exception("Question attribute '{$attributeName}' is not defined for IMEX and the import breaks here ");
            }
        }

    }

    private function saveQuestionAttribute(string $attributeName, $value)
    {

        $model = $this->currentModel;
        if(!($model instanceof Question)) {
            throw new InvalidModelTypeException();
        }
        foreach ($this->languages as $language) {
            $attributeModel = QuestionAttribute::model()
                ->find("qid=:qid and attribute=:attributeName and language=:language", [
                    ':qid' => $model->qid,
                    ':attributeName' => $attributeName,
                    ':language' => $language,
                ]);
            if (!($attributeModel instanceof QuestionAttribute)) {
                $attributeModel = new QuestionAttribute();
                $attributeValues = [
                    'language' => $language,
                    'qid' => $model->qid,
                    'attribute' => $attributeName,
                    'value' => $value,
                ];
                $attributeModel->setAttributes($attributeValues);
            }
            // missing in LS validation, need to set again
            $attributeModel->language = $language;
            $attributeModel->value = $value;

            $attributeModel->validate();
            if (!$attributeModel->save()) {
                throw new Exception("error creating question attribute '{$attributeName}' for question {$model->title}, errors: "
                    . serialize($attributeModel->errors));
            }
        }
    }


    /**
     * @throws Exception
     */
    private function saveSubQuestions()
    {
        $i = 1;
        $this->subQuestion = null;
        $this->currentModel = $this->findSubQuestion();

        if (!($this->currentModel instanceof Question)) {
            $this->currentModel = new Question();
        }

        // subquestion validation in yii model is broken, need to make an array and apply in loop
        $attributes = [
            'sid' => $this->survey->sid,
            'type' => $this->question->type,
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'question_order' => $this->subQuestionOrder,
            'mandatory' => "N",
            'parent_qid' => $this->question->qid,
        ];
        foreach ($attributes as $key => $value) {
            $this->currentModel->{$key} = $value;
        }

        $result = $this->currentModel->save();
        if (!$result) {
            throw new Exception('Error saving subQuestion : ' . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
        }

        $this->subQuestion = $this->currentModel;

        foreach ($this->languages as $language) {
            $this->currentModel = $this->findQuestionL10n($this->subQuestion->qid, $language);

            $languageValueKey = self::COLUMN_VALUE . "-" . $language;
            $languageHelpKey = self::COLUMN_HELP . "-" . $language;

            if (!($this->currentModel instanceof QuestionL10n)) {
                $this->currentModel = new QuestionL10n();
            }

            $this->currentModel->setAttributes([
                'qid' => $this->subQuestion->qid,
                'question' => $this->rowAttributes[$languageValueKey],
                'help' => $this->rowAttributes[$languageHelpKey],
                'language' => $language,
            ]);

            $result = $this->currentModel->save();
            if (!$result) {
                throw new Exception("Error saving subQuestion language '$language': " . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
            }
        }

        $this->subQuestionOrder++;
    }

    /**
     * @throws Exception
     */
    private function saveAnswers()
    {
        $this->currentModel = $this->findAnswer();
        if (!($this->currentModel instanceof Answer)) {
            $this->currentModel = new Answer();
        }
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
            'qid' => $this->question->qid,
        ]);
        $result = $this->saveAnswer();
        if (!$result) {
            throw new Exception('Error saving answer : ' . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
        }
        $answerId = $this->currentModel->aid;
        foreach ($this->languages as $language) {
            $this->currentModel = $this->findAnswerL10n($answerId, $language);
            if (!($this->currentModel instanceof AnswerL10n)) {
                $this->currentModel = new AnswerL10n();
            }
            $this->currentModel->setAttributes([
                'aid' => $answerId,
                'language' => $language,
            ]);
            $result = $this->saveAnswerL10n($language);
            if (!$result) {
                throw new Exception('Error saving answer : ' . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
            }
        }
    }

    /**
     * @return Answer|null
     */
    protected function findAnswer(): ?Answer
    {
        if (empty($this->question)) {
            return null;
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('qid=:qid');
        $criteria->params[':qid'] = $this->question->qid;

        $criteria->addCondition('code=:code');
        $criteria->params[':code'] = $this->rowAttributes[self::COLUMN_CODE];

        return Answer::model()->find($criteria);
    }

    /**
     * @param int $answerId
     * @param string $language
     * @return AnswerL10n|null
     */
    protected function findAnswerL10n(int $answerId, string $language): ?AnswerL10n
    {
        if (empty($this->question)) {
            return null;
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('aid=:aid');
        $criteria->params[':aid'] = $answerId;

        return AnswerL10n::model()->find($criteria);
    }

    /**
     * @return bool
     */
    private function saveAnswer(): bool
    {
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
            'code' => $this->rowAttributes[self::COLUMN_CODE],
            'qid' => $this->question->qid,
            'sortorder' => $this->answerOrder,
        ]);
        $this->answerOrder++;

        return $this->currentModel->save();
    }

    /**
     * @param string $language
     * @return bool
     */
    private function saveAnswerL10n(string $language): bool
    {
        $languageValueKey = self::COLUMN_VALUE . "-" . $language;
        $this->currentModel->setAttributes([
            'answer' => $this->rowAttributes[$languageValueKey],
        ]);
        return $this->currentModel->save();
    }


    /**
     * @return bool
     * @throws Exception
     */
    private function validateStructure(): bool
    {
        $this->parseLanguages();
        if (!$this->validateLanguages()) {
            return false;
        }

        if (!$this->validateModels()) {
            return false;
        }
        return true;
    }


    private function parseLanguages()
    {
        $headerValues = array_keys($this->readerData[0]);
        foreach ($headerValues as $value) {
            $searchValue = static::COLUMN_VALUE;
            $isLang = is_int(strpos($value, (string)$searchValue));
            if ($isLang) {
                $langStart = strpos($value, "-") + 1;
                $langugage = strtolower(trim(substr($value, $langStart, strlen($value))));
                $this->languages[] = $langugage;
            }
        }
    }

    /**
     * @return bool
     */
    private function validateLanguages(): bool
    {
        if (empty($this->languages)) {
            $this->addError("file", "Languages not defined in file. Must have cols like 'value-en' etc... ");
        }

        foreach ($this->languages as $language) {
            if (!in_array($language, $this->languages)) {
                $this->addError("file", sprintf("Language '%s' not used in survey", $language));
            }

            if (!empty($this->errors)) {
                return false;
            }
        }

        return empty($this->errors);
    }


    /**
     * @return bool
     * @throws Exception
     */
    private function validateModels(): bool
    {
        $thisModel = null;
        $i = 0;
        foreach ($this->readerData as $row) {
            $i++;
            $this->rowAttributes = $row;
            try {
                $this->initType();
            } catch (Exception $e) {
                $this->addError("file", sprintf("Invaid row type '%s' on row %s", $this->rowAttributes[self::COLUMN_TYPE], $i));
            }
        }
        return empty($this->errors);

    }


    /**
     * @return Question|null
     */
    private function findSubQuestion(): ?Question
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $criteria->addCondition('parent_qid=:parent_qid');
        $criteria->params[':parent_qid'] = $this->question->qid;

        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];

        return Question::model()->find($criteria);
    }


    /**
     * @return Question|null
     */
    private function findQuestion(): ?Question
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $criteria->addCondition('parent_qid=0');
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];
        return Question::model()->find($criteria);
    }

    /**
     * @param int $qid
     * @param string $language
     * @return QuestionL10n|null
     */
    private function findQuestionL10n(int $qid, string $language): ?QuestionL10n
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('qid=:qid');
        $criteria->params[':qid'] = $qid;

        return QuestionL10n::model()->find($criteria);
    }

}
