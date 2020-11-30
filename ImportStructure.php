<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportStructure extends ImportFromFile
{
    /** @var LSActiveRecord  */
    public $currentModel;

    /** @var string */
    public $importModelsClassName = "";


    /** @var Question $question current question (main/parent) */
    private $question;

    /** @var Question $subQuestion current subQuestion (main/parent) */
    private $subQuestion;

    /** @var QuestionGroup $questionGroup current questionGroup */
    private $questionGroup;

    /** @var int  */
    private $groupOrder = 1;

    /** @var int  */
    private $questionOrder = 1;

    /** @var int  */
    private $subQuestionOrder = 1;

    /** @var int  */
    private $answerOrder = 1;

    /** @var string[]  */
    private $languages = [];


    const COLUMN_TYPE = 'type';
    const COLUMN_SUBTYPE = 'subtype';
    const COLUMN_CODE = 'code';
    const COLUMN_RELEVANCE = 'relevance';
    const COLUMN_OPTIONS = 'options';
    const COLUMN_VALUE = 'value';
    const COLUMN_HELP = 'help';
    const COLUMN_MANDATORY = 'mandatory';

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function importModel($attributes)
    {
        $this->questionCodeColumn = static::COLUMN_CODE;
        $this->rowAttributes = $attributes;
        $this->initType();

        switch ($this->type) {
            case ExportQuestions::TYPE_GROUP:
                return $this->saveGroups();
            case ExportQuestions::TYPE_QUESTION:
                return $this->saveQuestions();
            case ExportQuestions::TYPE_ANSWER:
                return $this->saveAnswers();
            case ExportQuestions::TYPE_SUB_QUESTION:
                return $this->saveSubQuestions();

        }
        $this->currentModel = null;
    }


    /**
     * {@inheritdoc}
     */
    protected function beforeProcess()
    {
        parent::beforeProcess();
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
                throw new \Exception('Invalid Type: ' . $this->rowAttributes[self::COLUMN_TYPE]);
        }
    }

    /**
     * @return QuestionGroup|null
     */
    protected function findGroup($language)
    {
        if ($this->type != ExportQuestions::TYPE_GROUP) {
            throw new \Exception('Not a group!');
        }
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->params[':language'] = $language;

        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;


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

        $result = QuestionGroup::model()->find($criteria);
        return $result;
    }

    /**
     * @throws Exception
     */
    private function saveGroups(){
        $i=0;
        $this->questionGroup = null;
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
            // other languages take main language record gid
            if ($this->questionGroup instanceof QuestionGroup) {
                $this->currentModel->gid = $this->questionGroup->gid;
            }

            // LS misses the validaion rule for 'sid' !!! so we must define this separately
            $this->currentModel->sid = $this->survey->sid;

            $result = $this->currentModel->save();

            if(!$result) {
                throw new \Exception('Error saving group : ' . serialize($this->currentModel->getErrors()));
            }

            if($i === 1) {
                $this->questionGroup = $this->currentModel;
            }
        }

        $this->groupOrder ++;
        $this->questionOrder = 1;
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
                throw new \Exception("Error saving baseQuestion nr $i: " . $this->rowAttributes[$languageValueKey] . serialize($this->currentModel->getErrors()));
            }
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
    private function saveSubQuestions(){
        $i=0;
        $this->subQuestion = null;
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
            if ($this->subQuestion instanceof Question) {
                $this->currentModel->qid = $this->subQuestion->qid;
            }

            $result = $this->currentModel->save();

            if(!$result) {
                //var_dump($this->currentModel->getAttributes());die;
                throw new \Exception('Error saving subQuestion : ' . serialize($this->rowAttributes) . serialize($this->currentModel->getErrors()));
            }
            if($i === 1) {
                $this->subQuestion = $this->currentModel;
            }
        }

        $this->subQuestionOrder ++;
    }


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
                throw new \Exception('Error saving answer : ' .serialize($this->rowAttributes). serialize($this->currentModel->getErrors()));
            }
        }
    }


    /**
     * @return Answer|null
     */
    protected function findAnswer($language)
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
    private function loadAnswer($language)
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
     */
    private function validateStructure()
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
    private function validateLanguages()
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
    private function validateModels()
    {
        $thisModel = null;
        $i = 0;
        foreach ($this->readerData as $row) {
            $i++;
            $this->rowAttributes = $row;
            try {
                $this->initType();
            } catch (\Exception $e) {
                $this->addError("file", sprintf("Invaid row type '%s' on row %s", $this->rowAttributes[self::COLUMN_TYPE], $i));
            }
        }
        return empty($this->errors);

    }



    /**
     * @param string $language
     * @return array|mixed|null
     */
    private function findSubQuestion($language)
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

        $question = Question::model()->find($criteria);
        return $question;
    }


    /**
     * @param string $language
     * @return array|mixed|null
     */
    private function findQuestion($language)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('language=:language');
        $criteria->addCondition('sid=:sid');

        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->params[':language'] = $language;

        $criteria->addCondition('parent_qid=0');
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];
        $question = Question::model()->find($criteria);
        return $question;
    }

}
