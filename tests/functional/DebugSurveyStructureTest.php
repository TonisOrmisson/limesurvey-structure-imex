<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Survey;
use Question;
use QuestionGroup;

/**
 * Debug test to understand the structure of imported surveys
 */
class DebugSurveyStructureTest extends DatabaseTestCase
{
    public function testInspectImportedSurveyStructure()
    {

        // Import a survey
        $blankSurveyPath = __DIR__ . '/../support/data/surveys/blank-survey.lss';
        $surveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        // Inspect the survey
        $survey = Survey::model()->findByPk($surveyId);
        $this->assertNotNull($survey);
        
        // Check groups
        $groups = QuestionGroup::model()->findAll('sid = :sid', [':sid' => $surveyId]);
        
        // Check questions
        $questions = Question::model()->findAll('sid = :sid', [':sid' => $surveyId]);
        
        // Try to create a minimal question if we have a group
        if (!empty($groups)) {
            $firstGroup = $groups[0];
            
            $testQuestion = new Question();
            $testQuestion->sid = $surveyId;
            $testQuestion->gid = $firstGroup->gid;
            $testQuestion->type = 'T';
            $testQuestion->title = 'TEST_' . uniqid();
            $testQuestion->question_order = 999;
            $testQuestion->scale_id = 0;
            $testQuestion->parent_qid = 0;
            $testQuestion->mandatory = 'N';
            $testQuestion->other = 'N';
            $testQuestion->relevance = '1';
            $testQuestion->same_default = 0;
            $testQuestion->same_script = 0;
            
            if ($testQuestion->save()) {
                // Clean up
                $testQuestion->delete();
                $this->assertTrue(true, 'Successfully created and cleaned up test question');
            } else {
                $this->fail('Failed to create test question: ' . print_r($testQuestion->getErrors(), true));
            }
        }
        
        $this->assertTrue(true, 'Debug test completed');
    }

}