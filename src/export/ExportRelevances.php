<?php

namespace tonisormisson\ls\structureimex\export;

use OpenSpout\Common\Entity\Row;
use QuestionGroup;


class ExportRelevances extends AbstractExport
{
    protected $sheetName = "relevances";


    protected function writeData()
    {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            // only base language - skip non-primary language groups
            if ($group->language != $oSurvey->language) {
                continue;
            }

            $this->writeGroup($group);

            foreach ($group->questions as $question) {

                $relevance = empty($question->relevance) ? '1' : $question->relevance;
                $row = Row::fromValues([null, $question->title, null, $relevance]);
                $this->writer->addRow($row);
                foreach ($question->subquestions as $subQuestion) {
                    $relevance = empty($subQuestion->relevance) ? '1' : $subQuestion->relevance;
                    $this->writer->addRow(Row::fromValues([null, $subQuestion->title, $question->title, $relevance]));
                }
            }
        }

    }

    private function writeGroup(QuestionGroup $group)
    {
        $relevance = empty($group->grelevance) ? '1' : $group->grelevance;
        $group_name = $group->getPrimaryTitle();
        $this->writer->addRow(Row::fromValues([$group_name, null, null, $relevance]));
    }


    protected function loadHeader()
    {
        $this->header = ['group', 'code', 'parent', 'relevance'];
    }
}
