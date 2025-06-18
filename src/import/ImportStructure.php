<?php

namespace tonisormisson\ls\structureimex\import;

use Answer;
use CDbCriteria;
use LSActiveRecord;
use Question;
use QuestionAttribute;
use QuestionGroup;
use tonisormisson\ls\structureimex\exceptions\ImexException;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\validation\MyQuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeValidator;


class ImportStructure
{
    const COLUMN_TYPE = 'type';
    const COLUMN_SUBTYPE = 'subtype';
    const COLUMN_CODE = 'code';
    const COLUMN_RELEVANCE = 'relevance';
    const COLUMN_THEME = 'theme';
    const COLUMN_OPTIONS = 'options';
    const COLUMN_VALUE = 'value';
    const COLUMN_HELP = 'help';
    const COLUMN_SCRIPT = 'script';
    const COLUMN_MANDATORY = 'mandatory';


}
