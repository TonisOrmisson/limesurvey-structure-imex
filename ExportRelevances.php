<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Common\Sheet;

class ExportRelevances extends AbstractExport
{

    protected $header = ['group','code','parent','relevance'];

    protected $sheetName = "relevances";


    protected function writeData() {
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
                $relevance = empty($question->relevance) ? '1' : $question->relevance;
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