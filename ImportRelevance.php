<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportRelevance extends ImportFromFile
{
    use AppTrait;

    /** @var Question  */
    public $currentModel;


    /**
     * @inheritdoc
     */
    protected function importModel($attributes) : void
    {
        $this->currentModel = null;
        $this->rowAttributes = $attributes;
        $this->currentModel = $this->findModel($attributes);

        $this->questionCodeColumn = 'code';

        if (empty($this->currentModel)) {
            $this->addError('currentModel', "Unable to find model for row " . json_encode($attributes));
            return;
        }

        $this->currentModel->{$this->relevanceAttribute} = $attributes['relevance'];

        if ($this->type === ExportQuestions::TYPE_GROUP) {
            $result = $this->updateGroup($this->currentModel);
        } else {
            $result = $this->updateQuestion($this->currentModel);
        }


        if ($result !== false) {
            $this->successfulModelsCount ++;
            return;
        }

        $this->addError('currentModel', "Unable to save model for row: " . json_encode($attributes));

        $this->failedModelsCount ++;

    }

    /**
     * @param QuestionGroup $model
     * @return bool|int
     */
    private function  updateGroup(QuestionGroup  $model) {
        if($model->validate([$this->relevanceAttribute])) {
            $criteria = new CDbCriteria();
            $criteria->addCondition('sid=:sid');
            $criteria->addCondition('gid=:gid');
            $criteria->params = [':gid' => $model->gid, ':sid'=> $this->survey->primaryKey];
            return QuestionGroup::model()->updateAll([$this->relevanceAttribute => $model->{$this->relevanceAttribute}], $criteria);
        }
        return false;
    }

    /**
     * @param Question $model
     * @return bool|int
     */
    private function  updateQuestion(Question $model) {
        if($model->validate([$this->relevanceAttribute])) {
            $criteria = new CDbCriteria();
            $criteria->addCondition('qid=:qid');
            $criteria->params = [':qid' => $model->qid];

            // delete all manually set conditions
            Condition::model()->deleteAll('qid=:qid', [':qid' => $model->qid]);

            return Question::model()->updateAll([$this->relevanceAttribute => $model->{$this->relevanceAttribute}], $criteria);
        }
        return false;

    }

    /**
     * @param $row
     * @return Question|QuestionGroup|null
     * @throws Exception
     */
    private function findModel($row)
    {

        $this->relevanceAttribute = 'relevance';

        if (!empty($row['group']) && ($model = $this->findGroup($row)) instanceof QuestionGroup) {
            $this->type = ExportQuestions::TYPE_GROUP;
            $this->relevanceAttribute = 'grelevance';
            return $model;
        }

        if (empty($row['group']) && !empty($row['parent']) && ($model = $this->findSubQuestion($row)) instanceof Question) {
            $this->type = ExportQuestions::TYPE_SUB_QUESTION;
            return $model;
        }

        if (($model = $this->findQuestion()) instanceof Question) {
            $this->type = ExportQuestions::TYPE_QUESTION;
            return $model;
        }
        $this->type = '';
        return null;
    }

    /**
     * @param $row
     * @return QuestionGroup|null
     * @throws Exception
     */
    protected function findGroup($row)
    {
        $criteria = new CDbCriteria();
        $criteria->params[':language'] = $this->language;
        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->params[':name']=$row['group'];

        $criteria->addCondition('t.language=:language');
        $criteria->addCondition('t.group_name=:name');

        if($this->isV4plusVersion()) {
            $criteria->addCondition('group.sid=:sid');
            /** @var QuestionGroupL10n $l10n */
            $l10n = QuestionGroupL10n::model()
                ->with('group')
                ->find($criteria);
            if($l10n === null) {
                throw new ErrorException("Unable to find group with name: " . $row['group']);
            }

            return $l10n->group;

        }

        $criteria->addCondition('sid=:sid');
        return QuestionGroup::model()->find($criteria);
    }

    /**
     * @param $row
     * @return Question|null
     */
    protected function findSubQuestion($row) {
        $this->questionCodeColumn = 'parent';
        $parent = $this->findQuestion();

        if (empty($parent)) {
            $this->addError('currentModel', "Unable to find parent question {$row['parent']} for question {$row['code']}");
            return null;
        }



        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;

        $criteria->addCondition('parent_qid=:parent_qid');
        $criteria->addCondition('title=:code');
        $criteria->params[':parent_qid'] =$parent->qid;
        $criteria->params[':code'] =$row['code'];
        $this->questionCodeColumn = 'code';

        if(!$this->isV4plusVersion()) {
            $criteria->addCondition('language=:language');
            $criteria->params[':language'] = $this->language;
        }

        return Question::model()->find($criteria);
    }


    /**
     * @return Question|null
     */
    protected function findQuestion()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->params[':sid'] = $this->survey->primaryKey;
        $criteria->addCondition('title=:code');
        $criteria->params[':code'] = $this->rowAttributes[$this->questionCodeColumn];
        $criteria->addCondition('parent_qid=0');


        if(!$this->isV4plusVersion()) {
            $criteria->addCondition('language=:language');
            $criteria->params[':language'] = $this->language;
        }
        return Question::model()->find($criteria);
    }


    protected function beforeProcess(): void
    {
        return;
    }
}
