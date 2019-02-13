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


    /**
     * @inheritdoc
     */
    protected function importModel($attributes)
    {
        $this->questionCodeColumn = 'code';
        $this->initModel($attributes);

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
                $question = $this->findQuestion();
                $this->question = $question;
                return $question;
                break;
            case ExportQuestions::TYPE_GROUP:
                return $this->findGroup();
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


}