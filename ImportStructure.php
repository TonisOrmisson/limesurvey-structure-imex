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


    const COLUMN_TYPE = 'type';
    const COLUMN_SUBTYPE = 'subtype';
    const COLUMN_LANGUAGE = 'language';
    const COLUMN_CODE = 'code';
    const COLUMN_TWO = 'two';
    const COLUMN_THREE = 'three';
    const COLUMN_RELEVANCE = 'relevance';
    const COLUMN_OPTIONS = 'options';

    /**
     * @inheritdoc
     */
    protected function importModel($attributes)
    {
        $this->questionCodeColumn = 'code';
        $this->initModel($attributes);


        if (empty($this->currentModel)) {
            $this->createNewModel();
        }

        $this->saveModel();
        $this->currentModel = null;
    }

    /**
     * @param array attributes
     * @throws Exception
     */
    protected function initModel($attributes)
    {
        $this->currentModel = null;
        $this->rowAttributes = $attributes;
        $this->initType();
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
        $this->language = $this->rowAttributes[self::COLUMN_LANGUAGE];
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
    protected function findGroup()
    {
        if ($this->type != ExportQuestions::TYPE_GROUP) {
            throw new \Exception('Not a group!');
        }
        $criteria = $this->baseCriteria();

        if ($this->surveyHasGroup($this->rowAttributes[self::COLUMN_CODE])) {
            $criteria->addCondition('gid=:gid');
            $criteria->params[':gid']= $this->rowAttributes[self::COLUMN_CODE];
        } else {
            $criteria->addCondition('group_name=:name');
            $criteria->params[':name']= $this->rowAttributes[self::COLUMN_TWO];
        }
        $result = QuestionGroup::model()->find($criteria);
        return $result;
    }

    /**
     * @return bool
     */
    private function saveModel()
    {
        switch ($this->type) {
            case ExportQuestions::TYPE_QUESTION:
                return $this->saveQuestion();
            case ExportQuestions::TYPE_SUB_QUESTION:
                return $this->saveSubQuestion();
            case ExportQuestions::TYPE_GROUP:
                return $this->saveGroup();
            case ExportQuestions::TYPE_ANSWER:
                return $this->saveAnswer();

        }
        return false;

    }


    /**
     * @return bool
     */
    private function saveGroup()
    {
        $this->currentModel->setAttributes([
            'sid' => $this->survey->sid,
            'group_name' => $this->rowAttributes[self::COLUMN_TWO],
            'description' => $this->rowAttributes[self::COLUMN_THREE],
            'grelevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'group_order' => $this->groupOrder,
        ]);


        $this->currentModel->sid = $this->survey->sid;
        if (!empty($this->questionGroup)) {
            $this->currentModel->gid = $this->questionGroup->gid;
        }

        $result = $this->currentModel->save();

        if (empty($this->questionGroup)) {
            $this->questionGroup = $this->currentModel;
            $this->groupOrder ++;
            $this->questionOrder = 1;
        }


        if (!$result) {
            var_dump($this->rowAttributes);
            var_dump($this->currentModel->attributes);
            var_dump($this->currentModel->errors);
            //var_dump($this->currentModel->getValidators());
            die;
        }
        return $result;

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

    /**
     * @return bool
     */
    private function saveAnswer()
    {
        $this->currentModel->setAttributes([
            'qid' => $this->question->qid,
            'code' => $this->rowAttributes[self::COLUMN_TWO],
            'answer' => $this->rowAttributes[self::COLUMN_THREE],
            'sortorder' => $this->answerOrder,
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
        ]);
        $this->answerOrder ++;

        return $this->currentModel->save();
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
    protected function findAnswer()
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
        $criteria->params[':language'] = $this->rowAttributes[self::COLUMN_LANGUAGE];
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
    private function createNewModel()
    {
        switch ($this->type) {
            case ExportQuestions::TYPE_ANSWER:
                $this->createNewAnswer();
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

    private function createNewAnswer()
    {
        $this->currentModel = new Answer();
        $this->currentModel->setAttributes([
            'sid' => $this->survey->primaryKey,
        ]);
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
        if (!$this->validateLanguages()) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function validateLanguages()
    {
        $languages = [];

        $surveyLanguages = $this->survey->allLanguages;
        foreach ($this->readerData as $row) {
            $language = strtolower(trim($row[self::COLUMN_LANGUAGE]));
            if(empty($language)) {
                $this->addError("file", "Language missing for: " . serialize($row));
            }
            if (!in_array($language, $surveyLanguages)) {
                $this->addError("file", sprintf("Language %s not used in survey", $language));
            }
            if (!empty($this->errors)) {
                return false;
            }
        }

        return empty($this->errors);
    }





}