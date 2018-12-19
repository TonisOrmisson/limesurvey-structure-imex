<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportRelevance extends ImportFromFile
{
    /** @var Question  */
    public $currentModel;
    public $importModelsClassName = Question::class;

    /** @var Survey $survey */
    protected $survey;

    /** @var string  */
    private $type = "";

    /** @var string  */
    private $relevanceAttribute = "";

    const TYPE_GROUP = 1;
    const TYPE_QUESTION = 2;
    const TYPE_SUBQUESTION = 3;

    public function __construct($survey)
    {
        parent::__construct();
        if (!($survey instanceof Survey)) {
            throw new ErrorException(get_class($survey) .' used as Survey');
        }
        $this->survey = $survey;
    }


    /**
     * @inheritdoc
     */
    protected function importModel($attributes)
    {
        $this->currentModel = null;
        $this->currentModel = $this->findModel($attributes);
        $result = false;

        if (empty($this->currentModel)) {
            $this->addError('currentModel', "Unable to find model for row " . var_dump($attributes));
            return;
        }

        $this->currentModel->{$this->relevanceAttribute} = $attributes['relevance'];


        if ($this->type === self::TYPE_GROUP) {
            $result = $this->updateGroup($this->currentModel);
        } else {
            $result = $this->updateQuestion($this->currentModel);
        }


        if ($result !== false) {
            $this->successfulModelsCount ++;
            return $result;
        }

        $this->addError('currentModel', "Unable to save model for row: " . serialize($attributes));

        $this->failedModelsCount ++;
        return $result;

    }

    /**
     * @param QuestionGroup $model
     * @return bool|int
     */
    private function  updateGroup($model) {
        if($model instanceof QuestionGroup) {
            if($model->validate([$this->relevanceAttribute])) {
                $criteria = new CDbCriteria();
                $criteria->addCondition('sid=:sid');
                $criteria->addCondition('gid=:gid');
                $criteria->params = [':gid' => $model->gid, ':sid'=>$this->survey->primaryKey];
                return QuestionGroup::model()->updateAll([$this->relevanceAttribute => $model->{$this->relevanceAttribute}], $criteria);
            }
            // TODO error?
        }
        return false;
    }

    /**
     * @param Question $model
     * @return bool|int
     */
    private function  updateQuestion($model) {
        if($model instanceof Question) {
            if($model->validate([$this->relevanceAttribute])) {
                $criteria = new CDbCriteria();
                $criteria->addCondition('qid=:qid');
                $criteria->params = [':qid' => $model->qid];

                // delete all manually set conditions
                Condition::model()->deleteAll('qid=:qid', [':qid' => $model->qid]);

                return Question::model()->updateAll([$this->relevanceAttribute => $model->{$this->relevanceAttribute}], $criteria);
            }
            // TODO error?
        }
        return false;

    }

    /**
     * @param $row
     * @return Question|QuestionGroup|null
     */
    private function findModel($row) {

        $this->relevanceAttribute = 'relevance';

        if (!empty($row['group']) && ($model = $this->findGroup($row)) instanceof QuestionGroup) {
            $this->type = self::TYPE_GROUP;
            $this->relevanceAttribute = 'grelevance';
            return $model;
        }

        if (empty($row['group']) && !empty($row['parent']) && ($model = $this->findSubQuestion($row)) instanceof Question) {
            $this->type = self::TYPE_SUBQUESTION;
            return $model;
        }

        if (($model = $this->findQuestion($row)) instanceof Question) {
            $this->type = self::TYPE_QUESTION;
            return $model;
        }
        $this->type = '';
        return null;
    }

    /**
     * @param $row
     * @return QuestionGroup|null
     */
    private function findGroup($row) {
        $criteria = $this->baseCriteria();
        $criteria->addCondition('group_name=:name');
        $criteria->params[':name']=$row['group'];
        return QuestionGroup::model()->find($criteria);
    }

    /**
     * @param $row
     * @return Question|null
     */
    private function findQuestion($row) {
        $criteria = $this->baseCriteria();

        $criteria->addCondition('parent_qid=0');
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $row['code'];

        return Question::model()->find($criteria);
    }

    /**
     * @param $row
     * @return Question|null
     */
    private function findSubQuestion($row) {
        $parent = $this->findQuestion(['code'=>$row['parent']]);

        if (empty($parent)) {
            $this->addError('currentModel', "Unable to find parent question {$row['parent']} for question {$row['code']}");
            return null;
        }

        $criteria = $this->baseCriteria();

        $criteria->addCondition('parent_qid=:parent_qid');
        $criteria->addCondition('title=:code');
        $criteria->params[':parent_qid'] =$parent->qid;
        $criteria->params[':code'] =$row['code'];

        return Question::model()->find($criteria);
    }

    /**
     * @return CDbCriteria
     */
    private function baseCriteria() {
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:survey_id');
        $criteria->addCondition('language=:language');
        $criteria->params = [
            ':survey_id'=>$this->survey->primaryKey,
            ':language'=>$this->survey->language,
        ];
        return $criteria;

    }
}