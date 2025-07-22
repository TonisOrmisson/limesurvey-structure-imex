<?php

namespace tonisormisson\ls\structureimex\export;

use Answer;
use CDbCriteria;
use OpenSpout\Common\Entity\Row;
use Question;
use QuestionAttribute;
use QuestionGroup;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\validation\MyQuestionAttribute;
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
    }


    private function addGroup(QuestionGroup $group)
    {
        $row = [
            self::TYPE_GROUP,
            '',
            $group->gid,
        ];
        
        foreach ($this->languages as $language) {
            if (!isset($group->questiongroupl10ns[$language])) {
                continue;
            }
            $row[] = $group->questiongroupl10ns[$language]->group_name ?? '';
            $row[] = $group->questiongroupl10ns[$language]->description ?? '';
            $row[] = ''; // no script for groups
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
        
        foreach ($this->languages as $language) {
            $l10n = $question->questionl10ns[$language] ?? null;
            $row[] = $l10n->question ?? '';
            $row[] = $l10n->help ?? '';
            $row[] = $l10n->script ?? '';
        }

        $row[] = $question->relevance ?? '';
        $row[] = $question->mandatory ?? '';

        if ($this->type !== self::TYPE_SUB_QUESTION) {
            $questionTheme = $question->question_theme_name;
            $row[] = ($questionTheme != 'core') ? $questionTheme : '';
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
            $row[] = json_encode($globalAttributes, JSON_UNESCAPED_UNICODE);
        } else {
            $row[] = '';
        }
        
        // Add language-specific attributes to "options-{language}" columns
        foreach ($this->languages as $language) {
            if (!empty($languageSpecificAttributes[$language])) {
                $row[] = json_encode($languageSpecificAttributes[$language], JSON_UNESCAPED_UNICODE);
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

        // Skip answers for M (Multiple Choice) questions - they use subquestions instead
        if ($question->type !== Question::QT_M_MULTIPLE_CHOICE) {
            $answers = $this->answersInMainLanguage($question);
            if (!empty($answers)) {
                foreach ($answers as $answer) {
                    $this->processAnswer($answer);
                }
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
        
        foreach ($this->languages as $language) {
            if (!isset($answer->answerl10ns[$language])) {
                continue;
            }
            $row[] = $answer->answerl10ns[$language]->answer ?? '';
            $row[] = ''; // no help texts for answers
            $row[] = ''; // no script for answers
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
        $header = ['Question Type', 'Attribute Name', 'Default Value', 'Description', 'Value Validation'];

        $row = Row::fromValues($header, $this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        
        // Get all question types in a logical order
        $questionTypes = $this->getAllQuestionTypes();
        $myQuestionAttribute = new MyQuestionAttribute();
        $possibleValues = $myQuestionAttribute->allowedValues();
        
        foreach ($questionTypes as $qType => $info) {
            // Add question type header row
            $data[] = Row::fromValues([
                $qType . ' - ' . $info['name'],
                '',
                '',
                $info['description'],
                ''
            ]);
            
            // Get attributes for this question type
            $attributes = QuestionAttributeDefinition::getAttributesForQuestionType($qType);
            
            if (empty($attributes)) {
                $data[] = Row::fromValues([
                    '',
                    'No specific attributes',
                    '',
                    'Uses only global question attributes (hidden, hide_tip)',
                    ''
                ]);
            } else {
                foreach ($attributes as $attrName => $attrDef) {
                    $description = $this->getAttributeDescription($attrName);
                    $validation = $possibleValues[$attrName] ?? 'Any value';
                    
                    $data[] = Row::fromValues([
                        '',
                        $attrName,
                        $attrDef['default'] ?? '',
                        $description,
                        $validation
                    ]);
                }
            }
            
            // Add empty row for separation
            $data[] = Row::fromValues(['', '', '', '', '']);
        }

        $this->writer->addRows($data);
    }




    private function getAttributeDescription($attributeName)
    {
        $descriptions = [
            'hidden' => 'Hide question from respondents (0=visible, 1=hidden)',
            'hide_tip' => 'Hide question help text (0=show, 1=hide)',
            'readonly' => 'Make question read-only (N=editable, Y=readonly)',
            'maximum_chars' => 'Maximum allowed characters for text input',
            'text_input_width' => 'Width of text input field in pixels',
            'display_rows' => 'Number of rows for textarea display',
            'num_value_int_only' => 'Accept only integer values (0=any, 1=integers)',
            'min_num_value_n' => 'Minimum allowed numerical value',
            'max_num_value_n' => 'Maximum allowed numerical value',
            'suffix' => 'Text to display after numerical input',
            'answer_order' => 'Order of answer options (normal/random)',
            'assessment_value' => 'Enable assessment scoring (0=off, 1=on)',
            'scale_export' => 'Export scale values instead of codes (0=codes, 1=values)',
            'min_answers' => 'Minimum required number of selections',
            'max_answers' => 'Maximum allowed number of selections',
            'em_validation_q_tip' => 'Custom validation error message (language-specific)',
            'date_format' => 'Date display format (e.g., Y-m-d, d/m/Y)',
            'dropdown_dates' => 'Use dropdown for date selection (0=calendar, 1=dropdown)',
            'max_filesize' => 'Maximum file size in kilobytes',
            'allowed_filetypes' => 'Comma-separated list of allowed file extensions',
            'other_replace_text' => 'Custom text for "Other" option (language-specific)',
            'array_filter' => 'Reference question to filter available subquestions',
            'array_filter_style' => 'Array filter style (0=disabled, 1=hidden)',
            'array_filter_exclude' => 'Exclude subquestions from filtering'
        ];
        
        return $descriptions[$attributeName] ?? 'Attribute specific to question type';
    }


    private function getAllQuestionTypes()
    {
        return [
            // === BASIC INPUT TYPES ===
            'T' => [
                'name' => 'Long Free Text',
                'common_attributes' => 'hidden, hide_tip, maximum_chars, text_input_width, display_rows',
                'description' => 'Multi-line text input with configurable size and character limits'
            ],
            'S' => [
                'name' => 'Short Free Text', 
                'common_attributes' => 'hidden, hide_tip, maximum_chars, text_input_width',
                'description' => 'Single-line text input with character limit validation'
            ],
            'U' => [
                'name' => 'Huge Free Text',
                'common_attributes' => 'hidden, hide_tip, maximum_chars, display_rows',
                'description' => 'Large text area for extensive text input'
            ],
            'N' => [
                'name' => 'Numerical Input',
                'common_attributes' => 'hidden, hide_tip, num_value_int_only, min_num_value_n, max_num_value_n',
                'description' => 'Number input with range validation and integer-only option'
            ],
            
            // === SINGLE CHOICE TYPES ===
            'L' => [
                'name' => 'List Radio',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value, scale_export',
                'description' => 'Radio buttons with single selection from predefined options'
            ],
            '!' => [
                'name' => 'List Dropdown',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Dropdown selection with single choice from list'
            ],
            'O' => [
                'name' => 'List with Comment',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value, other_replace_text',
                'description' => 'Radio list with additional comment field for selected option'
            ],
            'Y' => [
                'name' => 'Yes/No Radio',
                'common_attributes' => 'hidden, hide_tip, answer_order',
                'description' => 'Simple Yes/No radio button selection'
            ],
            'G' => [
                'name' => 'Gender',
                'common_attributes' => 'hidden, hide_tip, answer_order',
                'description' => 'Gender selection with Male/Female options'
            ],
            'I' => [
                'name' => 'Language Switch',
                'common_attributes' => 'hidden, hide_tip, answer_order',
                'description' => 'Language selection for multi-language surveys'
            ],
            
            // === MULTIPLE CHOICE TYPES ===
            'M' => [
                'name' => 'Multiple Choice',
                'common_attributes' => 'hidden, hide_tip, min_answers, max_answers, answer_order, array_filter, array_filter_style, array_filter_exclude',
                'description' => 'Checkboxes allowing multiple selections with validation and array filtering'
            ],
            'P' => [
                'name' => 'Multiple Choice with Comments',
                'common_attributes' => 'hidden, hide_tip, min_answers, max_answers, answer_order',
                'description' => 'Multiple choice with comment fields for each selected option'
            ],
            
            // === ARRAY TYPES ===
            'F' => [
                'name' => 'Array (Flexible Labels)',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Matrix question with custom row/column labels'
            ],
            'A' => [
                'name' => 'Array 5 Point Choice',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Matrix with 5-point scale (1-5) for each row'
            ],
            'B' => [
                'name' => 'Array 10 Choice Questions',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Matrix with 10-point scale (1-10) for each row'
            ],
            'C' => [
                'name' => 'Array Yes/Uncertain/No',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Matrix with Yes/Uncertain/No options for each row'
            ],
            'E' => [
                'name' => 'Array Increase/Same/Decrease',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Matrix with Increase/Same/Decrease options'
            ],
            'H' => [
                'name' => 'Array by Column',
                'common_attributes' => 'hidden, hide_tip, answer_order, assessment_value',
                'description' => 'Flexible array with dropdown selections in columns'
            ],
            '1' => [
                'name' => 'Array Dual Scale',
                'common_attributes' => 'hidden, hide_tip, answer_order',
                'description' => 'Matrix with two separate scales for each row'
            ],
            ':' => [
                'name' => 'Array Numbers',
                'common_attributes' => 'hidden, hide_tip, answer_order, num_value_int_only',
                'description' => 'Matrix with numerical input fields'
            ],
            ';' => [
                'name' => 'Array Text',
                'common_attributes' => 'hidden, hide_tip, answer_order, maximum_chars',
                'description' => 'Matrix with text input fields for each cell'
            ],
            
            // === MULTIPLE INPUT TYPES ===
            'Q' => [
                'name' => 'Multiple Short Text',
                'common_attributes' => 'hidden, hide_tip, maximum_chars, text_input_width',
                'description' => 'Multiple text inputs based on subquestions'
            ],
            'K' => [
                'name' => 'Multiple Numerical Input',
                'common_attributes' => 'hidden, hide_tip, num_value_int_only, suffix',
                'description' => 'Multiple numerical inputs with validation'
            ],
            
            // === SPECIAL TYPES ===
            'D' => [
                'name' => 'Date',
                'common_attributes' => 'hidden, hide_tip, date_format, dropdown_dates',
                'description' => 'Date picker with configurable format and display options'
            ],
            'R' => [
                'name' => 'Ranking',
                'common_attributes' => 'hidden, hide_tip, min_answers, max_answers',
                'description' => 'Drag-and-drop ranking of options in order of preference'
            ],
            '|' => [
                'name' => 'File Upload',
                'common_attributes' => 'hidden, hide_tip, max_filesize, allowed_filetypes',
                'description' => 'File upload with size and type restrictions'
            ],
            '*' => [
                'name' => 'Equation',
                'common_attributes' => 'hidden, hide_tip, readonly',
                'description' => 'Calculate and display computed values based on other answers'
            ],
            'X' => [
                'name' => 'Text Display',
                'common_attributes' => 'hidden, hide_tip, readonly',
                'description' => 'Display-only text or HTML content without input'
            ],
            '5' => [
                'name' => '5 Point Choice',
                'common_attributes' => 'hidden, hide_tip, answer_order',
                'description' => 'Single 5-point scale selection (1-5)'
            ]
        ];
    }

    /**
     * @return QuestionGroup[]
     */
    private function groupsInMainLanguage()
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
    private function questionsInMainLanguage($group)
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
    private function subQuestionsInMainLanguage($question)
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('parent_qid=:qid');
        $criteria->params[':qid'] = $question->qid;
        $criteria->params[':sid'] = $this->survey->primaryKey;
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

        return Answer::model()->findAll($criteria);
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
