<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportStructure extends ImportFromFile
{
    public LSActiveRecord $currentModel;


    /** @var ?Question $question current question (main/parent) */
    private ?Question $question;

    /** @var ?QuestionGroup $questionGroup current questionGroup */
    private ?QuestionGroup $questionGroup;

    private int $groupOrder = 1;
    private int $questionOrder = 1;
    private int $subQuestionOrder = 1;
    private int $answerOrder = 1;

    /** @var string[]  */
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
    protected function importModel($attributes) : void
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

    protected function beforeProcess() : void
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
     * @param $language
     * @return QuestionGroup|null
     * @throws Exception
     */
    protected function findGroup($language)  : ?QuestionGroup
    {
        if ($this->type != ExportQuestions::TYPE_GROUP) {
            throw new Exception('Not a group!');
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $result = null;

        // if the file is an export file, it will possibly contain group id
        if(!empty($this->rowAttributes[self::COLUMN_CODE])) {
            $gidCriteria = clone $criteria;
            $gidCriteria->addCondition('gid=:gid');
            $gidCriteria->params[':gid']= $this->rowAttributes[self::COLUMN_CODE];
            $result = QuestionGroup::model()->find($gidCriteria);
        }
        if($result instanceof QuestionGroup) {
            return $result;
        }

        // otherwise try to look by name and hope it has not been changed
        $languageValueKey = self::COLUMN_VALUE . "-" .$language;
        $criteria->addCondition('group_name=:name');
        $criteria->params[':name']= $this->rowAttributes[$languageValueKey];

        /** @var ?QuestionGroup $result */
        $result = QuestionGroup::model()->find($criteria);
        return $result;
    }

    /**
     * @throws Exception
     */
    private function saveGroups()
    {
        $i=0;
        $this->questionGroup = null;
        $this->setGroupsInitialOrder();
        foreach ($this->languages as $language) {
            $i++;
            $this->currentModel = $this->findGroup($language);
            $languageValueKey = self::COLUMN_VALUE . "-" .$language;
            $languageHelpKey = self::COLUMN_HELP . "-" .$language;

            if(!($this->currentModel instanceof QuestionGroup)) {
                $this->currentModel = new QuestionGroup();
            }

            $this->currentModel->setAttributes([
                'sid' => (int) $this->survey->sid,
                'group_name' => $this->rowAttributes[$languageValueKey],
                'description' => $this->rowAttributes[$languageHelpKey],
                'grelevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
                'language' => $language,
                'group_order' => $this->groupOrder,
            ]);
            // relevance not in LS model rules!!
            $this->currentModel->grelevance = $this->rowAttributes[self::COLUMN_RELEVANCE];

            // other languages take main language record gid
            if ($this->questionGroup instanceof QuestionGroup) {
                $this->currentModel->gid = $this->questionGroup->gid;
            }

            // LS misses the validaion rule for 'sid' !!! so we must define this separately
            $this->currentModel->sid = $this->survey->sid;

            $result = $this->currentModel->save();

            if(!$result) {
                throw new Exception('Error saving group : ' . serialize($this->currentModel->getErrors()));
            }

            if($i === 1) {
                $this->questionGroup = $this->currentModel;
            }
        }

        $this->groupOrder ++;
        $this->questionOrder = 1;
    }

    private function setGroupsInitialOrder()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $this->language;
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->order = "group_order DESC";
        $lastGroup = QuestionGroup::model()->find($criteria);

        if($lastGroup instanceof QuestionGroup) {
           $this->groupOrder = $lastGroup->group_order +1;
        }

    }

    /**
     * @throws Exception
     */
    private function saveQuestions(){
        $i=0;
        $this->question = null;
        foreach ($this->languages as $language) {
            $i++;
            $this->currentModel = $this->findQuestion($language);

            $languageValueKey = self::COLUMN_VALUE . "-" .$language;
            $languageHelpKey = self::COLUMN_HELP . "-" .$language;

            if(!($this->currentModel instanceof Question)) {
                $this->currentModel = new Question();
            }

            $mandatory = "Y";
            if(in_array(strtoupper($this->rowAttributes[self::COLUMN_MANDATORY]), ['Y', 'N'])) {
                $mandatory = strtoupper($this->rowAttributes[self::COLUMN_MANDATORY]);
            }

            $this->currentModel->setAttributes([
                'sid' => $this->survey->sid,
                'type' => $this->rowAttributes[self::COLUMN_SUBTYPE],
                'gid' => $this->questionGroup->gid,
                'title' => $this->rowAttributes[self::COLUMN_CODE],
                'question' => $this->rowAttributes[$languageValueKey],
                'help' => $this->rowAttributes[$languageHelpKey],
                'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
                'language' => $language,
                'question_order' => $this->questionOrder,
                'mandatory' => $mandatory,
            ]);

            // other languages take main language record gid
            if ($this->question instanceof Question) {
                $this->currentModel->qid = $this->question->qid;
            }


            $result = $this->currentModel->save();

            if(!$result) {
                throw new Exception("Error saving baseQuestion nr $i: " . $this->rowAttributes[$languageValueKey] . serialize($this->currentModel->getErrors()));
            }

            $questionTheme = "";
            if(!empty($this->rowAttributes[self::COLUMN_THEME])) {
                $questionTheme = $this->rowAttributes[self::COLUMN_THEME];
            }
            if (!empty($questionTheme)) {
                $this->saveQuestionAttribute("question_template", $questionTheme);
            }

            $this->saveQuestionAttributes();
            if($i === 1) {
                $this->question = $this->currentModel;
            }
        }
        $this->questionOrder ++;
        $this->subQuestionOrder = 1;
        $this->answerOrder = 1;
    }

    /**
     * @throws Exception
     */
    private function saveQuestionAttributes()
    {
        if(!isset($this->rowAttributes[self::COLUMN_OPTIONS])) {
            return;
        }

        $attributeInput =$this->rowAttributes[self::COLUMN_OPTIONS];
        $attributeArray = (array) json_decode($attributeInput);
        if(empty($attributeArray)) {
            return;
        }
        // Filter the attributes to only those that need to be validated, unless the
        // importUnknownAttributes setting is set.
        if (!$this->get('importUnknownAttributes', 'Survey', $this->survey->sid, false)) {
            $this->validateAttributes($attributeArray);
        }
        $myAttributes = new MyQuestionAttribute();
        $myAttributes->setAttributes($attributeArray, false);
        $myAttributes->validate();
        foreach ($myAttributes->attributes as $attributeName => $value) {
            if(is_null($value)) {
                continue;
            }
            $this->saveQuestionAttribute($attributeName, $value);
        }
    }

    private function validateAttributes($attributeArray){
        $allowedAttributes = (new MyQuestionAttribute())->attributeNames();
        if(empty($attributeArray)) {
            return;
        }
        foreach ($attributeArray as $attributeName => $value) {
            if(!in_array($attributeName, $allowedAttributes)) {
                throw new \Exception("Question attribute '{$attributeName}' is not defined for IMEX and the import breaks here ");
            }
        }

    }

    private function saveQuestionAttribute(string $attributeName, $value)
    {
        foreach ($this->languages as $language) {
            $attributeModel = QuestionAttribute::model()
                ->find("qid=:qid and attribute=:attributeName and language=:language",[
                    ':qid' => $this->currentModel->qid,
                    ':attributeName' => $attributeName,
                    ':language' => $language
                ]);
            if(!($attributeModel instanceof QuestionAttribute)) {
                $attributeModel = new QuestionAttribute();
                $attributeValues = [
                    'language' => $language,
                    'qid' => $this->currentModel->qid,
                    'attribute' => $attributeName,
                    'value' => $value,
                ];
                $attributeModel->setAttributes($attributeValues);
            }
            // missing in LS validation, need to set again
            $attributeModel->language = $language;
            $attributeModel->value = $value;

            $attributeModel->validate();
            if(!$attributeModel->save()) {
                throw new Exception("error creating question attribute '{$attributeName}' for question {$this->currentModel->name}, errors: "
                    . serialize($attributeModel->errors));
            }
        }
    }


    /**
     * @throws Exception
     */
    private function saveSubQuestions(){
        $i=0;
        $subQuestion = null;
        foreach ($this->languages as $language) {
            $i++;
            $this->currentModel = $this->findSubQuestion($language);

            $languageValueKey = self::COLUMN_VALUE . "-" .$language;
            $languageHelpKey = self::COLUMN_HELP . "-" .$language;

            if(!($this->currentModel instanceof Question)) {
                $this->currentModel = new Question();
            }

            // subquestion validation in yii model is broken, need to to an array and apply in loop
            $attributes  = [
                'sid' => $this->survey->sid,
                'type' => $this->question->type,
                'gid' => $this->questionGroup->gid,
                'title' => $this->rowAttributes[self::COLUMN_CODE],
                'question' => $this->rowAttributes[$languageValueKey],
                'help' => $this->rowAttributes[$languageHelpKey],
                'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
                'language' => $language,
                'question_order' => $this->subQuestionOrder,
                'mandatory' => "N",
                'parent_qid' => $this->question->qid,
            ];
            foreach ($attributes as $key => $value) {
                $this->currentModel->{$key} = $value;
            }


            // other languages take main language record gid
            if ($subQuestion instanceof Question) {
                $this->currentModel->qid = $subQuestion->qid;
            }

            $result = $this->currentModel->save();

            if(!$result) {
                throw new Exception('Error saving subQuestion : ' . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
            }
            if($i === 1) {
                $subQuestion = $this->currentModel;
            }
        }

        $this->subQuestionOrder ++;
    }

    /**
     * @throws Exception
     */
    private function saveAnswers()
    {
        foreach ($this->languages as $language) {
            $this->currentModel = $this->findAnswer($language);
            if(!($this->currentModel instanceof Answer)) {
                $this->currentModel = new Answer();
            }
            $this->currentModel->setAttributes([
                'sid' => $this->survey->primaryKey,
                'language' => $language,
                'qid' => $this->question->qid,
            ]);
            $result = $this->loadAnswer($language);
            if(!$result) {
                throw new Exception('Error saving answer : ' .serialize($this->rowAttributes). serialize($this->currentModel->getErrors()));
            }
        }
    }

    /**
     * @param string $language
     * @return Answer|null
     */
    protected function findAnswer(string $language) : ?Answer
    {
        if (empty($this->question)) {
            return null;
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('qid=:qid');
        $criteria->params[':qid'] = $this->question->qid;

        $criteria->addCondition('code=:code');
        $criteria->params[':code'] = $this->rowAttributes[self::COLUMN_CODE];

        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        return Answer::model()->find($criteria);
    }


    /**
     * @param string $language
     * @return bool
     */
    private function loadAnswer(string $language) : bool
    {
        $languageValueKey = self::COLUMN_VALUE . "-" .$language;
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
            'code' => $this->rowAttributes[self::COLUMN_CODE],
            'answer' => $this->rowAttributes[$languageValueKey],
            'qid' => $this->question->qid,
            'sortorder' => $this->answerOrder
        ]);
        $this->answerOrder ++;

        return $this->currentModel->save();
    }


    /**
     * @return bool
     * @throws Exception
     */
    private function validateStructure() : bool
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
            $isLang = is_int(strpos($value, $searchValue));
            if($isLang) {
                $langStart = strpos($value, "-") +1;
                $langugage = strtolower(trim(substr($value, $langStart, strlen($value))));
                $this->languages[] =  $langugage;
            }
        }
    }

    /**
     * @return bool
     */
    private function validateLanguages() : bool
    {
        if(empty($this->languages)) {
            $this->addError("file", "Languages not defined in file. Must have cols like 'value-en' etc... ");
        }

        foreach ($this->languages as $language) {
            if(!in_array($language, $this->languages)) {
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
    private function validateModels() : bool
    {
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
     * @param string $language
     * @return Question|null
     */
    private function findSubQuestion(string $language) :?Question
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $criteria->addCondition('parent_qid=:parent_qid');
        $criteria->params[':parent_qid'] = $this->question->qid;

        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];

        return Question::model()->find($criteria);
    }


    /**
     * @param string $language
     * @return Question|null
     */
    private function findQuestion(string  $language) :?Question
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->addCondition('sid=:sid');

        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->params[':language'] = $language;

        $criteria->addCondition('parent_qid=0');
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];
        return Question::model()->find($criteria);
    }

}
