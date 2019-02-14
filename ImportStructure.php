<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportStructure extends ImportFromFile
{
    /** @var LSActiveRecord  */
    public $currentModel;

    /** @var string */
    public $importModelsClassName = "";

    /** @var array */
    public $attributes = [];

    /** @var Question $question current question (main/parent) */
    private $question;

    /** @var QuestionGroup $questionGroup current questionGroup */
    private $questionGroup;

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

    protected function initModel($attributes)
    {
        $this->currentModel = null;
        $this->attributes = $attributes;
        $this->initType();

        switch ($this->type) {
            case ExportQuestions::TYPE_QUESTION:
                break;
            case ExportQuestions::TYPE_GROUP:
                break;
            case ExportQuestions::TYPE_ANSWER:
                break;
        }

        $this->currentModel = $this->findModel();

    }

    protected function initType()
    {
        switch (strtolower($this->attributes[0])) {
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
                throw new \Exception('Invalid Type: ' . $this->attributes[0]);
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
        $criteria->params[':gid']= $this->attributes['code'];

        return QuestionGroup::model()->find($criteria);
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
                break;
            case ExportQuestions::TYPE_GROUP:
                $model = $this->findGroup();
                $this->questionGroup = $model;
                return $model;
                break;
            case ExportQuestions::TYPE_ANSWER:
                return $this->findAnswer();
                break;
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
        $criteria->params[':code'] = $this->attributes['two'];
        $criteria->params[':language'] = $this->attributes['language'];
        return Answer::model()->find($criteria);
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
                $this->currentModel = $this->createNewQuestion();
                return;
            case ExportQuestions::TYPE_SUB_QUESTION:
                $this->currentModel = $this->createNewSubQuestion();
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
            'title' => $this->attributes[self::COLUMN_CODE],
            'question' => $this->attributes[self::COLUMN_TWO],
            'relevance' => $this->attributes[self::COLUMN_RELEVANCE],
        ]);

    }


    private function createNewQuestion()
    {
        $this->createBaseQuestion();

        $this->currentModel->setAttributes([
            'type' => $this->attributes[self::COLUMN_SUBTYPE],
            'help' => $this->attributes[self::COLUMN_THREE],
        ]);
    }

    private function createNewSubQuestion()
    {
        $this->createBaseQuestion();

        $this->currentModel->setAttributes([
            'type' => $this->question->type,
            'parent_qid' => $this->question->primaryKey,
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