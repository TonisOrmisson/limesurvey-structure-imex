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
        
        $this->writeHelpSheet();
    }

    private function writeHelpSheet()
    {
        $this->setSheet('helpSheet');
        $header = ['Element Type', 'Field Name', 'Description', 'Example Values', 'Expression Syntax'];

        $row = Row::fromValues($header, $this->headerStyle);
        $this->writer->addRow($row);

        $data = [];
        
        // Question Group relevance documentation
        $data[] = Row::fromValues([
            'Question Group',
            '',
            'Controls when an entire group of questions is shown',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'group',
            'Question group name/title',
            'Demographics, Contact Info',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'code',
            'Not used for groups (leave empty)',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'parent',
            'Not used for groups (leave empty)',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'relevance',
            'Expression determining when group is shown (1=always)',
            '1, age.NAOK > 18, country.NAOK == "US"',
            'ExpressionManager syntax'
        ]);
        
        // Empty row for separation
        $data[] = Row::fromValues(['', '', '', '', '']);
        
        // Question relevance documentation
        $data[] = Row::fromValues([
            'Question',
            '',
            'Controls when a specific question is shown',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'group',
            'Not used for questions (leave empty)',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'code',
            'Question code/identifier',
            'Q001, gender, age',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'parent',
            'Not used for main questions (leave empty)',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'relevance',
            'Expression determining when question is shown',
            '1, gender.NAOK == "M", age.NAOK >= 21',
            'ExpressionManager syntax'
        ]);
        
        // Empty row for separation
        $data[] = Row::fromValues(['', '', '', '', '']);
        
        // Subquestion relevance documentation
        $data[] = Row::fromValues([
            'Subquestion',
            '',
            'Controls when a subquestion (array item) is shown',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'group',
            'Not used for subquestions (leave empty)',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'code',
            'Subquestion code/identifier',
            'SQ001, item1, option_a',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'parent',
            'Parent question code that contains this subquestion',
            'Q001, matrix_q, rating_scale',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'relevance',
            'Expression determining when subquestion is shown',
            '1, previous_answer.NAOK == "Y"',
            'ExpressionManager syntax'
        ]);
        
        // Empty row for separation
        $data[] = Row::fromValues(['', '', '', '', '']);
        
        // Expression syntax documentation
        $data[] = Row::fromValues([
            'Expression Syntax',
            '',
            'LimeSurvey ExpressionManager syntax rules',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Basic Values:',
            '1',
            'Always show (default if empty)',
            '1',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '0',
            'Never show (hide permanently)',
            '0',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Question References:',
            'question_code.NAOK',
            'Reference another question\'s value safely',
            'age.NAOK, gender.NAOK',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Comparisons:',
            '\'==',
            'Equal to',
            'gender.NAOK == "M"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '\'!=',
            'Not equal to',
            'country.NAOK != "US"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '\'>, <, >=, <=',
            'Numerical comparisons',
            'age.NAOK >= 18, score.NAOK < 100',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Logical Operators:',
            'and, &&',
            'Both conditions must be true',
            'age.NAOK >= 18 and gender.NAOK == "M"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            'or, ||',
            'Either condition must be true',
            'country.NAOK == "US" or country.NAOK == "CA"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '\'!',
            'Not (negation)',
            '!(age.NAOK < 18)',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Text Matching:',
            '\'strpos()',
            'Check if text contains substring',
            'strpos(comments.NAOK, "good") !== false',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '\'trim()',
            'Remove whitespace',
            'trim(name.NAOK) != ""',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Functions:',
            '\'is_empty()',
            'Check if question is empty',
            '!is_empty(email.NAOK)',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '\'count()',
            'Count selected items in multiple choice',
            'count(interests.NAOK) >= 2',
            ''
        ]);
        
        // Empty row for separation
        $data[] = Row::fromValues(['', '', '', '', '']);
        
        // Usage examples
        $data[] = Row::fromValues([
            'Common Examples',
            '',
            '',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Age-based relevance:',
            '',
            'Show question only for adults',
            'age.NAOK >= 18',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Gender-specific:',
            '',
            'Show only for males',
            'gender.NAOK == "M"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Multiple conditions:',
            '',
            'Adult males only',
            'age.NAOK >= 18 and gender.NAOK == "M"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Previous answer:',
            '',
            'Show if previous answer was Yes',
            'interested.NAOK == "Y"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Non-empty check:',
            '',
            'Show if email was provided',
            'trim(email.NAOK) != ""',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Multiple choice count:',
            '',
            'Show if 2+ options selected',
            'count(hobbies.NAOK) >= 2',
            ''
        ]);
        
        // Empty row for separation
        $data[] = Row::fromValues(['', '', '', '', '']);
        
        // Important notes
        $data[] = Row::fromValues([
            'Important Notes',
            '',
            '',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'NAOK suffix:',
            '',
            'Always use .NAOK for question references',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '',
            'Prevents errors if question is empty/unanswered',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'String values:',
            '',
            'Always use double quotes for text values',
            '"M", "US", "Yes"',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Hierarchy:',
            '',
            'Group relevance affects all questions in group',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '',
            'Question relevance affects question and subquestions',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            'Testing:',
            '',
            'Test expressions thoroughly before deployment',
            '',
            ''
        ]);
        
        $data[] = Row::fromValues([
            '',
            '',
            'Use Survey Test mode to verify logic',
            '',
            ''
        ]);

        $this->writer->addRows($data);
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
