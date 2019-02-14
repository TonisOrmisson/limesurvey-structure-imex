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
     * @throws Exception
     */
    protected function initType()
    {
        switch (strtolower($this->rowAttributes[0])) {
            case strtolower(ExportQuestions::TYPE_QUESTION):
                $this->type = ExportQuestions::TYPE_QUESTION;
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
                throw new \Exception('Invalid Type: ' . $this->rowAttributes[0]);
        }
    }

    /**
     * @return QuestionGroup|null
     */
    protected function findGroup()
    {
        if ($this->type != ExportQuestions::TYPE_QUESTION) {
            throw new \Exception('Not a group!');
        }

        $criteria = $this->baseCriteria();
        $criteria->addCondition('gid=:gid');
        $criteria->params[':gid']= $this->rowAttributes['code'];

        return QuestionGroup::model()->find($criteria);
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
            'group_name' => $this->rowAttributes[self::COLUMN_TWO],
            'description' => $this->rowAttributes[self::COLUMN_THREE],
            'grelevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'group_order' => $this->groupOrder,
        ]);
        $this->groupOrder ++;
        $this->questionOrder = 1;

        return $this->currentModel->save();
    }

    /**
     * @return bool
     */
    private function saveQuestion()
    {
        $this->currentModel->setAttributes([
            'type' => $this->rowAttributes[self::COLUMN_SUBTYPE],
            'gid' => $this->questionGroup->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'help' => $this->rowAttributes[self::COLUMN_THREE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'question_order' => $this->questionOrder,
        ]);

        $this->questionOrder ++ ;
        $this->answerOrder = 1;
        $this->subQuestionOrder = 1;
        return $this->currentModel->save();
    }

    /**
     * @return bool
     */
    private function saveSubQuestion()
    {
        $this->currentModel->setAttributes([
            'type' => $this->question->type,
            'parent_qid' => $this->question->qid,
            'gid' => $this->question->gid,
            'title' => $this->rowAttributes[self::COLUMN_CODE],
            'question' => $this->rowAttributes[self::COLUMN_TWO],
            'help' => $this->rowAttributes[self::COLUMN_THREE],
            'relevance' => $this->rowAttributes[self::COLUMN_RELEVANCE],
            'language' => $this->rowAttributes[self::COLUMN_LANGUAGE],
            'question_order' => $this->subQuestionOrder,
        ]);
        $this->subQuestionOrder ++ ;
        return $this->currentModel->save();
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
                $this->question = $model;
                return $model;
            case ExportQuestions::TYPE_SUB_QUESTION:
                return $this->findSubQuestion();
            case ExportQuestions::TYPE_GROUP:
                $model = $this->findGroup();
                $this->questionGroup = $model;
                return $model;
            case ExportQuestions::TYPE_ANSWER:
                return $this->findAnswer();
        }
        return null;
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
        $criteria->params[':qid'] = $this->rowAttributes[$this->question->qid];
        return Question::model()->find($criteria);
    }

    /**
     * @return void|null
     */
    private function createNewModel()
    {
        switch ($this->type) {
            case ExportQuestions::TYPE_ANSWER:
                $this->currentModel = $this->createNewAnswer();
                return;
            case ExportQuestions::TYPE_QUESTION:
                $this->currentModel = $this->createBaseQuestion();
                return;
            case ExportQuestions::TYPE_SUB_QUESTION:
                $this->currentModel = $this->createBaseQuestion();
                return;
            case ExportQuestions::TYPE_GROUP:
                $this->currentModel = $this->createNewQuestionGroup();
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


}