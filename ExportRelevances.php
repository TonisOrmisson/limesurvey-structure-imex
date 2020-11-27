<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

class ExportRelevances extends AbstractExport
{


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


    protected function loadHeader()
    {
        $this->header = ['group','code','parent','relevance'];
    }
}
