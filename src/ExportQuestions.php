<?php

namespace tonisormisson\ls\structureimex;

use Answer;
use CDbCriteria;
use OpenSpout\Common\Entity\Row;
use Question;
use QuestionAttribute;
use QuestionGroup;
use QuestionTemplate;

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


    private function addGroup(QuestionGroup $group)
    {
        $row = [
            self::TYPE_GROUP,
            null,
            $group->gid,
        ];
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($group->questiongroupl10ns[$language])) {
                    continue;
                }
                $row[] = $group->questiongroupl10ns[$language]->group_name;
                $row[] = $group->questiongroupl10ns[$language]->description;
            }
        } else {
            foreach ($this->languageGroups($group) as $lGroup) {
                $row[] = $lGroup->group_name;
                $row[] = $lGroup->description;
            }
        }

        $row[] = $group->grelevance;
        $row[] = null; // no mandatory
        $row[] = null; // no theme
        $row[] = null; // no options

        $row = Row::fromValues($row, $this->groupStyle);
        $this->writer->addRow($row);


    }

    private function addQuestion(Question $question)
    {

        $row = [
            $this->type,
            ($this->type === self::TYPE_SUB_QUESTION ? null : $question->type),
            $question->title,
        ];

        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($question->questionl10ns[$language])) {
                    continue;
                }
                $row[] = $question->questionl10ns[$language]->question;
                $row[] = $question->questionl10ns[$language]->help;
            }
        } else {
            foreach ($this->languageQuestions($question) as $lQuestion) {
                $row[] = $lQuestion->question;
                $row[] = $lQuestion->help;
            }
        }
        $row[] = $question->relevance;
        $row[] = $question->mandatory;

        if ($this->type !== self::TYPE_SUB_QUESTION) {
            if ($this->isV4plusVersion()) {
                $questionTheme = $question->question_theme_name;
                $row[] = ($questionTheme != 'core') ? $questionTheme : null;
            } else {
                $questionTemplate = QuestionTemplate::getNewInstance($question);
                $questionTheme = $questionTemplate->getQuestionTemplateFolderName();
                $row[] = !(empty($questionTheme)) ? $questionTheme : null;
            }
        } else {
            $row[] = null;
        }

        $attributes = $this->getQuestionAttributes($question);
        $exportAttributes = [];
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                // We already exported the question template/theme on it's own column,
                // so we don't need to export it again as part of the question attributes.
                if ($attribute->attribute === 'question_template') {
                    continue;
                }
                $exportAttributes[$attribute->attribute] = $attribute->value;
            }
            $row[] = json_encode($exportAttributes);
        }

        $style = $this->type === self::TYPE_SUB_QUESTION ? $this->subQuestionStyle : $this->questionStyle;

        $row = Row::fromValues($row, $style);
        $this->writer->addRow($row);


    }

    /**
     * @param QuestionGroup $group
     */
    private function processGroup($group)
    {

        $this->type = self::TYPE_GROUP;
        $this->addGroup($group);

        foreach ($this->questionsInMainLanguage($group) as $question) {
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


        $answers = $this->answersInMainLanguage($question);
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $this->processAnswer($answer);
            }
        }

        if ($this->type === self::TYPE_SUB_QUESTION) {
            return;
        }

        $subQuestions = $this->subQuestionsInMainLanguage($question);
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
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($answer->answerl10ns[$language])) {
                    continue;
                }
                $row[] = $answer->answerl10ns[$language]->answer;
                $row[] = null; // no help texts for answers
            }
        } else {
            foreach ($this->languages as $language) {
                $lAnswer = $this->answerInLanguage($answer, $language);
                $row[] = $lAnswer->answer;
                $row[] = null; // no help texts for answers
            }
        }

        $row = Row::fromValues($row);
        $this->writer->addRow($row);

    }

    private function writeHelpSheet()
    {
        $this->setSheet('helpSheet');
        $header = ['Question type code', 'Question type'];

        $row = Row::fromValues($header, $this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        foreach ($this->qTypes() as $code => $qType) {
            $data[] = Row::fromValues([$code, $qType['name']]);
        }

        $this->writer->addRows($data);
    }


    private function writeOptionsSheet()
    {
        $this->setSheet('possibleAttributes');
        $header = ['Attribute name', 'Attribute description', 'Value valiudation'];

        $row = Row::fromValues($header, $this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        $attributes = new MyQuestionAttribute();
        $possibleValues = $attributes->allowedValues();
        foreach ($attributes->attributeLabels() as $name => $label) {
            $data[] = Row::fromValues([$name, $label, $possibleValues[$name]]);
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
        if (!$this->isV4plusVersion()) {
            $criteria->addCondition("language='" . $this->survey->language . "'");
        }
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
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition('gid=' . $group->gid);
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
        if (!$this->isV4plusVersion()) {
            $criteria->addCondition("language='" . $this->survey->language . "'");
        }

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
        $criteria->addCondition('sid=' . $this->survey->primaryKey);
        $criteria->addCondition('qid=' . $question->qid);
        return Question::model()->findAll($criteria);
    }


    /**
     * @param Question $question
     * @return Question[]
     */
    private function subQuestionsInMainLanguage($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('parent_qid=:qid');
        $criteria->params[':qid'] = $question->qid;
        $criteria->params[':sid'] = $this->survey->primaryKey;

        if (!$this->isV4plusVersion()) {
            $criteria->addCondition('language=:language');
            $criteria->params[':language'] = $this->survey->language;
        }

        return Question::model()->findAll($criteria);
    }

    /**
     * @param Question $question
     * @return Answer[]
     */
    private function answersInMainLanguage($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');
        $criteria->order = 'sortorder ASC';
        $criteria->params[':qid'] = $question->qid;

        if (!$this->isV4plusVersion()) {
            $criteria->addCondition('language=:language');
            $criteria->params[':language'] = $this->survey->language;
        }

        return Answer::model()->findAll($criteria);
    }

    /**
     * @param Answer $answer
     * @param $language
     * @return Answer|null
     */
    private function answerInLanguage(Answer $answer, $language)
    {
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
            $this->header[] = ImportStructure::COLUMN_VALUE . "-" . $language;
            $this->header[] = ImportStructure::COLUMN_HELP . "-" . $language;
        }

        $this->header[] = ImportStructure::COLUMN_RELEVANCE;
        $this->header[] = ImportStructure::COLUMN_MANDATORY;
        $this->header[] = ImportStructure::COLUMN_THEME;
        $this->header[] = ImportStructure::COLUMN_OPTIONS;
    }

    private function getQuestionAttributes($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');

        $criteria->params[':qid'] = $question->qid;

        return QuestionAttribute::model()->findAll($criteria);
    }


}
