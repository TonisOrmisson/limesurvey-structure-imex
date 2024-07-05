<?php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

class ExportRelevancesV5 extends AbstractExport
{


    protected $sheetName = "relevances";


    protected function writeData() {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            $relevance = empty($group->grelevance) ? '1' :$group->grelevance;
            $row = WriterEntityFactory::createRowFromArray([$group->questiongroupl10ns[$oSurvey->language]->group_name ?? '',null,null,$relevance]);
            $this->writer->addRow($row);

            foreach ($group->questions as $question) {
                $relevance = empty($question->relevance) ? '1' : $question->relevance;
                $row = WriterEntityFactory::createRowFromArray([null,$question->title, null,$relevance]);
                $this->writer->addRow($row);
                if (!empty($question->subquestions)) {
                    foreach ($question->subquestions as $subQuestion) {
                        $relevance = empty($subQuestion->relevance) ? '1' : $subQuestion->relevance;
                        $row = WriterEntityFactory::createRowFromArray([null,$subQuestion->title, $question->title, $relevance]);
                        $this->writer->addRow($row);
                    }
                }
            }
        }

    }


    protected function loadHeader()
    {
        $this->header = ['group','code','parent','relevance'];
    }
}
