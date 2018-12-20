<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Common\Sheet;

class Export extends CModel
{

    /** @var Survey $survey */
    protected $survey;

    /** @var string */
    public $fileName;

    /** @var ExcelWriter */
    public $writer;

    /** @var  string[] */
    protected $header = ['group','code','parent','relevance'];

    /** @var StyleBuilder */
    public $headerStyle;


    /** @var array Currently processed columns */
    protected $columns = [];

    /** @var array Currently processed rows */
    protected $rows  = [];


    /** @var Sheet */
    protected $sheet;

    /** @var string */
    protected $sheetName = "relevances";

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
        $this->fileName = "survey_{$this->survey->primaryKey}_logic_". substr(bin2hex(random_bytes(10)),0,4).".ods";

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
        $this->setSheet('relevances');
        $this->writer->addRowWithStyle($this->header,  $this->headerStyle);
    }

    /**
     * @param string $sheetName
     */
    protected function setSheet($sheetName){
        $this->sheetName = $sheetName;

        if($this->sheetsCount === 0){
            $this->sheet = $this->writer->getCurrentSheet();
        }else{
            $this->sheet = $this->writer->addNewSheetAndMakeItCurrent();
        }
        $this->sheet->setName($this->sheetName);
        $this->sheetsCount ++;
    }

    private function writeData() {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            // only base language
            if ($group->language != $oSurvey->language) {
                continue;
            }

            $relevance = empty($group->grelevance) ? '1' :$group->grelevance;

            $this->writer->addRow([$group->group_name,null,null,$relevance]);

            foreach ($group->questions as $question) {
                // only base language
                if ($question->language != $oSurvey->language) {
                    continue;
                }
                $relevance = empty($question->relevance) ? '1' :$question->relevance;
                $this->writer->addRow([null,$question->title, null,$relevance]);
                if (!empty($question->subquestions)) {
                    foreach ($question->subquestions as $subQuestion) {
                        $relevance = empty($subQuestion->relevance) ? '1' : $subQuestion->relevance;

                        $this->writer->addRow([null,$subQuestion->title, $question->title, $relevance]);
                    }
                }
            }
        }

    }


}