<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';


class ExportQuestions extends AbstractExport
{
    const TYPE_GROUP = 'G';
    const TYPE_QUESTION = 'Q';
    const TYPE_SUB_QUESTION = 'sq';
    const TYPE_ANSWER = 'a';

    // Question types. Codeing as per LS
    const QT_LONG_FREE = 'T';
    const QT_DROPDOWN = 'L';
    const QT_RADIO = 'Z';
    const QT_MULTI = 'M';
    const QT_ARRAY = 'F';
    const QT_MULTIPLE_SHORT_TEXT = 'Q';
    const QT_MULTIPLE_NUMERICAL = 'K';
    const QT_NUMERICAL = 'N';
    const QT_HTML = 'X';
    const QT_MULTI_W_COMMENTS = 'P';

    protected $header = ['type', 'language', 'one', 'two', 'three', 'relevance', 'options'];

    protected $sheetName = "questions";


    protected function writeData() {
        $oSurvey = $this->survey;
        foreach ($oSurvey->groups as $group) {
            $this->processGroup($group);
        }
        $this->writeHelpSheet();
    }

    /**
     * @param QuestionGroup $group
     */
    private function processGroup($group) {
        $row = [
            self::TYPE_GROUP,
            null,
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
            $question->type,
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
            null,
            $answer->language,
            $question->title,
            $answer->code,
            $answer->answer,
        ];
        $this->writer->addRow($row);
    }

    private function writeHelpSheet()
    {
        $this->setSheet('helpSheet');
        $header = ['Question type code', 'Question type'];
        $this->writer->addRowWithStyle($header,  $this->headerStyle);
        $data = [];
        foreach ($this->qTypes() as $code => $qType) {
            $data[] = [$code, $qType['name']];
        }

        $this->writer->addRows($data);
    }


    private function qTypes()
    {
        return [
            self::QT_LONG_FREE => [
                "name" => "Long free text",
            ],
            self::QT_DROPDOWN => [
                "name" => "Dropdown list",
            ],
            self::QT_RADIO => [
                "name" => "Radio list",
            ],
            self::QT_MULTI => [
                "name" => "Multiple choice",
            ],
            self::QT_MULTI_W_COMMENTS => [
                "name" => "Multiple choice with comments",
            ],
            self::QT_ARRAY => [
                "name" => "Array",
            ],
            self::QT_MULTIPLE_SHORT_TEXT => [
                "name" => "Multiple short text",
            ],
            self::QT_MULTIPLE_NUMERICAL => [
                "name" => "Multiple numerical input",
            ],
            self::QT_NUMERICAL => [
                "name" => "Numerical input",
            ],
            self::QT_HTML => [
                "name" => "Text display",
            ],
            self::QT_HTML => [
                "name" => "Text display",
            ],
        ];
    }

}