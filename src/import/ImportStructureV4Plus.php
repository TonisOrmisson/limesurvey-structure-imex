<?php

namespace tonisormisson\ls\structureimex\import;

use Answer;
use AnswerL10n;
use CDbCriteria;
use Exception;
use LSActiveRecord;
use Question;
use QuestionAttribute;
use QuestionGroup;
use QuestionGroupL10n;
use QuestionL10n;
use tonisormisson\ls\structureimex\exceptions\InvalidModelTypeException;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\validation\MyQuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeValidator;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;


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
    const COLUMN_SCRIPT = 'script';
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
        
        // Check if the language-specific column exists in the row data
        if (empty($language) || !isset($this->rowAttributes[$languageValueKey])) {
            return null; // Cannot find group without valid language data
        }
        
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

            // Skip if language data not present in import file
            if (!isset($this->rowAttributes[$languageValueKey])) {
                continue;
            }

            if (!($this->currentModel instanceof QuestionGroupL10n)) {
                $this->currentModel = new QuestionGroupL10n();
            }

            $this->currentModel->setAttributes([
                'gid' => (int)$this->questionGroup->gid,
                'group_name' => $this->rowAttributes[$languageValueKey],
                'description' => $this->rowAttributes[$languageHelpKey] ?? '',
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
        
        $this->question = $this->currentModel;
        $this->saveQuestionAttributes();

        foreach ($this->languages as $language) {
            $this->currentModel = $this->findQuestionL10n($this->question->qid, $language);            $languageValueKey = self::COLUMN_VALUE . "-" . $language;
            $languageHelpKey = self::COLUMN_HELP . "-" . $language;
            $languageScriptKey = self::COLUMN_SCRIPT . "-" . $language;

            if (!($this->currentModel instanceof QuestionL10n)) {
                $this->currentModel = new QuestionL10n();
            }

            $this->currentModel->setAttributes([
                'qid' => $this->question->qid,
                'question' => $this->rowAttributes[$languageValueKey],
                'help' => $this->rowAttributes[$languageHelpKey],
                'script' => $this->rowAttributes[$languageScriptKey] ?? '',
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
    }    /**
     * @throws Exception
     */
    private function saveQuestionAttributes()
    {
        \Yii::log("saveQuestionAttributes: Starting for question {$this->question->title} (QID: {$this->question->qid})", 'error', 'plugin.andmemasin.imex');
        \Yii::log("saveQuestionAttributes: Starting for question {$this->question->title} (QID: {$this->question->qid})", 'debug', 'plugin.andmemasin.imex');
        
        if (!isset($this->rowAttributes[self::COLUMN_OPTIONS])) {
            \Yii::log("saveQuestionAttributes: No options column found", 'debug', 'plugin.andmemasin.imex');
            return;
        }

        $attributeInput = $this->rowAttributes[self::COLUMN_OPTIONS];
        \Yii::log("saveQuestionAttributes: Raw attribute input: " . $attributeInput, 'debug', 'plugin.andmemasin.imex');
        
        // Fix common spreadsheet issues with curly quotes (UTF-8)
        $attributeInput = str_replace("\u{201C}", '"', $attributeInput); // Left double quote
        $attributeInput = str_replace("\u{201D}", '"', $attributeInput); // Right double quote
        $attributeInput = str_replace("\u{2018}", "'", $attributeInput); // Left single quote
        $attributeInput = str_replace("\u{2019}", "'", $attributeInput); // Right single quote
        // Also handle the specific bytes we see in the hex
        $attributeInput = str_replace("\xE2\x80\x9C", '"', $attributeInput); // UTF-8 left double quote
        $attributeInput = str_replace("\xE2\x80\x9D", '"', $attributeInput); // UTF-8 right double quote
        
        $attributeArray = (array)json_decode($attributeInput);
        \Yii::log("saveQuestionAttributes: Decoded attribute array: " . print_r($attributeArray, true), 'debug', 'plugin.andmemasin.imex');
        
        if (empty($attributeArray)) {
            \Yii::log("saveQuestionAttributes: Attribute array is empty, skipping", 'debug', 'plugin.andmemasin.imex');
            return;
        }
        
        // Filter out invalid attributes if validation is enabled
        if (!$this->plugin->getImportUnknownAttributes()) {
            \Yii::log("saveQuestionAttributes: Filtering valid attributes", 'debug', 'plugin.andmemasin.imex');
            $attributeArray = $this->filterValidAttributes($attributeArray);
            \Yii::log("saveQuestionAttributes: Filtered attribute array: " . print_r($attributeArray, true), 'debug', 'plugin.andmemasin.imex');
        }
        
        // Separate attributes into global and language-specific
        $separatedAttributes = QuestionAttributeLanguageManager::separateAttributes($attributeArray);
        $globalAttributes = $separatedAttributes['global'];
        $languageSpecificAttributes = $separatedAttributes['language_specific'];
        
        \Yii::log("saveQuestionAttributes: Global attributes: " . print_r($globalAttributes, true), 'debug', 'plugin.andmemasin.imex');
        \Yii::log("saveQuestionAttributes: Language-specific attributes: " . print_r($languageSpecificAttributes, true), 'debug', 'plugin.andmemasin.imex');

        // Process global attributes (stored with language = NULL or empty)
        foreach ($globalAttributes as $attributeName => $value) {
            if (is_null($value)) {
                \Yii::log("saveQuestionAttributes: Skipping null global attribute: $attributeName", 'debug', 'plugin.andmemasin.imex');
                continue;
            }
            \Yii::log("saveQuestionAttributes: Processing global attribute: $attributeName = $value", 'debug', 'plugin.andmemasin.imex');
            $this->saveGlobalQuestionAttribute($attributeName, $value);
        }
        
        // Process language-specific attributes (stored with language code)
        foreach ($languageSpecificAttributes as $attributeName => $value) {
            if (is_null($value)) {
                \Yii::log("saveQuestionAttributes: Skipping null language-specific attribute: $attributeName", 'debug', 'plugin.andmemasin.imex');
                continue;
            }
            \Yii::log("saveQuestionAttributes: Processing language-specific attribute: $attributeName = $value", 'debug', 'plugin.andmemasin.imex');
            $this->saveLanguageSpecificQuestionAttribute($attributeName, $value);
        }
    }

    /**
     * Filter out invalid attributes and return only valid ones
     * @param array $attributeArray
     * @return array
     */
    private function filterValidAttributes($attributeArray)
    {
        if (empty($attributeArray)) {
            return $attributeArray;
        }
        
        $validator = new QuestionAttributeValidator($this->survey);
        $questionType = $this->question->type;
        
        $validAttributes = [];
        $invalidAttributes = [];
        
        foreach ($attributeArray as $attributeName => $value) {
            // Test each attribute individually
            $singleAttribute = [$attributeName => $value];
            
            if ($validator->validateQuestionAttributes($questionType, $singleAttribute)) {
                $validAttributes[$attributeName] = $value;
            } else {
                $invalidAttributes[$attributeName] = $value;
            }
        }
        
        // Log invalid attributes as warnings but continue with valid ones
        if (!empty($invalidAttributes)) {
            $invalidNames = array_keys($invalidAttributes);
            $this->plugin->getWarningManager()->addWarning(
                "Skipping invalid attributes for question '{$this->question->title}' (type '{$questionType}'): " . implode(', ', $invalidNames),
                'warning'
            );
        }
        
        return $validAttributes;
    }    /**
     * Save a global question attribute (same value for all languages, stored with language=NULL)
     */
    private function saveGlobalQuestionAttribute(string $attributeName, $value)
    {
        $model = $this->currentModel;
        if(!($model instanceof Question)) {
            throw new InvalidModelTypeException();
        }
        
        \Yii::log("saveGlobalQuestionAttribute: Saving global $attributeName = $value for question {$model->title} (QID: {$model->qid})", 'debug', 'plugin.andmemasin.imex');
        
        // Global attributes are stored with language = NULL or empty string
        $attributeModel = QuestionAttribute::model()
            ->find("qid=:qid and attribute=:attributeName and (language='' OR language IS NULL)", [
                ':qid' => $model->qid,
                ':attributeName' => $attributeName,
            ]);
            
        if (!($attributeModel instanceof QuestionAttribute)) {
            \Yii::log("saveGlobalQuestionAttribute: Creating new global QuestionAttribute for $attributeName", 'debug', 'plugin.andmemasin.imex');
            $attributeModel = new QuestionAttribute();
            $attributeValues = [
                'language' => '', // Global attributes use empty string for language
                'qid' => $model->qid,
                'attribute' => $attributeName,
                'value' => $value,
            ];
            $attributeModel->setAttributes($attributeValues);
        } else {
            \Yii::log("saveGlobalQuestionAttribute: Updating existing global QuestionAttribute for $attributeName", 'debug', 'plugin.andmemasin.imex');
        }
        
        // Set values explicitly (in case LS validation is missing)
        $attributeModel->language = '';
        $attributeModel->value = $value;

        $attributeModel->validate();
        if (!$attributeModel->save()) {
            \Yii::log("saveGlobalQuestionAttribute: FAILED to save global $attributeName: " . serialize($attributeModel->getErrors()), 'error', 'plugin.andmemasin.imex');
            throw new Exception("error creating global question attribute '{$attributeName}' for question {$model->title}, errors: "
                . serialize($attributeModel->getErrors()));
        } else {
            \Yii::log("saveGlobalQuestionAttribute: Successfully saved global $attributeName = $value", 'debug', 'plugin.andmemasin.imex');
        }
    }

    /**
     * Save a language-specific question attribute (different value per language)
     */
    private function saveLanguageSpecificQuestionAttribute(string $attributeName, $value)
    {
        $model = $this->currentModel;
        if(!($model instanceof Question)) {
            throw new InvalidModelTypeException();
        }
        
        \Yii::log("saveLanguageSpecificQuestionAttribute: Saving language-specific $attributeName = $value for question {$model->title} (QID: {$model->qid})", 'debug', 'plugin.andmemasin.imex');
        
        // Language-specific attributes are stored with the specific language code
        foreach ($this->languages as $language) {
            \Yii::log("saveLanguageSpecificQuestionAttribute: Processing language: $language", 'debug', 'plugin.andmemasin.imex');
            
            $attributeModel = QuestionAttribute::model()
                ->find("qid=:qid and attribute=:attributeName and language=:language", [
                    ':qid' => $model->qid,
                    ':attributeName' => $attributeName,
                    ':language' => $language,
                ]);
                
            if (!($attributeModel instanceof QuestionAttribute)) {
                \Yii::log("saveLanguageSpecificQuestionAttribute: Creating new language-specific QuestionAttribute for $attributeName ($language)", 'debug', 'plugin.andmemasin.imex');
                $attributeModel = new QuestionAttribute();
                $attributeValues = [
                    'language' => $language,
                    'qid' => $model->qid,
                    'attribute' => $attributeName,
                    'value' => $value,
                ];
                $attributeModel->setAttributes($attributeValues);
            } else {
                \Yii::log("saveLanguageSpecificQuestionAttribute: Updating existing language-specific QuestionAttribute for $attributeName ($language)", 'debug', 'plugin.andmemasin.imex');
            }
            
            // Set values explicitly (in case LS validation is missing)
            $attributeModel->language = $language;
            $attributeModel->value = $value;

            $attributeModel->validate();
            if (!$attributeModel->save()) {
                \Yii::log("saveLanguageSpecificQuestionAttribute: FAILED to save language-specific $attributeName ($language): " . serialize($attributeModel->getErrors()), 'error', 'plugin.andmemasin.imex');
                throw new Exception("error creating language-specific question attribute '{$attributeName}' for question {$model->title} ($language), errors: "
                    . serialize($attributeModel->getErrors()));
            } else {
                \Yii::log("saveLanguageSpecificQuestionAttribute: Successfully saved language-specific $attributeName = $value ($language)", 'debug', 'plugin.andmemasin.imex');
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
        if (empty($this->readerData) || !isset($this->readerData[0])) {
            // No data to parse languages from
                return;
        }
        
        
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
