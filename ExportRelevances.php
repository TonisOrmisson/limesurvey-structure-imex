<?php

use OpenSpout\Common\Entity\Row;

require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

class ExportRelevances extends AbstractExport
{
    protected $sheetName = "relevances";


    protected function writeData() {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            // only base language
            if (!$this->isV4plusVersion() & $group->language != $oSurvey->language) {
                continue;
            }

            $this->writeGroup($group);

            foreach ($group->questions as $question) {
                if (!$this->isV4plusVersion() ) {
                    // only base language
                    if($question->language != $oSurvey->language) {
                        continue;
                    }
                }
                $relevance = empty($question->relevance) ? '1' : $question->relevance;
                $row = Row::fromValues([null,$question->title, null,$relevance]);
                $this->writer->addRow($row);
                if (!empty($question->subquestions)) {
                    foreach ($question->subquestions as $subQuestion) {
                        $relevance = empty($subQuestion->relevance) ? '1' : $subQuestion->relevance;
                        $this->writer->addRow(Row::fromValues([null,$subQuestion->title, $question->title, $relevance]));
                    }
                }
            }
        }

    }

    private function writeGroup(QuestionGroup $group)
    {
        $relevance = empty($group->grelevance) ? '1' :$group->grelevance;
        if ($this->isV4plusVersion() ) {
            $group_name = $group->getPrimaryTitle();
        } else {
            $group_name = $group->group_name;
        }
        $this->writer->addRow(Row::fromValues([$group_name,null,null,$relevance]));
    }


    protected function loadHeader()
    {
        $this->header = ['group','code','parent','relevance'];
    }
}
