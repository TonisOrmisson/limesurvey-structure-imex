<?php

namespace tonisormisson\ls\structureimex\export;

use CModel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\Common\Entity\Sheet;
use Survey;
use tonisormisson\ls\structureimex\AppTrait;
use tonisormisson\ls\structureimex\StructureImEx;
use Yii;

abstract class AbstractExport extends CModel
{
    use AppTrait;


    /** @var string */
    public $path = "tmp/runtime/";

    /** @var string */
    public $fileName;

    /** @var AbstractWriterMultiSheets */
    public $writer;

    /** @var  string[] */
    protected $header = [];

    /** @var Style */
    public $headerStyle;


    /** @var array Currently processed columns */
    protected $columns = [];

    /** @var array Currently processed rows */
    protected $rows = [];


    /** @var Sheet */
    protected $sheet;

    /** @var string */
    protected $sheetName = "data";

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

    /** @var integer main LimeSurvey application sw version number eg 3 etc */
    protected $applicationMajorVersion = 3;

    protected StructureImEx $plugin;

    use AppTrait;


    public function __construct(protected Survey $survey)
    {

        $this->applicationMajorVersion = intval($this->app()->getConfig("versionnumber"));

        $this->fileName = "survey_{$this->survey->primaryKey}_{$this->sheetName}_" . substr(bin2hex(random_bytes(10)), 0, 4) . ".xlsx";
        $this->languages = $this->survey->getAllLanguages();

        $this->writer = new \OpenSpout\Writer\XLSX\Writer();
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
        return $this->path . $this->fileName;
    }

    public function attributeNames()
    {
        return [];
    }

    protected function initStyles()
    {
        $this->headerStyle = new Style();
        $this->headerStyle->setFontBold();
        $this->headerStyle->setFontColor(Color::BLUE);


        $this->groupStyle = new Style();
        $this->groupStyle->setFontColor(Color::WHITE);
        $this->groupStyle->setBackgroundColor(Color::GREEN);

        $this->questionStyle = new Style();
        $this->questionStyle->setFontBold();
        $this->questionStyle->setBackgroundColor(Color::LIGHT_GREEN);

        $this->subQuestionStyle = new Style();
        //$this->subQuestionStyle->setBackgroundColor(Color::LIGHT_GREEN)
    }


    protected function writeHeaders()
    {
        $this->loadHeader();
        $this->setSheet($this->sheetName);
        $row = Row::fromValues($this->header, $this->headerStyle);
        $this->writer->addRow($row);
    }

    /**
     * @param string $sheetName
     */
    protected function setSheet($sheetName)
    {
        $this->sheetName = $sheetName;

        if ($this->sheetsCount === 0) {
            $this->sheet = $this->writer->getCurrentSheet();
        } else {
            $this->sheet = $this->writer->addNewSheetAndMakeItCurrent();
        }
        $this->sheet->setName($this->sheetName);
        $this->sheetsCount++;
    }

    protected abstract function writeData();

    protected abstract function loadHeader();


}
