<?php

namespace tonisormisson\ls\structureimex\export;

use Answer;
use CDbCriteria;
use OpenSpout\Common\Entity\Row;
use Question;
use QuestionAttribute;
use QuestionGroup;
use QuestionL10n;
use QuestionTemplate;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\validation\MyQuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeValidator;
use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

class ExportQuestions extends AbstractExport
{
    private $type = "";



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
            '',
            $group->gid,
        ];
        
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($group->questiongroupl10ns[$language])) {
                    continue;
                }
                $row[] = $group->questiongroupl10ns[$language]->group_name ?? '';
                $row[] = $group->questiongroupl10ns[$language]->description ?? '';
                $row[] = ''; // no script for groups
            }
        } else {
            foreach ($this->languageGroups($group) as $lGroup) {
                $row[] = $lGroup->group_name ?? '';
                $row[] = $lGroup->description ?? '';
                $row[] = ''; // no script for groups
            }
        }

        $row[] = $group->grelevance ?? '';
        $row[] = ''; // no mandatory
        $row[] = ''; // no theme
        $row[] = ''; // no options
        
        // Add empty values for language-specific options columns
        foreach ($this->languages as $language) {
            $row[] = ''; // no language-specific options for groups
        }

        $row = Row::fromValues($row, $this->groupStyle);
        $this->writer->addRow($row);


    }

    private function addQuestion(Question $question)
    {

        $row = [
            $this->type,
            ($this->type === self::TYPE_SUB_QUESTION ? '' : $question->type),
            $question->title,
        ];
        
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($question->questionl10ns[$language])) {
                    continue;
                }
                $row[] = $question->questionl10ns[$language]->question ?? '';
                $row[] = $question->questionl10ns[$language]->help ?? '';
                $row[] = $question->questionl10ns[$language]->script ?? '';
            }
        } else {
            foreach ($this->languages as $language) {
                $lQuestion = $this->getQuestionL10nForLanguage($question, $language);
                $row[] = $lQuestion ? $lQuestion->question : '';
                $row[] = $lQuestion ? $lQuestion->help : '';
                $row[] = $lQuestion ? ($lQuestion->script ?? '') : '';
            }
        }
        $row[] = $question->relevance ?? '';
        $row[] = $question->mandatory ?? '';

        if ($this->type !== self::TYPE_SUB_QUESTION) {
            if ($this->isV4plusVersion()) {
                $questionTheme = $question->question_theme_name;
                $row[] = ($questionTheme != 'core') ? $questionTheme : '';
            } else {
                $questionTemplate = QuestionTemplate::getNewInstance($question);
                $questionTheme = $questionTemplate->getQuestionTemplateFolderName();
                $row[] = !(empty($questionTheme)) ? $questionTheme : '';
            }
        } else {
            $row[] = '';
        }

        $attributes = $this->getQuestionAttributes($question);
        
        
        // Separate global and language-specific attributes
        $globalAttributes = [];
        $languageSpecificAttributes = [];
        
        if (!empty($attributes)) {
            
            // Group attributes by type and language
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->attribute;
                $attributeValue = $attribute->value;
                $attributeLanguage = $attribute->language;
                
                // Skip question_template as it's exported in its own column
                if ($attributeName === 'question_template') {
                    continue;
                }
                
                // Only export attributes that are defined for this question type
                if (!QuestionAttributeDefinition::isValidAttribute($question->type, $attributeName)) {
                    continue;
                }
                
                // Only export attributes with non-default values
                if (!QuestionAttributeDefinition::isNonDefaultValue($question->type, $attributeName, $attributeValue)) {
                    continue;
                }
                
                // Separate by global vs language-specific
                if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
                    // Global attributes (stored with empty language)
                    if (empty($attributeLanguage)) {
                        $globalAttributes[$attributeName] = $attributeValue;
                    }
                } else {
                    // Language-specific attributes (stored with language code)
                    if (!empty($attributeLanguage)) {
                        if (!isset($languageSpecificAttributes[$attributeLanguage])) {
                            $languageSpecificAttributes[$attributeLanguage] = [];
                        }
                        $languageSpecificAttributes[$attributeLanguage][$attributeName] = $attributeValue;
                    }
                }
            }
        }
        
        
        // Add global attributes to the "options" column
        if (!empty($globalAttributes)) {
            $row[] = json_encode($globalAttributes);
        } else {
            $row[] = '';
        }
        
        // Add language-specific attributes to "options-{language}" columns
        foreach ($this->languages as $language) {
            if (!empty($languageSpecificAttributes[$language])) {
                $row[] = json_encode($languageSpecificAttributes[$language]);
            } else {
                $row[] = '';
            }
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
            '',
            $answer->code,
        ];
        
        if ($this->isV4plusVersion()) {
            foreach ($this->languages as $language) {
                if (!isset($answer->answerl10ns[$language])) {
                    continue;
                }
                $row[] = $answer->answerl10ns[$language]->answer ?? '';
                $row[] = ''; // no help texts for answers
                $row[] = ''; // no script for answers
            }
        } else {
            foreach ($this->languages as $language) {
                $lAnswer = $this->answerInLanguage($answer, $language);
                $row[] = $lAnswer->answer ?? '';
                $row[] = ''; // no help texts for answers
                $row[] = ''; // no script for answers
            }
        }

        // Add the missing columns for answers (relevance, mandatory, theme, options)
        $row[] = ''; // no relevance for answers
        $row[] = ''; // no mandatory for answers  
        $row[] = ''; // no theme for answers
        $row[] = ''; // no options for answers
        
        // Add empty values for language-specific options columns
        foreach ($this->languages as $language) {
            $row[] = ''; // no language-specific options for answers
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

    /**
     * Get QuestionL10n data for a specific language (for older LimeSurvey versions)
     * @param Question $question
     * @param string $language
     * @return QuestionL10n|null
     */
    private function getQuestionL10nForLanguage($question, $language)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('qid=:qid');
        $criteria->addCondition('language=:language');
        $criteria->params[':qid'] = $question->qid;
        $criteria->params[':language'] = $language;
        
        return QuestionL10n::model()->find($criteria);
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
            $this->header[] = ImportStructure::COLUMN_SCRIPT . "-" . $language;
        }

        $this->header[] = ImportStructure::COLUMN_RELEVANCE;
        $this->header[] = ImportStructure::COLUMN_MANDATORY;
        $this->header[] = ImportStructure::COLUMN_THEME;
        $this->header[] = ImportStructure::COLUMN_OPTIONS;
        
        // Add language-specific options columns
        foreach ($this->languages as $language) {
            $this->header[] = ImportStructure::COLUMN_OPTIONS . "-" . $language;
        }
    }

    private function getQuestionAttributes($question)
    {
        // Use direct SQL query to avoid any model filtering issues
        $sql = "SELECT * FROM {{question_attributes}} WHERE qid = :qid AND value != ''";
        $command = \Yii::app()->db->createCommand($sql);
        $command->bindValue(':qid', $question->qid);
        $rows = $command->queryAll();
        
        // Convert to QuestionAttribute objects
        $attributes = [];
        foreach ($rows as $row) {
            $attr = new QuestionAttribute();
            $attr->qaid = $row['qaid'];
            $attr->qid = $row['qid'];
            $attr->attribute = $row['attribute'];
            $attr->value = $row['value'];
            $attr->language = $row['language'];
            $attributes[] = $attr;
        }
        
        return $attributes;
    }



}
