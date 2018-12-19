<?php

/**
 * An abstract class for various file imports
 */
abstract class ImportFromFile extends CModel
{
    /** @var CUploadedFile imported file  */
    public $file;

    /** @var array|bool imported raw data  */
    public $data;

    /** @var string[] allowed extension types */
    public $allowedTypes = ['csv','xls','xlsx'];

    /** @var integer Total number of processed records */
    public $processedModelsCount = 0;

    /** @var integer Total number of successfully processed records */
    public $successfulModelsCount = 0;

    /** @var integer Total number of records failed the processing */
    public $failedModelsCount = 0;

    /** @var LSActiveRecord current model being processed */
    public $currentModel;

    /** @var LSActiveRecord[] Models that failed the processing */
    public $failedModels;

    /** @var string The Classname of importable models */
    public $importModelsClassName;

    //FIXME validate filetypes (eg rules)


    function __construct()
    {
        if(!$this->importModelsClassName){
            throw new ErrorException('You need to set importable models class name in: '.__CLASS__);
        }
    }

    /**
     * @param CUploadedFile $file
     * @return bool
     */
    public function loadFile($file){
        $this->file = $file;
        $this->validate(array('file'));
        if($this->hasErrors()){
            return false;
        }
        $sPath = Yii::app()->getConfig('tempdir');
        $sFileName = $sPath . '/' . randomChars(20);
        @ini_set('auto_detect_line_endings', true);
        if (!@$this->file->saveAs($sFileName)) {
            $this->addError('file',gT('Error saving file'));
            return false;
        }

        $rows   = array_map('str_getcsv', file($sFileName));
        if(!$rows){
            $this->addError('file',gT('Error getting data from file'));
            return false;
        }

        $header = array_shift($rows);
        $this->data  = array();
        foreach($rows as $row) {
            $this->data[] = array_combine($header, $row);
        }

        return true;
    }

    public function process(){
        if(empty($this->data)) {
            $this->addError('data',gT('No data to import!'));
        } else {
            foreach ($this->data as $row){
                $this->importModel($row);
                if (!empty($this->getErrors())) {
                    return false;
                }
            }
        }

    }

    /**
     * @param array $attributes
     */
    abstract protected function importModel($attributes);

    /**
     * @inheritdoc
     */
    public function attributeNames()
    {
        return array(
            'file'=> gT('Import file'),
            'processedModelsCount'=> gT('Total records processed'),
            'successfulModelsCount'=> gT('Successful records'),
            'failedModelsCount'=> gT('Failed records'),
        );
    }
}