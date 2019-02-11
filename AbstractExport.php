<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Common\Sheet;

abstract class AbstractExport extends CModel
{

    /** @var Survey $survey */
    protected $survey;

    /** @var string */
    public $fileName;

    /** @var \Box\Spout\Writer\AbstractMultiSheetsWriter */
    public $writer;

    /** @var  string[] */
    protected $header = [];

    /** @var StyleBuilder */
    public $headerStyle;

    /** @var array Currently processed columns */
    protected $columns = [];

    /** @var array Currently processed rows */
    protected $rows  = [];


    /** @var Sheet */
    protected $sheet;

    /** @var string */
    protected $sheetName = "";

    /** @var integer */
    protected $sheetsCount = 0;


    /**
     * Export constructor.
     * @param $survey
     * @throws ErrorException
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public function __construct($survey)
    {

        if (!($survey instanceof Survey)) {
            throw new ErrorException(get_class($survey) .' used as Survey');
        }

        $this->survey = $survey;
        $this->fileName = "survey_{$this->survey->primaryKey}_{$this->sheetName}_". substr(bin2hex(random_bytes(10)),0,4).".ods";

        $this->writer = WriterFactory::create(Type::ODS);

        $this->headerStyle = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor(Color::BLUE)
            ->build();

        $this->writer->openToFile($this->fileName);
        $this->setHeaders();
        $this->writeData();
        $this->writer->close();
    }

    public function attributeNames()
    {
        // TODO: Implement attributeNames() method.
    }

    protected function setHeaders()
    {
        $this->setSheet($this->sheetName);
        $this->writer->addRowWithStyle($this->header,  $this->headerStyle);
    }

    /**
     * @param string $sheetName
     */
    protected function setSheet($sheetName){
        $this->sheetName = $sheetName;

        if($this->sheetsCount === 0) {
            $this->sheet = $this->writer->getCurrentSheet();
        } else {
            $this->sheet = $this->writer->addNewSheetAndMakeItCurrent();
        }
        $this->sheet->setName($this->sheetName);
        $this->sheetsCount ++;
    }

    protected  abstract function writeData();

}