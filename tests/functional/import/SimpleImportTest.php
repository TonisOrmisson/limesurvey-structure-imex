<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;

/**
 * CRITICAL: Simple test to check if import basic logic works
 */
class SimpleImportTest extends DatabaseTestCase
{
    /**
     * Test that we can manually create and modify question attributes
     * This bypasses the import system to test the basics
     */
    public function testManualQuestionAttributeModification()
    {
        // Import a survey
        $surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        
        // The blank survey is truly blank, so let's create a question manually
        $question = $this->createQuestionWithGroup($surveyId);
        
        $this->assertNotNull($question, 'Should be able to create a test question');
        echo "DEBUG: Created question ID: " . $question->qid . " with type: " . $question->type . "\n";
        
        // Manual attribute creation test
        $attribute = new QuestionAttribute();
        $attribute->qid = $question->qid;
        $attribute->attribute = 'hide_tip';
        $attribute->value = '1';
        $result = $attribute->save();
        
        if (!$result) {
            $errors = $attribute->getErrors();
            echo "DEBUG: QuestionAttribute save errors: " . print_r($errors, true) . "\n";
        }
        
        $this->assertTrue($result, 'Should be able to save question attribute manually');
        
        // Read it back
        $readAttribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr',
            'params' => [':qid' => $question->qid, ':attr' => 'hide_tip']
        ]);
        
        $this->assertNotNull($readAttribute, 'Should be able to read back the attribute');
        $this->assertEquals('1', $readAttribute->value, 'Attribute value should match');
        echo "SUCCESS: Manual attribute creation/read works\n";
        
        // Test update
        $readAttribute->value = '0';
        $updateResult = $readAttribute->save();
        $this->assertTrue($updateResult, 'Should be able to update attribute');
        
        // Read updated value
        $updatedAttribute = QuestionAttribute::model()->find([
            'condition' => 'qid = :qid AND attribute = :attr',
            'params' => [':qid' => $question->qid, ':attr' => 'hide_tip']
        ]);
        
        $this->assertEquals('0', $updatedAttribute->value, 'Updated value should persist');
        echo "SUCCESS: Manual attribute update works\n";
    }
    
    /**
     * Test if we can at least create the import class without errors
     */
    public function testImportClassCreation()
    {
        // Import a survey
        $surveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        
        $plugin = $this->createRealPlugin($surveyId);
        $survey = \Survey::model()->findByPk($surveyId);
        
        // Try to create the import class
        try {
            $import = new \tonisormisson\ls\structureimex\import\ImportStructureV4Plus($plugin, $survey);
            $this->assertNotNull($import, 'Should be able to create import instance');
            echo "SUCCESS: ImportStructureV4Plus class created successfully\n";
        } catch (\Exception $e) {
            echo "ERROR: Failed to create ImportStructureV4Plus: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            $this->fail('Should be able to create import class: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a test question with a group in the given survey
     */
    protected function createQuestionWithGroup($surveyId)
    {
        $survey = \Survey::model()->findByPk($surveyId);
        
        // Get or create a question group
        $groups = $survey->groups;
        if (empty($groups)) {
            $group = new \QuestionGroup();
            $group->sid = $survey->sid;
            $group->group_name = 'Test Group';
            $group->group_order = 1;
            $result = $group->save();
            if (!$result) {
                echo "ERROR: Failed to save question group: " . print_r($group->getErrors(), true) . "\n";
                return null;
            }
            echo "DEBUG: Created question group ID: " . $group->gid . "\n";
        } else {
            $group = $groups[0];
            echo "DEBUG: Using existing question group ID: " . $group->gid . "\n";
        }
        
        // Create question
        $question = new Question();
        $question->sid = $survey->sid;
        $question->gid = $group->gid;
        $question->type = 'L'; // List (Radio)
        $question->title = 'TestQ1';
        $question->mandatory = 'N';
        $question->question_order = 1;
        $result = $question->save();
        
        if (!$result) {
            echo "ERROR: Failed to save question: " . print_r($question->getErrors(), true) . "\n";
            return null;
        }
        
        echo "DEBUG: Created question ID: " . $question->qid . "\n";
        
        // Create localized content if needed
        if (class_exists('QuestionL10n')) {
            $questionL10n = new \QuestionL10n();
            $questionL10n->qid = $question->qid;
            $questionL10n->language = $survey->language ?? 'en';
            $questionL10n->question = 'Test question';
            $questionL10n->help = '';
            $questionL10n->save();
        }
        
        return $question;
    }
}