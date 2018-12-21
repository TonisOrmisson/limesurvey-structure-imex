<?php

require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;

/**
 * An abstract class for various file imports
 */
abstract class ImportFromFile extends CModel
{
    /** @var CUploadedFile imported file  */
    public $file;

    /** @var string  */
    public $fileName;

    /** @var SpreadsheetReader */
    private $reader;

    /** @var array  */
    public $readerData;

    /** @var array|bool imported raw data  */
    public $data;

    /** @var array|bool imported current row  */
    public $row;

    /** @var string[] allowed extension types */
    public $allowedTypes = [Type::ODS, Type::XLSX, 'xls'];

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
        $sFileName = $sPath . '/' . $this->file->name;
        $this->fileName = $sFileName;

        @ini_set('auto_detect_line_endings', true);
        if (!@$this->file->saveAs($sFileName)) {
            $this->addError('file',gT('Error saving file'));
            return false;
        }
        $this->prepare();

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

    public function prepare(){
        $this->reader = new SpreadsheetReader($this->fileName);
        $this->setReaderData();
        $this->prepareReaderData();
        return true;

    }

    protected function prepareReaderData(){
        if(!empty($this->readerData)){
            foreach ($this->readerData as $key => $row){
                $this->row = $row;
            }
        }

    }


    /**
     * read current worksheet row by row and set row data as readerData
     */
    private function setReaderData(){
        $this->readerData = [];
        foreach ($this->reader as $row) {
            // skip empty rows
            if(empty($row[0]) && empty($row[1]) && empty($row[2])){
                continue;
            }
            $this->readerData[] = $row;
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