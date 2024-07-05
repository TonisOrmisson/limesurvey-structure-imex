<?php

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

require_once __DIR__ . DIRECTORY_SEPARATOR.'vendor/autoload.php';


class ExportQuestionsV5 extends AbstractExport
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

        foreach ($this->getGroups() as $group) {
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
        foreach ($this->survey->allLanguages as $language) {
            if (!isset($group->questiongroupl10ns[$language])) {
                continue;
            }
            $row[] = $this->escapeString($group->questiongroupl10ns[$language]->group_name);
            $row[] = $this->escapeString($group->questiongroupl10ns[$language]->description);
        }

        $row[] = $group->grelevance;
        $row[] = null; // no mandatory
        $row[] = null; // no theme
        $row[] = null; // no attributes
        foreach ($this->survey->allLanguages as $language) {
            $row[] = null; // no i18n attributes
        }

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

        $i18nExportAttributes = [];
        foreach ($this->survey->allLanguages as $language) {
            $i18nExportAttributes[$language] = [];
            if (!isset($question->questionl10ns[$language])) {
                continue;
            }
            $row[] = $this->escapeString($question->questionl10ns[$language]->question);
            $row[] = $this->escapeString($question->questionl10ns[$language]->help);
        }
        $row[] = $question->relevance;
        $row[] = $question->mandatory;
        if ($this->type !== self::TYPE_SUB_QUESTION) {
            $questionTheme = $question->question_theme_name;
            $row[] = ($questionTheme != 'core') ? $questionTheme : null;
        } else {
            $row[] = null;
        }
        $attributes = $this->getQuestionAttributes($question);
        $exportAttributes = [];
        if(!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if ($attribute->attribute === 'question_template') {
                    continue;
                }
                if (!empty($attribute->language)) {
                    $i18nExportAttributes[$attribute->language][$attribute->attribute] = $attribute->value;
                } else {
                    $exportAttributes[$attribute->attribute] = $attribute->value;
                }
            }
        }
        if (!empty($exportAttributes)) {
            $row[] = json_encode($exportAttributes);
        } else {
            // Fill empty cells to match header
            $row[] = null;
        }
        foreach ($this->survey->allLanguages as $language) {
            $row[] = !empty($i18nExportAttributes[$language]) ? json_encode($i18nExportAttributes[$language]) : null;
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

        foreach ($this->getQuestions($group) as $question)
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

        $answers = $this->getAnswers($question);
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $this->processAnswer($answer);
            }
        }

        if ($this->type === self::TYPE_SUB_QUESTION) {
            return;
        }

        $subQuestions = $this->getSubquestions($question);
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
        foreach ($this->survey->allLanguages as $language) {
            if (!isset($answer->answerl10ns[$language])) {
                continue;
            }
            $row[] = $answer->answerl10ns[$language]->answer;
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
    private function getGroups()
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->order = 'group_order ASC';
        return QuestionGroup::model()->findAll($criteria);
    }

    /**
     * @param QuestionGroup $group
     * @return Question[]
     */
    private function getQuestions($group)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition('gid=:gid');
        $criteria->addCondition('parent_qid=0 or parent_qid IS NULL');

        $criteria->params[':gid'] = $group->gid;

        $criteria->order = 'question_order ASC';

        return Question::model()->findAll($criteria);
    }

    /**
     * @param Question $question
     * @return Question[]
     */
    private function getSubquestions($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('parent_qid=:qid');

        $criteria->params[':qid'] = $question->qid;
        $criteria->params[':sid'] =  $this->survey->primaryKey;

        $criteria->order = 'question_order ASC';

        return Question::model()->findAll($criteria);
    }

    /**
     * @param Question $question
     * @return Answer[]
     */
    private function getAnswers($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');
        $criteria->order = 'sortorder ASC';

        $criteria->params[':qid'] = $question->qid;

        return Answer::model()->findAll($criteria);
    }

    private function getQuestionAttributes($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');

        $criteria->params[':qid'] = $question->qid;

        return QuestionAttribute::model()->findAll($criteria);
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
        $this->header[] = ImportStructure::COLUMN_THEME;
        $this->header[] = ImportStructure::COLUMN_OPTIONS;

        foreach ($this->languages as $language) {
            $this->header[] = ImportStructure::COLUMN_OPTIONS ."-".$language;
        }
    }

}
