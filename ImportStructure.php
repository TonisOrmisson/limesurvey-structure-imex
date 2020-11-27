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
    const COLUMN_LANGUAGE = 'language';
    const COLUMN_CODE = 'code';
    const COLUMN_TWO = 'two';
    const COLUMN_THREE = 'three';
    const COLUMN_RELEVANCE = 'relevance';
    const COLUMN_OPTIONS = 'options';
    const COLUMN_VALUE = 'value';
    const COLUMN_HELP = 'help';


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
            case ExportQuestions::TYPE_ANSWER:
                return $this->saveAnswers();
            case ExportQuestions::TYPE_QUESTION:
                return $this->saveQuestion();
            case ExportQuestions::TYPE_SUB_QUESTION:
                return $this->saveSubQuestion();

        }
        $this->currentModel = null;
    }

    /**
     * @throws Exception
     */
    protected function initModel()
    {
        $this->currentModel = null;
        $this->currentModel = $this->findModel();
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
        $criteria->addCondition('sid=:sid');

        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->params[':language'] = $language;
        $languageValueKey = self::COLUMN_VALUE . "-" .$language;


        // if the file is an export file, it will possibly contain group id
        if(!empty($this->rowAttributes[self::COLUMN_CODE])) {
            $criteria->params[':gid']= $this->rowAttributes[self::COLUMN_CODE];
        } else {
            // otherwise try to look by name and hope it has not been changed
            $criteria->addCondition('group_name=:name');
            $criteria->params[':name']= $this->rowAttributes[$languageValueKey];
        }

        $result = QuestionGroup::model()->find($criteria);
        return $result;
    }

    /**
     * @throws Exception
     */
    private function saveGroups(){
        foreach ($this->languages as $language) {
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

            // LS misses the validaion rule for 'sid' !!! so we must define this separately
            $this->currentModel->sid = $this->survey->sid;

            $result = $this->currentModel->save();

            if(!$result) {
                throw new \Exception('Error saving group : ' . serialize($this->currentModel->getErrors()));
            }
            $this->questionGroup = $this->currentModel;
        }

        $this->groupOrder ++;
        $this->questionOrder = 1;
    }

    /**
     * @return bool
     */
    private function saveQuestion()
    {
        $this->currentModel->setAttributes([
            'sid' => $this->survey->sid,
            'type' => $this->rowAttributes[self::COLUMN_SUBTYPE],
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'help' => $this->rowAttributes[self::COLUMN_THREE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'question_order' => $this->questionOrder,
        ]);

        if (!empty($this->question)) {
            $this->currentModel->qid = $this->question->qid;
        }



        // the question is always the first row of questions inserted
        $result =  $this->currentModel->save();

        if (!$result) {
            throw new \Exception("Unable to save question. Errors: " . serialize($this->currentModel->errors));
        }

        if (empty($this->question)) {
            $this->question = $this->currentModel;
            $this->questionOrder ++ ;
            $this->answerOrder = 1;
            $this->subQuestionOrder = 1;
        }
        return true;

    }

    /**
     * @return bool
     */
    private function saveSubQuestion()
    {

        if (empty($this->question)) {
            throw new \Exception('Question missing for subquestion');
        }

        $attributes = [
            'sid' => $this->survey->sid,
            'type' => $this->question->type,
            'parent_qid' => $this->question->qid,
            'gid' => $this->question->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'help' => $this->rowAttributes[self::COLUMN_THREE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'question_order' => $this->subQuestionOrder,
        ];

        foreach ($attributes as $key => $value) {
            $this->currentModel->{$key} = $value;
        }

        $result = $this->currentModel->save();
        if (!$result) {
            throw new \Exception("Unable to save sub-question. Errors: " . serialize($this->currentModel->errors));
        }

        $this->subQuestionOrder ++ ;
        return true;


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
                throw new \Exception('Error saving answer : ' . serialize($this->currentModel->getErrors()));
            }
        }
    }

    /**
     * @return Answer|QuestionGroup|Question|null
     * @throws Exception
     */
    private function findModel()
    {
        switch ($this->type) {
            case ExportQuestions::TYPE_QUESTION:
                $model = $this->findQuestion();

                // a set of new questions
                if (!empty($this->question) &&  $this->rowAttributes[self::COLUMN_CODE] !== $this->question->title) {
                    $this->question = null;
                }
                return $model;
            case ExportQuestions::TYPE_SUB_QUESTION:
                return $this->findSubQuestion();
            case ExportQuestions::TYPE_GROUP:
                $model = $this->findGroup();
                // a set of new questions
                if (!empty($this->questionGroup) &&  $this->rowAttributes[self::COLUMN_TWO] !== $this->questionGroup->group_name) {
                    $this->questionGroup = null;
                    return $model;
                }
                return $model;
            case ExportQuestions::TYPE_ANSWER:
                return $this->findAnswer();
            default:
                throw new \InvalidArgumentException('Invalid type: ' . $this->type);
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
        $criteria = $this->baseCriteria();
        $criteria->addCondition('qid=:qid');
        $criteria->addCondition('code=:code');
        $criteria->addCondition('language=:language');
        $criteria->params[':qid'] = $this->question->qid;
        $criteria->params[':code'] = $this->rowAttributes[self::COLUMN_TWO];
        $criteria->params[':language'] = $language;
        return Answer::model()->find($criteria);
    }


    /**
     * @return Question|null
     */
    private function findSubQuestion()
    {

        $criteria = $this->baseCriteria();

        $criteria->addCondition('parent_qid=:qid');
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];
        $criteria->params[':qid'] = $this->question->qid;
        $result = Question::model()->find($criteria);
        return $result;
    }

    /**
     * @return void|null
     */
    private function createNewModels()
    {
        switch ($this->type) {
            case ExportQuestions::TYPE_ANSWER:
                $this->createNewAnswers();
                return;
            case ExportQuestions::TYPE_QUESTION:
                $this->createBaseQuestion();
                return;
            case ExportQuestions::TYPE_SUB_QUESTION:
                $this->createSubQuestion();
                return;
            case ExportQuestions::TYPE_GROUP:
                $this->createNewQuestionGroup();
                return;
        }
        return null;
    }


    private function createBaseQuestion()
    {

        $this->currentModel = new Question();
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
        ]);
    }

    private function createSubQuestion()
    {
        $attributes = [
            'sid' => $this->survey->sid,
            'type' => $this->rowAttributes[self::COLUMN_SUBTYPE],
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'help' => $this->rowAttributes[self::COLUMN_THREE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'question_order' => $this->questionOrder,
        ];
        $this->currentModel = null;
        $this->currentModel = new Question();
        foreach ($attributes as $key => $value) {
            $this->currentModel->{$key} = $value;
        }

    }

    private function createNewQuestionGroup()
    {
        $this->currentModel = new QuestionGroup();
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
        ]);
    }



    private function createNewAnswers()
    {
        foreach ($this->languages as $language) {
            $this->currentModel = new Answer();
            $this->currentModel->setAttributes([
                'sid' => $this->survey->primaryKey,
                'language' => $language,
                'qid' => $this->question->qid,
            ]);
            $this->loadAnswer($language);
        }
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
     * @param string $gid group id
     * @return bool
     */
    private function surveyHasGroup($gid)
    {
        $criteria = $this->baseCriteria();
        $criteria->addCondition('gid=:gid');
        $criteria->params[':gid'] = $gid;
        $result = QuestionGroup::model()->find($criteria);

        return !empty($result);
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






}
