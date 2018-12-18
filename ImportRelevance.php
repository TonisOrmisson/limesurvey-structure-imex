<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'ImportFromFile.php';

class ImportRelevance extends ImportFromFile
{
    /** @var Question  */
    public $currentModel;
    public $importModelsClassName = Question::class;


    /**
     * @inheritdoc
     */
    protected function importModel($attributes)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('title=:code');
        $criteria->params = [':code'=>$attributes['code']];
        // see if we can find A (any) question with this title
        $this->currentModel = Question::model()->find($criteria);
        if($this->currentModel instanceof Question){
            // validate the relevance on the question
            $this->currentModel->relevance = $attributes['relevance'];
            if($this->currentModel->validate(['relevance'])) {
                $this->successfulModelsCount ++;
                // relevance is OK-> load this to all questions with this title
                return Question::model()->updateAll(['relevance'=>$this->currentModel->relevance], $criteria);
            }
            $this->failedModelsCount ++;
            return false;
        }
    }
}