<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\WriterMultiSheetsAbstract;

abstract class AbstractExport extends CModel
{

    /** @var Survey $survey */
    protected $survey;

    /** @var string */
    public $path = "tmp/runtime/";

    /** @var string */
    public $fileName;

    /** @var WriterMultiSheetsAbstract */
    public $writer;

    /** @var  string[] */
    protected $header = [];

    /** @var Style */
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

    /** @var Style */
    protected $groupStyle;

    /** @var Style */
    protected $questionStyle;

    /** @var Style */
    protected $subQuestionStyle;

    /** @var string[] survey languages */
    protected $languages = [];



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
        $this->languages = $survey->getAllLanguages();

        $this->writer = WriterFactory::createFromType(Type::ODS);
        $this->initStyles();


        $this->writer->openToFile($this->getFullFileName());
        $this->writeHeaders();
        $this->writeData();
        $this->writer->close();
    }

    /**
     * @return string
     */
    public function getFullFileName()
    {
        return $this->path.$this->fileName;
    }

    protected function initStyles()
    {
        $this->headerStyle = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor(Color::BLUE)
            ->build();


        $this->groupStyle = (new StyleBuilder())
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::GREEN)
            ->build();

        $this->questionStyle = (new StyleBuilder())
            ->setFontBold()
            ->setBackgroundColor(Color::LIGHT_GREEN)
            ->build();

        $this->subQuestionStyle = (new StyleBuilder())
            //->setBackgroundColor(Color::LIGHT_GREEN)
            ->build();
    }

    public function attributeNames()
    {
        // TODO: Implement attributeNames() method.
    }

    protected function writeHeaders()
    {
        $this->loadHeader();
        $this->setSheet($this->sheetName);
        $row = WriterEntityFactory::createRowFromArray($this->header,$this->headerStyle);
        $this->writer->addRow($row);
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
    protected  abstract function loadHeader();

}
