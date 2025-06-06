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
        
        echo "\n=== SURVEY DEBUG INFO ===\n";
        echo "Survey ID: {$surveyId}\n";
        echo "Survey Admin: {$survey->admin}\n";
        echo "Survey Language: {$survey->language}\n";
        
        // Check groups
        $groups = QuestionGroup::model()->findAll('sid = :sid', [':sid' => $surveyId]);
        echo "Number of groups: " . count($groups) . "\n";
        
        foreach ($groups as $group) {
            echo "Group ID: {$group->gid}, Name: {$group->group_name}, Order: {$group->group_order}\n";
        }
        
        // Check questions
        $questions = Question::model()->findAll('sid = :sid', [':sid' => $surveyId]);
        echo "Number of questions: " . count($questions) . "\n";
        
        foreach ($questions as $question) {
            echo "Question ID: {$question->qid}, Title: {$question->title}, Type: {$question->type}, GID: {$question->gid}\n";
        }
        
        // Try to create a minimal question if we have a group
        if (!empty($groups)) {
            $firstGroup = $groups[0];
            echo "Attempting to create test question in group {$firstGroup->gid}...\n";
            
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
                echo "✓ Successfully created test question ID: {$testQuestion->qid}\n";
                // Clean up
                $testQuestion->delete();
                echo "✓ Successfully cleaned up test question\n";
            } else {
                echo "✗ Failed to create test question\n";
                echo "Errors: " . print_r($testQuestion->getErrors(), true) . "\n";
            }
        }
        
        $this->assertTrue(true, 'Debug test completed');
    }

}