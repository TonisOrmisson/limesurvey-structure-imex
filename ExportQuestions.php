<?php
require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';


class ExportQuestions extends AbstractExport
{
    private $type = "";

    /** @var Question $question Main / parent question */
    private $question;

    /** @var \Box\Spout\Writer\Style\Style */
    private $groupStyle;

    /** @var \Box\Spout\Writer\Style\Style */
    private $questionStyle;

    /** @var \Box\Spout\Writer\Style\Style */
    private $subQuestionStyle;


    const TYPE_GROUP = 'G';
    const TYPE_QUESTION = 'Q';
    const TYPE_SUB_QUESTION = 'sq';
    const TYPE_ANSWER = 'a';

    // Question types. Coding as per LS
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

    protected $header = [
        ImportStructure::COLUMN_TYPE,
        ImportStructure::COLUMN_SUBTYPE,
        ImportStructure::COLUMN_LANGUAGE,
        ImportStructure::COLUMN_CODE,
        ImportStructure::COLUMN_TWO,
        ImportStructure::COLUMN_THREE,
        ImportStructure::COLUMN_RELEVANCE,
        ImportStructure::COLUMN_OPTIONS,
    ];

    protected $sheetName = "questions";


    protected function writeData()
    {

        foreach ($this->groupsInMainLanguage() as $group) {
            $this->processGroup($group);
        }

        $this->writeHelpSheet();
    }


    /**
     * @param QuestionGroup $group
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\SpoutException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    private function addGroup($group) {
        $row = [
            self::TYPE_GROUP,
            $group->gid,
            $group->language,
            $group->gid,
            $group->group_name,
            $group->description,
            $group->grelevance,
        ];

        $this->writer->addRowWithStyle($row,  $this->groupStyle);

    }

    /**
     * @param $question Question
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\SpoutException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    private function addQuestion($question) {
        $row = [
            $this->type,
            ($this->type === self::TYPE_SUB_QUESTION ? $this->question->title : $question->type),
            $question->language,
            $question->title,
            $question->question,
            $question->help,
            $question->relevance,
        ];
        $style = $this->type === self::TYPE_SUB_QUESTION ? $this->subQuestionStyle : $this->questionStyle;

        $this->writer->addRowWithStyle($row,  $style);

    }

    /**
     * @param QuestionGroup $group
     */
    private function processGroup($group) {

        foreach ($this->languageGroups($group) as $lGroup) {
            $this->type = self::TYPE_GROUP;
            $this->addGroup($lGroup);
        }

        foreach ($this->questionsInMainLanguage() as $question)
        {
            $this->question = $question;
            $this->type = self::TYPE_QUESTION;
            $this->processQuestion($question);
        }
    }


    /**
     * @param Question $question
     */
    private function processQuestion($question)
    {

        foreach ($this->languageQuestions($question) as $lQuestion) {
            $this->addQuestion($lQuestion);
        }


        foreach ($this->languageQuestions($question) as $lQuestion) {
            if (!empty($lQuestion->answers)) {
                foreach ($lQuestion->answers as $answer) {
                    $this->processAnswer($answer, $lQuestion);
                }
            }
        }


        if (!empty($question->subquestions)) {
            foreach ($question->subquestions as $subQuestion) {
                $this->type = self::TYPE_SUB_QUESTION;

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

    /**
     * @return QuestionGroup[]
     */
    private function groupsInMainLanguage()
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition("language='" . $this->survey->language."'");
        $criteria->order = 'group_order ASC';
        return QuestionGroup::model()->findAll($criteria);
    }

    /**
     * @param QuestionGroup $group
     * @return QuestionGroup[]
     */
    private function languageGroups($group)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' .  $this->survey->primaryKey);
        $criteria->addCondition('gid=' .  $group->gid);
        return QuestionGroup::model()->findAll($criteria);
    }

    /**
     * @return Question[]
     */
    private function questionsInMainLanguage()
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition("language='" . $this->survey->language."'");
        $criteria->order = 'question_order ASC';
        return Question::model()->findAll($criteria);
    }

    /**
     * @param Question $question
     * @return Question[]
     */
    private function languageQuestions($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' .  $this->survey->primaryKey);
        $criteria->addCondition('qid=' .  $question->qid);
        return Question::model()->findAll($criteria);
    }


}