<?php

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';


class ExportQuestions extends AbstractExport
{
    private $type = "";

    /** @var Question $question Main / parent question */
    private $question;


    const TYPE_GROUP = 'G';
    const TYPE_QUESTION = 'Q';
    const TYPE_SUB_QUESTION = 'sq';
    const TYPE_ANSWER = 'a';

    // Question types. Coding as per LS
    const QT_LONG_FREE = 'T';
    const QT_DROPDOWN = 'L';
    const QT_RADIO = 'Z';
    const QT_LIST_WITH_COMMENT = 'O';
    const QT_MULTI = 'M';
    const QT_ARRAY = 'F';
    const QT_MULTIPLE_SHORT_TEXT = 'Q';
    const QT_MULTIPLE_NUMERICAL = 'K';
    const QT_NUMERICAL = 'N';
    const QT_HTML = 'X';
    const QT_MULTI_W_COMMENTS = 'P';
    const QT_SHORT_FREE_TEXT = 'S';
    const QT_EQUATION = '*';

    protected $sheetName = "questions";


    protected function writeData()
    {

        foreach ($this->groupsInMainLanguage() as $group) {
            $this->processGroup($group);
        }

        $this->writeHelpSheet();
        $this->writeOptionsSheet();
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
            null,
            $group->gid,
        ];
        foreach ($this->languageGroups($group) as $lGroup) {
            $row[] = $lGroup->group_name;
            $row[] = $lGroup->description;
        }

        $row[] = $lGroup->grelevance;
        $row[] = null; // no options
        $row[] = null; // no attributes

        $row = WriterEntityFactory::createRowFromArray($row,$this->groupStyle);
        $this->writer->addRow($row);


    }

    /**
     * @param $question Question in main language
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\SpoutException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    private function addQuestion($question) {

        $row = [
            $this->type,
            ($this->type === self::TYPE_SUB_QUESTION ? null : $question->type),
            $question->title,
        ];

        foreach ($this->languageQuestions($question) as $lQuestion) {
            $row[] = $lQuestion->question;
            $row[] = $lQuestion->help;
        }
        $row[] = $question->relevance;
        $row[] = $question->mandatory;
        $attributes = $question->getQuestionAttributes();
        $exportAttributes = [];
        if(!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $exportAttributes[$attribute->attribute] = $attribute->value;
            }
            $row[] = json_encode($exportAttributes);
        }

        $style = $this->type === self::TYPE_SUB_QUESTION ? $this->subQuestionStyle : $this->questionStyle;

        $row = WriterEntityFactory::createRowFromArray($row, $style);
        $this->writer->addRow($row);


    }

    /**
     * @param QuestionGroup $group
     */
    private function processGroup($group) {

        $this->type = self::TYPE_GROUP;
        $this->addGroup($group);

        foreach ($this->questionsInMainLanguage($group) as $question)
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
        $this->addQuestion($question);


        $answers = $this->answersInThisLanguage($question);
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $this->processAnswer($answer);
            }
        }

        if ($this->type === self::TYPE_SUB_QUESTION) {
            return;
        }

        $subQuestions = $this->subQuestionsInThisLanguage($question);
        if (!empty($subQuestions)) {
            foreach ($subQuestions as $subQuestion) {
                $this->type = self::TYPE_SUB_QUESTION;
                $this->addQuestion($subQuestion);
            }
        }


    }


    /**
     * @param Answer $answer
     */
    private function processAnswer(Answer $answer)
    {
        $row = [
            self::TYPE_ANSWER,
            null,
            $answer->code,
        ];
        foreach ($this->languages as $language) {
            $lAnswer = $this->answerInLanguage($answer, $language);
            $row[] = $lAnswer->answer;
            $row[] = null; // no help texts for answers
        }

        $row = WriterEntityFactory::createRowFromArray($row);
        $this->writer->addRow($row);

    }

    private function writeHelpSheet()
    {
        $this->setSheet('helpSheet');
        $header = ['Question type code', 'Question type'];

        $row = WriterEntityFactory::createRowFromArray($header,$this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        foreach ($this->qTypes() as $code => $qType) {
            $data[] = WriterEntityFactory::createRowFromArray([$code, $qType['name']]);
        }

        $this->writer->addRows($data);
    }


    private function writeOptionsSheet()
    {
        $this->setSheet('possibleAttributes');
        $header = ['Attribute name', 'Attribute description', 'Value valiudation'];

        $row = WriterEntityFactory::createRowFromArray($header,$this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        $attributes = new MyQuestionAttribute();
        $possibleValues = $attributes->allowedValues();
        foreach ($attributes->attributeLabels() as $name => $label) {
            $data[] = WriterEntityFactory::createRowFromArray([$name, $label, $possibleValues[$name]]);
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
            self::QT_LIST_WITH_COMMENT => [
                "name" => "Radio list with comment",
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
            self::QT_SHORT_FREE_TEXT => [
                "name" => "Short free text",
            ],
            self::QT_EQUATION => [
                "name" => "Equation",
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
     * @param QuestionGroup $group
     * @return Question[]
     */
    private function questionsInMainLanguage($group)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition('gid=:gid');
        $criteria->addCondition('parent_qid=0 or parent_qid IS NULL');
        $criteria->addCondition("language='" . $this->survey->language."'");

        $criteria->params[':gid'] = $group->gid;

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


    /**
     * @param Question $question
     * @return Question[]
     */
    private function subQuestionsInThisLanguage($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('parent_qid=:qid');
        $criteria->addCondition('language=:language');

        $criteria->params[':language'] = $question->language;
        $criteria->params[':qid'] = $question->qid;
        $criteria->params[':sid'] =  $this->survey->primaryKey;

        return Question::model()->findAll($criteria);
    }
    /**
     * @param Question $question
     * @return Answer[]
     */
    private function answersInThisLanguage($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');
        $criteria->addCondition('language=:language');
        $criteria->order = 'sortorder ASC';

        $criteria->params[':language'] = $question->language;
        $criteria->params[':qid'] = $question->qid;

        return Answer::model()->findAll($criteria);
    }

    /**
     * @param Answer $answer
     * @param $language
     * @return Answer|null
     */
    private function answerInLanguage(Answer $answer, $language) {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');
        $criteria->addCondition('language=:language');
        $criteria->addCondition('code=:code');

        $criteria->params[':language'] = $language;
        $criteria->params[':code'] = $answer->code;
        $criteria->params[':qid'] = $answer->qid;

        return Answer::model()->find($criteria);

    }

    protected function loadHeader()
    {
        $this->header = [
            ImportStructure::COLUMN_TYPE,
            ImportStructure::COLUMN_SUBTYPE,
            ImportStructure::COLUMN_CODE,
        ];

        foreach ($this->languages as $language) {
            $this->header[] = ImportStructure::COLUMN_VALUE ."-".$language;
            $this->header[] = ImportStructure::COLUMN_HELP ."-".$language;
        }

        $this->header[] = ImportStructure::COLUMN_RELEVANCE;
        $this->header[] = ImportStructure::COLUMN_MANDATORY;
        $this->header[] = ImportStructure::COLUMN_OPTIONS;
    }

}
