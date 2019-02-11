<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';

use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Common\Sheet;

class ExportQuestions extends AbstractExport
{
    const TYPE_GROUP = 'G';
    const TYPE_QUESTION = 'Q';
    const TYPE_SUB_QUESTION = 'sq';
    const TYPE_ANSWER = 'a';

    protected $header = ['type', 'language', 'one', 'two', 'three', 'relevance', 'options'];

    protected $sheetName = "questions";


    protected function writeData() {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            $this->processGroup($group);
        }
    }

    /**
     * @param QuestionGroup $group
     */
    private function processGroup($group) {
        $row = [
            self::TYPE_GROUP,
            $group->language,
            $group->gid,
            $group->group_name,
            $group->description,
            $group->grelevance,
        ];

        $this->writer->addRow($row);

        foreach ($group->questions as $question) {
            $this->processQuestion($question);
        }
    }


    /**
     * @param Question $question
     */
    private function processQuestion($question)
    {
        $row = [
            self::TYPE_QUESTION,
            $question->language,
            $question->title,
            $question->question,
            $question->help,
            $question->relevance,
        ];

        $this->writer->addRow($row);


        if (!empty($question->answers)) {
            foreach ($question->answers as $answer) {
                $this->processAnswer($answer, $question);
            }
        }

        if (!empty($question->subquestions)) {
            foreach ($question->subquestions as $subQuestion) {
                $this->processQuestion($subQuestion);
            }
        }
    }

    /**
     * @param Answer $answer
     * @param Question $question
     */
    private function processAnswer($answer,  $question)
    {
        $row = [
            self::TYPE_ANSWER,
            $answer->language,
            $question->title,
            $answer->code,
            $answer->answer,
        ];
        $this->writer->addRow($row);
    }

}